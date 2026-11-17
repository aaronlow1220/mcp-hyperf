<?php

declare(strict_types=1);

namespace AtelliTech\Hyperf\Mcp;

use RuntimeException;

/**
 * Thrown by a tool to report an error to the model as an `isError` tool result.
 */
class ToolException extends RuntimeException
{
}
