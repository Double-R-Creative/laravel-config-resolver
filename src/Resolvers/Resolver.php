<?php

namespace DRC\ConfigResolver\Resolvers;

abstract class Resolver
{
    public function __construct(
        public string $configKey,
        public mixed $fallback = null,
    ) {
    }

    abstract public function resolve();
}
