# Viewer Contract Documentation

`\Greg\View\ViewerContract` is a contract for custom viewers.

Extends: [`\ArrayAccess`](http://php.net/manual/en/class.arrayaccess.php).

# Table of contents:

* [Methods](#methods)

# Methods:

Includes [`\ArrayAccess`](http://php.net/manual/en/class.arrayaccess.php) methods.

* [render](#render) - Render a template file;
* [renderIfExists](#renderifexists) - Render a template file if exists;
* [renderString](#renderstring) - Render a template string;
* [renderStringIfExists](#renderstringifexists) - Render a template string if exists;
* [assign](#assign) - Assign parameters to all templates;
* [assigned](#assigned) - Get assigned parameters;
* [hasAssigned](#hasassigned) - Determine if assigned parameters exists;
* [deleteAssigned](#deleteassigned) - Delete assigned parameters;
* [setPaths](#setpaths) - Replace templates directories;
* [addPaths](#addpaths) - Add templates directories;
* [addPath](#addpath) - Add a template directory;
* [getPaths](#getpaths) - Get templates directories;
* [addExtension](#addextension) - Add an extension, optionally with a compiler;
* [getExtensions](#getextensions) - Get all known extensions;
* [getSortedExtensions](#getsortedextensions) - Get all known extensions in a good sorted way;
* [hasCompiler](#hascompiler) - Determine if a compiler exists by extension;
* [getCompiler](#getcompiler) - Get compiler by extension;
* [getCompilers](#getcompilers) - Get all registered compilers;
* [getCompilersExtensions](#getcompilersextensions) - Get compilers extensions;
* [removeCompiledFiles](#removecompiledfiles) - Remove all compiled files from compilers compilation paths;
* [directive](#directive) - Register a directive;
* [hasDirective](#hasdirective) - Determine if a directive exists;
* [format](#format) - Execute a directive.

## render

Render a template file.

```php
render(string $name, array $params = []): string
```

`$name` - Template file;  
`$params` - Template custom parameters.

_Example:_

```php
echo $viewer->render('welcome', [
    'name' => 'Greg',
]);
```

## renderIfExists

Render a template file if exists. See [`render`](#render) method.

## renderString

Render a template string.

```php
renderString(string $id, string $string, array $params = []): string
```

`$id` - Template unique id. It should has the compiler extension;  
`$string` - Template string;  
`$params` - Template parameters. Will be available only in current template.

_Example:_

```php
echo $viewer->renderString('welcome.blade.php', "Hello {{ $name }}!", [
    'name' => 'Greg',
]);
```

## renderStringIfExists

Render a template string if its compiler exists. See [`renderString`](#renderstring) method.

## assign

Assign parameters to all templates.

```php
assign(string|array $key, string $value = null): $this
```

`$key` - Parameter key or an array of parameters;  
`$value` - Parameter value if `$key` is not an array.  

_Example:_

```php
$viewer->assign('author', 'Greg');

$viewer->assign([
    'position' => 'Web Developer',
    'website' => 'http://greg.md/',
]);
```

## assigned

Get assigned parameters.

```php
assigned(string|array $key = null): any
```

`$key` - Parameter key or an array of keys;  

_Example:_

```php
$all = $viewer->assigned();

$foo = $viewer->assigned('foo');
```

## hasAssigned

Determine if assigned parameters exists.

```php
hasAssigned(string|array $key = null): boolean
```

`$key` - Parameter key or an array of keys;  

_Example:_

```php
if ($viewer->hasAssigned()) {
    // Has assigned parameters.
}

if ($viewer->hasAssigned('foo')) {
    // Has assigned parameter 'foo'.
}
```

## deleteAssigned

Delete assigned parameters.

```php
deleteAssigned(string|array $key = null): $this
```

`$key` - Parameter key or an array of keys;  

_Example:_

```php
// Delete 'foo' parameter.
$viewer->deleteAssigned('foo');

// Delete 'foo' and 'baz' parameters.
$viewer->deleteAssigned(['bar', 'baz']);

// Delete all parameters.
$viewer->deleteAssigned();
```

## setPaths

Replace templates directories.

```php
setPaths(array $paths): $this
```

`$paths` - Templates directories.  

_Example:_

```php
$viewer->setPaths([
    __DIR__ . '/views',
]);
```

## addPaths

Add templates directories. See [`setPaths`](#setpaths) method.

```php
addPaths(array $paths): $this
```

## addPath

Add a template directory.

```php
addPath(string $path): $this
```

`$path` - Template directory.  

_Example:_

```php
$viewer->addPath(__DIR__ . '/views');
```

## getPaths

Get templates directories.

```php
getPaths(): array
```

## addExtension

Add an extension, optionally with a compiler.

```php
addExtension(string $extension, \Greg\View\CompilerInterface|callable(): \Greg\View\CompilerInterface $compiler = null): $this
```

`$extension` - Template extension;
`$compiler` - Template compiler.

_Example:_

```php
$viewer->addExtension('.template');

$viewer->addExtension('.blade.php', function (\Greg\View\Viewer $viewer) {
    return new \Greg\View\ViewBladeCompiler($viewer, __DIR__ . '/compiled');
});
```

## getExtensions

Get all known extensions.

```php
getExtensions(): string[]
```

## getSortedExtensions

Get all known extensions in good a sorted way.

```php
getExtensions(): string[]
```

## hasCompiler

Determine if a compiler exists by extension.

```php
hasCompiler(string $extension): boolean
```

## getCompiler

Get compiler by extension.

```php
getCompiler(string $extension): \Greg\View\CompilerInterface
```

`$extension` - Template extension.

Returns an interface of [`\Greg\View\CompilerInterface`](#).

_Example:_

```php
$compiler = $viewer->getCompiler('.blade.php');

$file = $compiler->getCompiledFile();
```

## getCompilers

Get all registered compilers.

```php
getCompilers(): \Greg\View\CompilerInterface[]
```

Returns an array of [`\Greg\View\CompilerInterface`](#) interfaces.

## getCompilersExtensions

Get all extensions which has compilers.

```php
getCompilersExtensions(): string[]
```

## removeCompiledFiles

Remove all compiled files from compilers compilation path.

```php
removeCompiledFiles(): $this
```

## directive

Register a directive.

```php
directive(string $name, callable(mixed ...$args): string $callable): $this
```

`$name` - Directive name;  
`$callable` - Callable.
&nbsp;&nbsp;&nbsp;&nbsp;`...$args` - Directive arguments.

_Example:_

```php
$viewer->directive('alert', function($message) {
    echo '<script>alert("' . $message . '");</script>';
});
```

## hasDirective

Determine if a directive exists.

```php
hasDirective(string $name): boolean
```

## format

Execute a directive.

```php
format(string $name, mixed ...$args): mixed
```

`$name` - Directive name;  
`...$args` - Directive arguments.

_Example:_

```php
$viewer->format('alert', 'I am an alert message!');
```
