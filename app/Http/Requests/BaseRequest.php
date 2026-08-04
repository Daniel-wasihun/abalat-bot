<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Services\BackMessage;

class BaseRequest extends FormRequest {
    public function authorize(): bool {
        // By default, allow all authenticated users
        // Child classes will override for specific checks
        return auth('api')->check();
    }
    protected function failedAuthorization() {
        throw new HttpResponseException(response()->json([
            'message' => BackMessage::get('unauthorized'),
        ], 403));
    }
    protected function failedValidation(Validator $validator) {
        $errors = [];
        $lang = request()->header('lang', 'en');
        $translations = \App\Services\FrontLang::getTranslations($lang);

        foreach ($validator->errors()->messages() as $field => $messages) {
            // Translate field name
            $fieldNameKey = "field.{$field}";
            $attribute = $translations[$fieldNameKey] ?? ucfirst(str_replace(['.', '_'], ' ', $field));

            // Take ONLY the first message for this field
            $message = $messages[0];

            if (isset($translations[$message])) {
                $translated = $this->translate($message, [
                    ':attribute' => $attribute
                ], $translations);
            } elseif (str_contains($message, '|')) {
                $parts = explode('|', $message);
                $key = $parts[0];
                $paramStr = $parts[1] ?? '';

                $replacements = [':attribute' => $attribute];
                if ($paramStr) {
                    foreach (explode(',', $paramStr) as $p) {
                        $pParts = explode('=', $p);
                        if (count($pParts) === 2) {
                            $replacements[':' . trim($pParts[0])] = trim($pParts[1]);
                        }
                    }
                }
                $translated = $this->translate($key, $replacements, $translations);
            } else {
                $rule = $this->extractRule($message);
                $translated = $this->translate("validation.{$rule}", [
                    ':attribute' => $attribute,
                    ':min' => $this->extractParam($message, 'min'),
                    ':max' => $this->extractParam($message, 'max'),
                    ':count' => $this->extractParam($message, 'min') ?: $this->extractParam($message, 'max'),
                    ':values' => $this->extractParam($message, 'values'),
                ], $translations);
            }

            $errors[$field] = [$translated ?: $message];
        }

        throw new HttpResponseException(response()->json([
            'message' => BackMessage::get('validation_error'),
            'errors' => $errors,
        ], 422));
    }

    private function translate(string $key, array $replace, array $translations): ?string {
        if (!isset($translations[$key])) return null;
        $message = $translations[$key];
        foreach ($replace as $k => $v) {
            $message = str_replace($k, (string)$v, $message);
        }
        return $message;
    }

    private function extractRule(string $message): string {
        $lower = strtolower($message);

        if (str_contains($lower, 'required')) return 'required';
        if (str_contains($lower, 'email')) return 'email';
        if (str_contains($lower, 'taken')) return 'unique';
        if (str_contains($lower, 'confirmed')) return 'confirmed';
        if (str_contains($lower, 'least')) return 'min';
        if (str_contains($lower, 'greater than')) return 'max';
        if (str_contains($lower, 'mimes')) return 'mimes';
        if (str_contains($lower, 'image')) return 'image';
        if (str_contains($lower, 'boolean')) return 'boolean';
        if (str_contains($lower, 'array')) return 'array';
        if (str_contains($lower, 'exists')) return 'exists';
        if (str_contains($lower, 'string')) return 'string';
        if (str_contains($lower, 'in ')) return 'in';

        return 'invalid'; // fallback
    }

    private function extractParam(string $message, string $type): ?string {
        return match ($type) {
            'min' => preg_match('/least (\d+)/i', $message, $m) ? $m[1] : null,
            'max' => preg_match('/greater than (\d+)/i', $message, $m) ? $m[1] : null,
            'values' => preg_match('/type: (.*?)[.$]/', $message, $m) ? $m[1] : null,
            default => null,
        };
    }
}
