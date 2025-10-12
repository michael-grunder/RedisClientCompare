<?php

require_once __DIR__ . '/KeyCommand.php';

class PersistCommand extends KeyCommand
{
    protected const NAME = 'PERSIST';
    protected const ATTRIBUTES = [
        'data_type' => 'generic',
        'readonly' => false,
    ];
}
