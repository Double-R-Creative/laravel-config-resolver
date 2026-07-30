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

## Exceptions

All exceptions extend `ConfigException` (which extends `RuntimeException`).

| Exception | Condition |
|---|---|
| `MissingConfigException` | Config key is null or blank |
| `InvalidClassException` | Value is not a string, or the class/interface does not exist |
| `InvalidContractException` | Class does not implement the required interface |
| `InvalidParentException` | Class does not extend the required parent |
| `NotInstantiableException` | Class is abstract or an interface |

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
