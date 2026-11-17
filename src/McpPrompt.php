<?php

declare(strict_types=1);

namespace AtelliTech\Hyperf\Mcp;

/**
 * A prompt template exposed through MCP `prompts/list` and `prompts/get`.
 */
interface McpPrompt
{
    /**
     * Unique prompt name, e.g. `summarize_sheet`.
     */
    public function name(): string;

    /**
     * Description shown to the user.
     */
    public function description(): string;

    /**
     * Arguments the prompt accepts. Build each one with {@see PromptArgument::make()}.
     *
     * Missing required arguments are rejected with `Invalid params` before {@see get()} is called.
     *
     * @return list<PromptArgument>
     */
    public function arguments(): array;

    /**
     * Render the prompt.
     *
     * Return a string to send a single `user` text message, or a list of messages built with
     * {@see PromptMessage}. Throw {@see McpException} to return a JSON-RPC error.
     *
     * @param array<string, string> $arguments
     * @param mixed $context whatever the caller passed to {@see McpServer::handle()}, e.g. the authenticated user
     * @return list<PromptMessage>|string
     */
    public function get(array $arguments, mixed $context): array|string;
}
