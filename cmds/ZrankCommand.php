<?php

require_once __DIR__ . '/KeyCommand.php';

class ZrankCommand extends KeyCommand
{
    protected const NAME = 'ZRANK';
    protected const ATTRIBUTES = [
        'data_type' => 'zset',
        'readonly' => true,
    ];

    protected function generateArguments(): array
    {
        return [$this->randomKey(), $this->randomValue()];
    }
}
