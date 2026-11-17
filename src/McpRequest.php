<?php

declare(strict_types=1);

namespace AtelliTech\Hyperf\Mcp;

use Psr\Http\Message\ServerRequestInterface;

/**
 * The parts of an HTTP request the MCP server needs.
 */
final class McpRequest
{
    public function __construct(
        public readonly string $method,
        public readonly string $accept,
        public readonly string $origin,
        public readonly string $host,
        public readonly string $protocolVersion,
        public readonly string $body,
    ) {
    }

    public static function fromPsr(ServerRequestInterface $request): self
    {
        $body = $request->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        return new self(
            $request->getMethod(),
            $request->getHeaderLine('Accept'),
            $request->getHeaderLine('Origin'),
            $request->getHeaderLine('Host'),
            $request->getHeaderLine('MCP-Protocol-Version'),
            $body->getContents(),
        );
    }
}
