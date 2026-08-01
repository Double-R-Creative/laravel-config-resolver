<?php

namespace DRC\ConfigResolver\Resolvers;

use DRC\ConfigResolver\Exceptions\EmptyStringException;
use DRC\ConfigResolver\Exceptions\InvalidPatternException;
use DRC\ConfigResolver\Exceptions\InvalidStringException;
use DRC\ConfigResolver\Exceptions\InvalidStringFormatException;
use DRC\ConfigResolver\Exceptions\MissingConfigException;

class StringResolver extends Resolver
{
    protected static array $resolved = [];

    protected bool $mustNotBeEmpty = false;

    protected ?string $pattern = null;

    protected ?string $requiredPrefix = null;

    protected ?array $allowedValues = null;

    public function nonEmpty(): static
    {
        $this->mustNotBeEmpty = true;

        return $this;
    }

    public function matches(string $pattern): static
    {
        $this->pattern = $pattern;

        return $this;
    }

    public function startsWith(string $prefix): static
    {
        $this->requiredPrefix = $prefix;

        return $this;
    }

    public function in(array $values): static
    {
        $this->allowedValues = $values;

        return $this;
    }

    protected function cacheKey(): string
    {
        return implode("\0", [
            $this->getKey(),
            serialize($this->getDefault()),
            $this->mustNotBeEmpty ? '1' : '0',
            $this->pattern ?? '',
            $this->requiredPrefix ?? '',
            serialize($this->allowedValues ?? []),
        ]);
    }

    public function resolve(): string
    {
        return $this->cached(function (): string {
            $value = $this->getResolvedKey();

            if ($value === null) {
                throw new MissingConfigException($this->getKey());
            }

            if (! is_string($value)) {
                throw new InvalidStringException($this->getKey(), $value);
            }

            if ($this->mustNotBeEmpty && trim($value) === '') {
                throw new EmptyStringException($this->getKey());
            }

            if ($this->pattern !== null) {
                $result = @preg_match($this->pattern, $value);

                if ($result === false) {
                    throw new InvalidPatternException($this->getKey(), $this->pattern);
                }

                if ($result !== 1) {
                    throw new InvalidStringFormatException(
                        $this->getKey(),
                        "match pattern [{$this->pattern}]"
                    );
                }
            }

            if ($this->requiredPrefix !== null && ! str_starts_with($value, $this->requiredPrefix)) {
                throw new InvalidStringFormatException(
                    $this->getKey(),
                    "start with [{$this->requiredPrefix}]"
                );
            }

            if ($this->allowedValues !== null && ! in_array($value, $this->allowedValues, true)) {
                throw new InvalidStringFormatException(
                    $this->getKey(),
                    'be one of ['.implode(', ', $this->allowedValues).']'
                );
            }

            return $value;
        });
    }
}
