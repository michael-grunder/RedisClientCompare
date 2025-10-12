<?php

require_once __DIR__ . '/KeyCommand.php';

class ZcardCommand extends KeyCommand
{
    protected const NAME = 'ZCARD';
    protected const ATTRIBUTES = [
        'data_type' => 'zset',
        'readonly' => true,
    ];
}
