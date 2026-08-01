<?php

namespace DRC\ConfigResolver\Exceptions;

class InvalidStringFormatException extends ConfigException
{
    public function __construct(string $key, string $requirement)
    {
        parent::__construct(
            "Configuration key [{$key}] must {$requirement}."
        );
    }
}
