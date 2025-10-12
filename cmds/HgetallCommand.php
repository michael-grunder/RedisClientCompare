<?php

require_once __DIR__ . '/KeyCommand.php';

class HgetallCommand extends KeyCommand
{
    protected const NAME = 'HGETALL';
    protected const ATTRIBUTES = [
        'data_type' => 'hash',
        'readonly' => true,
    ];
}
