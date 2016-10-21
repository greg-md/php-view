# Greg PHP View

[![StyleCI](https://styleci.io/repos/70835580/shield?style=flat)](https://styleci.io/repos/70835580)
[![Build Status](https://travis-ci.org/greg-md/php-view.svg)](https://travis-ci.org/greg-md/php-view)
[![Total Downloads](https://poser.pugx.org/greg-md/php-view/d/total.svg)](https://packagist.org/packages/greg-md/php-view)
[![Latest Stable Version](https://poser.pugx.org/greg-md/php-view/v/stable.svg)](https://packagist.org/packages/greg-md/php-view)
[![Latest Unstable Version](https://poser.pugx.org/greg-md/php-view/v/unstable.svg)](https://packagist.org/packages/greg-md/php-view)
[![License](https://poser.pugx.org/greg-md/php-view/license.svg)](https://packagist.org/packages/greg-md/php-view)

A better Viewer and Blade Compiler for web artisans.

# Documentation

## Viewer

`\Greg\View\Viewer` is the main class which initialize a new view manager.

#### Example:

```php
$viewer = new \Greg\View\Viewer('./views', $sharedParams = []);

$response = $viewer->render('home', [
    'author' => 'Greg',
]);

$response->send();
```

#### Methods:

- **`__construct(string|array $path, array $params = [])`** 
    
    This is the constructor of the Viewer.
    
    **Arguments:**
    
    `$path` - Templates directory;  
    `$params` - This parameters will be assigned in all templates.

- **`render(string $name, array $params = [], boolean $returnAsString = false)`**
    
    Render a template by name.
    
    **Arguments:**
    
    `$name` - Template name, relative to registered paths;  
    `$params` - Template parameters. Will be available only in this template.  
    `$returnAsString` - If `true`, returned content will be a string, otherwise will return an `\Greg\Support\Http\Response` object.
    
    **Example:**
    
    ```php
    $response = $viewer->render('home', [
        'author' => 'Greg',
    ]);
    
    $response->send();
    ```

- **`renderIfExists(string $name, array $params = [], boolean $returnAsString = false)`**
    
    Render a template by name if template exists. See `render` method.

- **`getRenderer(string $name, array $params = [])`**
    
    Get an instance of `\Greg\View\ViewRenderer` by template name.
    
    **Arguments:**
    
    `$name` - Template name, relative to registered paths;  
    `$params` - Template parameters. Will be available only in this template.  
    
    **Example:**
    
    ```php
    $renderer = $viewer->getRenderer('home', [
        'author' => 'Greg',
    ]);
    
    echo $renderer->load();
    ```

- **`getRendererIfExists(string $name, array $params = [])`**

    Get an instance of `\Greg\View\ViewRenderer` by template name if template exists. See `getRenderer` method.

- **`assign(string|array $key, string $value = null)`**
    
    Assign parameters to all templates.
    
    **Arguments:**
    
    `$key` - Parameter key or an array of parameters;  
    `$value` - Parameter value if `$key` is not an array.  
    
    **Example:**
    
    ```php
    $viewer->assign('author', 'Greg');
    
    $viewer->assign([
        'position' => 'Web Developer',
        'website' => 'http://greg.md/',
    ]);
    ```

- **`setPaths(array $paths)`**
    
    Replace templates directories.
    
    **Arguments:**
    
    `$paths` - Templates directories.  
    
    **Example:**
    
    ```php
    $viewer->setPaths([
        './views',
    ]);
    ```

- **`addPaths(array $paths)`**

    Add new templates directories. See `setPaths` method.

- **`addPath(string $path)`**
    
    Add new template directory.
    
    **Arguments:**
    
    `$path` - Template directory.  
    
    **Example:**
    
    ```php
    $viewer->addPath('./views');
    ```

- **`getPaths()`**

    Get templates directories.
    
    **Example:**
    
    ```php
    foreach($viewer->getPaths() as $path) {
        echo $path . "\n";
    }
    ```

- **`addExtension(string $extension, \Greg\View\CompilerInterface|callable $compiler = null)`**
    
    Add new extension.
    
    **Arguments:**
    
    `$extension` - Template extension.  
    `$compiler` - Template compiler.
    
    **Example:**
    
    ```php
    $viewer->addExtension('.template');
    
    $viewer->addExtension('.blade.php', function (Viewer $viewer) {
        return new ViewBladeCompiler($viewer, './storage');
    });
    ```

- **`getExtensions()`**

    Get all known extensions.

- **`getCompiler(string $extension)`**
    
    Get compiler by extension.
    
    **Arguments:**
    
    `$extension` - Template extension.

    **Example:**
    
    ```php
    $compiler = $viewer->getCompiler('.blade.php');
    
    $file = $compiler->getCompiledFile();
    ```

- **`getCompilers()`**

    Get all registered compilers.

- **`getCompilersExtensions()`**

    Get all extensions which have compilers.

- **`getFile(string $name)`**
    
    Get file path by template name.
    
    **Arguments:**
    
    `$name` - Template name.

- **`clearCompiledFiles()`**

    Clear all compiled files.

- **`directive(string $name, callable $callable)`**
    
    Register a new directive.
    
    **Arguments:**
    
    `$name` - Directive name;  
    `$callable` - Directive executive function.
    
    **Example:**
    
    ```php
    $viewer->directive('alert', function($message) {
        echo '<script>alert("' . $message . '");</script>';
    });
    ```

- **`format(string $name, mixed ...$args)`**
    
    Execute a directive.
    
    **Arguments:**
    
    `$name` - Directive name;  
    `...$args` - Directive arguments.
    
    **Example:**
    
    ```php
    $viewer->format('alert', 'I am an alert message!');
    ```
