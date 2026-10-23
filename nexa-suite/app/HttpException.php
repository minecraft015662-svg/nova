<?php

declare(strict_types=1);

namespace Nexa;

final class HttpException extends \RuntimeException
{
    public function __construct(
        public readonly int $status,
        string $message,
        public readonly string $errorCode = 'error'
    ) {
        parent::__construct($message);
    }
}
