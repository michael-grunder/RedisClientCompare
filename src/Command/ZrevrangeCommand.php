<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class ZrevrangeCommand extends KeyCommand
{
    protected const NAME = 'ZREVRANGE';
    protected const ATTRIBUTES = [
        'data_type' => 'zset',
        'readonly' => true,
    ];

    protected function generateArguments(): array
    {
        $key = $this->randomKey();
        $start = random_int(0, 50);
        $stop = random_int(0, 1) === 0 ? -1 : $start + random_int(0, 50);
        $args = [$key, (string) $start, (string) $stop];

        if (random_int(0, 1) === 1) {
            $args[] = 'WITHSCORES';
        }

        return $args;
    }
}
