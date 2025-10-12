<?php

require_once __DIR__ . '/KeyCommand.php';

class HmgetCommand extends KeyCommand
{
    protected const NAME = 'HMGET';
    protected const ATTRIBUTES = [
        'data_type' => 'hash',
        'readonly' => true,
    ];

    protected function generateArguments(): array
    {
        $fields = [];
        $count = random_int(1, 20);
        for ($i = 0; $i < $count; $i++) {
            $fields[] = $this->randomField();
        }

        return array_merge([$this->randomKey()], $fields);
    }
}
