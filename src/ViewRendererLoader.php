<?php

namespace Greg\View;

class ViewRendererLoader
{
    protected $_r_e_n_d_e_r_e_r_ = null;

    public function __construct(ViewRenderer $renderer)
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
        } catch (\Exception $e) {
            ob_end_clean();

            throw $e;
        }

        if ($extended = $this->_r_e_n_d_e_r_e_r_->getExtended()) {
            $viewer = $this->_r_e_n_d_e_r_e_r_->getViewer();

            if (is_array($extended)) {
                if (!$file = $viewer->getCompiledFileFromString($extended['id'], $extended['string'])) {
                    throw new ViewException('Could not find a compiler for view `' . $extended['id'] . '`.');
                }
            } else {
                if (!$file = $viewer->getCompiledFile($extended)) {
                    throw new ViewException('View file `' . $extended . '` does not exist in view paths.');
                }
            }

            $extendedRenderer = new ViewRenderer($viewer, $file, $viewer->getParams());

            $extendedRenderer->setContent($content);

            $extendedRenderer->setSections($this->_r_e_n_d_e_r_e_r_->getSections());

            $extendedRenderer->setStacks($this->_r_e_n_d_e_r_e_r_->getStacks());

            $content = (new self($extendedRenderer))->_l_o_a_d_();
        }

        return $content;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->_r_e_n_d_e_r_e_r_, $name], $arguments);
    }
}
