# View Blade Compiler Documentation

`\Greg\View\ViewBladeCompiler` is an extended [Blade Compiler](docs/BladeCompiler.md), specially for the [Viewer](Viewer.md).

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
    * [Format](#format) - Execute a directive registered in the [Viewer](#).

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

Create a section.

```php
section(string $name, string $content = null)
```

`$name` - Section name;  
`$content` - Section content.

### endsection

End current section.

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

Display current section.

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

Push contents in a stack.

```php
push(string $name, string $content = null)
```

`$name` - Stack name;  
`$content` - Stack content.

### stack

Display contents from a stack.

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

Extend a template with another template file.

```php
extends(string $name)
```

`$name` - Template file.

### extendsString

Extend a template with another template string.

```php
extendsString(string $id, string $string)
```

`$id` - Template unique id;  
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
render(string $name, array $params = [])
```

`$name` - Template file;  
`$params` - Template custom parameters.

### renderIfExists

Render a template file with current parameters if template exists. See [render](#render) directive.

### _Example:_

```blade
@render("foo")

@renderIfExists("bar")
```

## Partial

### partial

Render a template file with new parameters.

```php
partial(string $name, array $params = [])
```

`$name` - Template file;  
`$params` - Template custom parameters.

### partialIfExists

Render a template file with new parameters if template exists. See [partial](#partial) directive.

### _Example:_

```blade
@partial("foo")

@partialIfExists("bar")
```
