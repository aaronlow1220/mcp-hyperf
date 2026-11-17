<?php

declare(strict_types=1);

namespace AtelliTech\Hyperf\Mcp;

use stdClass;

final class Schema
{
    /**
     * Build an object input schema. Empty properties are encoded as `{}` rather than `[]`.
     *
     * @param array<string, mixed> $properties
     * @param list<string> $required
     * @return array<string, mixed>
     */
    public static function object(array $properties = [], array $required = [], bool $additionalProperties = false): array
    {
        $schema = [
            'type' => 'object',
            'properties' => $properties === [] ? new stdClass() : $properties,
            'additionalProperties' => $additionalProperties,
        ];

        if ($required !== []) {
            $schema['required'] = $required;
        }

        return $schema;
    }
}
