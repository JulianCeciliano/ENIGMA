<?php

namespace App\Http\Controllers;

use App\Services\CipherService;
use Inertia\Inertia;
use Inertia\Response;

final class CipherPageController extends Controller
{
    public function breakPage(CipherService $cipherService): Response
    {
        return Inertia::render('break/index', ['ciphers' => $cipherService->catalog()]);
    }

    public function catalog(CipherService $cipherService): Response
    {
        return Inertia::render('ciphers/catalog', ['ciphers' => $cipherService->catalog()]);
    }

    public function decrypt(CipherService $cipherService): Response
    {
        return Inertia::render('ciphers/decrypt', ['ciphers' => $cipherService->catalog()]);
    }

    public function encrypt(CipherService $cipherService): Response
    {
        return Inertia::render('ciphers/encrypt', ['ciphers' => $cipherService->catalog()]);
    }
}
