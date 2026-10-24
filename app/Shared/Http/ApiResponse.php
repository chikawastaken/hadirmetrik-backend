<?php

namespace App\Shared\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Consistent JSON envelope for every API response.
 *
 * Success: { "success": true, "data": <...>, "message": <optional> }
 * Error:   { "success": false, "message": <...>, "errors": <optional> }
 */
class ApiResponse extends JsonResource
{
    public function __construct(
        mixed $resource,
        private readonly int $statusCode = 200,
        private readonly ?string $message = null,
    ) {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $payload = [
            'success' => true,
            'data' => $this->resource,
        ];

        if ($this->message !== null) {
            $payload['message'] = $this->message;
        }

        return $payload;
    }

    /**
     * Bypass resource wrapping so the envelope stays flat.
     */
    public function toResponse($request): JsonResponse
    {
        return response()->json($this->toArray($request), $this->statusCode);
    }

    public static function success(mixed $data, ?string $message = null, int $status = 200): self
    {
        return new self($data, $status, $message);
    }

    public static function error(string $message, int $status = 422, mixed $errors = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
