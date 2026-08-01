<?php

namespace DRC\ConfigResolver\Resolvers;

abstract class Resolver
{
    protected static array $resolved = [];

    public function __construct(
        public string $configKey,
        public mixed $default = null,
    ) {}

    public static function flushCache(): void
    {
        static::$resolved = [];
    }

    abstract public function resolve();

    abstract protected function cacheKey(): string;

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

    /**
     * @template T
     *
     * @param  callable(): T  $resolver
     * @return T
     */
    protected function cached(callable $resolver): mixed
    {
        $key = $this->cacheKey();

        if (array_key_exists($key, static::$resolved)) {
            return static::$resolved[$key];
        }

        return static::$resolved[$key] = $resolver();
    }
}
