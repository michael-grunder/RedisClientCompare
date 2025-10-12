<?php

require_once __DIR__ . '/KeyCommand.php';

class LrangeCommand extends KeyCommand
{
    protected const NAME = 'LRANGE';
    protected const ATTRIBUTES = [
        'data_type' => 'list',
        'readonly' => true,
    ];

    protected function generateArguments(): array
    {
        return [
            $this->randomKey(),
            (string) random_int(0, 2),
            (string) random_int(3, 12),
        ];
    }
}
