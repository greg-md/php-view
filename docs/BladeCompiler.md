# Blade Compiler Documentation

Yeah, I'm sure you've heard about [Laravel Blade Templates](https://laravel.com/docs/5.3/blade).
On the surface it's pretty much the same, but it's realized in a much better way and has several useful functionality.

`\Greg\View\BladeCompiler` is an independent compiler which you can work with.
It has only independent directives, but you can extend it any time.

Implements: `\Greg\View\CompilerStrategy`.

_Example:_

```php
$compiler = new \Greg\View\BladeCompiler(__DIR__ . '/compiled');

$compiledFile = $compiler->getCompiledFile(__DIR__ . '/welcome.blade.php');

include $compiledFile;
```

# Available extenders:
* [ViewBladeCompiler](ViewBladeCompiler.md) - A Blade compiler specially for the [Viewer](Viewer.md).

# Table of contents:

Below is a list of **supported methods** of the `BladeCompiler`:

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

Below is a list of **supported directives and template formats**.

* **Display data**
    * [Secured](#display-secured-data) - Display data throw `htmlentities` to prevent XSS attacks;
    * [Raw](#display-raw-data) - Display raw data;
    * [Comments](#comments) - Writing template comments.
* **Statements**
    * [If](#if-statement) - If Statement;
    * [Unless](#unless-statement) - Unless Statement;
    * [For](#for-statement) - For statement;
    * [Foreach](#foreach-statement) - Foreach statement;
    * [While](#while-statement) - While statement;
    * [Switch](#switch-statement) - Switch statement;
    * [Verbatim](#verbatim-statement) - Verbatim statement.
* **Directives**
    * [Stop](#stop) - Stop template execution.

# Methods

Below is a list of **supported methods** of the `BladeCompiler`:

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

# Template syntax

Below is a list of **supported directives and template formats**.

## Display secured data

Display data throw `htmlentities` to prevent XSS attacks.

_Example:_

```blade
Hello, {{ $name or 'guest' }}.
```

## Display raw data

Display data as it is.

_Example:_

```blade
Hello, {!! $name or '<em>guest</em>' !!}.
```

## Comments

Writing template comments.

_Example:_

```blade
{{-- This comment will not be present in the rendered HTML --}}
```

## If Statement

_Example:_

```blade
@if (count($records) === 1)
    I have one record!
@elseif (count($records) > 1)
    I have multiple records!
@else
    I don't have any records!
@endif
```

## Unless Statement

_Example:_

```blade
@unless (Auth::check())
    You are not signed in.
@elseunless (Auth::isVerified())
    You should verity your account.
@else
   Hello, {{ Auth::name() }}
@endunless
```

## For Statement

_Example:_

```blade
@for ($i = 0; $i < 10; $i++)
    The current value is {{ $i }}
@endfor
```

## Foreach statement

_Example:_

```blade
@foreach ($users as $user)
    @continue($user->type == 1)

    <p>This is user {{ $user->id }}</p>

    @break($user->number == 5)
@empty
    <p>No users</p>
@endforeach
```

You can also set a loop variable in foreach. This variable provides access to some useful bits of information such as the current loop index and whether this is the first or last iteration through the loop.

_Example:_

```blade
@foreach ($users as $user, $loop)
    @if ($loop->first)
        This is the first iteration.
    @endif

    @if ($loop->last)
        This is the last iteration.
    @endif

    <p>This is user {{ $user->id }}</p>
@endforeach
```

The loop variable contains a variety of useful properties:

* `$loop->index` - The index of the current loop iteration (starts at 0);
* `$loop->iteration` - The current loop iteration (starts at 1);
* `$loop->remaining` - The iteration remaining in the loop;
* `$loop->count` - The total number of items in the array being iterated;
* `$loop->first` - Whether this is the first iteration through the loop;
* `$loop->last` - Whether this is the last iteration through the loop;
* `$loop->depth` - The nesting level of the current loop;
* `$loop->parent` - When in a nested loop, the parent's loop variable.

## While statement

_Example:_

```blade
@while (true)
    <p>I'm looping forever.</p>
@endwhile
```

## Switch statement

_Example:_

```blade
@switch ($color)
    @case('red')
        The color is red.
        
        @break
    @case('green')
        The color is green.

        @break
    @default
        The color is blue.
@endswitch
```

## Verbatim statement

If you don't want to compile some content, you can use `@verbatim` statement.

_Example:_

```blade
@verbatim
    <div class="container">
        Hello, {{ name }}.
    </div>
@endverbatim
```

## Stop

Stop executing the template.

_Example:_

```blade
I will be visible.

@stop

I will not be visible.
```