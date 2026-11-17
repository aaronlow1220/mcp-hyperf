# AGENTS.md

`atellitech/mcp-hyperf` is a PHP library: a stateless MCP server over the Streamable HTTP transport that apps mount on a route in their existing Hyperf HTTP server. It serves tools, prompts and resources. [README.md](README.md) is the user-facing documentation; keep it in sync with any public API change.

## Commands

```bash
composer install
vendor/bin/phpstan analyse                 # level 7, src/ only
vendor/bin/php-cs-fixer fix --dry-run --diff
composer codetest                          # runs php-cs-fixer fix (rewrites files!) then phpstan
```

There is no test suite. To check behaviour, write a throwaway script outside the repo that requires `vendor/autoload.php`, builds `new McpServer(...)` with anonymous-class tools/prompts/resources, and calls `handle(new McpRequest('POST', 'application/json, text/event-stream', '', 'host', '', $jsonBody), $context)`.

## Layout

- `src/McpServer.php`: the whole protocol. It handles HTTP-level checks (Origin, Accept, protocol version, body size), JSON-RPC parsing and dispatch for `initialize`, `ping`, `tools/*`, `prompts/*` and `resources/*`.
- `src/McpRequest.php` / `src/McpResponse.php`: framework-neutral input and output. `McpResponse::$body === null` means an empty body.
- Extension points that apps implement: `McpTool`, `McpPrompt`, `McpResource` (fixed URI) and `McpResourceTemplate` (URI template).
- Value objects: `PromptArgument`, `PromptMessage`, `ResourceContents` and `Schema`, which builds JSON Schema objects.
- Exceptions: `ToolException` (tool error → `isError` result the model reads) and `McpException` (prompt/resource error → JSON-RPC error, code in `getCode()`).
- `src/Hyperf/`: the only Hyperf-dependent code. `McpServerFactory` builds the server from `config/autoload/mcp.php`, and `HttpResponder` converts `McpResponse` to a Hyperf response. `ConfigProvider` registers both and publishes `publish/mcp.php`.

## Design rules

- **Core stays framework-free.** Only `src/Hyperf/` and `ConfigProvider` may use Hyperf classes. The core depends on PSR interfaces only.
- **Stateless.** No sessions, no SSE streams, no server-initiated messages. `GET`/`DELETE` return 405. Don't add features that need state across requests, such as subscriptions or `listChanged` notifications, without discussing it first.
- **Auth is the app's job.** The app passes the authenticated user as `$context` to `handle()`, and every tool/prompt/resource receives it. Don't add auth logic here.
- **Backward compatibility.** This is a published library. Add new `McpServer` constructor parameters at the end, since callers may pass them by position. Don't change existing interface method signatures.
- **Capabilities.** `tools` is always advertised. `prompts`/`resources` are advertised only when some are registered.
- **Error codes.** HTTP-level failures use 4xx statuses. JSON-RPC errors for a valid request use HTTP 200: `-32602` invalid params (also unknown tool/prompt), `-32601` unknown method, `-32002` resource not found. Notifications (no `id`) get 202 with no body.
- **Supported protocol version** is `2025-06-18` (`McpServer::PROTOCOL_VERSIONS`).

## Code conventions

- PHP ≥ 8.3, `declare(strict_types=1);` in every file, namespace `AtelliTech\Hyperf\Mcp`.
- Concrete classes are `final`, with `readonly` promoted properties. Value objects expose a `toArray()` that produces the MCP wire format.
- Add phpstan array shapes in docblocks (`@param array<string, mixed>`, `@return list<...>`). Code must pass level 7.
- Empty JSON objects must encode as `{}`, not `[]`. Use `new stdClass()`, as `Schema::object()` and `ping` do.
- `McpServer` compiles URI templates into regexes with the `#` delimiter, so escape `#` inside patterns (`[^/?\#]`).
- Style comes from `.php-cs-fixer.php` (PSR-2 + Symfony + PhpCsFixer rule sets). Run the fixer instead of formatting by hand.
