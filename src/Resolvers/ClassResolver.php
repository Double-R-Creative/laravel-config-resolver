<?php

namespace Vendor\ConfigResolver\Resolvers;

use ReflectionClass;
use Vendor\ConfigResolver\Exceptions\InvalidClassException;
use Vendor\ConfigResolver\Exceptions\InvalidContractException;
use Vendor\ConfigResolver\Exceptions\InvalidParentException;
use Vendor\ConfigResolver\Exceptions\MissingConfigException;
use Vendor\ConfigResolver\Exceptions\NotInstantiableException;

class ClassResolver
{
    protected ?string $requiredContract = null;

    protected ?string $requiredParent = null;

    protected bool $mustBeInstantiable = false;

    public function __construct(
        protected string $configKey
    ) {
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
        $class = config($this->configKey);

        if (blank($class)) {
            throw new MissingConfigException($this->configKey);
        }

        if (! is_string($class) || ! (class_exists($class) || interface_exists($class))) {
            throw new InvalidClassException(
                $this->configKey,
                (string) $class
            );
        }

        if (
            ($this->requiredContract !== null) &&
            ! is_a($class, $this->requiredContract, true)
        ) {
            throw new InvalidContractException(
                $this->configKey,
                $class,
                $this->requiredContract
            );
        }

        if (
            $this->requiredParent !== null &&
            ! is_a($class, $this->requiredParent, true)
        ) {
            throw new InvalidParentException(
                $this->configKey,
                $class,
                $this->requiredParent
            );
        }

        if ($this->mustBeInstantiable) {
            $reflection = new ReflectionClass($class);

            if (! $reflection->isInstantiable()) {
                throw new NotInstantiableException(
                    $this->configKey,
                    $class
                );
            }
        }

        return $class;
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
