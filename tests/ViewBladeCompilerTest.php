<?php

namespace Greg\View\Tests;

use Greg\View\ViewBladeCompiler;
use PHPUnit\Framework\TestCase;

class ViewBladeCompilerTest extends TestCase
{
    /**
     * @var ViewBladeCompiler
     */
    private $compiler = null;

    public function setUp()
    {
        parent::setUp();

        $this->compiler = new ViewBladeCompiler(__DIR__ . '/compiled');
    }

    public function tearDown()
    {
        parent::tearDown();

        foreach (glob(__DIR__ . '/compiled/*.php') as $file) {
            unlink($file);
        }
    }

    /** @test */
    public function it_adds_custom_view_directives()
    {
        $this->compiler->addViewDirective('eco');

        $this->renderedStringEquals('Hello World!', '@eco("Hello World!")');
    }

    protected function renderedStringEquals($expected, $actual)
    {
        $this->assertEquals($expected, $this->renderString($actual));
    }

    protected function renderString($string)
    {
        return $this->render($this->compiler->getCompiledFileFromString('test', $string));
    }

    protected function renderedFileEquals($expected, $actual)
    {
        $this->assertEquals($expected, $this->renderFile($actual));
    }

    protected function renderFile($file)
    {
        return $this->render($this->compiler->getCompiledFile($file));
    }

    protected function render($file)
    {
        ob_start();

        require $file;

        return ob_get_clean();
    }

    protected function format($name, ...$args)
    {
        switch ($name) {
            case 'eco':
                return $this->formatEco(...$args);
        }

        return null;
    }

    protected function formatEco($content) {
        return $content;
    }
}