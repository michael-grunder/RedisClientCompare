<?php

require_once __DIR__ . '/KeyCommand.php';

class SmembersCommand extends KeyCommand
{
    protected const NAME = 'SMEMBERS';
    protected const ATTRIBUTES = [
        'data_type' => 'set',
        'readonly' => true,
    ];
}
