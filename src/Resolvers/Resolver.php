<?php

namespace DRC\ConfigResolver\Resolvers;

abstract class Resolver
{
    public function __construct(
        public string $configKey,
        public mixed $default = null,
    ) {}

    abstract public function resolve();

    public function getKey(): ?string
    {
        return $this->configKey;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function getResolvedKey(): mixed
    {
        return config($this->getKey(), $this->getDefault());
    }
}
