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

    public function partialLoop($name, array $values, array $params = [])
    {
        if ($file = $this->viewer->getFile($name)) {
            return $this->partialFileLoop($file, $values, $params);
        }

        throw new \Exception('View file `' . $name . '` does not exist in view paths.');
    }

    public function partialLoopIfExists($name, array $values, array $params = [])
    {
        if ($file = $this->viewer->getFile($name)) {
            return $this->partialFileLoop($file, $values, $params);
        }

        return null;
    }

    public function partialFileLoop($file, array $values, array $params = [])
    {
        $content = [];

        foreach ($values as $key => $value) {
            $content[] = $this->partialFile($file, $params + [
                'key'   => $key,
                'value' => $value,
            ]);
        }

        return implode('', $content);
    }

    protected function setExtended($name)
    {
        $this->extended = (string) $name;

        return $this;
    }

    protected function getExtended()
    {
        return $this->extended;
    }

    protected function setContent($content)
    {
        $this->content = (string) $content;

        return $this;
    }

    protected function getContent()
    {
        return $this->content;
    }

    protected function content()
    {
        echo $this->getContent();

        return $this;
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

    public function section($name, $content = null)
    {
        if ($this->currentSection) {
            throw new \Exception('You can not have another section in a section.');
        }

        if (func_num_args() > 1) {
            $this->sections[$name] = $content;
        } else {
            $this->currentSection = $name;

            ob_start();
        }

        return $this;
    }

    public function parentSection()
    {
        $this->loadSection($this->currentSection);

        return $this;
    }

    public function endSection()
    {
        if (!$this->currentSection) {
            throw new \Exception('You can not end an undefined section.');
        }

        $this->sections[$this->currentSection] = ob_get_clean();

        $this->currentSection = null;

        return $this;
    }

    public function displaySection()
    {
        if (!$this->currentSection) {
            throw new \Exception('You can not end an undefined section.');
        }

        $this->loadSection($this->currentSection, ob_get_clean());

        $this->currentSection = null;

        return $this;
    }

    public function loadSection($name, $else = null)
    {
        echo $this->hasSection($name) ? $this->sections[$name] : $else;

        return $this;
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

                $renderer
                    ->setContent($content)
                    ->setSections($this->getSections());

                $content = $renderer->load();
            }

            return $content;
        } catch (\Exception $e) {
            ob_end_clean();

            throw $e;
        }
    }
}
