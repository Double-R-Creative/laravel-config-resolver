<?php

namespace DRC\ConfigResolver\Exceptions;

class InvalidPatternException extends ConfigException
{
    public function __construct(string $key, string $pattern)
    {
        parent::__construct(
            "Configuration key [{$key}] uses an invalid regex pattern [{$pattern}]."
        );
    }
}
