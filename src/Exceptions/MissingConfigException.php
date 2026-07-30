<?php

namespace DRC\ConfigResolver\Exceptions;

class MissingConfigException extends ConfigException
{
    public function __construct(string $key)
    {
        parent::__construct(
            "Configuration key [{$key}] is missing."
        );
    }
}