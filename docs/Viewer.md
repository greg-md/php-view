# Viewer Documentation

`\Greg\View\Viewer` is the main class which initializes a new view manager.

Implements: [`\Greg\View\ViewerContract`](ViewerContract.md).

_Example:_

```php
$viewer = new \Greg\View\Viewer(__DIR__ . '/views');

echo $viewer->render('welcome', [
    'name' => 'Greg',
]);
```

# Table of contents:

* [Magic methods](#magic-methods)
* [Methods](#methods)

# Magic methods:

* [__construct](#__construct)

## __construct 

Initialize the viewer.

```php
__construct(string|array $path, array $params = [])
```

`$path` - Templates directory;  
`$params` - This parameters will be assigned in all templates.

_Example:_

```php
$viewer = new \Greg\View\Viewer(__DIR__ . '/views', [
    'repository' => 'greg-md/php-view',
]);
```

# Methods:

Includes [Viewer Contract](ViewerContract.md) methods.

* [getCompiledFile](#getcompiledfile) - Get compiled file by a template file;
* [getCompiledFileFromString](#getcompiledfilefromstring) - Get compiled file by a template string;

## getCompiledFile

Get compiled file by a template file.

```php
getCompiledFile(string $name): string
```

`$name` - Template name.

## getCompiledFileFromString

Get compiled file by a template string.

```php
getCompiledFile(string $id, string $string): string
```

`$id` - Template unique id. It should has the compiler extension;  
`$name` - Template string.
