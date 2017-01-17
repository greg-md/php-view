# View Compiler Strategy Documentation

`\Greg\View\ViewCompilerStrategy` is an extended [Compiler Strategy](CompilerStrategy.md) of custom compilers, specially for the [Viewer Contract](ViewerContract.md).

Extends: [`\Greg\View\CompilerStrategy`](CompilerStrategy.md).

_Example:_

```php
class FooCompiler extends \Greg\View\BladeCompiler implements \Greg\View\ViewCompilerStrategy
{
    public function addViewDirective($name)
    {
        // @todo add a directive that was already registered in the Viewer.
    }
```

# Methods:

Below is a list of **new methods**:

* [addViewDirective](#addviewdirective) - Add a directive that was already registered in the [Viewer Contract](ViewerContract.md), but not in the compiler.

## addViewDirective

Add a directive that was already registered in the [Viewer Contract](ViewerContract.md), but not in the compiler.
