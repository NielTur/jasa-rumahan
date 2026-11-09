<?php

namespace App\Exceptions;

use RuntimeException;

// Dilempar dari Service untuk error bisnis (401, 403, 409, dst.).
// Handler mengubahnya jadi respons JSON sesuai kontrak.
class ApiException extends RuntimeException
{
    public function __construct(string $message, private int $status = 400)
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }
}