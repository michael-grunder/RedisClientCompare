<?php

require_once __DIR__ . '/KeyCommand.php';

class TtlCommand extends KeyCommand
{
    protected const NAME = 'TTL';
    protected const ATTRIBUTES = [
        'data_type' => 'generic',
        'readonly' => true,
    ];
}
