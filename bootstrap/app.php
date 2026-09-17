<?php

use App\Exceptions\Discogs\DiscogsAuthException;
use App\Exceptions\Discogs\DiscogsException;
use App\Exceptions\Discogs\DiscogsNotFoundException;
use App\Exceptions\Discogs\DiscogsRateLimitException;
use App\Exceptions\Discogs\DiscogsServerException;
use App\Exceptions\DuplicateCollectionItemException;
use App\Exceptions\DuplicateWishlistItemException;
use App\Exceptions\ReleaseNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ReleaseNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        });

        $exceptions->render(function (DuplicateCollectionItemException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });

        $exceptions->render(function (DuplicateWishlistItemException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });

        // Specific subclasses must be registered before the base DiscogsException.
        $exceptions->render(function (DiscogsRateLimitException $e) {
            return response()->json(['message' => $e->getMessage()], 503)
                ->header('Retry-After', (string) $e->retryAfter());
        });

        $exceptions->render(function (DiscogsAuthException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        });

        $exceptions->render(function (DiscogsNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        });

        $exceptions->render(function (DiscogsServerException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        });

        $exceptions->render(function (DiscogsException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        });
    })->create();
