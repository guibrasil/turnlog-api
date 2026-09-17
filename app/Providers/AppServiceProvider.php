<?php

declare(strict_types=1);

namespace App\Providers;

use App\Discogs\CachingDiscogsClient;
use App\Discogs\DiscogsClient;
use App\Discogs\HttpDiscogsClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DiscogsClient::class, fn (): CachingDiscogsClient => new CachingDiscogsClient(
            new HttpDiscogsClient(token: config('services.discogs.token')),
        ));
    }

    public function boot(): void {}
}
