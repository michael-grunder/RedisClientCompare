<?php

require_once __DIR__ . '/KeyCommand.php';

class DecrbyCommand extends KeyCommand
{
    protected const NAME = 'DECRBY';
    protected const ATTRIBUTES = [
        'data_type' => 'string',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        return [$this->randomKey(), random_int(1, 1000)];
    }
}
