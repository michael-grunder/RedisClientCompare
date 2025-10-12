<?php

require_once __DIR__ . '/KeyCommand.php';

class ZaddCommand extends KeyCommand
{
    protected const NAME = 'ZADD';
    protected const ATTRIBUTES = [
        'data_type' => 'zset',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        return [
            $this->randomKey(),
            random_int(-1000, 1000),
            $this->randomValue(),
        ];
    }
}
