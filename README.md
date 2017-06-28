# Greg PHP View

[![StyleCI](https://styleci.io/repos/71001054/shield?style=flat)](https://styleci.io/repos/71001054)
[![Build Status](https://travis-ci.org/greg-md/php-view.svg)](https://travis-ci.org/greg-md/php-view)
[![Total Downloads](https://poser.pugx.org/greg-md/php-view/d/total.svg)](https://packagist.org/packages/greg-md/php-view)
[![Latest Stable Version](https://poser.pugx.org/greg-md/php-view/v/stable.svg)](https://packagist.org/packages/greg-md/php-view)
[![Latest Unstable Version](https://poser.pugx.org/greg-md/php-view/v/unstable.svg)](https://packagist.org/packages/greg-md/php-view)
[![License](https://poser.pugx.org/greg-md/php-view/license.svg)](https://packagist.org/packages/greg-md/php-view)

A powerful Viewer for PHP.

# Table of Contents:

* [Requirements](#requirements)
* [Compilers](#compilers)
* [How It Works](#how-it-works)
* [Documentation](#documentation)
* [License](#license)
* [Huuuge Quote](#huuuge-quote)

# Requirements

* PHP Version `^5.6 || ^7.0`

# Compilers

- PHP
- Blade

# How It Works

**First of all**, you have to initialize a [Viewer](docs/Viewer.md):

```php
$viewsDirectory = __DIR__ . '/views';

$viewer = new \Greg\View\Viewer($viewsDirectory);
```

**Optionally**, you can add a view compiler. For example a [Blade Compiler](docs/ViewBladeCompiler.md) specially created for the [Viewer Contract](docs/ViewerContract.md):

```php
// Turn it to a callable, to load only when using blade templates.
$viewer->addExtension('.blade.php', function () {
    $compiledViewsDirectory = __DIR__ . '/compiled';

    return new \Greg\View\ViewBladeCompiler($compiledViewsDirectory);
});
```

_By default it will use [Renderer](docs/Renderer.md) as an instance of a template._

_Note:_ If you want to use your own compiler, it has to be an instance of [Compiler Strategy](docs/CompilerStrategy.md).

**Now**, you can render views where you want in your application.

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

* [Viewer](docs/Viewer.md) - The view manager;
* [Renderer](docs/Renderer.md) - Instance of a template. Could be accessed via `$this` variable in the template.
* [Loader](docs/Loader.md) - A special loader for [Renderer](docs/Renderer.md) which will give access only to its public properties and methods;
* [Blade Compiler](docs/BladeCompiler.md) - An independent template compiler;
* [View Blade Compiler](docs/ViewBladeCompiler.md) - An extended [Blade Compiler](docs/BladeCompiler.md), specially for the [Viewer Contract](ViewerContract.md).
* [Viewer Contract](docs/ViewerContract.md) - A contract of a viewer;
* [Compiler Strategy](docs/CompilerStrategy.md) - A strategy for custom compilers;
* [View Compiler Strategy](docs/ViewCompilerStrategy.md) - A strategy for custom [Viewer Contract](docs/ViewerContract.md) compilers;

# License

MIT © [Grigorii Duca](http://greg.md)

# Huuuge Quote

![I fear not the man who has practiced 10,000 programming languages once, but I fear the man who has practiced one programming language 10,000 times. #horrorsquad](http://greg.md/huuuge-quote-fb.jpg)
