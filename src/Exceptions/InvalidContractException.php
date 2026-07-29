<?php

namespace Vendor\ConfigResolver\Exceptions;

class InvalidContractException extends ConfigException
{
    public function __construct(
        string $key,
        string $class,
        string $contract
    ) {
        parent::__construct(
            "Configuration key [{$key}] resolves to [{$class}], which must implement or extend [{$contract}]."
        );
    }
}
