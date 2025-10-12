<?php

require_once __DIR__ . '/KeyCommand.php';

class HdelCommand extends KeyCommand
{
    protected const NAME = 'HDEL';
    protected const ATTRIBUTES = [
        'data_type' => 'hash',
        'readonly' => false,
    ];
}
