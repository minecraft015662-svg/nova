<?php

declare(strict_types=1);

namespace Nexa;

final class PterodactylException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly ?array $payload = null
    ) {
        parent::__construct($message);
    }
}
