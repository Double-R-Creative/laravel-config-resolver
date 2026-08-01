<?php

namespace DRC\ConfigResolver\Resolvers;

use DRC\ConfigResolver\Exceptions\InvalidClassException;
use DRC\ConfigResolver\Exceptions\InvalidContractException;
use DRC\ConfigResolver\Exceptions\InvalidParentException;
use DRC\ConfigResolver\Exceptions\MissingConfigException;
use DRC\ConfigResolver\Exceptions\NotInstantiableException;
use DRC\ConfigResolver\Resolvers\Resolver;
use ReflectionClass;

class ClassResolver extends Resolver
{
    protected static array $resolved = [];

    protected ?string $requiredContract = null;

    protected ?string $requiredParent = null;

    protected bool $mustBeInstantiable = false;

    public static function flushCache(): void
    {
        static::$resolved = [];
    }

    protected function cacheKey(): string
    {
        return implode("\0", [
            $this->getKey(),
            serialize($this->getDefault()),
            $this->requiredContract ?? '',
            $this->requiredParent ?? '',
            $this->mustBeInstantiable ? '1' : '0',
        ]);
    }

    public function implements(string $contract): static
    {
        $this->requiredContract = $contract;

        return $this;
    }

    public function extends(string $parent): static
    {
        $this->requiredParent = $parent;

        return $this;
    }

    public function instantiable(): static
    {
        $this->mustBeInstantiable = true;

        return $this;
    }

    public function resolve(): string
    {
        $key = $this->cacheKey();

        if (array_key_exists($key, static::$resolved)) {
            return static::$resolved[$key];
        }

        $class = $this->getResolvedKey();

        if (blank($class)) {
            throw new MissingConfigException($this->getKey());
        }

        if (! is_string($class) || ! (class_exists($class) || interface_exists($class))) {
            throw new InvalidClassException(
                $this->getKey(),
                (string) $class
            );
        }

        if (
            ($this->requiredContract !== null) &&
            ! is_a($class, $this->requiredContract, true)
        ) {
            throw new InvalidContractException(
                $this->getKey(),
                $class,
                $this->requiredContract
            );
        }

        if (
            $this->requiredParent !== null &&
            ! is_a($class, $this->requiredParent, true)
        ) {
            throw new InvalidParentException(
                $this->getKey(),
                $class,
                $this->requiredParent
            );
        }

        if ($this->mustBeInstantiable) {
            $reflection = new ReflectionClass($class);

            if (! $reflection->isInstantiable()) {
                throw new NotInstantiableException(
                    $this->getKey(),
                    $class
                );
            }
        }

        return static::$resolved[$key] = $class;
    }

    public function make(array $parameters = []): object
    {
        $class = $this->resolve();

        return app()->make($class, $parameters);
    }

    public function new(...$parameters): object
    {
        $class = $this->resolve();

        return new $class(...$parameters);
    }
}
