<?php

require_once __DIR__ . '/KeyFieldCommand.php';

abstract class KeyFieldValueCommand extends KeyFieldCommand
{
    protected function generateArguments(): array
    {
        return [$this->randomKey(), $this->randomField(), $this->randomValue()];
    }
}
