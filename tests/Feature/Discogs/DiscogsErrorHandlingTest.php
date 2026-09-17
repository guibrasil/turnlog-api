<?php

declare(strict_types=1);

use App\Discogs\DiscogsClient;
use App\Discogs\FakeDiscogsClient;
use App\Exceptions\Discogs\DiscogsAuthException;
use App\Exceptions\Discogs\DiscogsException;
use App\Exceptions\Discogs\DiscogsNotFoundException;
use App\Exceptions\Discogs\DiscogsRateLimitException;
use App\Exceptions\Discogs\DiscogsServerException;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

// ─── HttpDiscogsClient error mapping ────────────────────────────────────────

describe('HttpDiscogsClient maps HTTP errors to domain exceptions', function (): void {
    it('throws DiscogsAuthException on 401', function (): void {
        Http::fake(['*' => Http::response(null, 401)]);

        $client = app(DiscogsClient::class);

        expect(fn () => $client->searchReleases('test'))
            ->toThrow(DiscogsAuthException::class, 'Discogs authentication failed.');
    });

    it('throws DiscogsNotFoundException on 404', function (): void {
        Http::fake(['*' => Http::response(null, 404)]);

        $client = app(DiscogsClient::class);

        expect(fn () => $client->getRelease(999999))
            ->toThrow(DiscogsNotFoundException::class, 'Release not found on Discogs.');
    });

    it('throws DiscogsRateLimitException on 429 with Retry-After header', function (): void {
        Http::fake(['*' => Http::response(null, 429, ['Retry-After' => '45'])]);

        $client = app(DiscogsClient::class);

        try {
            $client->searchReleases('test');
        } catch (DiscogsRateLimitException $e) {
            expect($e->getMessage())->toBe('Discogs rate limit reached.');
            expect($e->retryAfter())->toBe(45);

            return;
        }

        $this->fail('Expected DiscogsRateLimitException was not thrown.');
    });

    it('uses default retryAfter of 60 when Retry-After header is absent', function (): void {
        Http::fake(['*' => Http::response(null, 429)]);

        $client = app(DiscogsClient::class);

        try {
            $client->searchReleases('test');
        } catch (DiscogsRateLimitException $e) {
            expect($e->retryAfter())->toBe(60);

            return;
        }

        $this->fail('Expected DiscogsRateLimitException was not thrown.');
    });

    it('throws DiscogsServerException on 500', function (): void {
        Http::fake(['*' => Http::response(null, 500)]);

        $client = app(DiscogsClient::class);

        expect(fn () => $client->searchReleases('test'))
            ->toThrow(DiscogsServerException::class, 'Discogs server error.');
    });

    it('throws DiscogsServerException on 503', function (): void {
        Http::fake(['*' => Http::response(null, 503)]);

        $client = app(DiscogsClient::class);

        expect(fn () => $client->getRelease(1))
            ->toThrow(DiscogsServerException::class);
    });

    it('throws base DiscogsException for unexpected 4xx status', function (): void {
        Http::fake(['*' => Http::response(null, 403)]);

        $client = app(DiscogsClient::class);

        expect(fn () => $client->searchReleases('test'))
            ->toThrow(DiscogsException::class, 'Discogs request failed with status 403.');
    });

    it('throws base DiscogsException on connection failure', function (): void {
        Http::fake(['*' => fn () => throw new ConnectionException('Network error')]);

        $client = app(DiscogsClient::class);

        expect(fn () => $client->searchReleases('test'))
            ->toThrow(DiscogsException::class, 'Could not connect to Discogs.');
    });
});

// ─── Endpoint responses when Discogs errors propagate ───────────────────────

describe('API endpoints return correct status codes for Discogs errors', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->fake = new FakeDiscogsClient;
        $this->app->instance(DiscogsClient::class, $this->fake);
    });

    it('returns 503 when Discogs search hits rate limit', function (): void {
        $this->fake->failWith(new DiscogsRateLimitException('Discogs rate limit reached.', 30));

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/releases/search?q=radiohead')
            ->assertStatus(503)
            ->assertJsonFragment(['message' => 'Discogs rate limit reached.'])
            ->assertHeader('Retry-After', '30');
    });

    it('returns 503 when Discogs auth fails on search', function (): void {
        $this->fake->failWith(new DiscogsAuthException('Discogs authentication failed.'));

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/releases/search?q=radiohead')
            ->assertStatus(503)
            ->assertJsonFragment(['message' => 'Discogs authentication failed.']);
    });

    it('returns 404 when Discogs reports release not found', function (): void {
        $this->fake->failWith(new DiscogsNotFoundException('Release not found on Discogs.'));

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/releases/barcode/5099749524224')
            ->assertStatus(404)
            ->assertJsonFragment(['message' => 'Release not found on Discogs.']);
    });

    it('returns 503 on Discogs server error during barcode lookup', function (): void {
        $this->fake->failWith(new DiscogsServerException('Discogs server error.'));

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/releases/barcode/5099749524224')
            ->assertStatus(503)
            ->assertJsonFragment(['message' => 'Discogs server error.']);
    });

    it('returns 503 on generic Discogs error', function (): void {
        $this->fake->failWith(new DiscogsException('Discogs request failed with status 403.'));

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/releases/search?q=radiohead')
            ->assertStatus(503)
            ->assertJsonFragment(['message' => 'Discogs request failed with status 403.']);
    });
});
