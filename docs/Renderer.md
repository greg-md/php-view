# Renderer Documentation

`\Greg\View\Renderer` is an instance of a template, and could be accessed via `$this` variable in the template.

_Example:_

```php
$viewer = new \Greg\View\Viewer(__DIR__ . '/views');

$renderer = new \Greg\View\Renderer($viewer, __DIR__ . '/welcome.php', [
    'name' => 'Greg',
]);

$renderer->section('header', 'I am a header!');

$renderer->setContent('I am a content!');

$content = (new \Greg\View\Loader($renderer))->_l_o_a_d_();

echo $content;
```

# Methods:

Below is a list of **supported methods**:

* [__construct](#__construct) - Constructor of the `ViewRenderer`;
* [render](#render) - Render a template file with current parameters;
* [renderIfExists](#renderifexists) - Render a template file with current parameters if template exists;
* [renderString](#renderstring) - Render a template string with current parameters;
* [renderStringIfExists](#renderstringifexists) - Render a template string with current parameters if template exists;
* [partial](#partial) - Render a template file with new parameters;
* [partialIfExists](#partialifexists) - Render a template file with new parameters if template exists;
* [partialString](#partialString) - Render a template string with new parameters;
* [partialStringIfExists](#partialstringifexists) - Render a template string with new parameters if template exists;
* [each](#each) - Render a template file with current parameters for each value;
* [eachIfExists](#eachifexists) - Render a template file with current parameters for each value if template exists;
* [eachString](#eachString) - Render a template string with current parameters for each value;
* [eachStringIfExists](#eachstringifexists) - Render a template string with current parameters for each value if template exists;
* [extend](#extend) - Extend template with another template file;
* [extendString](#extendstring) - Extend template with another template string;
* [content](#content) - Get content;
* [section](#section) - Start or add a section;
* [parent](#parent) - Get parent section;
* [endSection](#endsection) - End current section;
* [show](#show) - End and get current section;
* [getSection](#getsection) - Get a section;
* [push](#push) - Start a pusher or push contents in a stack;
* [endPush](#endpush) - End current pusher and add it to the stack;
* [stack](#stack) - Get a stack;
* [format](#format) - Execute a directive registered in the [Viewer](Viewer.md);
* [viewer](#viewer) - Get [Viewer](/greg-md/php-view/wiki/Viewer);
* [params](#params) - Get parameters;
* [file](#file) - Get file;
* [extended](#extended) - Get extended template file;
* [setContent](#setcontent) - Set content;
* [setSections](#setsections) - Set sections;
* [getSections](#getsections) - Get sections;
* [hasSection](#hassection) - Check if section exists;
* [setStacks](#setstacks) - Set stacks;
* [getStacks](#getstacks) - Get stacks;
* [hasStack](#hasstack) - Check if stack exists;
* [__call](#call) - Execute a directive registered in the [Viewer](Viewer.md).

## __construct

This is the constructor of the `ViewRenderer`.

```php
__construct(Viewer $viewer, string $file, array $params = [])
```

`$viewer` - The [Viewer](/greg-md/php-view/wiki/Viewer);  
`$file` - Template file;  
`$params` - Template parameters.

_Example:_

```php
$viewer = new \Greg\View\Viewer(__DIR__ . '/views');

$renderer = new \Greg\View\ViewRenderer($viewer, __DIR__ . '/welcome.php', [
    'name' => 'Greg',
]);
```

## render

Render a template file with current parameters.

```php
render(string $name, array $params = []): string
```

`$name` - Template file;  
`$params` - Template custom parameters.

_Example:_

```php
echo $renderer->render('header', [
    'title' => 'I am a header!',
]);
```

## renderIfExists

Render a template file with current parameters if template exists. See [`render`](#render) method.

## renderString

Render a template string with current parameters.

```php
renderString(string $id, string $string, array $params = []): string
```

`$id` - Template unique id. It should has the compiler extension;  
`$string` - Template string;  
`$params` - Template custom parameters.

_Example:_

```php
echo $renderer->renderString('header.php', '<header><?php echo $title?></header>', [
    'title' => 'I am a header!',
]);
```

## renderStringIfExists

Render a template string with current parameters if its compiler exists. See [`renderString`](#renderstring) method.

## partial

Render a template file with new parameters.

```php
partial(string $name, array $params = []): string
```

`$name` - Template file;  
`$params` - Template parameters.

## partialIfExists

 Render a template file with new parameters if template exists. See [`partial`](#partial) method.

## partialString

Render a template string with new parameters.

```php
partialString(string $id, string $string, array $params = []): string
```

`$id` - Template unique id. It should has the compiler extension;  
`$string` - Template string;  
`$params` - Template custom parameters.

## partialStringIfExists

 Render a template string with new parameters if its compiler exists. See [`partialString`](#partialstring) method.

## each

Render a template file with current parameters for each value.

```php
each(string $name, array $values, array $params = [], string $valueKeyName = null, string $emptyName = null): string
```

`$name` - Template file;  
`$values` - Values;  
`$params` - Template custom parameters;  
`$valueKeyName` - The key name of the current value;  
`$emptyName` - If no values, will render this template file.

## eachIfExists

Render a template file with current parameters for each value if template exists. See [`each`](#each) method.

## eachString

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

## eachStringIfExists

Render a template string with current parameters for each value if template exists. See [`eachString`](#eachstring) method.

## extend

Extend template.

```php
extend(string $name): $this
```

`$name` - Template name, relative to registered paths;

## content

Get registered content.

```php
content(): string
```

## section

Start or register a new section.

```php
section(string $name, string $content = null): $this
```

`$name` - Section name;  
`$content` - Section content.

## parent

Get parent section.

```php
parent(): $this
```

## endSection

Finish and register current section content.

```php
endSection(): $this
```

## show

Finish and return current section content.

```php
show(): string
```

## getSection

Get section content.

```php
getSection(string $name, string $else = null): string
```

`$name` - Section name;  
`$else` - If the section doesn't exists, you see this content.

## push

Start or add content in a stack.

```php
push(string $name, string $content = null): $this
```

`$name` - Stack name;  
`$content` - Stack content.

## endPush

Finish and add content in a stack.

```php
endPush(): $this
```

## stack

Get stack contents.

```php
stack(string $name, string $else = null): string
```

`$name` - Stack name;  
`$else` - If the stack doesn't exists, you see this content.

## format

Execute a directive registered in the [Viewer](/greg-md/php-view/wiki/Viewer).

```php
format(string $name, mixed ...$args): mixed
```

`$name` - Directive name;  
`...$args` - Directive arguments.

## setViewer

Set [Viewer](/greg-md/php-view/wiki/Viewer).

```php
setViewer(\Greg\View\Viewer $viewer): $this
```

`$viewer` - [`\Greg\View\Viewer`](/greg-md/php-view/wiki/Viewer).

## getViewer

Get [Viewer](/greg-md/php-view/wiki/Viewer).

```php
getViewer(): \Greg\View\Viewer
```

Returns [`\Greg\View\Viewer`](/greg-md/php-view/wiki/Viewer).

## setParams

Set registered parameters.

```php
setParams(array $params): $this
```

`$params` - Parameters.

## getParams

Get registered parameters.

```php
getParams(): array
```

## setFile

Set registered file.

```php
setFile(string $file): $this
```

`$file` - File full path.

## getFile

Get registered file.

```php
getFile(): string
```

## setExtended

Set extended template.

```php
setExtended(string $name): $this
```

`$name` - Extended template name.

## getExtended

Get extended template.

```php
getExtended(): string
```

## setContent

Set registered content.

```php
setContent(string $content): $this
```

`$content` - Registered content.

## getContent

Get registered content.

```php
getContent(): string
```

## setSections

Set registered sections.

```php
setSections(array $sections): $this
```

`$sections` - Registered sections.

## getSections

Get registered sections.

```php
getSections(): array
```

## hasSection

Check if section exists.

```php
hasSection(string $name): boolean
```

`$name` - Section name.

## setStacks

Set registered stacks.

```php
setStacks(array $stacks): $this
```

`$stacks` - Registered stacks.

## getStacks

Get registered stacks.

```php
getStacks(): array
```

## hasStack

Check if stack exists.

```php
hasStack(string $name): boolean
```

`$name` - Stack name.
