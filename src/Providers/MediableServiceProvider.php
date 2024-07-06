<?php

namespace LarabizCMS\Mediable\Providers;

use Illuminate\Support\ServiceProvider;
use LarabizCMS\Mediable\ImageConversion;
use LarabizCMS\Mediable\ImageConversionRepository;
use LarabizCMS\Mediable\MediaRepository;
use LarabizCMS\Mediable\Models\Media;
use LarabizCMS\Mediable\Observes\MediaObserve;

class MediableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        $this->app->singleton(ImageConversion::class, ImageConversionRepository::class);
        $this->app->singleton(\LarabizCMS\Mediable\Media::class, MediaRepository::class);
    }

    public function boot(): void
    {
        Media::observe(MediaObserve::class);
    }
}
