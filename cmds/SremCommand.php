<?php

require_once __DIR__ . '/KeyCommand.php';

class SremCommand extends KeyCommand
{
    protected const NAME = 'SREM';
    protected const ATTRIBUTES = [
        'data_type' => 'set',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        $values = [];
        $count = random_int(1, 5);
        for ($i = 0; $i < $count; $i++) {
            $values[] = $this->randomValue();
        }

        return array_merge([$this->randomKey()], $values);
    }
}
