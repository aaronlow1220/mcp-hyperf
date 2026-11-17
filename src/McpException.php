<?php

declare(strict_types=1);

namespace AtelliTech\Hyperf\Mcp;

use RuntimeException;

/**
 * Thrown by a prompt or resource to return a JSON-RPC error to the client.
 *
 * Tools should throw {@see ToolException} instead, so the model can read the error.
 */
class McpException extends RuntimeException
{
    public const INVALID_PARAMS = -32602;

    public const RESOURCE_NOT_FOUND = -32002;

    public function __construct(string $message, int $code = self::INVALID_PARAMS)
    {
        parent::__construct($message, $code);
    }

    public static function invalidParams(string $message = 'Invalid params.'): self
    {
        return new self($message, self::INVALID_PARAMS);
    }

    public static function resourceNotFound(string $message = 'Resource not found.'): self
    {
        return new self($message, self::RESOURCE_NOT_FOUND);
    }
}
