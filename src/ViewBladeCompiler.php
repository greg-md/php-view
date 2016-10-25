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

    public function directive($name, callable $callable)
    {
        $this->viewer->directive($name, $callable);

        $this->addOptionalStatement($name, function ($expr = null) use ($name) {
            return $this->compileFormat('"' . addslashes($name) . '", ' . $expr);
        });

        return $this;
    }

    protected function setup()
    {
        $this->addStatements([
            'extends'         => 'compileExtends',
            'section'         => 'compileSection',
            'yield'           => 'compileYield',
            'render'          => 'compileRender',
            'renderIfExists'  => 'compileRenderIfExists',
            'partial'         => 'compilePartial',
            'partialIfExists' => 'compilePartialIfExists',
            'each'            => 'compileEach',
            'eachIfExists'    => 'compileEachIfExists',

            'push'  => 'compilePush',
            'stack' => 'compileStack',
        ]);

        $this->addEmptyStatements([
            'content'    => 'compileContent',
            'parent'     => 'compileParent',
            'endsection' => 'compileEndSection',
            'show'       => 'compileShow',
            'endpush'    => 'compileEndPush',
        ]);

        $this->addOptionalStatements([
            'format' => 'compileFormat',
        ]);
    }

    protected function compileRender($expr)
    {
        return '<?php echo $this->render(' . $expr . ')?>';
    }

    protected function compileRenderIfExists($expr)
    {
        return '<?php echo $this->renderIfExists(' . $expr . ')?>';
    }

    protected function compilePartial($expr)
    {
        return '<?php echo $this->partial(' . $expr . ')?>';
    }

    protected function compilePartialIfExists($expr)
    {
        return '<?php echo $this->partialIfExists(' . $expr . ')?>';
    }

    protected function compileEach($expr)
    {
        return '<?php echo $this->each(' . $expr . ')?>';
    }

    protected function compileEachIfExists($expr)
    {
        return '<?php echo $this->eachIfExists(' . $expr . ')?>';
    }

    protected function compileExtends($name)
    {
        return '<?php $this->extend(' . $name . ')?>';
    }

    protected function compileContent()
    {
        return '<?php echo $this->content()?>';
    }

    protected function compileSection($expr)
    {
        return '<?php $this->section(' . $expr . ')?>';
    }

    protected function compileParent()
    {
        return '<?php echo $this->parent()?>';
    }

    protected function compileEndSection()
    {
        return '<?php $this->endSection()?>';
    }

    protected function compileShow()
    {
        return '<?php echo $this->show()?>';
    }

    protected function compileYield($expr)
    {
        return '<?php echo $this->getSection(' . $expr . ')?>';
    }

    protected function compilePush($expr)
    {
        return '<?php $this->push(' . $expr . ')?>';
    }

    protected function compileEndPush()
    {
        return '<?php $this->endPush()?>';
    }

    protected function compileStack($expr)
    {
        return '<?php echo $this->stack(' . $expr . ')?>';
    }

    protected function compileFormat($expr)
    {
        return '<?php echo $this->format(' . $expr . ')?>';
    }
}
