<?php

require_once __DIR__ . '/KeyCommand.php';

class LtrimCommand extends KeyCommand
{
    protected const NAME = 'LTRIM';
    protected const ATTRIBUTES = [
        'data_type' => 'list',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        $start = random_int(0, 5);
        $stop = $start + random_int(0, 10);

        return [$this->randomKey(), (string) $start, (string) $stop];
    }
}
