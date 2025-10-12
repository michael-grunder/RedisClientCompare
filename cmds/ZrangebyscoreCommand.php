<?php

require_once __DIR__ . '/KeyCommand.php';

class ZrangebyscoreCommand extends KeyCommand
{
    protected const NAME = 'ZRANGEBYSCORE';
    protected const ATTRIBUTES = [
        'data_type' => 'zset',
        'readonly' => true,
    ];

    protected function generateArguments(): array
    {
        $key = $this->randomKey();
        $min = random_int(-1000, 0);
        $max = random_int($min, $min + 1000);
        $args = [$key, $min, $max];

        if (random_int(0, 1) === 1) {
            $args[] = 'WITHSCORES';
        }

        return $args;
    }
}
