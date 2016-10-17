<?php

namespace Greg\View;

class ViewBladeCompiler extends BladeCompiler
{
    protected $viewer = null;

    public function __construct(Viewer $viewer, $compilationPath)
    {
        $this->viewer = $viewer;

        parent::__construct($compilationPath);

        $this->setup();
    }

    protected function setup()
    {
        $this->addStatements([
            'extends' => 'compileExtends',
            'section' => 'compileSection',
            'yield' => 'compileLoadSection',
        ]);

        $this->addEmptyStatements([
            'content' => 'compileContent',
            'parent' => 'compileParentSection',
            'endsection' => 'compileEndSection',
            'show' => 'compileDisplaySection',
        ]);
    }

    protected function compileExtends($name)
    {
        return '<?php $this->setExtended(' . $name . ')?>';
    }

    protected function compileContent()
    {
        return '<?php $this->content()?>';
    }

    protected function compileSection($expr)
    {
        return '<?php $this->section(' . $expr . ')?>';
    }

    protected function compileParentSection()
    {
        return '<?php $this->parentSection()?>';
    }

    protected function compileEndSection()
    {
        return '<?php $this->endSection()?>';
    }

    protected function compileDisplaySection()
    {
        return '<?php $this->displaySection()?>';
    }

    protected function compileLoadSection($expr)
    {
        return '<?php $this->loadSection(' . $expr . ')?>';
    }
}