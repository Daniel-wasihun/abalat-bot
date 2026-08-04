<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use App\Services\BackMessage;

class Response {
    /**
     * Returns a 200 response
     *
     * @param mixed $data
     * @return \Illuminate\Http\JsonResponse
     */
    public static function _200($data): JsonResponse {
        if ($data instanceof \Illuminate\Http\Resources\Json\JsonResource) {
            return $data->response()->setStatusCode(200);
        }
        return response()->json($data, 200);
    }

    /**
     * Returns a 201 response for creation
     *
     * @param mixed $data
     * @return \Illuminate\Http\JsonResponse
     */
    public static function _201($data): JsonResponse {
        if ($data instanceof \Illuminate\Http\Resources\Json\JsonResource) {
            return $data->response()->setStatusCode(201);
        }
        return response()->json($data, 201);
    }

    /**
     * Returns a 204 response for no content
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function _204(): JsonResponse {
        return response()->json(null, 204);
    }

    /**
     * Returns a 401 response
     *
     * @param string|null $message
     * @return \Illuminate\Http\JsonResponse
     */
    public static function _401($message = null): JsonResponse {
        $response = ['message' => BackMessage::get('unauthorized')];
        if ($message != null) {
            $response['message'] = $message;
        }

        return response()->json($response, 401);
    }

    /**
     * Returns a 404 response for not found
     *
     * @param string|array|null $message
     * @return \Illuminate\Http\JsonResponse
     */
    public static function _404($message = null): JsonResponse {
        $response = ['message' => BackMessage::get('resource_not_found')];

        if (is_array($message) && isset($message['message'])) {
            $response = $message;
        } elseif ($message != null) {
            $response['message'] = $message;
        }

        return response()->json($response, 404);
    }

    /**
     * Returns a 422 validation error
     * with messages
     *
     * @param string|null $message
     * @param array|null $errors
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function _422($message = null, $errors = null): JsonResponse {
        $responseData = [];
        if ($errors) {
            $responseData['errors'] = $errors;
        }
        if ($message) {
            $responseData['message'] = $message;
        }

        return response()->json($responseData, 422);
    }

    /**
     * Returns a 500 internal server error
     *
     * @param string|null $message
     * @return \Illuminate\Http\JsonResponse
     */
    public static function _500($message = null): JsonResponse {
        $response = ['message' => BackMessage::get('internal_server_error')];
        if ($message != null) {
            $response['message'] = $message;
        }

        return response()->json($response, 500);
    }

    /**
     * Returns a 403 forbidden error
     *
     * @param string|null $message
     * @return \Illuminate\Http\JsonResponse
     */
    public static function _403($message = null): JsonResponse {
        $response = ['message' => BackMessage::get('forbidden')];
        if ($message != null) {
            $response['message'] = $message;
        }

        return response()->json($response, 403);
    }

    /**
     * Returns a 409 conflict error
     *
     * @param string|null $message
     * @return \Illuminate\Http\JsonResponse
     */
    public static function _409($message = null): JsonResponse {
        $response = ['message' => BackMessage::get('conflict')];
        if ($message != null) {
            $response['message'] = $message;
        }

        return response()->json($response, 409);
    }

    /**
     * Returns a 429 too many requests error
     *
     * @param string|null $message
     * @return \Illuminate\Http\JsonResponse
     */
    public static function _429($message = null): JsonResponse {
        $response = ['message' => 'Too Many Requests'];
        if ($message != null) {
            $response['message'] = $message;
        }

        return response()->json($response, 429);
    }
}
