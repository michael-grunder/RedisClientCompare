<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class ZrevrangebylexCommand extends ZsetLexRangeCommand
{
    protected const NAME = 'ZREVRANGEBYLEX';
    protected const ATTRIBUTES = [
        'data_type' => 'zset',
        'readonly' => true,
    ];

    protected function generateArguments(): array
    {
        $key = $this->randomKey();
        [$min, $max] = $this->randomLexRange();
        $args = [$key, $max, $min];

        if (random_int(0, 1) === 1) {
            $args[] = 'LIMIT';
            $args[] = (string) random_int(0, 25);
            $args[] = (string) random_int(1, 25);
        }

        return $args;
    }
}

