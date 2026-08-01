<?php

namespace DRC\ConfigResolver\Exceptions;

class MissingArrayKeyException extends ConfigException
{
    public function __construct(string $key, string|int $requiredKey)
    {
        parent::__construct(
            "Configuration key [{$key}] must contain key [{$requiredKey}]."
        );
    }
}
