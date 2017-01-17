<?php

namespace Greg\View;

class ViewRenderer
{
    protected $file = null;

    protected $params = [];

    /**
     * @var Viewer
     */
    protected $viewer = null;

    protected $extended = null;

    protected $content = null;

    protected $sections = [];

    protected $currentSection = null;

    protected $stacks = [];

    protected $currentStack = null;

    public function __construct(ViewerContract $viewer, $file, array $params = [])
    {
        $this->setViewer($viewer);

        $this->setFile($file);

        $this->setParams($params);
    }

    public function render($name, array $params = [])
    {
        return $this->partial($name, $params + $this->params);
    }

    public function renderIfExists($name, array $params = [])
    {
        return $this->partialIfExists($name, $params + $this->params);
    }

    public function partial($name, array $params = [])
    {
        if ($file = $this->viewer->getCompiledFile($name)) {
            return $this->partialFile($file, $params);
        }

        throw new ViewException('View file `' . $name . '` does not exist in view paths.');
    }

    public function partialIfExists($name, array $params = [])
    {
        if ($file = $this->viewer->getCompiledFile($name)) {
            return $this->partialFile($file, $params);
        }

        return null;
    }

    public function renderString($id, $string, array $params = [])
    {
        return $this->partialString($id, $string, $params + $this->params);
    }

    public function renderStringIfExists($id, $string, array $params = [])
    {
        return $this->partialStringIfExists($id, $string, $params + $this->params);
    }

    public function partialString($id, $string, array $params = [])
    {
        if ($file = $this->viewer->getCompiledFileFromString($id, $string)) {
            return $this->partialFile($file, $params);
        }

        throw new ViewException('Could not find a compiler for view `' . $id . '`.');
    }

    public function partialStringIfExists($id, $string, array $params = [])
    {
        if ($file = $this->viewer->getCompiledFileFromString($id, $string)) {
            return $this->partialFile($file, $params);
        }

        return null;
    }

    protected function partialFile($file, array $params = [])
    {
        $renderer = (new self($this->viewer, $file, $params + $this->viewer->assigned()));

        return (new ViewRendererLoader($renderer))->_l_o_a_d_();
    }

    public function each($name, array $values, array $params = [], $valueKeyName = null, $emptyName = null)
    {
        if ($file = $this->viewer->getCompiledFile($name)) {
            $emptyFile = $emptyName ? $this->viewer->getCompiledFile($emptyName) : null;

            return $this->eachFile($file, $values, $params, $valueKeyName, $emptyFile);
        }

        throw new ViewException('View file `' . $name . '` does not exist in view paths.');
    }

    public function eachIfExists($name, array $values, array $params = [], $valueKeyName = null, $emptyName = null)
    {
        if ($file = $this->viewer->getCompiledFile($name)) {
            $emptyFile = $emptyName ? $this->viewer->getCompiledFile($emptyName) : null;

            return $this->eachFile($file, $values, $params, $valueKeyName, $emptyFile);
        }

        return null;
    }

    public function eachString($id, $string, array $values, array $params = [], $valueKeyName = null, $emptyId = null, $emptyString = null)
    {
        if ($file = $this->viewer->getCompiledFileFromString($id, $string)) {
            $emptyFile = $emptyId ? $this->viewer->getCompiledFileFromString($emptyId, $emptyString) : null;

            return $this->eachFile($file, $values, $params, $valueKeyName, $emptyFile);
        }

        throw new ViewException('Could not find a compiler for view `' . $id . '`.');
    }

    public function eachStringIfExists($id, $string, array $values, array $params = [], $valueKeyName = null, $emptyId = null, $emptyString = null)
    {
        if ($file = $this->viewer->getCompiledFileFromString($id, $string)) {
            $emptyFile = $emptyId ? $this->viewer->getCompiledFileFromString($emptyId, $emptyString) : null;

            return $this->eachFile($file, $values, $params, $valueKeyName, $emptyFile);
        }

        return null;
    }

    protected function eachFile($file, array $values, array $params = [], $valueKeyName = null, $emptyFile = null)
    {
        $content = [];

        foreach ($values as $key => $value) {
            $content[] = $this->partialFile($file, $params + [
                $valueKeyName ?: 'value' => $value,
            ]);
        }

        if (!$content and $emptyFile) {
            $content[] = $this->partialFile($emptyFile, $params);
        }

        return implode('', $content);
    }

    public function extend($name)
    {
        return $this->setExtended($name);
    }

    public function extendString($id, $string)
    {
        return $this->setExtendedString($id, $string);
    }

    public function content()
    {
        return $this->getContent();
    }

    public function section($name, $content = null)
    {
        if ($this->currentSection) {
            ob_get_clean();

            throw new ViewException('You cannot have a section in another section.');
        }

        if (func_num_args() > 1) {
            $this->sections[$name] = $content;
        } else {
            $this->currentSection = $name;

            ob_start();
        }

        return $this;
    }

    public function parent()
    {
        return $this->getSection($this->currentSection);
    }

    public function endSection()
    {
        if (!$this->currentSection) {
            throw new ViewException('You cannot end an undefined section.');
        }

        $this->sections[$this->currentSection] = ob_get_clean();

        $this->currentSection = null;

        return $this;
    }

    public function show()
    {
        if (!$this->currentSection) {
            throw new ViewException('You cannot end an undefined section.');
        }

        $content = $this->getSection($this->currentSection, ob_get_clean());

        $this->currentSection = null;

        return $content;
    }

    public function getSection($name, $else = null)
    {
        return $this->hasSection($name) ? $this->sections[$name] : $else;
    }

    public function push($name, $content = null)
    {
        if ($this->currentStack) {
            ob_get_clean();

            throw new ViewException('You cannot have a stack in another stack.');
        }

        if (func_num_args() > 1) {
            $this->stacks[$name][] = $content;
        } else {
            $this->currentStack = $name;

            ob_start();
        }

        return $this;
    }

    public function endPush()
    {
        if (!$this->currentStack) {
            throw new ViewException('You cannot end an undefined stack.');
        }

        $this->stacks[$this->currentStack][] = ob_get_clean();

        $this->currentStack = null;

        return $this;
    }

    public function stack($name, $else = null)
    {
        return $this->hasStack($name) ? implode('', $this->stacks[$name]) : $else;
    }

    public function format($name, ...$args)
    {
        return $this->viewer->format($name, ...$args);
    }

    public function setViewer(ViewerContract $viewer)
    {
        $this->viewer = $viewer;

        return $this;
    }

    public function getViewer()
    {
        return $this->viewer;
    }

    public function setParams(array $params)
    {
        $this->params = $params;

        return $this;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function setFile($file)
    {
        $this->file = (string) $file;

        return $this;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function setExtended($name)
    {
        $this->extended = (string) $name;

        return $this;
    }

    public function setExtendedString($id, $name)
    {
        $this->extended = [
            'id'        => (string) $id,
            'string'    => (string) $name,
        ];

        return $this;
    }

    public function getExtended()
    {
        return $this->extended;
    }

    public function setContent($content)
    {
        $this->content = (string) $content;

        return $this;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setSections(array $sections)
    {
        $this->sections = $sections;

        return $this;
    }

    public function getSections()
    {
        return $this->sections;
    }

    public function hasSection($name)
    {
        return array_key_exists($name, $this->sections);
    }

    public function setStacks(array $stacks)
    {
        $this->stacks = $stacks;

        return $this;
    }

    public function getStacks()
    {
        return $this->stacks;
    }

    public function hasStack($name)
    {
        return array_key_exists($name, $this->stacks);
    }

    /*
    public function __call($name, $arguments)
    {
        return $this->format($name, ...$arguments);
    }
    */
}
