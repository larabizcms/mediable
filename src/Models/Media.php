<?php

namespace LarabizCMS\Mediable\Models;

use Eloquent;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use LarabizCMS\Mediable\Media as MediaContract;

/**
 * LarabizCMS\Mediable\Models\Media
 *
 * @property int $id
 * @property string $disk
 * @property int|null $user_id
 * @property string $name
 * @property string $type
 * @property string $path
 * @property string|null $mime_type
 * @property string|null $extension
 * @property string|null $image_size
 * @property int $size
 * @property array|null $conversions
 * @property array|null $metadata
 * @property int|null $parent_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read string $media_url
 * @method static Builder|Media newModelQuery()
 * @method static Builder|Media newQuery()
 * @method static Builder|Media onlyTrashed()
 * @method static Builder|Media query()
 * @method static Builder|Media whereConversions($value)
 * @method static Builder|Media whereCreatedAt($value)
 * @method static Builder|Media whereDeletedAt($value)
 * @method static Builder|Media whereDisk($value)
 * @method static Builder|Media whereExtension($value)
 * @method static Builder|Media whereId($value)
 * @method static Builder|Media whereMetadata($value)
 * @method static Builder|Media whereMimeType($value)
 * @method static Builder|Media whereName($value)
 * @method static Builder|Media whereParentId($value)
 * @method static Builder|Media wherePath($value)
 * @method static Builder|Media whereSize($value)
 * @method static Builder|Media whereType($value)
 * @method static Builder|Media whereUpdatedAt($value)
 * @method static Builder|Media whereUserId($value)
 * @method static Builder|Media withTrashed()
 * @method static Builder|Media withoutTrashed()
 * @method static Builder|Media findByPath(string $path, string $disk = 'public', array $columns = ['*'])
 * @mixin Eloquent
 */
class Media extends Model
{
    use SoftDeletes;

    public const TYPE_FILE = 'file';
    public const TYPE_DIR = 'dir';

    protected Filesystem $filesystem;

    protected $table = 'media';

    protected $fillable = [
        'user_id',
        'name',
        'conversions',
        'path',
        'parent_id',
        'mime_type',
        'size',
        'type',
        'metadata',
        'extension',
        'image_size',
    ];

    protected $casts = [
        'conversions' => 'array',
        'metadata' => 'array',
    ];

    protected $appends = ['url', 'is_directory', 'readable_size', 'is_image'];

    public function findByPath(string $path, string $disk = 'public', array $columns = ['*']): ?Model
    {
        return $this->where(['path' => $path, 'disk' => $disk])->first($columns);
    }

    public function uploadable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'uploaded_by_type', 'uploaded_by_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id', 'id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id', 'id');
    }

    /**
     * Get the original media url.
     *
     * @return string|null
     */
    public function getUrlAttribute(): ?string
    {
        return $this->getUrl();
    }

    public function getIsDirectoryAttribute(): bool
    {
        return $this->isDirectory();
    }

    public function getReadableSizeAttribute(): string
    {
        return $this->readableSize();
    }

    public function isDirectory(): bool
    {
        return $this->type === static::TYPE_DIR;
    }

    public function getIsImageAttribute(): bool
    {
        return $this->isImage();
    }

    public function isImage(): bool
    {
        return in_array($this->mime_type, config('filesystems.image_mime_types'));
    }

    public function readableSize(int $precision = 1): string
    {
        return app(MediaContract::class)->readableSize($this->size, $precision);
    }

    /**
     * Get the url to the file.
     *
     * @param string $conversion
     * @return string
     */
    public function getUrl(?string $conversion = null): ?string
    {
        if ($this->disk !== 'public') {
            return null;
        }

        return $this->filesystem()->url(
            $this->getPath($conversion)
        );
    }

    /**
     * Get the full path to the file.
     *
     * @param string|null $conversion
     * @return string|null
     */
    public function getFullPath(?string $conversion = null): null|string
    {
        return $this->filesystem()->path(
            $this->getPath($conversion)
        );
    }

    /**
     * Get the path to the file on disk.
     *
     * @param string|null $conversion
     * @return string|null
     */
    public function getPath(?string $conversion = null): ?string
    {
        if ($conversion) {
            return $this->conversions[$conversion]['path'] ?? null;
        }

        return $this->path;
    }

    /**
     * Get the url of the file by its path.
     *
     * @param string $path The path of the file
     * @return string|null The url of the file, or null if it doesn't exist
     */
    public function getUrlByPath(string $path): ?string
    {
        // Get the url of the file using the filesystem
        return $this->filesystem()->url($path);
    }

    /**
     * Get the collection of conversions.
     *
     * @return Collection
     */
    public function collectConversion(): Collection
    {
        return collect($this->conversions ?? []);
    }

    public function generateConversionPath(string $conversion): string
    {
        $folder = date('Y/m/d');

        $folder = "{$folder}/conversions/{$conversion}";

        if ($this->filesystem()->directoryMissing($folder)) {
            $this->filesystem()->makeDirectory($folder);
        }

        return "{$folder}/{$this->name}";
    }

    public function getConversionResponse(): array
    {
        // srcset="elva-fairy-480w.jpg 480w, elva-fairy-800w.jpg 800w"
        $conversions = $this->collectConversion()->prepend(
            ['path' => $this->path, 'image_size' => $this->image_size],
            'origin'
        );

        $response = $conversions->mapWithKeys(
            fn($item, $conversion) => [$conversion => $this->getUrlByPath($item['path'])]
        );

        $srcset = $response->map(
            function ($url, $conversion) use ($conversions) {
                $size = $conversions[$conversion]['image_size'] ?? 'autoxauto';
                $width = explode('x', $size)[0];
                return "{$url} {$width}w";
            }
        )->implode(', ');

        return $response->put('srcset', $srcset)->toArray();
    }

    /**
     * Get the filesystem where the associated file is stored.
     *
     * @return Filesystem|FilesystemAdapter
     */
    public function filesystem(): Filesystem|FilesystemAdapter
    {
        return $this->filesystem ??= Storage::disk($this->disk);
    }

    public function forceDelete(): ?bool
    {
        $this->filesystem()->delete($this->path);

        return parent::forceDelete();
    }
}
