<?php

require_once __DIR__ . '/KeyCommand.php';

class SismemberCommand extends KeyCommand
{
    protected const NAME = 'SISMEMBER';
    protected const ATTRIBUTES = [
        'data_type' => 'set',
        'readonly' => true,
    ];

    protected function generateArguments(): array
    {
        return [$this->randomKey(), $this->randomValue()];
    }
}
