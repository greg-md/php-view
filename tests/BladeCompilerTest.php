<?php

namespace Greg\View\Tests;

use Greg\View\BladeCompiler;
use Greg\View\ViewException;
use PHPUnit\Framework\TestCase;

class ExtendedBladeCompiler extends BladeCompiler
{
    public function boot()
    {
        $this->bootCompilers();

        $this->bootDirectives();

        $this->bootOptionalDirectives();

        $this->bootEmptyDirectives();

        return parent::boot();
    }

    public function bootCompilers()
    {
        $compilers = $this->getCompilers();

        $this->setCompilers([]);

        $this->addCompilers($compilers);
    }

    public function bootDirectives()
    {
        $directives = $this->getDirectives();

        $this->setDirectives([]);

        $this->addDirectives($directives);
    }

    public function bootOptionalDirectives()
    {
        $directives = $this->getOptionalDirectives();

        $this->setOptionalDirectives([]);

        $this->addOptionalDirectives($directives);
    }

    public function bootEmptyDirectives()
    {
        $directives = $this->getEmptyDirectives();

        $this->setEmptyDirectives([]);

        $this->addEmptyDirectives($directives);
    }
}

class BladeCompilerTest extends TestCase
{
    /**
     * @var BladeCompiler
     */
    private $compiler = null;

    public function setUp()
    {
        parent::setUp();

        $this->compiler = new ExtendedBladeCompiler(__DIR__ . '/compiled');
    }

    public function tearDown()
    {
        parent::tearDown();

        foreach (glob(__DIR__ . '/compiled/*.php') as $file) {
            unlink($file);
        }
    }

    /** @test */
    public function it_changes_compilation_path()
    {
        $this->compiler->setCompilationPath(__DIR__ . '/custom');

        $this->assertEquals(__DIR__ . '/custom', $this->compiler->getCompilationPath());
    }

    /** @test */
    public function it_gets_compiled_file()
    {
        $file = __DIR__ . '/view/default.blade.php';

        $compiledFile = __DIR__ . '/compiled/6e0ef330951b91930b6aaa22edf9986e.php';

        $this->assertEquals($compiledFile, $this->compiler->getCompiledFile($file));

        // Gets already existent file
        $this->assertEquals($compiledFile, $this->compiler->getCompiledFile($file));
    }

    /** @test */
    public function it_gets_compiled_file_from_string()
    {
        // Coverage
        $this->compiler->getCompiledFileFromString('test', 'Hello World!');

        $this->assertEquals(
            __DIR__ . '/compiled/098f6bcd4621d373cade4e832627b4f6.php',
            $this->compiler->getCompiledFileFromString('test', 'Hello World!')
        );
    }

    /** @test */
    public function it_throws_an_error_if_file_not_found()
    {
        $this->expectException(ViewException::class);

        $this->compiler->getCompiledFile(__DIR__ . '/undefined.file');
    }

    /** @test */
    public function it_removes_compiled_files()
    {
        $this->compiler->getCompiledFile(__DIR__ . '/view/default.blade.php');

        $this->compiler->removeCompiledFiles();

        $this->assertEmpty(glob(__DIR__ . '/compiled/*.php'));
    }

    /** @test */
    public function it_adds_custom_compiler()
    {
        $this->compiler->addCompiler(function ($content) {
            return str_replace('[SPLIT]', '<!-- Split content -->', $content);
        });

        $this->renderedStringEquals('Hello World! <!-- Split content -->', 'Hello World! [SPLIT]');
    }

    /** @test */
    public function it_adds_custom_directives()
    {
        $this->compiler->addDirective('eco', function ($content) {
            return '<?php echo ' . $content . '?>';
        });

        $this->compiler->addEmptyDirective('br', function () {
            return '<br />';
        });

        $this->compiler->addOptionalDirective('comment', function ($comment = null) {
            return '<!-- <?php echo ' . ($comment ?: '""') . '?> -->';
        });

        $this->renderedStringEquals(
            'Hello World! <br /> <!--  --> <!-- This is a comment -->',
            '@eco("Hello World!") @br @comment @comment("This is a comment")'
        );
    }

    /** @test */
    public function it_renders_comments()
    {
        $this->renderedStringEquals('Hello World!', 'Hello {{-- I am a comment --}}World!');
    }

    /** @test */
    public function it_renders_raw_content()
    {
        $this->renderedStringEquals('I am a raw <strong>echo</strong>.', '{!! "I am a raw <strong>echo</strong>." !!}');
    }

    /** @test */
    public function it_renders_content()
    {
        $this->renderedStringEquals(
            htmlentities('I am a raw <strong>echo</strong>.'),
            '{{ "I am a raw <strong>echo</strong>." }}'
        );
    }

    /** @test */
    public function it_renders_or()
    {
        $this->renderedStringEquals('bar', '{{ $foo or "bar" }}');
    }

    /** @test */
    public function it_renders_if()
    {
        $this->renderedStringEquals('foo', '@if(true)foo@endif');

        $this->renderedStringEquals('bar', '@if(false)foo@elseif(true)bar@endif');

        $this->renderedStringEquals('bar', '@if(false)foo@else;bar@endif');
    }

    /** @test */
    public function it_renders_unless()
    {
        $this->renderedStringEquals('foo', '@unless(false)foo@endunless');

        $this->renderedStringEquals('bar', '@unless(true)foo@elseunless(false)bar@endunless');

        $this->renderedStringEquals('bar', '@unless(true)foo@else;bar@endunless');
    }

    /** @test */
    public function it_renders_for()
    {
        $this->renderedStringEquals('12345', '@for($i = 1; $i <= 5; ++$i){{ $i }}@endfor');
    }

    /** @test */
    public function it_renders_foreach()
    {
        $this->renderedStringEquals(
            '345',
            '@foreach([1, 2, 3, 4, 5] as $i)@if($i === 1)@continue@endif@continue($i === 2){{ $i }}@endforeach'
        );

        $this->renderedStringEquals(
            '1234',
            '@foreach([1, 2, 3, 4, 5] as $i)@if($i === 5)@break@endif{{ $i }}@endforeach'
        );

        $this->renderedStringEquals(
            '1234',
            '@foreach([1, 2, 3, 4, 5] as $i)@break($i === 5){{ $i }}@endforeach'
        );

        $this->renderedStringEquals(
            'Empty',
            '@foreach([] as $i){{ $i }}@empty;Empty@endforeach'
        );

        $this->renderedStringEquals(
            '1234last',
            '@foreach([1, 2, 3, 4, 5] as $i, $iterator){{ $iterator->last ? "last" : $i }}@endforeach'
        );
    }

    /** @test */
    public function it_renders_while()
    {
        $this->renderedStringEquals(
            '11',
            '<?php $array = [1, 2, 3]?>@while(array_shift($array))1@if(count($array) === 1)@stop@endif@endwhile'
        );
    }

    /** @test */
    public function it_renders_switch()
    {
        $this->renderedStringEquals('bar', '@switch("bar")@case("foo")foo@break@case("bar")bar@break@endswitch');

        $this->renderedStringEquals('default', '@switch("bar")@case("foo")foo@break@default;default@break@endswitch');
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
}
