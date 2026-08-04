<?php

use App\Models\CipherOperation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

it('shows the terminal access screen to guests', function (): void {
    $this->get('/terminal-access')->assertSuccessful();
});

it('serves the login route with GET to guests', function (): void {
    $this->get('/login')->assertSuccessful();
});

it('authenticates an operator through the terminal form', function (): void {
    $user = User::factory()->create([
        'email' => 'operator@example.com',
        'password' => 'password',
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('ciphers.catalog'));

    $this->assertAuthenticatedAs($user);
});

it('returns an authenticated operators history and clears only that history', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    CipherOperation::factory()->for($user)->create(['algorithm' => 'caesar']);
    CipherOperation::factory()->for($otherUser)->create(['algorithm' => 'rot13']);
    Passport::actingAs($user, ['*']);

    $this->getJson('/api/history')
        ->assertSuccessful()
        ->assertJsonPath('data.0.algorithm', 'caesar');

    $this->deleteJson('/api/history')->assertSuccessful();

    expect(CipherOperation::query()->whereBelongsTo($user)->count())->toBe(0)
        ->and(CipherOperation::query()->whereBelongsTo($otherUser)->count())->toBe(1);
});

it('renders the protected cipher pages with the catalog contract', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/ciphers/catalog')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('ciphers/catalog')
            ->has('ciphers', 10)
        );
});
