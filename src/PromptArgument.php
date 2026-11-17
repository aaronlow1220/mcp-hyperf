<?php

declare(strict_types=1);

namespace AtelliTech\Hyperf\Mcp;

/**
 * An argument accepted by an {@see McpPrompt}. Values are always strings.
 */
final class PromptArgument
{
    public function __construct(
        public readonly string $name,
        public readonly string $description = '',
        public readonly bool $required = false,
    ) {
    }

    public static function make(string $name, string $description = '', bool $required = false): self
    {
        return new self($name, $description, $required);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $argument = ['name' => $this->name];
        if ($this->description !== '') {
            $argument['description'] = $this->description;
        }
        $argument['required'] = $this->required;

        return $argument;
    }
}
