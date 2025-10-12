<?php

require_once __DIR__ . '/KeyCommand.php';

class LremCommand extends KeyCommand
{
    protected const NAME = 'LREM';
    protected const ATTRIBUTES = [
        'data_type' => 'list',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        return [
            $this->randomKey(),
            (string) random_int(-3, 3),
            $this->randomValue(),
        ];
    }
}
