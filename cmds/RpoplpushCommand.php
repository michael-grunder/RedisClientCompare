<?php

require_once __DIR__ . '/KeyCommand.php';

class RpoplpushCommand extends KeyCommand
{
    protected const NAME = 'RPOPLPUSH';
    protected const ATTRIBUTES = [
        'data_type' => 'list',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        return [$this->randomKey(), $this->randomKey()];
    }
}
