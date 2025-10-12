<?php

require_once __DIR__ . '/KeyCommand.php';

class DelCommand extends KeyCommand
{
    protected const NAME = 'DEL';
    protected const ATTRIBUTES = [
        'data_type' => 'generic',
        'readonly' => false,
    ];
}
