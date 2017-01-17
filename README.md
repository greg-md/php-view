# Greg PHP View

[![StyleCI](https://styleci.io/repos/71001054/shield?style=flat)](https://styleci.io/repos/71001054)
[![Build Status](https://travis-ci.org/greg-md/php-view.svg)](https://travis-ci.org/greg-md/php-view)
[![Total Downloads](https://poser.pugx.org/greg-md/php-view/d/total.svg)](https://packagist.org/packages/greg-md/php-view)
[![Latest Stable Version](https://poser.pugx.org/greg-md/php-view/v/stable.svg)](https://packagist.org/packages/greg-md/php-view)
[![Latest Unstable Version](https://poser.pugx.org/greg-md/php-view/v/unstable.svg)](https://packagist.org/packages/greg-md/php-view)
[![License](https://poser.pugx.org/greg-md/php-view/license.svg)](https://packagist.org/packages/greg-md/php-view)

A better Viewer for web artisans.

# Requirements

* PHP Version `^5.6 || ^7.0`

# Compilers

- PHP
- Blade

# How It Works

**First of all**, you have to create a new [Viewer](docs/Viewer.md):

```php
$viewsDirectory = __DIR__ . '/views';

$viewer = new \Greg\View\Viewer($viewsDirectory);
```

**Optionally** you can add a view compiler. For example a Blade Compiler specially created for this Viewer:

```php
$viewer->addExtension('.blade.php', function () {
    $compiledViewsDirectory = __DIR__ . '/compiled';

    return new \Greg\View\ViewBladeCompiler($compiledViewsDirectory);
});
```

_By default it will use `php` language as view compiler._

_Note:_ If you want to use your own compiler, it has to be an instance of `\Greg\View\CompilerStrategy`.

**Now** you can render views where you want in your application.

Create a template file in the views directory. For example `welcome.blade.php`:

```blade
<html>
    <body>
        <h1>Hello, {{ $name }}</h1>
    </body>
</html>
```

Use `welcome` template in your application:

```php
$content = $viewer->render('welcome', [
    'name' => 'Greg',
]);

echo $content;
```

# Documentation

* [Viewer](docs/Viewer.md) - The main class which initializes a new view manager;
* [Renderer](docs/Renderer.md) - The renderer of a template file from the [Viewer](docs/Viewer.md);
* [Blade Compiler](docs/BladeCompiler.md) - An independent template compiler;
* [View Blade Compiler](docs/ViewBladeCompiler.md) - An extended [Blade Compiler](docs/BladeCompiler.md), specially for the [Viewer](Viewer.md).
* [Compiler Strategy](docs/CompilerStrategy.md) - A strategy for custom compilers.
