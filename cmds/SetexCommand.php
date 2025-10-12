<?php

require_once __DIR__ . '/KeyCommand.php';

class SetexCommand extends KeyCommand
{
    protected const NAME = 'SETEX';
    protected const ATTRIBUTES = [
        'data_type' => 'string',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        return [
            $this->randomKey(),
            (string) random_int(1, 3600),
            $this->randomValue(),
        ];
    }
}
