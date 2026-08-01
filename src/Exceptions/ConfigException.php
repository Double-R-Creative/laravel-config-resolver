<?php

namespace DRC\ConfigResolver\Exceptions;

use RuntimeException;

abstract class ConfigException extends RuntimeException
{
    protected static function describeValue(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if ($value === true || $value === false) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return get_debug_type($value);
    }
}