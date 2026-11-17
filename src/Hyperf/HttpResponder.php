<?php

declare(strict_types=1);

namespace AtelliTech\Hyperf\Mcp\Hyperf;

use AtelliTech\Hyperf\Mcp\McpResponse;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Psr\Http\Message\ResponseInterface;

/**
 * Turns an {@see McpResponse} into a Hyperf HTTP response.
 */
final class HttpResponder
{
    public function __construct(private readonly HttpResponse $response)
    {
    }

    public function respond(McpResponse $result): ResponseInterface
    {
        if ($result->body === null) {
            return $this->response->raw('')->withStatus($result->status);
        }

        return $this->response->json($result->body)->withStatus($result->status);
    }
}
