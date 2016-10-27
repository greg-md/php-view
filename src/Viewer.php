<?php

namespace Greg\View;

use Greg\Support\Accessor\ArrayAccessTrait;
use Greg\Support\Http\Response;
use Greg\Support\Obj;

class Viewer implements \ArrayAccess
{
    use ArrayAccessTrait;

    protected $paths = [];

    protected $compilers = [
        '.php'   => null,
        '.html'  => null,
        '.phtml' => null,
    ];

    protected $directives = [];

    public function __construct($path, array $params = [])
    {
        $this->setPaths((array) $path);

        $this->assign($params);

        return $this;
    }

    public function render($name, array $params = [])
    {
        return new Response($this->renderContent($name, $params));
    }

    public function renderIfExists($name, array $params = [])
    {
        return new Response($this->renderContentIfExists($name, $params));
    }

    public function renderContent($name, array $params = [])
    {
        if ($file = $this->getCompiledFile($name)) {
            return $this->renderFileContent($file, $params);
        }

        throw new \Exception('View file `' . $name . '` does not exist in view paths.');
    }

    public function renderContentIfExists($name, array $params = [])
    {
        if ($file = $this->getCompiledFile($name)) {
            return $this->renderFileContent($file, $params);
        }

        return null;
    }

    protected function renderFileContent($file, array $params = [])
    {
        $renderer = new ViewRenderer($this, $file, $params + $this->getParams());

        return (new ViewRendererLoader($renderer))->_l_o_a_d_();
    }

    public function getCompiledFile($name)
    {
        foreach ($this->getPaths() as $path) {
            if (!is_dir($path)) {
                continue;
            }

            foreach ($this->getExtensions() as $extension) {
                if (is_file($file = $path . DIRECTORY_SEPARATOR . ltrim($name . $extension, '\/'))) {
                    if ($compiler = $this->getCompiler($extension)) {
                        $file = $compiler->getCompiledFile($file);
                    }

                    return $file;
                }
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

    /**
     * @param $extension
     *
     * @throws \Exception
     *
     * @return CompilerInterface
     */
    public function getCompiler($extension)
    {
        if (!array_key_exists($extension, $this->compilers)) {
            throw new \Exception('View compiler for extension `' . $extension . '` not found.');
        }

        $compiler = &$this->compilers[$extension];

        if (is_callable($compiler)) {
            $compiler = Obj::callCallableWith($compiler, $this);
        }

        if ($compiler and !($compiler instanceof CompilerInterface)) {
            throw new \Exception('View compiler for extension `' . $extension . '` should be an instance of `CompilerInterface`.');
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

        return $this;
    }

    public function hasDirective($name)
    {
        return array_key_exists($name, $this->directives);
    }

    public function format($name, ...$args)
    {
        if (!$this->hasDirective($name)) {
            throw new \Exception('Directive `' . $name . '` is not defined.');
        }

        return Obj::callCallable($this->directives[$name], ...$args);
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
