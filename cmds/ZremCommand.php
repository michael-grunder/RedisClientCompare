<?php

require_once __DIR__ . '/KeyCommand.php';

class ZremCommand extends KeyCommand
{
    protected const NAME = 'ZREM';
    protected const ATTRIBUTES = [
        'data_type' => 'zset',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        return [$this->randomKey(), $this->randomValue()];
    }
}
