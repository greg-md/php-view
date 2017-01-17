# View Blade Compiler Documentation

`\Greg\View\ViewBladeCompiler` is an extended [Blade Compiler](docs/BladeCompiler.md), specially for the [Viewer Contract](ViewerContract.md).

Extends: [`\Greg\View\BladeCompiler`](BladeCompiler.md).

Implements: `\Greg\View\ViewCompilerStrategy`.

# Table of contents:

Below is a list of **new methods**:

* [addViewDirective](#addviewdirective) - Add a directive that was already registered in the `Viewer`, but not in the compiler.

Below is a list of **new directives and template formats**.

* **Statements**
    * [Section](#section-statement) - Section statement;
    * [Push](#push-statement) - Push statement.
* **Directives**
    * [Extends](#extends) - Extend template;
    * [Render](#render) - Render another template with existing parameters.
    * [Partial](#partial) - Render another template with new parameters;
    * [Each](#each) - Render template for each element in array;
    * [Content](#content) - Get registered content;
    * [Format](#format) - Execute a directive registered in the [Viewer Contract](ViewerContract.md).

# New methods

Below is a list of **new methods** of the `ViewBladeCompiler`:

## addViewDirective

Add a directive that was already registered in the `Viewer`, but not in the compiler.

```php
addViewDirective(string $name): $this
```

`$name` - Directive name;  

_Example:_

```php
$compiler->addViewDirective('alert');
```

# New template syntax

Below is a list of **new directives and template formats**.

## Section Statement

### section

Start or add a section;

```php
section(string $name, string $content = null)
```

`$name` - Section name;  
`$content` - Section content.

### endsection

End and register current section.

```php
endsection()
```

### yield

Display a section.

```php
yield(string $name): string
```

`$name` - Section name.

### parent

Display parent section.

```php
parent(): string
```

### show

End and display current section.

```php
show(): string
```

### _Example_:

_Example 1:_

```blade
@section("hello-world", "Hello")

@section("hello-world")
    @parent World!
@endsection

@yield("hello-world")
```

_Output:_

```html
Hello World!
```

_Example 2:_

```blade
@section("hello-world")
    Hello
@endsection

@section("hello-world")
    @parent World!
@show
```

_Output:_

```html
Hello World!
```

## Push Statement

### push

Start a pusher or push contents in the stack.

```php
push(string $name, string $content = null)
```

`$name` - Stack name;  
`$content` - Stack content.

### endpush

End current pusher and add it to the stack.

```php
endpush()
```

### stack

Display contents from the stack.

```php
stack(string $name): string
```

`$name` - Stack name.  

### _Example_:

```blade
@push("js", "<script>alert('Foo')</script>")

@push("js")
    <script>alert('Bar')</script>
@endpush

@stack("js")
```

_Output:_

```html
<script>alert('Foo')</script>
<script>alert('Bar')</script>
```

## Extends

### extends

Extend template with another template file.

```php
extends(string $name)
```

`$name` - Template file.

### extendsString

Extend template with another template string.

```php
extendsString(string $id, string $string)
```

`$id` - Template unique id. It should has the compiler extension;  
`$string` - Template string.

### content

Display parent content.

```php
content(): string
```

### _Example:_

Create a template `layout.blade.php`:

```blade
<section class="content">
    @content
</section>
```

Extend `layout` template:

```blade
@extends("layout")

Hello World!
```

_Output:_

```html
<section class="content">
    Hello World!
</section>
```

## Render

### render

Render a template file with current parameters.

```php
render(string $name, array $params = []): string
```

`$name` - Template file;  
`$params` - Template custom parameters.

### renderIfExists

Render a template file with current parameters if template exists. See [render](#render) directive.

### renderString

Render a template string with current parameters.

```php
renderString(string $id, string $string, array $params = []): string
```

`$id` - Template unique id. It should has the compiler extension;  
`$string` - Template string;  
`$params` - Template custom parameters.

### renderStringIfExists

Render a template string with current parameters if its compiler exists. See [renderString](#renderstring) directive.

### _Example:_

```blade
@render("foo")

@renderIfExists("bar")
```

## Partial

### partial

Render a template file with new parameters.

```php
partial(string $name, array $params = []): string
```

`$name` - Template file;  
`$params` - Template custom parameters.

### partialIfExists

Render a template file with new parameters if template exists. See [partial](#partial) directive.

### partialString

Render a template string with new parameters.

```php
partialString(string $id, string $string, array $params = []): string
```

`$id` - Template unique id. It should has the compiler extension;  
`$string` - Template string;  
`$params` - Template custom parameters.

### partialIfExists

Render a template string with new parameters if its compiler exists. See [partialString](#partialstring) directive.

### _Example:_

```blade
@partial("foo")

@partialIfExists("bar")
```

## Each

### each

Render a template file with current parameters for each value.

```php
each(string $name, array $values, array $params = [], string $valueKeyName = null, string $emptyName = null): string
```

`$name` - Template file;  
`$values` - Values;  
`$params` - Template custom parameters;  
`$valueKeyName` - The key name of the current value;  
`$emptyName` - If no values, will render this template file.

### eachIfExists

Render a template file with current parameters for each value if template exists. See [each](#each) directive.

### eachString

Render a template string with current parameters for each value.

```php
eachString(string $id, string $string, array $values, array $params = [], string $valueKeyName = null, string $emptyId = null, string $emptyString = null): string
```

`$id` - Template unique id. It should has the compiler extension;  
`$string` - Template string;  
`$values` - Values;  
`$params` - Template custom parameters;  
`$valueKeyName` - The key name of the current value;  
`$emptyId` - Template unique id. Will use it if no values found;
`$emptyString` - Template string. Will use it if no values found.

### eachStringIfExists

Render a template string with current parameters for each value if its compiler exists. See [eachString](#eachstring) directive.

### _Example:_

```blade
@each("foo", [1, 2])

@renderIfExists("bar", [1, 2])
```

## Format

Execute a directive registered in the [Viewer Contract](ViewerContract.md).

```php
format(string $name, mixed ...$args)
```

_Example:_

```blade
@format("alert", "I am a javascript alert!")

<!-- or -->

@alert("I am a javascript alert!")
```
