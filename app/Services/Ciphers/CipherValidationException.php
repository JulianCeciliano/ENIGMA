<?php

declare(strict_types=1);

namespace App\Services\Ciphers;

use InvalidArgumentException;

final class CipherValidationException extends InvalidArgumentException
{
    public function __construct(string $message, public readonly string $field = 'parameters')
    {
        parent::__construct($message);
    }
}
