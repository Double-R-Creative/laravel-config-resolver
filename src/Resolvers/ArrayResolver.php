<?php

namespace DRC\ConfigResolver\Resolvers;

use DRC\ConfigResolver\Exceptions\EmptyArrayException;
use DRC\ConfigResolver\Exceptions\InvalidArrayException;
use DRC\ConfigResolver\Exceptions\InvalidArrayTypeException;
use DRC\ConfigResolver\Exceptions\MissingArrayKeyException;
use DRC\ConfigResolver\Exceptions\MissingArrayValueException;
use DRC\ConfigResolver\Exceptions\MissingConfigException;

class ArrayResolver extends Resolver
{
    protected static array $resolved = [];

    protected bool $mustNotBeEmpty = false;

    protected array $requiredKeys = [];

    protected array $requiredValues = [];

    protected ?string $requiredType = null;

    public function nonEmpty(): static
    {
        $this->mustNotBeEmpty = true;

        return $this;
    }

    public function hasKeys(array $keys): static
    {
        $this->requiredKeys = $keys;

        return $this;
    }

    public function hasValues(array $values): static
    {
        $this->requiredValues = $values;

        return $this;
    }

    public function isList(): static
    {
        $this->requiredType = 'list';

        return $this;
    }

    public function isAssociative(): static
    {
        $this->requiredType = 'associative';

        return $this;
    }

    protected function cacheKey(): string
    {
        return implode("\0", [
            $this->getKey(),
            serialize($this->getDefault()),
            $this->mustNotBeEmpty ? '1' : '0',
            serialize($this->requiredKeys),
            serialize($this->requiredValues),
            $this->requiredType ?? '',
        ]);
    }

    public function resolve(): array
    {
        return $this->cached(function (): array {
            $value = $this->getResolvedKey();

            if ($value === null) {
                throw new MissingConfigException($this->getKey());
            }

            if (! is_array($value)) {
                throw new InvalidArrayException($this->getKey(), $value);
            }

            if ($this->mustNotBeEmpty && $value === []) {
                throw new EmptyArrayException($this->getKey());
            }

            foreach ($this->requiredKeys as $key) {
                if (! array_key_exists($key, $value)) {
                    throw new MissingArrayKeyException($this->getKey(), $key);
                }
            }

            foreach ($this->requiredValues as $required) {
                if (! in_array($required, $value, true)) {
                    throw new MissingArrayValueException($this->getKey(), $required);
                }
            }

            if ($this->requiredType === 'list' && ! array_is_list($value)) {
                throw new InvalidArrayTypeException($this->getKey(), 'list');
            }

            if ($this->requiredType === 'associative' && array_is_list($value)) {
                throw new InvalidArrayTypeException($this->getKey(), 'associative');
            }

            return $value;
        });
    }
}
