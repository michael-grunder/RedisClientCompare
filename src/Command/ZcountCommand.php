<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class ZcountCommand extends KeyCommand
{
    protected const NAME = 'ZCOUNT';
    protected const ATTRIBUTES = [
        'data_type' => 'zset',
        'readonly' => true,
    ];

    protected function generateArguments(): array
    {
        $key = $this->randomKey();
        $minValue = random_int(-1000, 0);
        $maxValue = random_int($minValue, $minValue + 1000);

        $min = (string) $minValue;
        $max = (string) $maxValue;

        if (random_int(0, 4) === 0) {
            $min = '-inf';
        } elseif (random_int(0, 4) === 0) {
            $min = '(' . $min;
        }

        if (random_int(0, 4) === 0) {
            $max = '+inf';
        } elseif (random_int(0, 4) === 0) {
            $max = '(' . $max;
        }

        return [$key, $min, $max];
    }
}

