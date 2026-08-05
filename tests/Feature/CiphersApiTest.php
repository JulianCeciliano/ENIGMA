<?php

use App\Models\CipherOperation;
use App\Models\User;
use App\Services\CipherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

it('returns the documented cipher catalog', function (): void {
    $response = $this->getJson('/api/ciphers');

    $response->assertSuccessful()
        ->assertJsonCount(10)
        ->assertJsonPath('0.id', 'atbash')
        ->assertJsonPath('5.id', 'vigenere');
});

it('encrypts and records a transformation through the api', function (): void {
    $user = User::factory()->create();
    Passport::actingAs($user, ['*']);

    $response = $this->postJson('/api/ciphers/transform', [
        'algorithm' => 'vigenere',
        'operation' => 'encrypt',
        'text' => 'ATTACKATDAWN',
        'parameters' => ['key' => 'LEMON'],
        'includeSteps' => true,
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('result', 'LXFOPVEFRNHR')
        ->assertJsonPath('algorithm', 'vigenere')
        ->assertJsonPath('operation', 'encrypt');

    $this->assertDatabaseHas('cipher_operations', [
        'user_id' => $user->id,
        'algorithm' => 'vigenere',
        'operation' => 'encrypt',
        'status' => 'success',
    ]);
});

it('supports the spanish transform alias and returns api validation errors', function (): void {
    $user = User::factory()->create();
    Passport::actingAs($user, ['*']);

    $this->postJson('/api/cifrado/transform', [
        'algorithm' => 'caesar',
        'operation' => 'cifrar',
        'text' => 'HELLO',
        'parameters' => ['shift' => 3],
    ])->assertSuccessful()->assertJsonPath('result', 'KHOOR');

    $this->postJson('/api/ciphers/transform', [
        'algorithm' => 'affine',
        'operation' => 'encrypt',
        'text' => 'HELLO',
        'parameters' => ['a' => 2, 'b' => 8],
    ])->assertStatus(400)
        ->assertJsonPath('errors.a.0', 'El multiplicador a debe ser coprimo con 26.');
});

it('accepts transformations from the authenticated web session used by the frontend', function (): void {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect();

    $this->postJson('/api/ciphers/transform', [
        'algorithm' => 'caesar',
        'operation' => 'encrypt',
        'text' => 'HELLO',
        'parameters' => ['shift' => 3],
    ])->assertSuccessful()->assertJsonPath('result', 'KHOOR');
});

it('serves the authenticated user and history through a web session', function (): void {
    $user = User::factory()->create();
    CipherOperation::factory()->for($user)->create(['algorithm' => 'caesar']);

    $this->actingAs($user, 'web');

    $this->getJson('/api/user')
        ->assertSuccessful()
        ->assertJsonPath('id', $user->id);

    $this->getJson('/api/history')
        ->assertSuccessful()
        ->assertJsonPath('data.0.algorithm', 'caesar');
});

it('rejects oversized cipher parameters during request validation', function (): void {
    $user = User::factory()->create();
    Passport::actingAs($user, ['*']);

    $this->postJson('/api/ciphers/transform', [
        'algorithm' => 'columnar',
        'operation' => 'encrypt',
        'text' => 'HELLO',
        'parameters' => ['key' => str_repeat('A', 81)],
    ])->assertStatus(400)
        ->assertJsonFragment([
            'parameters.key' => ['La clave no puede superar los 80 caracteres.'],
        ]);
});

it('breaks a deterministic cipher through the api', function (): void {
    $user = User::factory()->create();
    Passport::actingAs($user, ['*']);
    $plainText = str_repeat('THE CRYPTOGRAPHY CLASS EXPLAINS WHY HISTORICAL CIPHERS ARE WEAK. ', 3);
    $cipherText = (new CipherService)->transform('atbash', 'encrypt', $plainText, [], false)['result'];

    $this->postJson('/api/ciphers/atbash/break', ['text' => $cipherText])
        ->assertSuccessful()
        ->assertJsonPath('plainText', $plainText)
        ->assertJsonPath('recoveredKey', 'Atbash fijo');
});
