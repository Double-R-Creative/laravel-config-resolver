<?php

namespace Tests\Resolvers;

use Tests\Fixtures\ConcreteClass;
use Tests\TestCase;
use DRC\ConfigResolver\Config;
use DRC\ConfigResolver\Exceptions\EmptyArrayException;
use DRC\ConfigResolver\Exceptions\InvalidArrayException;
use DRC\ConfigResolver\Exceptions\InvalidArrayTypeException;
use DRC\ConfigResolver\Exceptions\MissingArrayKeyException;
use DRC\ConfigResolver\Exceptions\MissingArrayValueException;
use DRC\ConfigResolver\Exceptions\MissingConfigException;
use DRC\ConfigResolver\Resolvers\ArrayResolver;

class ArrayResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        ArrayResolver::flushCache();

        config()->set('resolver.list', ['stripe', 'paypal']);
        config()->set('resolver.assoc', ['url' => 'https://example.com', 'secret' => 's3cr3t']);
        config()->set('resolver.empty', []);
    }

    public function test_creates_a_resolver_via_config_facade(): void
    {
        $resolver = Config::array('resolver.list');

        $this->assertInstanceOf(ArrayResolver::class, $resolver);
    }

    public function test_resolve_returns_the_array(): void
    {
        $result = Config::array('resolver.list')->resolve();

        $this->assertSame(['stripe', 'paypal'], $result);
    }

    public function test_resolve_allows_empty_array(): void
    {
        $result = Config::array('resolver.empty')->resolve();

        $this->assertSame([], $result);
    }

    public function test_resolve_throws_invalid_array_exception_when_value_is_not_an_array(): void
    {
        config()->set('resolver.invalid', 'not-an-array');

        $this->expectException(InvalidArrayException::class);
        $this->expectExceptionMessage('resolver.invalid');

        Config::array('resolver.invalid')->resolve();
    }

    public function test_resolve_throws_missing_config_exception_when_key_is_missing(): void
    {
        $this->expectException(MissingConfigException::class);
        $this->expectExceptionMessage('resolver.missing');

        Config::array('resolver.missing')->resolve();
    }

    public function test_resolve_throws_missing_config_exception_when_value_is_null(): void
    {
        config()->set('resolver.null', null);

        $this->expectException(MissingConfigException::class);
        $this->expectExceptionMessage('resolver.null');

        Config::array('resolver.null')->resolve();
    }

    public function test_default_is_used_when_config_key_is_missing(): void
    {
        $result = Config::array('resolver.missing', ['fallback'])->resolve();

        $this->assertSame(['fallback'], $result);
    }

    public function test_default_is_validated_against_constraints(): void
    {
        $this->expectException(MissingArrayKeyException::class);
        $this->expectExceptionMessage('url');

        Config::array('resolver.missing', ['only' => 'value'])
            ->hasKeys(['url'])
            ->resolve();
    }

    public function test_non_empty_passes_for_non_empty_array(): void
    {
        $result = Config::array('resolver.list')->nonEmpty()->resolve();

        $this->assertSame(['stripe', 'paypal'], $result);
    }

    public function test_non_empty_throws_for_empty_array(): void
    {
        $this->expectException(EmptyArrayException::class);
        $this->expectExceptionMessage('resolver.empty');

        Config::array('resolver.empty')->nonEmpty()->resolve();
    }

    public function test_has_keys_passes_when_all_keys_present(): void
    {
        $result = Config::array('resolver.assoc')
            ->hasKeys(['url', 'secret'])
            ->resolve();

        $this->assertSame(['url' => 'https://example.com', 'secret' => 's3cr3t'], $result);
    }

    public function test_has_keys_throws_when_key_missing(): void
    {
        $this->expectException(MissingArrayKeyException::class);
        $this->expectExceptionMessage('missing_key');

        Config::array('resolver.assoc')
            ->hasKeys(['url', 'missing_key'])
            ->resolve();
    }

    public function test_has_values_passes_when_all_values_present(): void
    {
        $result = Config::array('resolver.list')
            ->hasValues(['stripe', 'paypal'])
            ->resolve();

        $this->assertSame(['stripe', 'paypal'], $result);
    }

    public function test_has_values_throws_when_value_missing(): void
    {
        $this->expectException(MissingArrayValueException::class);
        $this->expectExceptionMessage('square');

        Config::array('resolver.list')
            ->hasValues(['stripe', 'square'])
            ->resolve();
    }

    public function test_is_list_passes_for_indexed_array(): void
    {
        $result = Config::array('resolver.list')->isList()->resolve();

        $this->assertSame(['stripe', 'paypal'], $result);
    }

    public function test_is_list_passes_for_empty_array(): void
    {
        $result = Config::array('resolver.empty')->isList()->resolve();

        $this->assertSame([], $result);
    }

    public function test_is_list_throws_for_associative_array(): void
    {
        $this->expectException(InvalidArrayTypeException::class);
        $this->expectExceptionMessage('list');

        Config::array('resolver.assoc')->isList()->resolve();
    }

    public function test_is_associative_passes_for_key_value_array(): void
    {
        $result = Config::array('resolver.assoc')->isAssociative()->resolve();

        $this->assertSame(['url' => 'https://example.com', 'secret' => 's3cr3t'], $result);
    }

    public function test_is_associative_throws_for_indexed_array(): void
    {
        $this->expectException(InvalidArrayTypeException::class);
        $this->expectExceptionMessage('associative');

        Config::array('resolver.list')->isAssociative()->resolve();
    }

    public function test_is_associative_throws_for_empty_array(): void
    {
        $this->expectException(InvalidArrayTypeException::class);
        $this->expectExceptionMessage('associative');

        Config::array('resolver.empty')->isAssociative()->resolve();
    }

    public function test_different_constraints_for_same_key_do_not_share_cache(): void
    {
        $list = Config::array('resolver.list')->isList()->resolve();

        $associativeThrows = false;

        try {
            Config::array('resolver.list')->isAssociative()->resolve();
        } catch (InvalidArrayTypeException) {
            $associativeThrows = true;
        }

        $this->assertSame(['stripe', 'paypal'], $list);
        $this->assertTrue($associativeThrows);
    }

    public function test_class_and_array_resolvers_do_not_share_cache(): void
    {
        config()->set('resolver.mixed', ConcreteClass::class);

        $class = Config::class('resolver.mixed')->resolve();

        $invalidArrayThrows = false;

        try {
            Config::array('resolver.mixed')->resolve();
        } catch (InvalidArrayException) {
            $invalidArrayThrows = true;
        }

        $this->assertSame(ConcreteClass::class, $class);
        $this->assertTrue($invalidArrayThrows);
    }
}
