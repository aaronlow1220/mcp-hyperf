<?php

declare(strict_types=1);

namespace AtelliTech\Hyperf\Mcp\Hyperf;

use AtelliTech\Hyperf\Mcp\McpPrompt;
use AtelliTech\Hyperf\Mcp\McpResource;
use AtelliTech\Hyperf\Mcp\McpResourceTemplate;
use AtelliTech\Hyperf\Mcp\McpServer;
use AtelliTech\Hyperf\Mcp\McpTool;
use Hyperf\Contract\ConfigInterface;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;

/**
 * Builds {@see McpServer} from `config/autoload/mcp.php`; tool, prompt and resource classes are resolved from the container.
 */
final class McpServerFactory
{
    public function __invoke(ContainerInterface $container): McpServer
    {
        /** @var array<string, mixed> $config */
        $config = $container->get(ConfigInterface::class)->get('mcp', []);

        $tools = [];
        foreach ((array) ($config['tools'] ?? []) as $class) {
            $tool = $container->get($class);
            if (! $tool instanceof McpTool) {
                throw new InvalidArgumentException(sprintf('MCP tool "%s" must implement %s.', $class, McpTool::class));
            }
            $tools[] = $tool;
        }

        $prompts = [];
        foreach ((array) ($config['prompts'] ?? []) as $class) {
            $prompt = $container->get($class);
            if (! $prompt instanceof McpPrompt) {
                throw new InvalidArgumentException(sprintf('MCP prompt "%s" must implement %s.', $class, McpPrompt::class));
            }
            $prompts[] = $prompt;
        }

        $resources = [];
        foreach ((array) ($config['resources'] ?? []) as $class) {
            $resource = $container->get($class);
            if (! $resource instanceof McpResource && ! $resource instanceof McpResourceTemplate) {
                throw new InvalidArgumentException(sprintf('MCP resource "%s" must implement %s or %s.', $class, McpResource::class, McpResourceTemplate::class));
            }
            $resources[] = $resource;
        }

        return new McpServer(
            name: (string) ($config['name'] ?? 'MCP Server'),
            version: (string) ($config['version'] ?? '1.0.0'),
            instructions: (string) ($config['instructions'] ?? ''),
            tools: $tools,
            maxBodySize: (int) ($config['max_body_size'] ?? McpServer::DEFAULT_MAX_BODY_SIZE),
            prompts: $prompts,
            resources: $resources,
        );
    }
}
