<?php

namespace LarabizCMS\Mediable\Facades;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Facade;
use Intervention\Image\Image;
use LarabizCMS\Mediable\Models\Media as MediaModel;

/**
 * @method static string upload(string|UploadedFile $source = null, string $disk = 'public', string $name = null)
 * @method static string guessExtension(string $mimeType)
 * @method static string readableSize(int $bytes, int $precision = 1)
 * @method static string sanitizeFileName(string $fileName)
 * @method static string pathToUploadedFile(string $path, bool $test = false)
 * @method static Image convert(MediaModel $media, string $conversion, string $toPath)
 * @method static string getImageSize(string $path)
 * @method static void setModel()
 * @method static string getModel()
 * @see \LarabizCMS\Mediable\Media
 */
class Media extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \LarabizCMS\Mediable\Media::class;
    }
}
