<?php

namespace Greg\View\Tests;

use Greg\View\Renderer;
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

        $this->assertArrayHasKey('foo', $this->viewer->assigned());
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

    /** @test */
    public function it_adds_custom_directives()
    {
        $this->viewer->directive('eco', function ($content) {
            return $content;
        });

        $this->renderedStringEquals('Hello World!', '@eco("Hello World!")');

        $renderer = new Renderer($this->viewer, null);

        $this->assertEquals('Hello World!', $renderer->eco('Hello World!'));
    }

    /** @test */
    public function it_renders_inside_view()
    {
        $this->renderedStringEquals('Hello World!', '@render("default")');

        $this->renderedStringEquals('Hello World!', 'Hello @renderString("world.blade.php", "World")!');

        $this->renderedStringEquals('', '@renderIfExists("undefined")');

        $this->renderedStringEquals('Hello !', 'Hello @renderStringIfExists("world.undefined", "World")!');
    }

    /** @test */
    public function it_partials_inside_view()
    {
        $this->renderedStringEquals('Hello World!', 'Hello @partial("world")!', ['world' => 'World']);

        $this->renderedStringEquals('Hello World!', 'Hello @partialString("world.blade.php", "World")!');

        $this->renderedStringEquals('', '@partialIfExists("undefined")');

        $this->renderedStringEquals('World', '@partialIfExists("world")');

        $this->renderedStringEquals('Hello World!', 'Hello @partialStringIfExists("world.blade.php", "World")!');

        $this->renderedStringEquals('Hello !', 'Hello @partialStringIfExists("world.undefined", "World")!');
    }

    /** @test */
    public function it_throws_an_exception_if_partial_not_found()
    {
        $this->expectException(ViewException::class);

        $this->renderString('@partial("undefined")');
    }

    /** @test */
    public function it_throws_an_exception_if_partial_string_not_found()
    {
        $this->expectException(ViewException::class);

        $this->renderString('@partialString("undefined", "Hello World!")');
    }

    /** @test */
    public function it_executes_each_directive()
    {
        $this->renderedStringEquals('Hello World!Hello World!', '@each("default", [1, 2])');

        $this->renderedStringEquals('Hello World!Hello World!', '@eachIfExists("default", [1, 2])');

        $this->renderedStringEquals('', '@eachIfExists("undefined", [1, 2])');

        $this->renderedStringEquals('Hello World!Hello World!', '@eachString("hello-world.blade.php", "Hello World!", [1, 2])');

        $this->renderedStringEquals('Hello World!Hello World!', '@eachStringIfExists("hello-world.blade.php", "Hello World!", [1, 2])');

        $this->renderedStringEquals('', '@eachStringIfExists("hello-world.undefined", "Hello World!", [1, 2])');

        $this->renderedStringEquals('No items found!', '@eachString("hello-world.blade.php", "Hello World!", [], [], null, "empty.blade.php", "No items found!")');
    }

    /** @test */
    public function it_throws_an_exception_if_each_not_found()
    {
        $this->expectException(ViewException::class);

        $this->renderString('@each("undefined", [1, 2])');
    }

    /** @test */
    public function it_throws_an_exception_if_each_string_not_found()
    {
        $this->expectException(ViewException::class);

        $this->renderString('@eachString("undefined", "Hello World!", [1, 2])');
    }

    /** @test */
    public function it_extends_an_view()
    {
        $this->renderedStringEquals('Hello World!', '@extends("hello")World');

        $this->renderedStringEquals('Hello World!', '@extendsString("hello.blade.php", "Hello @content!")World');
    }

    /** @test */
    public function it_throws_an_exception_if_extends_undefined_view()
    {
        $this->expectException(ViewException::class);

        $this->renderString('@extends("undefined")');
    }

    /** @test */
    public function it_uses_sections()
    {
        $this->renderedStringEquals('Hello World!', '@section("hello-world");@parent;Hello World!@endsection;@yield("hello-world");');

        $this->renderedStringEquals('Hello World!', '@section("hello-world")Hello World!@show');

        $this->renderedStringEquals('Hello World!', '@section("hello-world", "Hello World!")@yield("hello-world")');
    }

    /** @test */
    public function it_cannot_use_sections_in_sections()
    {
        $this->expectException(ViewException::class);

        $this->expectExceptionMessage('You cannot have a section in another section.');

        $this->renderString('@section("hello-world")Hello World!@section("second")Huh@endsection@show');
    }

    /** @test */
    public function it_cannot_end_undefined_sections()
    {
        $this->expectException(ViewException::class);

        $this->renderString('@endsection');
    }

    /** @test */
    public function it_cannot_show_undefined_sections()
    {
        $this->expectException(ViewException::class);

        $this->renderString('@show');
    }

    /** @test */
    public function it_uses_pushes()
    {
        $this->renderedStringEquals('Hello World!', '@push("hello")Hello@endpush@push("hello") World!@endpush@stack("hello")');

        $this->renderedStringEquals('Hello World!', '@push("hello", "Hello")@push("hello", " World!")@stack("hello")');
    }

    /** @test */
    public function it_cannot_push_in_another_push()
    {
        $this->expectException(ViewException::class);

        $this->renderString('@push("hello")Hello@push("world")@endpush@endpush');
    }

    /** @test */
    public function it_cannot_end_undefined_push()
    {
        $this->expectException(ViewException::class);

        $this->renderString('@endpush');
    }

    protected function renderedStringEquals($expected, $actual, array $params = [])
    {
        $this->assertEquals($expected, $this->renderString($actual, $params));
    }

    protected function renderString($string, array $params = [])
    {
        return $this->viewer->renderString('test.blade.php', $string, $params);
    }
}
