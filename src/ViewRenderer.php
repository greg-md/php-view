<?php

namespace Greg\View;

class ViewRenderer
{
    protected $file = null;

    protected $params = [];

    protected $viewer = null;

    protected $extended = null;

    protected $content = null;

    protected $sections = [];

    protected $currentSection = null;

    protected $stacks = [];

    protected $currentStack = null;

    public function __construct(Viewer $viewer)
    {
        $this->viewer = $viewer;
    }

    public function render($name, array $params = [])
    {
        return $this->partial($name, $params + $this->params);
    }

    public function renderIfExists($name, array $params = [])
    {
        return $this->partialIfExists($name, $params + $this->params);
    }

    public function renderFile($file, array $params = [])
    {
        return $this->partialFile($file, $params + $this->params);
    }

    public function partial($name, array $params = [])
    {
        if ($file = $this->viewer->getFile($name)) {
            return $this->partialFile($file, $params);
        }

        throw new \Exception('View file `' . $name . '` does not exist in view paths.');
    }

    public function partialIfExists($name, array $params = [])
    {
        if ($file = $this->viewer->getFile($name)) {
            return $this->partialFile($file, $params);
        }

        return null;
    }

    public function partialFile($file, array $params = [])
    {
        $renderer = new self($this->viewer);

        $renderer->register($this->viewer->getCompiledFile($file), $params);

        return $renderer->load();
    }

    public function each($name, array $values, $valueName = null, $emptyName = null, array $params = [])
    {
        if ($file = $this->viewer->getFile($name)) {
            if ($emptyName) {
                $emptyName = $this->viewer->getFile($emptyName);
            }

            return $this->eachFile($file, $values, $valueName, $emptyName, $params);
        }

        throw new \Exception('View file `' . $name . '` does not exist in view paths.');
    }

    public function eachIfExists($name, array $values, $valueName = null, $emptyName = null, array $params = [])
    {
        if ($file = $this->viewer->getFile($name)) {
            if ($emptyName) {
                $emptyName = $this->viewer->getFile($emptyName);
            }

            return $this->eachFile($file, $values, $valueName, $emptyName, $params);
        }

        return null;
    }

    public function eachFile($file, array $values, $valueName = null, $emptyFile = null, array $params = [])
    {
        $content = [];

        foreach ($values as $key => $value) {
            $content[] = $this->partialFile($file, $params + [
                $valueName ?: 'value' => $value,
            ]);
        }

        if (!$content and $emptyFile) {
            $content[] = $this->partialFile($emptyFile, $params);
        }

        return implode('', $content);
    }

    protected function extend($name)
    {
        $this->extended = (string) $name;

        return $this;
    }

    protected function content()
    {
        echo $this->getContent();

        return $this;
    }

    public function section($name, $content = null)
    {
        if ($this->currentSection) {
            throw new \Exception('You cannot have a section in another section.');
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
        $this->yieldSection($this->currentSection);

        return $this;
    }

    public function endSection()
    {
        if (!$this->currentSection) {
            throw new \Exception('You cannot end an undefined section.');
        }

        $this->sections[$this->currentSection] = ob_get_clean();

        $this->currentSection = null;

        return $this;
    }

    public function show()
    {
        if (!$this->currentSection) {
            throw new \Exception('You cannot end an undefined section.');
        }

        $this->yieldSection($this->currentSection, ob_get_clean());

        $this->currentSection = null;

        return $this;
    }

    public function yieldSection($name, $else = null)
    {
        echo $this->hasSection($name) ? $this->sections[$name] : $else;

        return $this;
    }

    public function push($name, $content = null)
    {
        if ($this->currentStack) {
            throw new \Exception('You cannot have a stack in another stack.');
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
            throw new \Exception('You cannot end an undefined stack.');
        }

        $this->stacks[$this->currentStack][] = ob_get_clean();

        $this->currentStack = null;

        return $this;
    }

    public function stack($name, $else = null)
    {
        echo $this->hasStack($name) ? implode('', $this->stacks[$name]) : $else;

        return $this;
    }

    public function format($name, ...$args)
    {
        $this->viewer->format($name, ...$args);
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

    public function hasStack($name)
    {
        return array_key_exists($name, $this->stacks);
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

    public function setStacks(array $stacks)
    {
        $this->stacks = $stacks;

        return $this;
    }

    public function getStacks()
    {
        return $this->stacks;
    }

    public function hasSection($name)
    {
        return array_key_exists($name, $this->sections);
    }

    public function register($file, array $params = [])
    {
        $this->file = $file;

        $this->params = $params;

        return $this;
    }

    public function load()
    {
        ob_start();

        try {
            extract($this->params);

            include $this->file;

            $content = ob_get_clean();

            if ($extended = $this->getExtended()) {
                $renderer = $this->viewer->getRenderer($extended);

                $renderer->setContent($content);

                $renderer->setStacks($this->getStacks());

                $renderer->setSections($this->getSections());

                $content = $renderer->load();
            }

            return $content;
        } catch (\Exception $e) {
            ob_end_clean();

            throw $e;
        }
    }
}
