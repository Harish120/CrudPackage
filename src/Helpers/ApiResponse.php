<?php

namespace Harry\CrudPackage\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Return a success response.
     *
     * @param mixed|null $data
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    public static function success(mixed $data = null, string $message = 'Success', int $statusCode = 200, array $meta = null): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if ($meta !== null) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return an error response.
     *
     * @param string $message
     * @param int $statusCode
     * @param array $errors
     * @return JsonResponse
     */
    public static function error(string $message = 'Error', int $statusCode = 400, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }
}
