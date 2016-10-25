<?php

namespace Greg\View;

use Greg\Support\Obj;

class ViewRendererLoader
{
    protected $__r__e__n__d__e__r__e__r__ = null;

    public function __construct(ViewRenderer $renderer)
    {
        $this->__r__e__n__d__e__r__e__r__ = $renderer;
    }

    public function __l__o__a__d__()
    {
        ob_start();

        try {
            extract($this->__r__e__n__d__e__r__e__r__->getParams());

            include $this->__r__e__n__d__e__r__e__r__->getFile();

            $content = ob_get_clean();

            if ($extended = $this->__r__e__n__d__e__r__e__r__->getExtended()) {
                $renderer = $this->__r__e__n__d__e__r__e__r__->getViewer()->getRenderer($extended);

                $renderer->setContent($content);

                $renderer->setStacks($this->__r__e__n__d__e__r__e__r__->getStacks());

                $renderer->setSections($this->__r__e__n__d__e__r__e__r__->getSections());

                $content = (new self($renderer))->__l__o__a__d__();
            }

            return $content;
        } catch (\Exception $e) {
            ob_end_clean();

            throw $e;
        }
    }

    public function __call($name, $arguments)
    {
        return Obj::callCallable([$this->__r__e__n__d__e__r__e__r__, $name], $arguments);
    }
}
