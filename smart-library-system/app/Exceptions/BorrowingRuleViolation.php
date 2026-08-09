<?php

namespace App\Exceptions;

use RuntimeException;

class BorrowingRuleViolation extends RuntimeException
{
    public static function because(string $safeMessage): self
    {
        return new self($safeMessage);
    }
}