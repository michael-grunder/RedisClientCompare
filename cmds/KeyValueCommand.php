<?php

require_once __DIR__ . '/KeyCommand.php';

abstract class KeyValueCommand extends KeyCommand
{
    protected function generateArguments(): array
    {
        return [$this->randomKey(), $this->randomValue()];
    }
}
