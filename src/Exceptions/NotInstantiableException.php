<?php

namespace Vendor\ConfigResolver\Exceptions;

class NotInstantiableException extends ConfigException
{
    public function __construct(
        string $key,
        string $class
    ) {
        parent::__construct(
            "Configuration key [{$key}] resolves to [{$class}], but it is not instantiable."
        );
    }
}
