<?php

require_once __DIR__ . '/KeyFieldValueCommand.php';

class HsetCommand extends KeyFieldValueCommand
{
    protected const NAME = 'HSET';
    protected const ATTRIBUTES = [
        'data_type' => 'hash',
        'readonly' => false,
    ];
}
