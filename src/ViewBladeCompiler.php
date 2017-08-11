<?php

namespace Greg\View;

use Greg\Support\Str;

class ViewBladeCompiler extends BladeCompiler implements ViewCompilerStrategy
{
    public function addViewDirective(string $name)
    {
        return parent::addOptionalDirective($name, function (?string $expr = null) use ($name) {
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

    protected function compileRender(string $expr): string
    {
        return '<?php echo $this->render(' . $expr . ')?>';
    }

    protected function compileRenderIfExists(string $expr): string
    {
        return '<?php echo $this->renderIfExists(' . $expr . ')?>';
    }

    protected function compilePartial(string $expr): string
    {
        return '<?php echo $this->partial(' . $expr . ')?>';
    }

    protected function compilePartialIfExists(string $expr): string
    {
        return '<?php echo $this->partialIfExists(' . $expr . ')?>';
    }

    protected function compileRenderString(string $expr): string
    {
        return '<?php echo $this->renderString(' . $expr . ')?>';
    }

    protected function compileRenderStringIfExists(string $expr): string
    {
        return '<?php echo $this->renderStringIfExists(' . $expr . ')?>';
    }

    protected function compilePartialString(string $expr): string
    {
        return '<?php echo $this->partialString(' . $expr . ')?>';
    }

    protected function compilePartialStringIfExists(string $expr): string
    {
        return '<?php echo $this->partialStringIfExists(' . $expr . ')?>';
    }

    protected function compileEach(string $expr): string
    {
        return '<?php echo $this->each(' . $expr . ')?>';
    }

    protected function compileEachIfExists(string $expr): string
    {
        return '<?php echo $this->eachIfExists(' . $expr . ')?>';
    }

    protected function compileEachString(string $expr): string
    {
        return '<?php echo $this->eachString(' . $expr . ')?>';
    }

    protected function compileEachStringIfExists(string $expr): string
    {
        return '<?php echo $this->eachStringIfExists(' . $expr . ')?>';
    }

    protected function compileExtends(string $expr): string
    {
        return '<?php $this->extend(' . $expr . ')?>';
    }

    protected function compileExtendsString(string $expr): string
    {
        return '<?php $this->extendString(' . $expr . ')?>';
    }

    protected function compileContent(): string
    {
        return '<?php echo $this->content()?>';
    }

    protected function compileSection(string $expr): string
    {
        return '<?php $this->section(' . $expr . ')?>';
    }

    protected function compileParent(): string
    {
        return '<?php echo $this->parent()?>';
    }

    protected function compileEndSection(): string
    {
        return '<?php $this->endSection()?>';
    }

    protected function compileShow(): string
    {
        return '<?php echo $this->show()?>';
    }

    protected function compileYield(string $expr): string
    {
        return '<?php echo $this->getSection(' . $expr . ')?>';
    }

    protected function compilePush(string $expr): string
    {
        return '<?php $this->push(' . $expr . ')?>';
    }

    protected function compileEndPush(): string
    {
        return '<?php $this->endPush()?>';
    }

    protected function compileStack(string $expr): string
    {
        return '<?php echo $this->stack(' . $expr . ')?>';
    }

    protected function compileFormat(string $expr): string
    {
        return '<?php echo $this->format(' . $expr . ')?>';
    }
}
