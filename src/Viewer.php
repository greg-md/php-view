<?php

namespace Greg\View;

use Greg\Support\Accessor\ArrayAccessTrait;
use Greg\Support\Http\Response;
use Greg\Support\Obj;
use Greg\Support\Str;

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

    public function render($name, array $params = [], $returnAsString = false)
    {
        if ($file = $this->getFile($name)) {
            return $this->renderFile($file, $params, $returnAsString);
        }

        throw new \Exception('View file `' . $name . '` does not exist in view paths.');
    }

    public function renderIfExists($name, array $params = [], $returnAsString = false)
    {
        if ($file = $this->getFile($name)) {
            return $this->renderFile($file, $params, $returnAsString);
        }

        return null;
    }

    public function renderFile($file, array $params = [], $returnAsString = false)
    {
        $content = $this->newRenderer($file, $params)->load();

        return $returnAsString ? new Response($content) : $content;
    }

    public function getRenderer($name, array $params = [])
    {
        if ($file = $this->getFile($name)) {
            return $this->getRendererFile($file, $params);
        }

        throw new \Exception('View file `' . $name . '` does not exist in view paths.');
    }

    public function getRendererIfExists($name, array $params = [])
    {
        if ($file = $this->getFile($name)) {
            return $this->getRendererFile($file, $params);
        }

        return null;
    }

    public function getRendererFile($file, array $params = [])
    {
        return $this->newRenderer($file, $params);
    }

    protected function newRenderer($file, array $params = [])
    {
        $renderer = new ViewRenderer($this);

        $renderer->register($this->getCompiledFile($file), $params + $this->getAccessor());

        return $renderer;
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

    public function setPaths(array $paths)
    {
        $this->paths = $paths;

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

    /**
     * @param $extension
     * @return CompilerInterface
     * @throws \Exception
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

    protected function getExtensions()
    {
        return array_keys($this->compilers);
    }

    protected function getCompilers()
    {
        return array_filter($this->compilers);
    }

    protected function getCompilersExtensions()
    {
        return array_keys($this->getCompilers());
    }

    public function getFile($name)
    {
        foreach ($this->getPaths() as $path) {
            if (!is_dir($path)) {
                continue;
            }

            foreach ($this->getExtensions() as $extension) {
                if (is_file($file = $path . DIRECTORY_SEPARATOR . ltrim($name . $extension, '\/'))) {
                    return $file;
                }
            }
        }

        return false;
    }

    public function getCompilerByFile($file)
    {
        $extensions = $this->getCompilersExtensions();

        usort($extensions, function ($a, $b) {
            return gmp_cmp(mb_strlen($a), mb_strlen($b)) * -1;
        });

        foreach ($extensions as $extension) {
            if (Str::endsWith($file, $extension)) {
                return $this->getCompiler($extension);
            }
        }

        return false;
    }

    public function getCompiledFile($file)
    {
        if ($compiler = $this->getCompilerByFile($file)) {
            $file = $compiler->getCompiledFile($file);
        }

        return $file;
    }

    public function clearCompiledFiles()
    {
        foreach($this->getCompilersExtensions() as $extension) {
            $this->getCompiler($extension)->clearCompiledFiles();
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
