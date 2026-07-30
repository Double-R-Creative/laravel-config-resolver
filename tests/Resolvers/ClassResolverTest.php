<?php

namespace Tests\Resolvers;

use Tests\Fixtures\AbstractClass;
use Tests\Fixtures\ConcreteClass;
use Tests\Fixtures\ContractImplementation;
use Tests\Fixtures\ExtendingAndImplementing;
use Tests\Fixtures\ExtendingConcrete;
use Tests\Fixtures\SomeContract;
use Tests\TestCase;
use DRC\ConfigResolver\Config;
use DRC\ConfigResolver\Exceptions\InvalidClassException;
use DRC\ConfigResolver\Exceptions\InvalidContractException;
use DRC\ConfigResolver\Exceptions\InvalidParentException;
use DRC\ConfigResolver\Exceptions\MissingConfigException;
use DRC\ConfigResolver\Exceptions\NotInstantiableException;
use DRC\ConfigResolver\Resolvers\ClassResolver;

class ClassResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        ClassResolver::flushCache();

        config()->set('resolver.class', ConcreteClass::class);
        config()->set('resolver.implementation', ContractImplementation::class);
        config()->set('resolver.extension', ExtendingConcrete::class);
        config()->set('resolver.abstract', AbstractClass::class);
        config()->set('resolver.interface', SomeContract::class);
    }

    public function test_creates_a_resolver_via_config_facade(): void
    {
        $resolver = Config::class('resolver.class');

        $this->assertInstanceOf(ClassResolver::class, $resolver);
    }

    public function test_resolve_throws_missing_config_exception_when_null(): void
    {
        config()->set('resolver.custom', null);

        $this->expectException(MissingConfigException::class);
        $this->expectExceptionMessage('resolver.custom');

        Config::class('resolver.custom')->resolve();
    }

    public function test_resolve_throws_missing_config_exception_when_blank(): void
    {
        config()->set('resolver.custom', '');

        $this->expectException(MissingConfigException::class);
        $this->expectExceptionMessage('resolver.custom');

        Config::class('resolver.custom')->resolve();
    }

    public function test_resolve_throws_invalid_class_exception_when_not_a_string(): void
    {
        config()->set('resolver.custom', 123);

        $this->expectException(InvalidClassException::class);
        $this->expectExceptionMessage('123');

        Config::class('resolver.custom')->resolve();
    }

    public function test_resolve_throws_invalid_class_exception_when_class_not_found(): void
    {
        config()->set('resolver.custom', 'NonExistent\\Class\\DoesNotExist');

        $this->expectException(InvalidClassException::class);
        $this->expectExceptionMessage('resolver.custom');

        Config::class('resolver.custom')->resolve();
    }

    public function test_resolve_returns_the_fqcn_string(): void
    {
        $result = Config::class('resolver.class')->resolve();

        $this->assertSame(ConcreteClass::class, $result);
    }

    public function test_implements_passes_when_class_satisfies_contract(): void
    {
        $result = Config::class('resolver.implementation')
            ->implements(SomeContract::class)
            ->resolve();

        $this->assertSame(ContractImplementation::class, $result);
    }

    public function test_implements_throws_when_class_does_not_satisfy_contract(): void
    {
        $this->expectException(InvalidContractException::class);
        $this->expectExceptionMessage('SomeContract');

        Config::class('resolver.class')
            ->implements(SomeContract::class)
            ->resolve();
    }

    public function test_extends_passes_when_class_extends_parent(): void
    {
        $result = Config::class('resolver.extension')
            ->extends(AbstractClass::class)
            ->resolve();

        $this->assertSame(ExtendingConcrete::class, $result);
    }

    public function test_extends_throws_when_class_does_not_extend_parent(): void
    {
        $this->expectException(InvalidParentException::class);
        $this->expectExceptionMessage('AbstractClass');

        Config::class('resolver.class')
            ->extends(AbstractClass::class)
            ->resolve();
    }

    public function test_combined_implements_and_extends_passes_when_both_satisfied(): void
    {
        config()->set('resolver.both', ExtendingAndImplementing::class);

        $result = Config::class('resolver.both')
            ->implements(SomeContract::class)
            ->extends(AbstractClass::class)
            ->resolve();

        $this->assertSame(ExtendingAndImplementing::class, $result);
    }

    public function test_combined_throws_invalid_parent_when_only_contract_met(): void
    {
        $this->expectException(InvalidParentException::class);

        Config::class('resolver.implementation')
            ->implements(SomeContract::class)
            ->extends(AbstractClass::class)
            ->resolve();
    }

    public function test_combined_throws_invalid_contract_when_only_parent_met(): void
    {
        $this->expectException(InvalidContractException::class);

        Config::class('resolver.extension')
            ->implements(SomeContract::class)
            ->extends(AbstractClass::class)
            ->resolve();
    }

    public function test_instantiable_passes_for_concrete_class(): void
    {
        $result = Config::class('resolver.class')
            ->instantiable()
            ->resolve();

        $this->assertSame(ConcreteClass::class, $result);
    }

    public function test_instantiable_throws_for_abstract_class(): void
    {
        $this->expectException(NotInstantiableException::class);
        $this->expectExceptionMessage('AbstractClass');

        Config::class('resolver.abstract')
            ->instantiable()
            ->resolve();
    }

    public function test_instantiable_throws_for_interface(): void
    {
        $this->expectException(NotInstantiableException::class);
        $this->expectExceptionMessage('SomeContract');

        Config::class('resolver.interface')
            ->instantiable()
            ->resolve();
    }

    public function test_make_instantiates_via_container(): void
    {
        $instance = Config::class('resolver.class')
            ->instantiable()
            ->make();

        $this->assertInstanceOf(ConcreteClass::class, $instance);
    }

    public function test_make_passes_parameters(): void
    {
        $instance = Config::class('resolver.class')
            ->instantiable()
            ->make(['name' => 'test-name']);

        $this->assertSame('test-name', $instance->name);
    }

    public function test_new_instantiates_directly(): void
    {
        $instance = Config::class('resolver.class')
            ->instantiable()
            ->new();

        $this->assertInstanceOf(ConcreteClass::class, $instance);
    }

    public function test_new_passes_parameters(): void
    {
        $instance = Config::class('resolver.class')
            ->instantiable()
            ->new('test-name');

        $this->assertSame('test-name', $instance->name);
    }
}
