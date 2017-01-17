# Blade Compiler Documentation

`\Greg\View\BladeCompiler` is an independent template compiler.

You can use and extend it in your own application.

Implements: `\Greg\View\CompilerStrategy`.

_Example:_

```php
$compiler = new \Greg\View\BladeCompiler(__DIR__ . '/compiled');

$compiledFile = $compiler->getCompiledFile(__DIR__ . '/welcome.blade.php');

include $compiledFile;
```

# Methods:

Below is a list of supported methods of the `BladeCompiler`:

* [__construct](#__construct) - Constructor of the `BladeCompiler`;
* [setCompilationPath](#setcompilationpath) - Set compilation path;
* [getCompilationPath](#getcompilationpath) - Get compilation path;
* [getCompiledFile](#getcompiledfile) - Get compiled file from a template file;
* [getCompiledFileFromString](#getcompiledfilefromstring) - Get compiled file from a template string;
* [removeCompiledFiles](#removecompiledfiles) - Remove all compiled files from compilation path;
* [compileFile](#compilefile) - Compile a template file;
* [compileString](#compilestring) - Compile a template string;
* [addCompiler](#addcompiler) - Add a compiler;
* [addDirective](#adddirective) - Add a template directive;
* [addEmptyDirective](#addemptydirective) - Add an empty template directive;
* [addOptionalDirective](#addoptionaldirective) - Add a template directive with optional parameters.

## __construct 

This is the constructor of the `BladeCompiler`;

```php
__construct(string $compilationPath)
```

`$compilationPath` - Compiled files path;

_Example:_

```php
$compiler = new \Greg\View\BladeCompiler(__DIR__ . '/compiled');
```

## setCompilationPath

Set compilation path.

```php
setCompilationPath(string $path): $this
```

`$path` - Compilation path;

## getCompilationPath

Get compilation path.

```php
getCompilationPath(): string
```

## getCompiledFile

Get compiled file from a template file.

```php
getCompiledFile(string $file): string
```

`$file` - Template file.

## getCompiledFileFromString

Get compiled file from a template string.

```php
getCompiledFileFromString(string $id, string $string): string
```

`$id` - Template unique id;
`$string` - Template string.

## removeCompiledFiles

Remove all compiled files from compilation path.

```php
removeCompiledFiles(): $this
```

## compileFile

Compile a template file.

```php
compileFile(string $file): string
```

## compileString

Compile a template string.

```php
compileString(string $string): string
```

## addCompiler

Add a template compiler.

```php
addCompiler(callable $compiler): $this
```

`$compiler` - A callable compiler.

## addDirective

Add a template directive.

```php
addDirective(string $name, callable $compiler)
```

`$name` - Directive name;  
`$compiler` - Directive callable compiler.

## addEmptyDirective

Add an empty template directive. See [`addDirective`](#adddirective) method.

## addOptionalDirective

Add a template directive with optional parameters. See [`addDirective`](#adddirective) method.
