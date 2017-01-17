# Compiler Strategy Documentation

`\Greg\View\CompilerStrategy` is a strategy of custom compilers.

_Example:_

```php
class FooCompiler implements \Greg\View\CompilerStrategy
{
    public function getCompiledFile($file)
    {
        // @todo return compiled file from the template file.
    }

    public function getCompiledFileFromString($id, $string)
    {
        // @todo return compiled file from the template string.
    }

    public function removeCompiledFiles()
    {
        // @todo remove all compiled files.
    }
}
```

# Methods:

Below is a list of **required methods**:

* [getCompiledFile](#getcompiledfile) - Get compiled file from a template file;
* [getCompiledFileFromString](#getcompiledfilefromstring) - Get compiled file from a template string;
* [removeCompiledFiles](#removecompiledfiles) - Remove all compiled files.

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

`$id` - Template unique id. It should has the compiler extension;  
`$string` - Template string.

## removeCompiledFiles

Remove all compiled files from compilation path.

```php
removeCompiledFiles(): $this
```
