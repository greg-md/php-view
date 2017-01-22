# Loader Documentation

`\Greg\View\Loader` is a special loader for [Renderer](Renderer.md) which will give access only to its public properties and methods.

_Example:_

```php
$viewer = new \Greg\View\Viewer(__DIR__ . '/views');

$renderer = new \Greg\View\Renderer($viewer, __DIR__ . '/welcome.php', [
    'name' => 'Greg',
]);

$content = (new \Greg\View\Loader($renderer))->_l_o_a_d_();
```

# Table of contents:

* [Magic methods](#magic-methods)
* [Methods](#methods)

# Magic methods:

* [__construct](#__construct);

## __construct

This is the constructor of the `Loader`;

```php
__construct(Renderer $renderer);
```

`$renderer` - An instance of [Renderer](Renderer.md);

# Methods:

* [\_l_o_a_d\_](#_l_o_a_d_) - Load [Renderer](Renderer.md).

## \_l_o_a_d\_

Load [Renderer](Renderer.md).

```php
_l_o_a_d_(): string
```

> **Why it's name is so ugly?**  
> It's simple. To avoid collision and avoid using it as a directive in templates.
