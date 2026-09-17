<?php

declare(strict_types=1);

namespace App\Exceptions\Discogs;

class DiscogsRateLimitException extends DiscogsException
{
    public function __construct(string $message, private readonly int $retryAfter = 60)
    {
        parent::__construct($message);
    }

    public function retryAfter(): int
    {
        return $this->retryAfter;
    }
}
