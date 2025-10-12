<?php

require_once __DIR__ . '/KeyCommand.php';

class ZrangeCommand extends KeyCommand
{
    protected const NAME = 'ZRANGE';
    protected const ATTRIBUTES = [
        'data_type' => 'zset',
        'readonly' => true,
    ];

    protected function generateArguments(): array
    {
        return [$this->randomKey(), '0', '-1'];
    }
}
