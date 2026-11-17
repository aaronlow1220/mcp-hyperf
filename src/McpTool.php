<?php

declare(strict_types=1);

namespace AtelliTech\Hyperf\Mcp;

/**
 * A tool exposed through MCP `tools/list` and `tools/call`.
 */
interface McpTool
{
    /**
     * Unique tool name, e.g. `list_subscribed_sheet`.
     */
    public function name(): string;

    /**
     * Description shown to the model.
     */
    public function description(): string;

    /**
     * JSON Schema of the arguments. Use {@see Schema::object()} to build it.
     *
     * @return array<string, mixed>
     */
    public function inputSchema(): array;

    /**
     * Run the tool.
     *
     * A string result is returned to the client as-is; anything else is JSON encoded.
     * Throw {@see ToolException} to return an `isError` tool result the model can read;
     * any other exception propagates to the app's exception handler.
     *
     * @param array<string, mixed> $arguments
     * @param mixed $context whatever the caller passed to {@see McpServer::handle()}, e.g. the authenticated user
     */
    public function call(array $arguments, mixed $context): mixed;
}
