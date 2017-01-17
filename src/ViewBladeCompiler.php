<?php

namespace Greg\View;

use Greg\Support\Str;

class ViewBladeCompiler extends BladeCompiler implements ViewCompilerStrategy
{
    public function addViewDirective($name)
    {
        return parent::addOptionalDirective($name, function ($expr = null) use ($name) {
            return $this->compileFormat('"' . addslashes($name) . '"' . (Str::isEmpty($expr) ? '' : ', ' . $expr));
        });
    }

    protected function boot()
    {
        $this->addDirectives([
            'extends'               => 'compileExtends',
            'extendsString'         => 'compileExtendsString',
            'section'               => 'compileSection',
            'yield'                 => 'compileYield',
            'render'                => 'compileRender',
            'renderIfExists'        => 'compileRenderIfExists',
            'partial'               => 'compilePartial',
            'partialIfExists'       => 'compilePartialIfExists',
            'renderString'          => 'compileRenderString',
            'renderStringIfExists'  => 'compileRenderStringIfExists',
            'partialString'         => 'compilePartialString',
            'partialStringIfExists' => 'compilePartialStringIfExists',
            'each'                  => 'compileEach',
            'eachIfExists'          => 'compileEachIfExists',
            'eachString'            => 'compileEachString',
            'eachStringIfExists'    => 'compileEachStringIfExists',

            'push'  => 'compilePush',
            'stack' => 'compileStack',
        ]);

        $this->addEmptyDirectives([
            'content'    => 'compileContent',
            'parent'     => 'compileParent',
            'endsection' => 'compileEndSection',
            'show'       => 'compileShow',
            'endpush'    => 'compileEndPush',
        ]);

        $this->addOptionalDirectives([
            'format' => 'compileFormat',
        ]);

        return parent::boot();
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

    protected function compileRenderString($expr)
    {
        return '<?php echo $this->renderString(' . $expr . ')?>';
    }

    protected function compileRenderStringIfExists($expr)
    {
        return '<?php echo $this->renderStringIfExists(' . $expr . ')?>';
    }

    protected function compilePartialString($expr)
    {
        return '<?php echo $this->partialString(' . $expr . ')?>';
    }

    protected function compilePartialStringIfExists($expr)
    {
        return '<?php echo $this->partialStringIfExists(' . $expr . ')?>';
    }

    protected function compileEach($expr)
    {
        return '<?php echo $this->each(' . $expr . ')?>';
    }

    protected function compileEachIfExists($expr)
    {
        return '<?php echo $this->eachIfExists(' . $expr . ')?>';
    }

    protected function compileEachString($expr)
    {
        return '<?php echo $this->eachString(' . $expr . ')?>';
    }

    protected function compileEachStringIfExists($expr)
    {
        return '<?php echo $this->eachStringIfExists(' . $expr . ')?>';
    }

    protected function compileExtends($expr)
    {
        return '<?php $this->extend(' . $expr . ')?>';
    }

    protected function compileExtendsString($expr)
    {
        return '<?php $this->extendString(' . $expr . ')?>';
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
