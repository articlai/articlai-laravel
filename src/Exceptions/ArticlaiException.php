<?php

namespace Articlai\Articlai\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class ArticlaiException extends Exception
{
    protected $details;

    protected $errorCode;

    public function __construct(
        string $message = 'An error occurred',
        array $details = [],
        string $errorCode = 'GENERAL_ERROR',
        int $code = 500,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->details = $details;
        $this->errorCode = $errorCode;
    }

    /**
     * Create a validation error exception
     */
    public static function validationFailed(array $errors): self
    {
        return new self(
            'Validation failed',
            $errors,
            'VALIDATION_ERROR',
            422
        );
    }

    /**
     * Create an authentication error exception
     */
    public static function authenticationFailed(string $message = 'Authentication failed'): self
    {
        return new self(
            $message,
            [],
            'AUTHENTICATION_ERROR',
            401
        );
    }

    /**
     * Create a not found error exception
     */
    public static function notFound(string $resource = 'Resource'): self
    {
        return new self(
            "{$resource} not found",
            [],
            'NOT_FOUND',
            404
        );
    }

    /**
     * Create a forbidden error exception
     */
    public static function forbidden(string $message = 'Access forbidden'): self
    {
        return new self(
            $message,
            [],
            'FORBIDDEN',
            403
        );
    }

    /**
     * Create a server error exception
     */
    public static function serverError(string $message = 'Internal server error'): self
    {
        return new self(
            $message,
            [],
            'SERVER_ERROR',
            500
        );
    }

    /**
     * Render the exception as an HTTP response
     */
    public function render(): JsonResponse
    {
        $response = [
            'error' => $this->getMessage(),
            'code' => $this->errorCode,
        ];

        if (! empty($this->details)) {
            $response['details'] = $this->details;
        }

        return response()->json($response, $this->getCode());
    }

    /**
     * Get the error details
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * Get the error code
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
