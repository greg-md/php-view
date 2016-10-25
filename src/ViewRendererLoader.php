<?php

namespace Greg\View;

use Greg\Support\Obj;

class ViewRendererLoader
{
    protected $renderer = null;

    public function __construct(ViewRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    public function load()
    {
        ob_start();

        try {
            extract(func_get_arg(0));

            include func_get_arg(1);

            $content = ob_get_clean();

            if ($extended = $this->renderer->getExtended()) {
                $renderer = $this->renderer->getViewer()->getRenderer($extended);

                $renderer->setContent($content);

                $renderer->setStacks($this->renderer->getStacks());

                $renderer->setSections($this->renderer->getSections());

                $content = $renderer->load();
            }

            return $content;
        } catch (\Exception $e) {
            ob_end_clean();

            throw $e;
        }
    }

    public function __call($name, $arguments)
    {
        return Obj::callCallable([$this->renderer, $name], $arguments);
    }
}