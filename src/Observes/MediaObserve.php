<?php

namespace LarabizCMS\Mediable\Observes;

use Illuminate\Database\Eloquent\Model;
use LarabizCMS\Mediable\Models\Media;

class MediaObserve
{
    /**
     * @param  Model|Media  $media
     * @return void
     */
    public function forceDeleted(Model $media): void
    {
        collect($media->conversions ?? [])->each(
            fn($conversion) => $media->filesystem()->delete($conversion)
        );
    }
}
