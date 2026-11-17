<?php

declare(strict_types=1);

namespace AtelliTech\Hyperf\Mcp;

/**
 * A resource with a fixed URI, exposed through MCP `resources/list` and `resources/read`.
 */
interface McpResource
{
    /**
     * Unique URI, e.g. `app://config/limits`.
     */
    public function uri(): string;

    /**
     * Short name shown to the user.
     */
    public function name(): string;

    public function description(): string;

    /**
     * MIME type of the contents, e.g. `application/json`. Return an empty string to omit it.
     */
    public function mimeType(): string;

    /**
     * Read the resource.
     *
     * A string is returned as text contents; return {@see ResourceContents} for binary data or
     * several contents. Throw {@see McpException} to return a JSON-RPC error.
     *
     * @param mixed $context whatever the caller passed to {@see McpServer::handle()}, e.g. the authenticated user
     * @return list<ResourceContents>|ResourceContents|string
     */
    public function read(mixed $context): array|ResourceContents|string;
}
