<?php

require_once __DIR__ . '/KeyFieldCommand.php';

class HincrbyCommand extends KeyFieldCommand
{
    protected const NAME = 'HINCRBY';
    protected const ATTRIBUTES = [
        'data_type' => 'hash',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        $delta = random_int(-100, 100);
        if ($delta === 0) {
            $delta = 1;
        }

        return [$this->randomKey(), $this->randomField(), (string) $delta];
    }
}
