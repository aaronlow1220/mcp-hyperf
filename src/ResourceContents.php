<?php

declare(strict_types=1);

namespace AtelliTech\Hyperf\Mcp;

/**
 * Text or binary contents returned by a resource. The URI and MIME type default to the resource's own.
 */
final class ResourceContents
{
    private function __construct(
        public readonly ?string $text,
        public readonly ?string $blob,
        public readonly ?string $mimeType,
        public readonly ?string $uri,
    ) {
    }

    public static function text(string $text, ?string $mimeType = null, ?string $uri = null): self
    {
        return new self($text, null, $mimeType, $uri);
    }

    /**
     * @param string $data raw bytes; they are base64 encoded for you
     */
    public static function blob(string $data, ?string $mimeType = null, ?string $uri = null): self
    {
        return new self(null, base64_encode($data), $mimeType, $uri);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(string $uri, string $mimeType): array
    {
        $contents = ['uri' => $this->uri ?? $uri];
        $type = $this->mimeType ?? $mimeType;
        if ($type !== '') {
            $contents['mimeType'] = $type;
        }
        if ($this->blob !== null) {
            $contents['blob'] = $this->blob;
        } else {
            $contents['text'] = (string) $this->text;
        }

        return $contents;
    }
}
