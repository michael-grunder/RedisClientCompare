<?php

require_once __DIR__ . '/KeyValueCommand.php';

class SetnxCommand extends KeyValueCommand
{
    protected const NAME = 'SETNX';
    protected const ATTRIBUTES = [
        'data_type' => 'string',
        'readonly' => false,
    ];
}
