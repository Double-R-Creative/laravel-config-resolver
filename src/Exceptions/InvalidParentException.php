<?php

namespace DRC\ConfigResolver\Exceptions;

class InvalidParentException extends ConfigException
{
    public function __construct(
        string $key,
        string $class,
        string $parent
    ) {
        parent::__construct(
            "Configuration key [{$key}] resolves to [{$class}], which must extend [{$parent}]."
        );
    }
}
