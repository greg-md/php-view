<?php

namespace Greg\View;

use Greg\Support\Obj;

class ViewRendererLoader
{
    /**
     * @var ViewRenderer
     */
    protected $_r_e_n_d_e_r_e_r_ = null;

    public function _construct(ViewRenderer $renderer)
    {
        $this->_r_e_n_d_e_r_e_r_ = $renderer;
    }

    public function _l_o_a_d_()
    {
        ob_start();

        try {
            extract($this->_r_e_n_d_e_r_e_r_->getParams());

            include $this->_r_e_n_d_e_r_e_r_->getFile();

            $content = ob_get_clean();

            if ($extended = $this->_r_e_n_d_e_r_e_r_->getExtended()) {
                $viewer = $this->_r_e_n_d_e_r_e_r_->getViewer();

                if (!$file = $viewer->getCompiledFile($extended)) {
                    throw new \Exception('View file `' . $extended . '` does not exist in view paths.');
                }

                $extendedRenderer = new ViewRenderer($viewer, $file, $viewer->getParams());

                $extendedRenderer->setContent($content);

                $extendedRenderer->setSections($this->_r_e_n_d_e_r_e_r_->getSections());

                $extendedRenderer->setStacks($this->_r_e_n_d_e_r_e_r_->getStacks());

                $content = (new self($extendedRenderer))->_l_o_a_d_();
            }

            return $content;
        } catch (\Exception $e) {
            ob_end_clean();

            throw $e;
        }
    }

    public function __call($name, $arguments)
    {
        return Obj::callCallable([$this->_r_e_n_d_e_r_e_r_, $name], $arguments);
    }
}
