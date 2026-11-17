<?php

declare(strict_types=1);

namespace AtelliTech\Hyperf\Mcp;

use AtelliTech\Hyperf\Mcp\Hyperf\McpServerFactory;

class ConfigProvider
{
    /**
     * Return config.
     *
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                McpServer::class => McpServerFactory::class,
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config file for mcp-hyperf.',
                    'source' => __DIR__ . '/../publish/mcp.php',
                    'destination' => BASE_PATH . '/config/autoload/mcp.php',
                ],
            ],
        ];
    }
}
