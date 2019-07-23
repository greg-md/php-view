<?php

namespace Greg\View;

use Greg\Support\Dir;
use PHPUnit\Framework\TestCase;

class ViewerTest extends TestCase
{
    private $compilationPath = __DIR__ . '/compiled';

    /**
     * @var Viewer
     */
    private $viewer = null;

    protected function setUp(): void
    {
        Dir::make($this->compilationPath);

        $this->viewer = new Viewer(__DIR__ . '/view');

        $this->viewer->addExtension('.blade.php', function () {
            return new ViewBladeCompiler(__DIR__ . '/compiled');
        });
    }

    protected function tearDown(): void
    {
        Dir::unlink($this->compilationPath);
    }

    public function testCanRenderFileIfExists()
    {
        $viewer = new Viewer(__DIR__ . '/view');

        $this->assertEquals('Hello World!', $this->viewer->renderIfExists('default'));

        $this->assertNull($viewer->renderIfExists('undefined'));
    }

    public function testCanThrowExceptionIfStringCompilerNotFound()
    {
        $viewer = new Viewer(__DIR__ . '/view');

        $this->expectException(ViewException::class);

        $viewer->renderString('undefined', 'Foo');
    }

    public function testCanRenderStringIfExists()
    {
        $viewer = new Viewer(__DIR__ . '/view');

        $this->assertEquals('Hello World!', $this->viewer->renderStringIfExists('hi.php', 'Hello World!'));

        $this->assertNull($viewer->renderStringIfExists('undefined', 'Hello World!'));
    }

    public function testCanAssignMultipleParameters()
    {
        $viewer = new Viewer(__DIR__ . '/view');

        $viewer->assignMultiple(['foo' => 'FOO', 'bar' => 'BAR']);

        $this->assertArrayHasKey('foo', $viewer->assigned());

        $this->assertArrayHasKey('bar', $viewer->assigned());
    }

    public function testCanDetermineIfHasAssignedValues()
    {
        $viewer = new Viewer(__DIR__ . '/view');

        $this->assertFalse($viewer->hasAssigned());

        $viewer->assign('foo', 'FOO');

        $this->assertTrue($viewer->hasAssigned());

        $this->assertFalse($viewer->hasAssigned('bar'));

        $this->assertTrue($viewer->hasAssigned('foo'));
    }

    public function testCanRemoveAssignedParameters()
    {
        $viewer = new Viewer(__DIR__ . '/view');

        $viewer->assign('foo', 'FOO');

        $this->assertTrue($viewer->hasAssigned('foo'));

        $viewer->removeAssigned('foo');

        $this->assertFalse($viewer->hasAssigned('foo'));

        $viewer->assignMultiple(['foo' => 'FOO', 'bar' => 'BAR']);

        $viewer->removeAssigned();

        $this->assertFalse($viewer->hasAssigned());
    }

    public function testCanSetPaths()
    {
        $viewer = new Viewer();

        $viewer->setPaths(__DIR__, __DIR__ . '/view');

        $this->assertEquals([__DIR__, __DIR__ . '/view'], $viewer->getPaths());
    }

    public function testCanAddPaths()
    {
        $viewer = new Viewer(__DIR__);

        $viewer->addPaths(__DIR__ . '/view', __DIR__ . '/view2');

        $this->assertEquals([__DIR__, __DIR__ . '/view', __DIR__ . '/view2'], $viewer->getPaths());
    }

    public function testCanRemoveCompiledFiles()
    {
        $viewer = new Viewer(__DIR__ . '/view');

        $viewer->addExtension('.blade.php', function () {
            return new ViewBladeCompiler($this->compilationPath);
        });

        $viewer->render('hello');

        $viewer->renderString('hello.php', 'Hello!');

        $this->assertNotEmpty(glob($this->compilationPath . '/*'));

        $viewer->removeCompiledFiles();

        $this->assertEmpty(glob($this->compilationPath . '/*'));
    }

    public function testCanThrowExceptionIfDirectiveNotFound()
    {
        $viewer = new Viewer();

        $this->expectException(ViewException::class);

        $viewer->format('undefined');
    }

    public function testCanActAsAnArray()
    {
        $viewer = new Viewer();

        $viewer['foo'] = 'FOO';

        $this->assertTrue(isset($viewer['foo']));

        $this->assertEquals('FOO', $viewer['foo']);

        $this->assertArrayHasKey('foo', $viewer->assigned());

        unset($viewer['foo']);

        $this->assertNull($viewer['foo']);

        $this->assertArrayNotHasKey('foo', $viewer->assigned());
    }

    public function testCanActParamsAsProperties()
    {
        $viewer = new Viewer();

        $viewer->foo = 'FOO';

        $this->assertEquals('FOO', $viewer->foo);

        $this->assertArrayHasKey('foo', $viewer->assigned());
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
            return new class() {
            };
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

        $renderer = new Renderer($this->viewer, __DIR__ . '/view/hello.blade.php');

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
