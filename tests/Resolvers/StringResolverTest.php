<?php

namespace Tests\Resolvers;

use Tests\Fixtures\ConcreteClass;
use Tests\TestCase;
use DRC\ConfigResolver\Config;
use DRC\ConfigResolver\Exceptions\EmptyStringException;
use DRC\ConfigResolver\Exceptions\InvalidArrayException;
use DRC\ConfigResolver\Exceptions\InvalidPatternException;
use DRC\ConfigResolver\Exceptions\InvalidStringException;
use DRC\ConfigResolver\Exceptions\InvalidStringFormatException;
use DRC\ConfigResolver\Exceptions\MissingConfigException;
use DRC\ConfigResolver\Resolvers\StringResolver;

class StringResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        StringResolver::flushCache();

        config()->set('resolver.string', 'stripe');
        config()->set('resolver.blank', '   ');
        config()->set('resolver.empty', '');
        config()->set('resolver.zero', '0');
    }

    public function test_creates_a_resolver_via_config_facade(): void
    {
        $resolver = Config::string('resolver.string');

        $this->assertInstanceOf(StringResolver::class, $resolver);
    }

    public function test_resolve_returns_the_string(): void
    {
        $result = Config::string('resolver.string')->resolve();

        $this->assertSame('stripe', $result);
    }

    public function test_resolve_throws_invalid_string_exception_when_value_is_not_a_string(): void
    {
        config()->set('resolver.invalid', 123);

        $this->expectException(InvalidStringException::class);
        $this->expectExceptionMessage('resolver.invalid');

        Config::string('resolver.invalid')->resolve();
    }

    public function test_resolve_throws_missing_config_exception_when_key_is_missing(): void
    {
        $this->expectException(MissingConfigException::class);
        $this->expectExceptionMessage('resolver.missing');

        Config::string('resolver.missing')->resolve();
    }

    public function test_resolve_throws_missing_config_exception_when_value_is_null(): void
    {
        config()->set('resolver.null', null);

        $this->expectException(MissingConfigException::class);
        $this->expectExceptionMessage('resolver.null');

        Config::string('resolver.null')->resolve();
    }

    public function test_default_is_used_when_config_key_is_missing(): void
    {
        $result = Config::string('resolver.missing', 'fallback')->resolve();

        $this->assertSame('fallback', $result);
    }

    public function test_default_is_validated_against_constraints(): void
    {
        $this->expectException(InvalidStringFormatException::class);
        $this->expectExceptionMessage('start with [pay-]');

        Config::string('resolver.missing', 'fallback')
            ->startsWith('pay-')
            ->resolve();
    }

    public function test_non_empty_passes_for_non_empty_string(): void
    {
        $result = Config::string('resolver.string')->nonEmpty()->resolve();

        $this->assertSame('stripe', $result);
    }

    public function test_non_empty_throws_for_empty_string(): void
    {
        $this->expectException(EmptyStringException::class);
        $this->expectExceptionMessage('resolver.empty');

        Config::string('resolver.empty')->nonEmpty()->resolve();
    }

    public function test_non_empty_throws_for_whitespace_only_string(): void
    {
        $this->expectException(EmptyStringException::class);
        $this->expectExceptionMessage('resolver.blank');

        Config::string('resolver.blank')->nonEmpty()->resolve();
    }

    public function test_non_empty_allows_zero_string(): void
    {
        $result = Config::string('resolver.zero')->nonEmpty()->resolve();

        $this->assertSame('0', $result);
    }

    public function test_matches_passes_when_pattern_matches(): void
    {
        $result = Config::string('resolver.string')
            ->matches('/^[a-z]+$/')
            ->resolve();

        $this->assertSame('stripe', $result);
    }

    public function test_matches_throws_when_pattern_does_not_match(): void
    {
        $this->expectException(InvalidStringFormatException::class);
        $this->expectExceptionMessage('match pattern');

        Config::string('resolver.string')
            ->matches('/^[0-9]+$/')
            ->resolve();
    }

    public function test_matches_throws_invalid_pattern_exception_for_bad_regex(): void
    {
        $this->expectException(InvalidPatternException::class);
        $this->expectExceptionMessage('resolver.string');

        Config::string('resolver.string')
            ->matches('not a regex')
            ->resolve();
    }

    public function test_starts_with_passes_when_prefix_matches(): void
    {
        $result = Config::string('resolver.string')
            ->startsWith('str')
            ->resolve();

        $this->assertSame('stripe', $result);
    }

    public function test_starts_with_throws_when_prefix_does_not_match(): void
    {
        $this->expectException(InvalidStringFormatException::class);
        $this->expectExceptionMessage('start with [pay-]');

        Config::string('resolver.string')
            ->startsWith('pay-')
            ->resolve();
    }

    public function test_in_passes_when_value_is_allowed(): void
    {
        $result = Config::string('resolver.string')
            ->in(['stripe', 'paypal'])
            ->resolve();

        $this->assertSame('stripe', $result);
    }

    public function test_in_throws_when_value_is_not_allowed(): void
    {
        $this->expectException(InvalidStringFormatException::class);
        $this->expectExceptionMessage('be one of [paypal, square]');

        Config::string('resolver.string')
            ->in(['paypal', 'square'])
            ->resolve();
    }

    public function test_chained_constraints_pass_when_all_satisfied(): void
    {
        $result = Config::string('resolver.string')
            ->nonEmpty()
            ->matches('/^[a-z]+$/')
            ->startsWith('str')
            ->in(['stripe', 'paypal'])
            ->resolve();

        $this->assertSame('stripe', $result);
    }

    public function test_different_constraints_for_same_key_do_not_share_cache(): void
    {
        $result = Config::string('resolver.string')->nonEmpty()->resolve();

        $formatThrows = false;

        try {
            Config::string('resolver.string')->matches('/^[0-9]+$/')->resolve();
        } catch (InvalidStringFormatException) {
            $formatThrows = true;
        }

        $this->assertSame('stripe', $result);
        $this->assertTrue($formatThrows);
    }

    public function test_string_resolver_cache_does_not_leak_to_array_resolver(): void
    {
        config()->set('resolver.mixed', ConcreteClass::class);

        Config::string('resolver.mixed')->resolve();

        $this->expectException(InvalidArrayException::class);
        $this->expectExceptionMessage('resolver.mixed');

        Config::array('resolver.mixed')->resolve();
    }
}
