<?php

namespace Greg\View\Tests;

use Greg\View\ViewBladeCompiler;
use Greg\View\Viewer;
use Greg\View\ViewException;
use PHPUnit\Framework\TestCase;

class FooCompiler
{
}

class ViewerTest extends TestCase
{
    /**
     * @var Viewer
     */
    private $viewer = null;

    public function setUp()
    {
        parent::setUp();

        $this->viewer = new Viewer(__DIR__ . '/view');

        $this->viewer->addExtension('.blade.php', function () {
            return new ViewBladeCompiler(__DIR__ . '/compiled');
        });
    }

    public function tearDown()
    {
        parent::tearDown();

        foreach (glob(__DIR__ . '/compiled/*.php') as $file) {
            unlink($file);
        }
    }

    /** @test */
    public function it_renders()
    {
        $this->assertEquals('Hello World!', $this->viewer->render('default'));
    }

    /** @test */
    public function it_throws_an_exception_if_file_does_not_exists()
    {
        $this->expectException(ViewException::class);

        $this->viewer->render('undefined');
    }

    /** @test */
    public function it_assigns_as_variable()
    {
        $this->viewer->assign('foo', 'bar');

        $this->assertArrayHasKey('foo', $this->viewer->getParams());
    }

    /** @test */
    public function it_throws_an_exception_if_compiler_not_found()
    {
        $this->expectException(ViewException::class);

        $this->viewer->getCompiler('.undefined');
    }

    /** @test */
    public function it_throws_an_exception_if_compiler_is_not_instanceof_compiler_strategy()
    {
        $this->expectException(ViewException::class);

        $this->viewer->addExtension('.foo', function () {
            return new FooCompiler();
        });

        $this->viewer->getCompiler('.foo');
    }
}
