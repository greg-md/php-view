<?php

namespace Greg\View;

class ViewRenderer
{
    protected $rendererFile = null;

    protected $rendererParams = [];

    protected $viewer = null;

    public function __construct(Viewer $viewer)
    {
        $this->viewer = $viewer;
    }

    public function render($name, array $params = [])
    {
        return $this->partial($name, $params + $this->rendererParams);
    }

    public function renderIfExists($name, array $params = [])
    {
        return $this->partialIfExists($name, $params + $this->rendererParams);
    }

    public function renderFile($file, array $params = [])
    {
        return $this->partialFile($file, $params + $this->rendererParams);
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

    public function partialFile($file, array $params)
    {
        $renderer = new self($this->viewer);

        $renderer->registerRenderer($this->viewer->getCompiledFile($file), $params);

        return $renderer->loadRenderer();
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

    public function loadRenderer()
    {
        ob_start();

        try {
            extract($this->rendererParams);

            include $this->rendererFile;

            return ob_get_clean();
        } catch (\Exception $e) {
            ob_end_clean();

            throw $e;
        }
    }

    public function registerRenderer($file, array $params)
    {
        $this->rendererFile = $file;

        $this->rendererParams = $params;

        return $this;
    }
}
