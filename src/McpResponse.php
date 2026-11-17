<?php

declare(strict_types=1);

namespace AtelliTech\Hyperf\Mcp;

/**
 * HTTP status plus JSON-RPC body; a null body means an empty response.
 */
final class McpResponse
{
    /**
     * @param null|array<string, mixed> $body
     */
    public function __construct(
        public readonly int $status,
        public readonly ?array $body,
    ) {
    }
}
