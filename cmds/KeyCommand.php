<?php

require_once __DIR__ . '/Command.php';

abstract class KeyCommand extends Command
{
    protected function generateArguments(): array
    {
        return [$this->randomScalarKey()];
    }

    protected function randomKey()
    {
        return $this->randomScalarKey();
    }

    protected function randomField()
    {
        return $this->randomScalarField();
    }
}
