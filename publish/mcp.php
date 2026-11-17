<?php

declare(strict_types=1);

return [
    // serverInfo returned by `initialize`
    'name' => 'MCP Server',
    'version' => '1.0.0',

    // optional instructions for the model, returned by `initialize`
    'instructions' => '',

    // classes implementing AtelliTech\Hyperf\Mcp\McpTool, resolved from the container
    'tools' => [],

    // classes implementing AtelliTech\Hyperf\Mcp\McpPrompt, resolved from the container
    'prompts' => [],

    // classes implementing AtelliTech\Hyperf\Mcp\McpResource or McpResourceTemplate, resolved from the container
    'resources' => [],

    'max_body_size' => 20971520,
];
