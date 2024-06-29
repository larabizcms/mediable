<?php

namespace LarabizCMS\Mediable\Observes;

use LarabizCMS\Mediable\Models\Media;

class MediaObserve
{
    public function deleted(Media $media): void
    {
        collect($media->conversions ?? [])->each(
            fn($conversion) => $media->filesystem()->delete($conversion)
        );
    }
}
