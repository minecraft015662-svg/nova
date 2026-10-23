<?php

declare(strict_types=1);

namespace Nexa;

final class RateLimiter
{
    public function __construct(private readonly Database $db)
    {
    }

    public function hit(string $bucket, int $max, int $windowSeconds): bool
    {
        $key = substr(hash('sha256', $bucket), 0, 48);
        $now = time();
        $row = $this->db->get('SELECT hits, reset_at FROM rate_limits WHERE bucket = :bucket', [':bucket' => $key]);

        if ($row === null || (int) $row['reset_at'] <= $now) {
            $this->db->run(
                'INSERT INTO rate_limits (bucket, hits, reset_at) VALUES (:bucket, 1, :reset_at)
                 ON CONFLICT(bucket) DO UPDATE SET hits = 1, reset_at = excluded.reset_at',
                [':bucket' => $key, ':reset_at' => $now + $windowSeconds]
            );

            return true;
        }

        if ((int) $row['hits'] >= $max) {
            return false;
        }

        $this->db->run('UPDATE rate_limits SET hits = hits + 1 WHERE bucket = :bucket', [':bucket' => $key]);

        return true;
    }

    public function enforce(string $bucket, int $max, int $windowSeconds, string $message = 'Too many requests. Try again shortly.'): void
    {
        if (!$this->hit($bucket, $max, $windowSeconds)) {
            abort(429, $message, 'rate_limited');
        }
    }
}
