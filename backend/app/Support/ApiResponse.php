<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

// Format respons seragam sesuai docs/API_CONTRACT.md §1
class ApiResponse
{
    public static function success(mixed $data = null, string $message = 'Berhasil.', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    // $errors hanya diisi untuk 422 (validasi)
    public static function error(string $message, int $status, ?array $errors = null): JsonResponse
    {
        $body = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status);
    }
}