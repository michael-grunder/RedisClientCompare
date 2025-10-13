<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class ZrevrangebyscoreCommand extends KeyCommand
{
    protected const NAME = 'ZREVRANGEBYSCORE';
    protected const ATTRIBUTES = [
        'data_type' => 'zset',
        'readonly' => true,
    ];

    protected function generateArguments(): array
    {
        $key = $this->randomKey();
        [$min, $max] = $this->randomScoreRange();
        $args = [$key, $max, $min];

        if (random_int(0, 1) === 1) {
            $args[] = 'WITHSCORES';
        }

        if (random_int(0, 1) === 1) {
            $args[] = 'LIMIT';
            $args[] = (string) random_int(0, 25);
            $args[] = (string) random_int(1, 25);
        }

        return $args;
    }

    /**
     * @return string[]
     */
    private function randomScoreRange(): array
    {
        if (random_int(0, 3) === 0) {
            return ['-inf', '+inf'];
        }

        $min = random_int(-1000, 0);
        $max = random_int($min, $min + 1000);

        $minBound = (string) $min;
        $maxBound = (string) $max;

        if (random_int(0, 1) === 1) {
            $minBound = '(' . $minBound;
        }

        if (random_int(0, 1) === 1) {
            $maxBound = '(' . $maxBound;
        }

        return [$minBound, $maxBound];
    }
}

