<?php

require_once __DIR__ . '/KeyCommand.php';

class IncrbyfloatCommand extends KeyCommand
{
    protected const NAME = 'INCRBYFLOAT';
    protected const ATTRIBUTES = [
        'data_type' => 'string',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        return [$this->randomKey(), (string) $this->randomFloat()];
    }
}
