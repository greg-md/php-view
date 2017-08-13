<?php

namespace Greg\View;

use Greg\Support\Dir;
use PHPUnit\Framework\TestCase;

class BladeCompilerTest extends TestCase
{
    private $compilationPath = __DIR__ . '/compiled';

    public function setUp()
    {
        Dir::make($this->compilationPath);
    }

    public function tearDown()
    {
        Dir::unlink($this->compilationPath);
    }

    public function testCanCompileVerbatim()
    {
        $compiler = new BladeCompiler($this->compilationPath);

        $this->renderedStringEquals(
            $compiler,
            '{{ Hello! }}',
            '@verbatim{{ Hello! }}@endverbatim'
        );
    }

    public function testCanThrowExceptionIfParametersInDirectiveAreRequired()
    {
        $compiler = new BladeCompiler($this->compilationPath);

        $this->expectException(ViewException::class);

        $this->renderString($compiler, '@if');
    }

    public function testCanAddCustomCompilers()
    {
        $compilationPath = $this->compilationPath;

        $compiler = new class($compilationPath) extends BladeCompiler {
            public $compiled = false;

            protected function boot()
            {
                $this->addCompilers([
                    'customCompiler',
                ]);
            }

            protected function customCompiler()
            {
                $this->compiled = true;
            }
        };

        $this->renderString($compiler, 'foo');

        $this->assertTrue($compiler->compiled);
    }

    public function testCanAddCustomDirectives()
    {
        $compilationPath = $this->compilationPath;

        $compiler = new class($compilationPath) extends BladeCompiler {
            public $compiled = false;

            protected function boot()
            {
                $this->addDirectives([
                    'foo' => 'compileFoo',
                ]);
            }

            protected function compileFoo()
            {
                $this->compiled = true;
            }
        };

        $this->renderString($compiler, '@foo()');

        $this->assertTrue($compiler->compiled);
    }

    public function testCanAddCustomEmptyDirectives()
    {
        $compilationPath = $this->compilationPath;

        $compiler = new class($compilationPath) extends BladeCompiler {
            public $compiled = false;

            protected function boot()
            {
                $this->addEmptyDirectives([
                    'foo' => 'compileFoo',
                ]);
            }

            protected function compileFoo()
            {
                $this->compiled = true;
            }
        };

        $this->renderString($compiler, '@foo');

        $this->assertTrue($compiler->compiled);
    }

    public function testCanAddCustomOptionalDirectives()
    {
        $compilationPath = $this->compilationPath;

        $compiler = new class($compilationPath) extends BladeCompiler {
            public $compiled = false;

            protected function boot()
            {
                $this->addOptionalDirectives([
                    'foo' => 'compileFoo',
                ]);
            }

            protected function compileFoo()
            {
                $this->compiled = true;
            }
        };

        $this->renderString($compiler, '@foo');

        $this->assertTrue($compiler->compiled);
    }

    public function testCanThrowExceptionIfCompilationPathIsNotARealPath()
    {
        $this->expectException(ViewException::class);

        new BladeCompiler('/undefined');
    }

    /** @test */
    public function it_gets_compiled_file()
    {
        $compiler = new BladeCompiler($this->compilationPath);

        $file = __DIR__ . '/view/default.blade.php';

        $compiledFile = __DIR__ . '/compiled/' . md5($file) . '.php';

        $this->assertEquals($compiledFile, $compiler->getCompiledFile($file));

        // Gets already existent file
        $this->assertEquals($compiledFile, $compiler->getCompiledFile($file));
    }

    /** @test */
    public function it_gets_compiled_file_from_string()
    {
        $compiler = new BladeCompiler($this->compilationPath);

        $this->assertEquals(
            __DIR__ . '/compiled/098f6bcd4621d373cade4e832627b4f6.php',
            $compiler->getCompiledFileFromString('test', 'Hello World!')
        );
    }

    /** @test */
    public function it_throws_an_error_if_file_not_found()
    {
        $compiler = new BladeCompiler($this->compilationPath);

        $this->expectException(ViewException::class);

        $compiler->getCompiledFile(__DIR__ . '/undefined.file');
    }

    /** @test */
    public function it_removes_compiled_files()
    {
        $compiler = new BladeCompiler($this->compilationPath);

        $compiler->getCompiledFile(__DIR__ . '/view/default.blade.php');

        $compiler->removeCompiledFiles();

        $this->assertEmpty(glob(__DIR__ . '/compiled/*.php'));
    }

    /** @test */
    public function it_adds_custom_compiler()
    {
        $compiler = new BladeCompiler($this->compilationPath);

        $compiler->addCompiler(function ($content) {
            return str_replace('[SPLIT]', '<!-- Split content -->', $content);
        });

        $this->renderedStringEquals($compiler, 'Hello World! <!-- Split content -->', 'Hello World! [SPLIT]');
    }

    /** @test */
    public function it_adds_custom_directives()
    {
        $compiler = new BladeCompiler($this->compilationPath);

        $compiler->addDirective('eco', function ($content) {
            return '<?php echo ' . $content . '?>';
        });

        $compiler->addEmptyDirective('br', function () {
            return '<br />';
        });

        $compiler->addOptionalDirective('comment', function ($comment = null) {
            return '<!-- <?php echo ' . ($comment ?: '""') . '?> -->';
        });

        $this->renderedStringEquals(
            $compiler,
            'Hello World! <br /> <!--  --> <!-- This is a comment -->',
            '@eco("Hello World!") @br @comment @comment("This is a comment")'
        );
    }

    /** @test */
    public function it_renders_comments()
    {
        $compiler = new BladeCompiler($this->compilationPath);

        $this->renderedStringEquals($compiler, 'Hello World!', 'Hello {{-- I am a comment --}}World!');
    }

    /** @test */
    public function it_renders_raw_content()
    {
        $compiler = new BladeCompiler($this->compilationPath);

        $this->renderedStringEquals($compiler, 'I am a raw <strong>echo</strong>.', '{!! "I am a raw <strong>echo</strong>." !!}');
    }

    /** @test */
    public function it_renders_content()
    {
        $compiler = new BladeCompiler($this->compilationPath);

        $this->renderedStringEquals(
            $compiler,
            htmlentities('I am a raw <strong>echo</strong>.'),
            '{{ "I am a raw <strong>echo</strong>." }}'
        );
    }

    /** @test */
    public function it_renders_or()
    {
        $compiler = new BladeCompiler($this->compilationPath);

        $this->renderedStringEquals($compiler, 'bar', '{{ $foo or "bar" }}');
    }

    /** @test */
    public function it_renders_if()
    {
        $compiler = new BladeCompiler($this->compilationPath);

        $this->renderedStringEquals($compiler, 'foo', '@if(true)foo@endif');

        $this->renderedStringEquals($compiler, 'bar', '@if(false)foo@elseif(true)bar@endif');

        $this->renderedStringEquals($compiler, 'bar', '@if(false)foo@else;bar@endif');
    }

    /** @test */
    public function it_renders_unless()
    {
        $compiler = new BladeCompiler($this->compilationPath);

        $this->renderedStringEquals($compiler, 'foo', '@unless(false)foo@endunless');

        $this->renderedStringEquals($compiler, 'bar', '@unless(true)foo@elseunless(false)bar@endunless');

        $this->renderedStringEquals($compiler, 'bar', '@unless(true)foo@else;bar@endunless');
    }

    /** @test */
    public function it_renders_for()
    {
        $compiler = new BladeCompiler($this->compilationPath);

        $this->renderedStringEquals($compiler, '12345', '@for($i = 1; $i <= 5; ++$i){{ $i }}@endfor');
    }

    /** @test */
    public function it_renders_foreach()
    {
        $compiler = new BladeCompiler($this->compilationPath);

        $this->renderedStringEquals(
            $compiler,
            '345',
            '@foreach([1, 2, 3, 4, 5] as $i)@if($i === 1)@continue@endif@continue($i === 2){{ $i }}@endforeach'
        );

        $this->renderedStringEquals(
            $compiler,
            '1234',
            '@foreach([1, 2, 3, 4, 5] as $i)@if($i === 5)@break@endif{{ $i }}@endforeach'
        );

        $this->renderedStringEquals(
            $compiler,
            '1234',
            '@foreach([1, 2, 3, 4, 5] as $i)@break($i === 5){{ $i }}@endforeach'
        );

        $this->renderedStringEquals(
            $compiler,
            'Empty',
            '@foreach([] as $i){{ $i }}@empty;Empty@endforeach'
        );

        $this->renderedStringEquals(
            $compiler,
            '1234last',
            '@foreach([1, 2, 3, 4, 5] as $i, $iterator){{ $iterator->last ? "last" : $i }}@endforeach'
        );
    }

    /** @test */
    public function it_renders_while()
    {
        $compiler = new BladeCompiler($this->compilationPath);

        $this->renderedStringEquals(
            $compiler,
            '11',
            '<?php $array = [1, 2, 3]?>@while(array_shift($array))1@if(count($array) === 1)@stop@endif@endwhile'
        );
    }

    /** @test */
    public function it_renders_switch()
    {
        $compiler = new BladeCompiler($this->compilationPath);

        $this->renderedStringEquals($compiler, 'bar', '@switch("bar")@case("foo")foo@break@case("bar")bar@break@endswitch');

        $this->renderedStringEquals($compiler, 'default', '@switch("bar")@case("foo")foo@break@default;default@break@endswitch');
    }

    protected function renderedStringEquals(BladeCompiler $compiler, $expected, $actual)
    {
        $this->assertEquals($expected, $this->renderString($compiler, $actual));
    }

    protected function renderString(BladeCompiler $compiler, $string)
    {
        return $this->render($compiler->getCompiledFileFromString('test', $string));
    }

    protected function renderedFileEquals(BladeCompiler $compiler, $expected, $actual)
    {
        $this->assertEquals($expected, $this->renderFile($compiler, $actual));
    }

    protected function renderFile(BladeCompiler $compiler, $file)
    {
        return $this->render($compiler->getCompiledFile($file));
    }

    protected function render($file)
    {
        ob_start();

        require $file;

        return ob_get_clean();
    }
}
