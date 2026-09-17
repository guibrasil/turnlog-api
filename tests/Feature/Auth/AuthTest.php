<?php

declare(strict_types=1);

use App\Models\User;

describe('register', function (): void {
    it('creates a user and returns a token', function (): void {
        $this->postJson('/api/auth/register', [
            'name' => 'Gui',
            'email' => 'gui@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertCreated()
            ->assertJsonStructure([
                'data' => ['token', 'type', 'user' => ['id', 'name', 'email']],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'gui@example.com']);
    });

    it('rejects a duplicate email', function (): void {
        User::factory()->create(['email' => 'gui@example.com']);

        $this->postJson('/api/auth/register', [
            'name' => 'Gui',
            'email' => 'gui@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertUnprocessable();
    });

    it('requires password confirmation', function (): void {
        $this->postJson('/api/auth/register', [
            'name' => 'Gui',
            'email' => 'gui@example.com',
            'password' => 'password',
        ])->assertUnprocessable();
    });

    it('requires all fields', function (): void {
        $this->postJson('/api/auth/register', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    });
});

describe('login', function (): void {
    it('returns a token for valid credentials', function (): void {
        $user = User::factory()->create();

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['token', 'type', 'user' => ['id', 'name', 'email']],
            ]);
    });

    it('rejects wrong credentials', function (): void {
        $user = User::factory()->create();

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('requires all fields', function (): void {
        $this->postJson('/api/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    });
});

describe('logout', function (): void {
    it('revokes the current token', function (): void {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertNoContent();

        expect($user->tokens()->count())->toBe(0);
    });

    it('requires authentication', function (): void {
        $this->postJson('/api/auth/logout')->assertUnauthorized();
    });
});
