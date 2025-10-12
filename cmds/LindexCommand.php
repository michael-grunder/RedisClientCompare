<?php

require_once __DIR__ . '/KeyCommand.php';

class LindexCommand extends KeyCommand
{
    protected const NAME = 'LINDEX';
    protected const ATTRIBUTES = [
        'data_type' => 'list',
        'readonly' => true,
    ];

    protected function generateArguments(): array
    {
        return [$this->randomKey(), (string) random_int(-10, 50)];
    }
}
