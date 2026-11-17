<?php

declare(strict_types=1);

namespace AtelliTech\Hyperf\Mcp;

/**
 * A family of resources addressed by a URI template, exposed through MCP
 * `resources/templates/list` and `resources/read`.
 *
 * Supported template syntax: `{var}` matches one path segment (no `/`, `?` or `#`) and is
 * URL-decoded; `{+var}` matches anything, including `/`. Example: `app://sheets/{id}`.
 */
interface McpResourceTemplate
{
    public function uriTemplate(): string;

    public function name(): string;

    public function description(): string;

    /**
     * MIME type of the contents. Return an empty string to omit it.
     */
    public function mimeType(): string;

    /**
     * Read the resource identified by `$uri`.
     *
     * Return null when the resource does not exist; the client gets a "Resource not found" error.
     * Throw {@see McpException} to return any other JSON-RPC error.
     *
     * @param array<string, string> $variables values extracted from the URI template
     * @param mixed $context whatever the caller passed to {@see McpServer::handle()}, e.g. the authenticated user
     * @return null|list<ResourceContents>|ResourceContents|string
     */
    public function read(string $uri, array $variables, mixed $context): array|ResourceContents|string|null;
}
