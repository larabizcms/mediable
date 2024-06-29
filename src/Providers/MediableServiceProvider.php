<?php

namespace LarabizCMS\Mediable\Providers;

use Intervention\Image\Image;
use LarabizCMS\Mediable\ImageConversion;
use LarabizCMS\Mediable\ImageConversionRepository;
use LarabizCMS\Mediable\MediaRepository;
use LarabizCMS\Mediable\Models\Media;
use LarabizCMS\Mediable\Observes\MediaObserve;

class MediableServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        $this->app->singleton(ImageConversion::class, ImageConversionRepository::class);
        $this->app->singleton(\LarabizCMS\Mediable\Media::class, MediaRepository::class);
    }

    public function boot()
    {
        Media::observe(MediaObserve::class);

        $this->app[ImageConversion::class]->register(
            'thumb',
            function (Image $image) {
                // you have access to intervention/image library,
                // perform your desired conversions here
                return $image->fit(64, 64);
            }
        );
    }
}
