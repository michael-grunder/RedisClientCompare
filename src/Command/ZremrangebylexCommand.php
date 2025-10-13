<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class ZremrangebylexCommand extends ZsetLexRangeCommand
{
    protected const NAME = 'ZREMRANGEBYLEX';
    protected const ATTRIBUTES = [
        'data_type' => 'zset',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        $key = $this->randomKey();
        [$min, $max] = $this->randomLexRange();

        return [$key, $min, $max];
    }
}

