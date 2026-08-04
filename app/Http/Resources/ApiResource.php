<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\BackMessage;

class ApiResource extends JsonResource {
    /**
     * Wrap the resource in a standard API response with optional message
     */
    public static function success($data, ?string $messageKey = null, array $replace = []): self|JsonResource {
        if ($data === null) {
            $response = new JsonResource(null);
        } else {
            $response = new static($data);
        }

        if ($messageKey) {
            $response = $response->additional([
                'message' => BackMessage::get($messageKey, $replace),
            ]);
        }
        return $response;
    }

    /**
     * Wrap the error in a standard API response
     */
    public static function error(string $messageKey, array $replace = []): array {
        return [
            'message' => BackMessage::get($messageKey, $replace),
        ];
    }


    /**
     * Allow chaining additional data (like token)
     */
    public function withToken(string $token, ?string $tokenId = null): self {
        $data = [
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];

        if ($tokenId) {
            $data['session_id'] = $tokenId;
        }

        return $this->additional(array_merge($this->additional, $data));
    }

    /**
     * Final response with status code
     */
    public function toResponse($request) {
        return parent::toResponse($request);
    }
}
