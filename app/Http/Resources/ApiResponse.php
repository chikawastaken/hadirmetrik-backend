<?php

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiResponse extends JsonResource
{
    private int $statusCode;

    private ?string $message;

    public function __construct($resource, int $statusCode = 200, ?string $message = null)
    {
        parent::__construct($resource);
        $this->statusCode = $statusCode;
        $this->message = $message;
    }

    public function toArray(Request $request): array
    {
        $data = [
            'success' => true,
            'data' => $this->resource,
        ];

        if ($this->message !== null) {
            $data['message'] = $this->message;
        }

        return $data;
    }

    public function render(Request $request)
    {
        return response()->json($this->toArray($request), $this->statusCode);
    }

    /**
     * Return a success response.
     */
    public static function success(mixed $data, ?string $message = null, int $status = 200): self
    {
        return new self($data, $status, $message);
    }

    /**
     * Return an error response with consistent format.
     */
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
