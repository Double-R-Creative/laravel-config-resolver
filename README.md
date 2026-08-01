# Laravel Config Resolver

Fluent configuration validation and class resolution for Laravel packages.

When a config item references a class name (e.g., which model or service to use), this package validates that the class exists, satisfies type constraints, and is instantiable — failing fast with clear exceptions instead of cryptic runtime errors.

This is especially useful in modular, multi-app Laravel setups where config items change per environment or per application, and you want to guarantee that the resolved class is safe to use before any business logic runs.

## Installation

```bash
composer require drc/laravel-config-resolver
```

## Quick Start

```php
use Vendor\ConfigResolver\Config;

// Resolve a class name from config
$class = Config::class('services.payment.gateway')->resolve();

// Resolve and instantiate via the Laravel container
$gateway = Config::class('services.payment.gateway')->make();

// Resolve and instantiate directly
$gateway = Config::class('services.payment.gateway')->new();
```

## Validation

### Require an interface

```php
$class = Config::class('services.payment.gateway')
    ->implements(PaymentGatewayContract::class)
    ->resolve();
```

Throws `InvalidContractException` if the class does not implement the required interface.

### Require a parent class

```php
$class = Config::class('models.customer')
    ->extends(BaseCustomer::class)
    ->resolve();
```

Throws `InvalidParentException` if the class does not extend the required parent.

### Require instantiable

```php
$class = Config::class('services.payment.gateway')
    ->instantiable()
    ->resolve();
```

Throws `NotInstantiableException` if the class is abstract or an interface.

### Stack constraints

All validation methods are chainable and run in order:

```php
$class = Config::class('models.customer')
    ->implements(CustomerContract::class)
    ->extends(BaseCustomer::class)
    ->instantiable()
    ->resolve();
```

## Array Resolution

Resolve a config value that must be an array. An empty array is valid by default.

```php
use Vendor\ConfigResolver\Config;

// Resolve an array from config
$gateways = Config::array('services.payment.gateways')->resolve();

// With a default when the config key is absent
$gateways = Config::array('services.payment.gateways', ['stripe'])->resolve();
```

### Require keys

Throws `MissingArrayKeyException` if the array does not contain every given key:

```php
$gateway = Config::array('services.payment.gateway')
    ->hasKeys(['url', 'secret'])
    ->resolve();
```

### Require values

Throws `MissingArrayValueException` if the array does not contain every given value (strict comparison):

```php
$gateway = Config::array('services.payment.gateways')
    ->hasValues(['stripe', 'paypal'])
    ->resolve();
```

### Require non-empty

Throws `EmptyArrayException` if the array is empty:

```php
$gateway = Config::array('services.payment.gateways')
    ->nonEmpty()
    ->resolve();
```

### Require a list or associative array

`isList()` uses `array_is_list()`. Throws `InvalidArrayTypeException` otherwise:

```php
// Indexed array (sequential integer keys)
$gateways = Config::array('services.payment.gateways')->isList()->resolve();

// Key/value pairs (any non-list array)
$gateway = Config::array('services.payment.gateway')->isAssociative()->resolve();
```

### Stack constraints

```php
$gateway = Config::array('services.payment.gateway')
    ->hasKeys(['url', 'secret'])
    ->nonEmpty()
    ->isAssociative()
    ->resolve();
```

## Resolution Caching

Each call to `resolve()` is fast (~5–20µs per call), but when the same resolver
is invoked repeatedly within a single request, results are cached to eliminate
redundant validation.

The cache is keyed on the full resolver state — config key, contract, parent,
and instantiable flag — so resolvers with different constraints always return
correct results independently.

```php
// First call runs validation, caches the result
$class = Config::class('services.payment.gateway')
    ->implements(SomeContract::class)
    ->resolve();

// Second call with identical constraints returns from cache
$class = Config::class('services.payment.gateway')
    ->implements(SomeContract::class)
    ->resolve(); // cached
```

If `resolve()` throws, nothing is cached — re-calling with the same state will
re-run all checks.

The cache is a static array that persists for the lifetime of the request.
Under PHP-FPM each request is a fresh process, so the cache starts empty every
time. Under Octane / Swoole / RoadRunner, statics persist across requests but
remain safe because identical resolver state always produces the same result.

To clear the cache explicitly:

```php
ClassResolver::flushCache();
```

This is called automatically in the test suite between tests to prevent stale
state across config changes.

## Exceptions

All exceptions extend `ConfigException` (which extends `RuntimeException`).

| Exception | Condition |
|---|---|
| `MissingConfigException` | Config key is null or blank |
| `InvalidClassException` | Value is not a string, or the class/interface does not exist |
| `InvalidContractException` | Class does not implement the required interface |
| `InvalidParentException` | Class does not extend the required parent |
| `NotInstantiableException` | Class is abstract or an interface |
| `InvalidArrayException` | Value is not an array |
| `EmptyArrayException` | Value is an empty array but must not be |
| `MissingArrayKeyException` | Array does not contain a required key |
| `MissingArrayValueException` | Array does not contain a required value |
| `InvalidArrayTypeException` | Array is not the required list or associative type |

## Instantiation

After resolution, you can create an instance:

```php
// Via the Laravel container (uses app()->make())
$instance = Config::class('services.payment.gateway')->make($parameters);

// Direct instantiation with new
$instance = Config::class('services.payment.gateway')->new($parameter1, $parameter2);
```

- `make()` accepts a single array of parameters forwarded to `app()->make()`.
- `new()` accepts variadic parameters forwarded to the constructor.

## Real-World Example

In a modular app, you might share a customer model package across multiple applications. Each app can override the base customer class via `.env`:

```env
CUSTOMER_MODEL_CLASS=App\Models\ApiCustomer
```

The config resolver ensures the override is valid before any model is resolved:

```php
$customerClass = Config::class('app.customer_model')
    ->implements(CustomerContract::class)
    ->extends(BaseCustomer::class)
    ->instantiable()
    ->resolve();
```

If the specified class doesn't exist, doesn't implement the contract, or is abstract, a clear exception is thrown immediately rather than surfacing as a cryptic error later.

## Testing

```bash
composer test
```
