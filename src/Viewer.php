<?php

namespace Greg\View;

use Greg\Support\Str;

class Viewer implements \ArrayAccess
{
    private $paths = [];

    private $params = [];

    private $compilers = [
        '.php'   => null,
        '.html'  => null,
        '.phtml' => null,
    ];

    private $directives = [];

    private $tmpFiles = [];

    public function __construct(string ...$paths)
    {
        $this->paths = $paths;
    }

    public function render(string $name, array $params = []): string
    {
        if ($file = $this->getCompiledFile($name)) {
            return $this->renderFile($file, $params);
        }

        throw new ViewException('View file `' . $name . '` does not exist in view paths.');
    }

    public function renderIfExists(string $name, array $params = []): ?string
    {
        if ($file = $this->getCompiledFile($name)) {
            return $this->renderFile($file, $params);
        }

        return null;
    }

    public function renderString(string $id, string $string, array $params = []): string
    {
        if ($file = $this->getCompiledFileFromString($id, $string)) {
            return $this->renderFile($file, $params);
        }

        throw new ViewException('Could not find a compiler for view `' . $id . '`.');
    }

    public function renderStringIfExists(string $id, string $string, array $params = []): ?string
    {
        if ($file = $this->getCompiledFileFromString($id, $string)) {
            return $this->renderFile($file, $params);
        }

        return null;
    }

    protected function renderFile(string $file, array $params = []): string
    {
        $renderer = new Renderer($this, $file, $params + $this->assigned());

        return (new Loader($renderer))->_l_o_a_d_();
    }

    public function getCompiledFile(string $name): ?string
    {
        foreach ($this->paths as $path) {
            foreach ($this->getExtensions() as $extension) {
                if (is_file($file = $path . DIRECTORY_SEPARATOR . ltrim($name . $extension, '\/'))) {
                    if ($this->hasCompiler($extension)) {
                        $file = $this->getCompiler($extension)->getCompiledFile($file);
                    }

                    return $file;
                }
            }
        }

        return null;
    }

    public function getCompiledFileFromString(string $id, string $string): ?string
    {
        foreach ($this->getSortedExtensions() as $extension) {
            if (Str::endsWith($id, $extension)) {
                if ($this->hasCompiler($extension)) {
                    return $this->getCompiler($extension)->getCompiledFileFromString($id, $string);
                }

                return $this->getTmpFileFromString($id, $string);
            }
        }

        return null;
    }

    protected function getTmpFileFromString(string $id, string $string): string
    {
        if (!array_key_exists($id, $this->tmpFiles)) {
            $file = tempnam(sys_get_temp_dir(), 'view');

            file_put_contents($file, $string);

            $this->tmpFiles[$id] = $file;
        }

        return $this->tmpFiles[$id];
    }

    public function assign(string $key, $value)
    {
        $this->params[$key] = $value;

        return $this;
    }

    public function assignMultiple(array $params)
    {
        $this->params = array_merge($this->params, $params);

        return $this;
    }

    public function assigned(string $key = null)
    {
        return func_num_args() ? $this->params[$key] ?? null : $this->params;
    }

    public function hasAssigned(string $key = null): bool
    {
        return func_num_args() ? array_key_exists($key, $this->params) : (bool) $this->params;
    }

    public function removeAssigned(string ...$args)
    {
        if ($args) {
            array_map(function ($key) {
                unset($this->params[$key]);
            }, $args);
        } else {
            $this->params = [];
        }

        return $this;
    }

    public function setPaths(string ...$paths)
    {
        $this->paths = $paths;

        return $this;
    }

    public function addPaths(string ...$paths)
    {
        $this->paths = array_merge($this->paths, $paths);

        return $this;
    }

    public function getPaths(): array
    {
        return $this->paths;
    }

    public function addExtension(string $extension, $compiler = null)
    {
        $this->compilers[$extension] = $compiler;

        return $this;
    }

    public function getExtensions(): array
    {
        return array_keys($this->compilers);
    }

    public function getSortedExtensions()
    {
        $extensions = $this->getExtensions();

        usort($extensions, function ($a, $b) {
            return mb_strlen($b) - mb_strlen($a);
        });

        return $extensions;
    }

    public function hasCompiler(string $extension): bool
    {
        return array_key_exists($extension, $this->getCompilers());
    }

    /**
     * @param $extension
     *
     * @throws ViewException
     *
     * @return CompilerStrategy
     */
    public function getCompiler(string $extension): CompilerStrategy
    {
        if (!$this->hasCompiler($extension)) {
            throw new ViewException('View compiler for extension `' . $extension . '` not found.');
        }

        $compiler = &$this->compilers[$extension];

        if (is_callable($compiler)) {
            $compiler = call_user_func_array($compiler, [$this]);
        }

        if (!($compiler instanceof CompilerStrategy)) {
            throw new ViewException('View compiler for extension `' . $extension . '` should be an instance of `' . CompilerStrategy::class . '`.');
        }

        return $compiler;
    }

    public function getCompilers(): array
    {
        return array_filter($this->compilers);
    }

    public function getCompilersExtensions(): array
    {
        return array_keys($this->getCompilers());
    }

    public function removeCompiledFiles()
    {
        foreach ($this->getCompilersExtensions() as $extension) {
            $this->getCompiler($extension)->removeCompiledFiles();
        }

        $this->removeTmpFiles();

        return $this;
    }

    protected function removeTmpFiles()
    {
        foreach ($this->tmpFiles as $file) {
            unlink($file);
        }

        $this->tmpFiles = [];

        return $this;
    }

    public function directive(string $name, callable $callable)
    {
        $this->directives[$name] = $callable;

        foreach ($this->getCompilersExtensions() as $extension) {
            $compiler = $this->getCompiler($extension);

            if (($compiler instanceof ViewCompilerStrategy)) {
                $compiler->addViewDirective($name);
            }
        }

        return $this;
    }

    public function hasDirective(string $name): bool
    {
        return array_key_exists($name, $this->directives);
    }

    public function format(string $name, ...$args): string
    {
        if (!$this->hasDirective($name)) {
            throw new ViewException('Directive `' . $name . '` is not defined.');
        }

        return call_user_func_array($this->directives[$name], $args);
    }

    public function offsetExists($key)
    {
        return $this->hasAssigned($key);
    }

    public function offsetSet($key, $value)
    {
        return $this->assign($key, $value);
    }

    public function offsetGet($key)
    {
        return $this->params[$key] ?? null;
    }

    public function offsetUnset($key)
    {
        return $this->removeAssigned($key);
    }

    public function __set(string $key, $value)
    {
        return $this->assign($key, $value);
    }

    public function __get(string $key)
    {
        return $this->assigned($key);
    }

    public function __destruct()
    {
        $this->removeTmpFiles();
    }
}
