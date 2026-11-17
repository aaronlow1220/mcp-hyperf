<?php

declare(strict_types=1);

namespace AtelliTech\Hyperf\Mcp;

use InvalidArgumentException;
use JsonException;
use stdClass;

/**
 * Stateless Streamable HTTP MCP server exposing tools, prompts and resources.
 *
 * It does not open its own listener: call {@see handle()} from an existing HTTP route.
 * Authentication is the caller's job; pass the authenticated user as `$context`.
 */
final class McpServer
{
    public const PROTOCOL_VERSIONS = ['2025-06-18'];

    public const DEFAULT_MAX_BODY_SIZE = 20971520;

    /**
     * @var array<string, McpTool>
     */
    private array $tools = [];

    /**
     * @var array<string, McpPrompt>
     */
    private array $prompts = [];

    /**
     * @var array<string, McpResource>
     */
    private array $resources = [];

    /**
     * @var list<array{template: McpResourceTemplate, pattern: string}>
     */
    private array $resourceTemplates = [];

    /**
     * @param iterable<McpTool> $tools
     * @param iterable<McpPrompt> $prompts
     * @param iterable<McpResource|McpResourceTemplate> $resources
     */
    public function __construct(
        private readonly string $name,
        private readonly string $version = '1.0.0',
        private readonly string $instructions = '',
        iterable $tools = [],
        private readonly int $maxBodySize = self::DEFAULT_MAX_BODY_SIZE,
        iterable $prompts = [],
        iterable $resources = [],
    ) {
        foreach ($tools as $tool) {
            if (isset($this->tools[$tool->name()])) {
                throw new InvalidArgumentException(sprintf('Duplicate MCP tool name "%s".', $tool->name()));
            }
            $this->tools[$tool->name()] = $tool;
        }

        foreach ($prompts as $prompt) {
            if (isset($this->prompts[$prompt->name()])) {
                throw new InvalidArgumentException(sprintf('Duplicate MCP prompt name "%s".', $prompt->name()));
            }
            $this->prompts[$prompt->name()] = $prompt;
        }

        $templates = [];
        foreach ($resources as $resource) {
            if ($resource instanceof McpResourceTemplate) {
                if (isset($templates[$resource->uriTemplate()])) {
                    throw new InvalidArgumentException(sprintf('Duplicate MCP resource template "%s".', $resource->uriTemplate()));
                }
                $templates[$resource->uriTemplate()] = true;
                $this->resourceTemplates[] = [
                    'template' => $resource,
                    'pattern' => self::compileUriTemplate($resource->uriTemplate()),
                ];
                continue;
            }
            if (isset($this->resources[$resource->uri()])) {
                throw new InvalidArgumentException(sprintf('Duplicate MCP resource URI "%s".', $resource->uri()));
            }
            $this->resources[$resource->uri()] = $resource;
        }
    }

    public function handle(McpRequest $request, mixed $context = null): McpResponse
    {
        if (! $this->originAllowed($request->origin, $request->host)) {
            return $this->rpcError(null, -32600, 'Origin is not allowed.', 403);
        }

        if (strtoupper($request->method) !== 'POST') {
            return new McpResponse(405, null);
        }

        return $this->post($request, $context);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toolDefinitions(): array
    {
        $definitions = [];
        foreach ($this->tools as $tool) {
            $definitions[] = [
                'name' => $tool->name(),
                'description' => $tool->description(),
                'inputSchema' => $tool->inputSchema(),
            ];
        }

        return $definitions;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function promptDefinitions(): array
    {
        $definitions = [];
        foreach ($this->prompts as $prompt) {
            $definition = ['name' => $prompt->name()];
            if ($prompt->description() !== '') {
                $definition['description'] = $prompt->description();
            }
            $arguments = array_map(static fn (PromptArgument $argument): array => $argument->toArray(), $prompt->arguments());
            if ($arguments !== []) {
                $definition['arguments'] = $arguments;
            }
            $definitions[] = $definition;
        }

        return $definitions;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function resourceDefinitions(): array
    {
        $definitions = [];
        foreach ($this->resources as $resource) {
            $definitions[] = self::describeResource(['uri' => $resource->uri()], $resource->name(), $resource->description(), $resource->mimeType());
        }

        return $definitions;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function resourceTemplateDefinitions(): array
    {
        $definitions = [];
        foreach ($this->resourceTemplates as ['template' => $template]) {
            $definitions[] = self::describeResource(['uriTemplate' => $template->uriTemplate()], $template->name(), $template->description(), $template->mimeType());
        }

        return $definitions;
    }

    /**
     * @param array<string, string> $definition
     * @return array<string, string>
     */
    private static function describeResource(array $definition, string $name, string $description, string $mimeType): array
    {
        $definition['name'] = $name;
        if ($description !== '') {
            $definition['description'] = $description;
        }
        if ($mimeType !== '') {
            $definition['mimeType'] = $mimeType;
        }

        return $definition;
    }

    /**
     * Turn a URI template into a regex: `{var}` matches one segment, `{+var}` matches anything.
     */
    private static function compileUriTemplate(string $template): string
    {
        $parts = preg_split('/(\{\+?[A-Za-z_][A-Za-z0-9_]*\})/', $template, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            throw new InvalidArgumentException(sprintf('Invalid MCP resource template "%s".', $template));
        }

        $pattern = '';
        $seen = [];
        foreach ($parts as $index => $part) {
            if ($index % 2 === 0) {
                if (str_contains($part, '{') || str_contains($part, '}')) {
                    throw new InvalidArgumentException(sprintf('Unsupported expression in MCP resource template "%s".', $template));
                }
                $pattern .= preg_quote($part, '#');
                continue;
            }
            $reserved = $part[1] === '+';
            $variable = substr($part, $reserved ? 2 : 1, -1);
            if (isset($seen[$variable])) {
                throw new InvalidArgumentException(sprintf('Duplicate variable "%s" in MCP resource template "%s".', $variable, $template));
            }
            $seen[$variable] = true;
            $pattern .= sprintf('(?P<%s>%s)', $variable, $reserved ? '.+' : '[^/?\#]+');
        }

        return '#^' . $pattern . '$#D';
    }

    private function originAllowed(string $origin, string $host): bool
    {
        if ($origin === '') {
            return true;
        }

        $originHost = parse_url($origin, PHP_URL_HOST);
        $requestHost = preg_replace('/:\d+$/', '', $host);

        return is_string($originHost) && is_string($requestHost) && $requestHost !== '' && strcasecmp($originHost, $requestHost) === 0;
    }

    private function post(McpRequest $request, mixed $context): McpResponse
    {
        if (! $this->accepts($request->accept)) {
            return $this->rpcError(null, -32600, 'Accept must include application/json and text/event-stream.', 406);
        }
        if ($request->protocolVersion !== '' && ! in_array($request->protocolVersion, self::PROTOCOL_VERSIONS, true)) {
            return $this->rpcError(null, -32600, 'Unsupported MCP protocol version.', 400);
        }
        if (strlen($request->body) > $this->maxBodySize) {
            return $this->rpcError(null, -32700, 'Parse error.', 400);
        }

        try {
            $payload = json_decode($request->body, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->rpcError(null, -32700, 'Parse error.', 400);
        }
        if (! is_array($payload) || array_is_list($payload)) {
            return $this->rpcError(null, -32600, 'Invalid request.', 400);
        }
        if (($payload['jsonrpc'] ?? null) !== '2.0' || ! is_string($payload['method'] ?? null) || $payload['method'] === '') {
            return $this->rpcError($this->requestId($payload), -32600, 'Invalid request.');
        }
        if (! array_key_exists('id', $payload)) {
            return new McpResponse(202, null);
        }

        $id = $payload['id'];
        if (! is_string($id) && ! is_int($id)) {
            return $this->rpcError(null, -32600, 'Invalid request.');
        }

        $params = $payload['params'] ?? [];
        if (! is_array($params) || (array_is_list($params) && $params !== [])) {
            return $this->rpcError($id, -32602, 'Invalid params.');
        }

        return match ($payload['method']) {
            'initialize' => $this->initialize($id, $params),
            'ping' => $this->success($id, new stdClass()),
            'tools/list' => $this->success($id, ['tools' => $this->toolDefinitions()]),
            'tools/call' => $this->callTool($id, $params, $context),
            'prompts/list' => $this->success($id, ['prompts' => $this->promptDefinitions()]),
            'prompts/get' => $this->getPrompt($id, $params, $context),
            'resources/list' => $this->success($id, ['resources' => $this->resourceDefinitions()]),
            'resources/templates/list' => $this->success($id, ['resourceTemplates' => $this->resourceTemplateDefinitions()]),
            'resources/read' => $this->readResource($id, $params, $context),
            default => $this->rpcError($id, -32601, 'Method not found.'),
        };
    }

    private function accepts(string $header): bool
    {
        $types = [];
        foreach (explode(',', strtolower($header)) as $part) {
            $types[] = trim(explode(';', $part, 2)[0]);
        }

        return in_array('application/json', $types, true) && in_array('text/event-stream', $types, true);
    }

    /**
     * @param array<mixed> $params
     */
    private function initialize(int|string $id, array $params): McpResponse
    {
        $requested = $params['protocolVersion'] ?? '';
        $version = is_string($requested) && in_array($requested, self::PROTOCOL_VERSIONS, true)
            ? $requested
            : self::PROTOCOL_VERSIONS[0];

        $result = [
            'protocolVersion' => $version,
            'capabilities' => $this->capabilities(),
            'serverInfo' => [
                'name' => $this->name,
                'version' => $this->version,
            ],
        ];
        if ($this->instructions !== '') {
            $result['instructions'] = $this->instructions;
        }

        return $this->success($id, $result);
    }

    /**
     * Tools are always advertised; prompts and resources only when some are registered.
     *
     * @return array<string, mixed>
     */
    private function capabilities(): array
    {
        $capabilities = ['tools' => ['listChanged' => false]];
        if ($this->prompts !== []) {
            $capabilities['prompts'] = ['listChanged' => false];
        }
        if ($this->resources !== [] || $this->resourceTemplates !== []) {
            $capabilities['resources'] = ['subscribe' => false, 'listChanged' => false];
        }

        return $capabilities;
    }

    /**
     * @param array<mixed> $params
     */
    private function getPrompt(int|string $id, array $params, mixed $context): McpResponse
    {
        $name = $params['name'] ?? null;
        $arguments = $params['arguments'] ?? [];
        if (! is_string($name) || ! isset($this->prompts[$name]) || ! is_array($arguments) || (array_is_list($arguments) && $arguments !== [])) {
            return $this->rpcError($id, -32602, 'Invalid params.');
        }
        foreach ($arguments as $value) {
            if (! is_string($value)) {
                return $this->rpcError($id, -32602, 'Invalid params.');
            }
        }

        $prompt = $this->prompts[$name];
        foreach ($prompt->arguments() as $argument) {
            if ($argument->required && ! isset($arguments[$argument->name])) {
                return $this->rpcError($id, -32602, sprintf('Missing required argument "%s".', $argument->name));
            }
        }

        try {
            /** @var array<string, string> $arguments */
            $messages = $prompt->get($arguments, $context);
        } catch (McpException $exception) {
            return $this->rpcError($id, $exception->getCode(), $exception->getMessage());
        }
        if (is_string($messages)) {
            $messages = [PromptMessage::user($messages)];
        }

        $result = [];
        if ($prompt->description() !== '') {
            $result['description'] = $prompt->description();
        }
        $result['messages'] = array_map(static fn (PromptMessage $message): array => $message->toArray(), $messages);

        return $this->success($id, $result);
    }

    /**
     * @param array<mixed> $params
     */
    private function readResource(int|string $id, array $params, mixed $context): McpResponse
    {
        $uri = $params['uri'] ?? null;
        if (! is_string($uri) || $uri === '') {
            return $this->rpcError($id, -32602, 'Invalid params.');
        }

        try {
            if (isset($this->resources[$uri])) {
                $resource = $this->resources[$uri];

                return $this->resourceResult($id, $resource->read($context), $uri, $resource->mimeType());
            }

            foreach ($this->resourceTemplates as ['template' => $template, 'pattern' => $pattern]) {
                if (preg_match($pattern, $uri, $matches) !== 1) {
                    continue;
                }
                $variables = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $variables[$key] = rawurldecode($value);
                    }
                }

                $contents = $template->read($uri, $variables, $context);
                if ($contents !== null) {
                    return $this->resourceResult($id, $contents, $uri, $template->mimeType());
                }
            }
        } catch (McpException $exception) {
            return $this->rpcError($id, $exception->getCode(), $exception->getMessage(), data: ['uri' => $uri]);
        }

        return $this->rpcError($id, McpException::RESOURCE_NOT_FOUND, 'Resource not found.', data: ['uri' => $uri]);
    }

    /**
     * @param list<ResourceContents>|ResourceContents|string $contents
     */
    private function resourceResult(int|string $id, array|ResourceContents|string $contents, string $uri, string $mimeType): McpResponse
    {
        if (is_string($contents)) {
            $contents = ResourceContents::text($contents);
        }
        if ($contents instanceof ResourceContents) {
            $contents = [$contents];
        }

        return $this->success($id, [
            'contents' => array_map(static fn (ResourceContents $item): array => $item->toArray($uri, $mimeType), $contents),
        ]);
    }

    /**
     * @param array<mixed> $params
     */
    private function callTool(int|string $id, array $params, mixed $context): McpResponse
    {
        $name = $params['name'] ?? null;
        $arguments = $params['arguments'] ?? [];
        if (! is_string($name) || ! is_array($arguments) || (array_is_list($arguments) && $arguments !== [])) {
            return $this->rpcError($id, -32602, 'Invalid params.');
        }
        if (! isset($this->tools[$name])) {
            return $this->rpcError($id, -32602, 'Invalid params.');
        }

        try {
            $result = $this->tools[$name]->call($arguments, $context);
        } catch (ToolException $exception) {
            return $this->toolResult($id, $exception->getMessage(), true);
        }

        $text = is_string($result)
            ? $result
            : json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return $this->toolResult($id, $text, false);
    }

    private function toolResult(int|string $id, string $text, bool $error): McpResponse
    {
        return $this->success($id, [
            'content' => [
                ['type' => 'text', 'text' => $text],
            ],
            'isError' => $error,
        ]);
    }

    private function success(int|string $id, mixed $result): McpResponse
    {
        return new McpResponse(200, [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
        ]);
    }

    /**
     * @param null|array<string, mixed> $data
     */
    private function rpcError(int|string|null $id, int $code, string $message, int $status = 200, ?array $data = null): McpResponse
    {
        $error = [
            'code' => $code,
            'message' => $message,
        ];
        if ($data !== null) {
            $error['data'] = $data;
        }

        return new McpResponse($status, [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => $error,
        ]);
    }

    /**
     * @param array<mixed> $payload
     */
    private function requestId(array $payload): int|string|null
    {
        $id = $payload['id'] ?? null;

        return is_string($id) || is_int($id) ? $id : null;
    }
}
