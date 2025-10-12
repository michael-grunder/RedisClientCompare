<?php

require_once __DIR__ . '/KeyCommand.php';

class ExpireCommand extends KeyCommand
{
    protected const NAME = 'EXPIRE';
    protected const ATTRIBUTES = [
        'data_type' => 'generic',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        return [$this->randomKey(), random_int(1, 3600)];
    }
}
