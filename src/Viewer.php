<?php

namespace Greg\View;

use Greg\Support\Accessor\ArrayAccessTrait;
use Greg\Support\Obj;
use Greg\Support\Str;

class Viewer implements ViewerContract
{
    use ArrayAccessTrait;

    private $paths = [];

    private $compilers = [
        '.php'   => null,
        '.html'  => null,
        '.phtml' => null,
    ];

    private $directives = [];

    public function __construct($path, array $params = [])
    {
        $this->setPaths((array) $path);

        $this->assign($params);

        return $this;
    }

    public function render($name, array $params = [])
    {
        if ($file = $this->getCompiledFile($name)) {
            return $this->renderFile($file, $params);
        }

        throw new ViewException('View file `' . $name . '` does not exist in view paths.');
    }

    public function renderIfExists($name, array $params = [])
    {
        if ($file = $this->getCompiledFile($name)) {
            return $this->renderFile($file, $params);
        }

        return null;
    }

    public function renderString($id, $string, array $params = [])
    {
        if ($file = $this->getCompiledFileFromString($id, $string)) {
            return $this->renderFile($file, $params);
        }

        throw new ViewException('Could not find a compiler for view `' . $id . '`.');
    }

    public function renderStringIfExists($id, $string, array $params = [])
    {
        if ($file = $this->getCompiledFileFromString($id, $string)) {
            return $this->renderFile($file, $params);
        }

        return null;
    }

    protected function renderFile($file, array $params = [])
    {
        $renderer = new ViewRenderer($this, $file, $params + $this->getParams());

        return (new ViewRendererLoader($renderer))->_l_o_a_d_();
    }

    public function getCompiledFile($name)
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

        return false;
    }

    public function getCompiledFileFromString($id, $string)
    {
        foreach ($this->getSortedExtensions() as $extension) {
            if (Str::endsWith($id, $extension)) {
                if ($this->hasCompiler($extension)) {
                    return $this->getCompiler($extension)->getCompiledFileFromString($id, $string);
                }

                return false;
            }
        }

        return false;
    }

    public function assign($key, $value = null)
    {
        if (is_array($key)) {
            $this->addToAccessor($key);
        } else {
            $this->setToAccessor($key, $value);
        }

        return $this;
    }

    public function getParams()
    {
        return $this->getAccessor();
    }

    public function setPaths(array $paths)
    {
        $this->paths = $paths;

        return $this;
    }

    public function addPaths(array $paths)
    {
        $this->paths = array_merge($this->paths, $paths);

        return $this;
    }

    public function addPath($path)
    {
        $this->paths[] = $path;

        return $this;
    }

    public function getPaths()
    {
        return $this->paths;
    }

    public function addExtension($extension, $compiler = null)
    {
        $this->compilers[$extension] = $compiler;

        return $this;
    }

    public function getExtensions()
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

    public function hasCompiler($extension)
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
    public function getCompiler($extension)
    {
        if (!$this->hasCompiler($extension)) {
            throw new ViewException('View compiler for extension `' . $extension . '` not found.');
        }

        $compiler = &$this->compilers[$extension];

        if (is_callable($compiler)) {
            $compiler = Obj::call($compiler, $this);
        }

        if (!($compiler instanceof CompilerStrategy)) {
            throw new ViewException('View compiler for extension `' . $extension . '` should be an instance of `' . CompilerStrategy::class . '`.');
        }

        return $compiler;
    }

    public function getCompilers()
    {
        return array_filter($this->compilers);
    }

    public function getCompilersExtensions()
    {
        return array_keys($this->getCompilers());
    }

    public function removeCompiledFiles()
    {
        foreach ($this->getCompilersExtensions() as $extension) {
            $this->getCompiler($extension)->removeCompiledFiles();
        }

        return $this;
    }

    public function directive($name, callable $callable)
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

    public function hasDirective($name)
    {
        return array_key_exists($name, $this->directives);
    }

    public function format($name, ...$args)
    {
        if (!$this->hasDirective($name)) {
            throw new ViewException('Directive `' . $name . '` is not defined.');
        }

        return Obj::call($this->directives[$name], ...$args);
    }

    public function __set($key, $value)
    {
        return $this->set($key, $value);
    }

    public function __get($key)
    {
        return $this->get($key);
    }
}
