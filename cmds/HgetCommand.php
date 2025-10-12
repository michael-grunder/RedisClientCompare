<?php

require_once __DIR__ . '/KeyCommand.php';

class HgetCommand extends KeyCommand
{
    protected const NAME = 'HGET';
    protected const ATTRIBUTES = [
        'data_type' => 'hash',
        'readonly' => true,
    ];
}
