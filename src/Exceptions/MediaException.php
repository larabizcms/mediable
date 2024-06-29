<?php

namespace LarabizCMS\Mediable\Exceptions;

use LarabizCMS\Mediable\Facades\Media;

class MediaException extends \Exception
{
    public static function extensionNotFound(string $name): static
    {
        return new static("File {$name} extension not found");
    }

    public static function fileNotFound(string $filename): static
    {
        return new static("File {$filename} not found");
    }

    public static function failedToUpload(string $filename): static
    {
        return new static("Failed to upload file {$filename}");
    }

    public static function mimeTypeNotSupported(array $types): static
    {
        return new static("File mime type not supported, supported types: ".implode(', ', $types));
    }

    public static function extensionNotSupported(array $extensions): static
    {
        return new static("File extension not supported, supported extensions: ".implode(', ', $extensions));
    }

    public static function maxFileSizeExceeded(int $maxSize): static
    {
        return new static('Maximum file size exceeded, max size: '. Media::readableSize($maxSize));
    }
}
