<?php

declare(strict_types=1);

namespace AtelliTech\Hyperf\Mcp;

use InvalidArgumentException;

/**
 * A message returned by {@see McpPrompt::get()}.
 */
final class PromptMessage
{
    /**
     * @param array<string, mixed> $content an MCP content block, e.g. `['type' => 'text', 'text' => '…']`
     */
    private function __construct(
        public readonly string $role,
        public readonly array $content,
    ) {
        if ($role !== 'user' && $role !== 'assistant') {
            throw new InvalidArgumentException('Prompt message role must be "user" or "assistant".');
        }
    }

    public static function user(string $text): self
    {
        return new self('user', ['type' => 'text', 'text' => $text]);
    }

    public static function assistant(string $text): self
    {
        return new self('assistant', ['type' => 'text', 'text' => $text]);
    }

    /**
     * A message that embeds a resource, e.g. `PromptMessage::resource('user', 'app://sheets/1', $json, 'application/json')`.
     */
    public static function resource(string $role, string $uri, string $text, string $mimeType = 'text/plain'): self
    {
        return new self($role, [
            'type' => 'resource',
            'resource' => ['uri' => $uri, 'mimeType' => $mimeType, 'text' => $text],
        ]);
    }

    /**
     * A message with any MCP content block (image, audio, resource_link…).
     *
     * @param array<string, mixed> $content
     */
    public static function content(string $role, array $content): self
    {
        return new self($role, $content);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['role' => $this->role, 'content' => $this->content];
    }
}
