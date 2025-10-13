<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class ZremrangebyrankCommand extends KeyCommand
{
    protected const NAME = 'ZREMRANGEBYRANK';
    protected const ATTRIBUTES = [
        'data_type' => 'zset',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        $key = $this->randomKey();
        $start = random_int(0, 50);
        $stop = random_int(0, 1) === 0 ? -1 : $start + random_int(0, 50);

        return [$key, (string) $start, (string) $stop];
    }
}

