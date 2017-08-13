<?php

namespace Greg\View;

use Greg\Support\Arr;
use Greg\Support\File;
use Greg\Support\Tools\InNamespaceRegex;

class BladeCompiler implements CompilerStrategy
{
    private const PHP_VAR_REGEX = '\$+[a-z_][a-z0-9_]*';

    private $compilationPath;

    private $compilers = [
        'compileDirectives',
        'compileComments',
        'compileRawEchos',
        'compileContentEchos',
    ];

    private $directives = [
        'if'            => 'compileIf',
        'elseif'        => 'compileElseIf',
        'unless'        => 'compileUnless',
        'elseunless'    => 'compileElseUnless',
        'for'           => 'compileFor',
        'while'         => 'compileWhile',

        'foreach'       => 'compileForeach',
        // Fallback blade template syntax
        'foreachloop'   => 'compileForeach',

        'switch' => 'compileSwitch',
        'case'   => 'compileCase',
    ];

    private $emptyDirectives = [
        'endif'     => 'compileEndIf',
        'endunless' => 'compileEndUnless',
        'endfor'    => 'compileEndFor',
        'endwhile'  => 'compileEndWhile',

        'empty'         => 'compileEmpty',
        'endforeach'    => 'compileEndForeach',

        // Fallback blade template syntax
        'endforeachloop'    => 'compileEndForeach',

        'default'   => 'compileDefault',
        'endswitch' => 'compileEndSwitch',

        'else' => 'compileElse',
        'stop' => 'compileStop',
    ];

    private $optionalDirectives = [
        'break'     => 'compileBreak',
        'continue'  => 'compileContinue',
    ];

    private $foreachEmptyVars = [];

    private $foreachLoopVars = [];

    private $verbatim = [];

    public function __construct(string $compilationPath)
    {
        $this->setCompilationPath($compilationPath);

        $this->boot();
    }

    public function compilationPath()
    {
        return $this->compilationPath;
    }

    protected function boot()
    {
    }

    public function getCompiledFile(string $file): string
    {
        $compilationFile = $this->getCompilationFile($file);

        if ($this->isFileExpired($file, $compilationFile)) {
            $this->save($compilationFile, $this->compileFile($file));
        }

        return $compilationFile;
    }

    public function getCompiledFileFromString(string $id, string $string): string
    {
        $compiledFile = $this->getCompilationFile($id);

        if ($this->isStringExpired($string, $compiledFile)) {
            $this->saveString($compiledFile, $string, $this->compileString($string));
        }

        return $compiledFile;
    }

    public function removeCompiledFiles()
    {
        foreach (glob($this->compilationPath . '/*.php') as $file) {
            unlink($file);
        }

        return $this;
    }

    public function compileFile(string $file): string
    {
        if (!file_exists($file)) {
            throw new ViewException('Blade file `' . $file . '` not found.');
        }

        return $this->compileString(file_get_contents($file));
    }

    public function compileString(string $string): string
    {
        $result = '';

        // Here we will loop through all of the tokens returned by the Zend lexer and
        // parse each one into the corresponding valid PHP. We will then have this
        // template as the correctly rendered PHP that can be rendered natively.
        foreach (token_get_all($string) as $token) {
            $result .= is_array($token) ? $this->parseToken($token) : $token;
        }

        return $result;
    }

    public function addCompiler(callable $callable)
    {
        $this->compilers[] = $callable;

        return $this;
    }

    public function addDirective(string $name, callable $compiler)
    {
        $this->directives[$name] = $compiler;

        return $this;
    }

    public function addEmptyDirective(string $name, callable $compiler)
    {
        $this->emptyDirectives[$name] = $compiler;

        return $this;
    }

    public function addOptionalDirective(string $name, callable $compiler)
    {
        $this->optionalDirectives[$name] = $compiler;

        return $this;
    }

    protected function compileVerbatim(string $content): string
    {
        return preg_replace_callback('#(?<!@)@verbatim(.*?)@endverbatim#is', function ($matches) {
            $this->verbatim[] = $matches[1];

            return '@__verbatim__@';
        }, $content);
    }

    protected function restoreVerbatim(string $content): string
    {
        return preg_replace_callback('#@__verbatim__@#', function () {
            return array_shift($this->verbatim);
        }, $content);
    }

    protected function compileComments(string $string): string
    {
        $regex = $this->inNamespaceRegex('{{--', '--}}');

        return preg_replace_callback('#(@)?(' . $regex . ')#s', function ($matches) {
            return $matches[1] ? $matches[2] : $this->compileComment($matches['captured']);
        }, $string);
    }

    protected function compileComment(string $string): string
    {
        return '<?php /* ' . $string . ' */ ?>';
    }

    protected function compileRawEchos(string $string): string
    {
        $regex = $this->inNamespaceRegex('{!!', '!!}');

        return preg_replace_callback('#(@)?(' . $regex . ')#s', function ($matches) {
            return $matches[1] ? $matches[2] : $this->compileRawEcho($matches['captured']);
        }, $string);
    }

    protected function compileContentEchos(string $string): string
    {
        $regex = $this->inNamespaceRegex('{{', '}}');

        return preg_replace_callback('#(@)?(' . $regex . ')#s', function ($matches) {
            return $matches[1] ? $matches[2] : $this->compileContentEcho($matches['captured']);
        }, $string);
    }

    protected function compileRawEcho(string $string): string
    {
        return '<?php echo ' . $this->parseOr($string) . '; ?>';
    }

    protected function compileContentEcho(string $string): string
    {
        return '<?php echo htmlentities(' . $this->parseOr($string) . '); ?>';
    }

    protected function compileDirectives(string $string): string
    {
        $directives = array_map('preg_quote', array_merge(
            array_keys($this->directives),
            array_keys($this->optionalDirectives),
            array_keys($this->emptyDirectives)
        ));

        usort($directives, function ($a, $b) {
            $a = mb_strlen($a);

            $b = mb_strlen($b);

            return $a > $b ? -1 : ($b > $a ? 1 : 0);
        });

        $directives = implode('|', $directives);

        $exprNamespace = $this->inNamespaceRegex('(', ')');

        $exprNamespace->recursive('recursive');

        $pattern = "@(?'directive'{$directives})" . "(?:[\\s\\t]*(?'recursive'{$exprNamespace}))?;?";

        return preg_replace_callback('#' . $pattern . '#is', function ($matches) {
            if (isset($this->directives[$matches['directive']])) {
                $callable = $this->directives[$matches['directive']];

                if (!isset($matches['captured'])) {
                    throw new ViewException('Parameters in `' . $matches['directive'] . '` directive are required.');
                }

                $args = [$matches['captured']];
            } elseif (isset($this->optionalDirectives[$matches['directive']])) {
                $callable = $this->optionalDirectives[$matches['directive']];

                $args = [];

                if (isset($matches['captured'])) {
                    $args[] = $matches['captured'];
                }
            } else {
                $callable = $this->emptyDirectives[$matches['directive']];

                $args = [];
            }

            if (!is_callable($callable) and is_scalar($callable)) {
                $callable = [$this, $callable];
            }

            return call_user_func_array($callable, $args);
        }, $string);
    }

    protected function compileIf(string $expr): string
    {
        return '<?php if(' . $expr . '): ?>';
    }

    protected function compileElseIf(string $expr): string
    {
        return '<?php elseif(' . $expr . '): ?>';
    }

    protected function compileEndIf(): string
    {
        return '<?php endif; ?>';
    }

    protected function compileUnless(string $expr): string
    {
        return '<?php if(!(' . $expr . ')): ?>';
    }

    protected function compileElseUnless(string $expr): string
    {
        return '<?php elseif(!(' . $expr . ')): ?>';
    }

    protected function compileEndUnless(): string
    {
        return '<?php endif; ?>';
    }

    protected function compileElse(): string
    {
        return '<?php else: ?>';
    }

    protected function compileFor(string $expr): string
    {
        return '<?php for(' . $expr . '): ?>';
    }

    protected function compileEndFor(): string
    {
        return '<?php endfor; ?>';
    }

    protected function compileForeach(string $expr): string
    {
        $this->foreachEmptyVars[$emptyVar = $this->uniqueVar('foreachEmpty')] = false;

        $parentLoopVar = Arr::last($this->foreachLoopVars) ?: 'null';

        $this->foreachLoopVars[] = $loopVar = $this->uniqueVar('foreachLoop');

        if (preg_match('#(.+)\s+as\s+(.+),\s*(' . self::PHP_VAR_REGEX . ')$#is', $expr, $matches)) {
            $iterator = $matches[1];

            $iteratorAlias = $matches[2];

            $realLoopVar = $matches[3];

            $iterationVar = $this->uniqueVar('foreachIterator');

            $iterationCountVar = $this->uniqueVar('foreachIteratorCount');

            $depth = count($this->foreachLoopVars);

            return "<?php
                {$emptyVar} = true;
                
                {$iterationVar} = {$iterator};
                
                {$iterationCountVar} = count({$iterationVar});
                
                {$loopVar} = (object)[
                    'iteration' => 0,
                    'index' => 0,
                    'remaining' => {$iterationCountVar},
                    'count' => {$iterationCountVar},
                    'first' => true,
                    'last' => {$iterationCountVar} == 1,
                    'depth' => {$depth},
                    'parent' => {$parentLoopVar},
                ];
                
                foreach({$iterationVar} as {$iteratorAlias}):
                    {$emptyVar} = false;
                    
                    ++{$loopVar}->iteration;
                    
                    {$loopVar}->index = {$loopVar}->iteration - 1;
                    
                    {$loopVar}->first = {$loopVar}->iteration == 1;
                    
                    --{$loopVar}->remaining;
                    
                    {$loopVar}->last = {$loopVar}->iteration == {$loopVar}->count;
                    
                    {$realLoopVar} = {$loopVar};
            ?>";
        }

        return "<?php {$emptyVar} = true; foreach({$expr}): {$emptyVar} = false; ?>";
    }

    protected function compileEmpty(): string
    {
        $this->foreachEmptyVars[$lastKey = Arr::lastKey($this->foreachEmptyVars)] = true;

        return '<?php endforeach; if(' . $lastKey . '): ?>';
    }

    protected function compileEndForeach(): string
    {
        array_pop($this->foreachLoopVars);

        if (array_pop($this->foreachEmptyVars)) {
            return '<?php endif; ?>';
        }

        return '<?php endforeach; ?>';
    }

    protected function compileWhile(string $expr): string
    {
        return '<?php while(' . $expr . '): ?>';
    }

    protected function compileEndWhile(): string
    {
        return '<?php endwhile; ?>';
    }

    protected function compileStop(): string
    {
        return '<?php return; ?>';
    }

    protected function compileSwitch(string $expr): string
    {
        return '<?php switch(' . $expr . '): case "' . uniqid(null, true) . '": break; ?>';
    }

    protected function compileCase(string $expr): string
    {
        return '<?php case ' . $expr . ': ?>';
    }

    protected function compileContinue(?string $expr = null): string
    {
        if ($expr) {
            return '<?php if(' . $expr . ') continue; ?>';
        }

        return '<?php continue; ?>';
    }

    protected function compileBreak(?string $expr = null): string
    {
        if ($expr) {
            return '<?php if(' . $expr . ') break; ?>';
        }

        return '<?php break; ?>';
    }

    protected function compileDefault(): string
    {
        return '<?php default: ?>';
    }

    protected function compileEndSwitch(): string
    {
        return '<?php endswitch; ?>';
    }

    protected function inNamespaceRegex(string $start, string $end = null): InNamespaceRegex
    {
        $pattern = new InNamespaceRegex($start, $end ?: $start);

        $pattern->capture('captured');

        $pattern->disableInQuotes();

        $pattern->newLines();

        $pattern->trim();

        return $pattern;
    }

    protected function uniqueVar(string $name): string
    {
        return '$' . $name . str_replace('.', '_', uniqid(null, true));
    }

    protected function addCompilers(array $compilers)
    {
        $this->compilers = array_merge($this->compilers, $compilers);

        return $this;
    }

    protected function addDirectives(array $directives)
    {
        $this->directives = array_merge($this->directives, $directives);

        return $this;
    }

    protected function addEmptyDirectives(array $directives)
    {
        $this->emptyDirectives = array_merge($this->emptyDirectives, $directives);

        return $this;
    }

    protected function addOptionalDirectives(array $directives)
    {
        $this->optionalDirectives = array_merge($this->optionalDirectives, $directives);

        return $this;
    }

    private function setCompilationPath(string $compilationPath)
    {
        if (($compilationPath = realpath($compilationPath)) === false) {
            throw new ViewException('Blade compilation path should be a real path.');
        }

        $this->compilationPath = $compilationPath;

        return $this;
    }

    private function saveString(string $compiledFile, string $string, string $compiledContent)
    {
        $this->save($compiledFile, $compiledContent);

        file_put_contents($this->templateStringFile($compiledFile), $string);

        return $this;
    }

    private function save(string $compiledFile, string $compiledContent)
    {
        File::makeDir($compiledFile);

        file_put_contents($compiledFile, $compiledContent);

        return $this;
    }

    private function getCompilationFile(string $id): string
    {
        return $this->compilationPath . DIRECTORY_SEPARATOR . md5($id) . '.php';
    }

    private function templateStringFile(string $file)
    {
        return pathinfo($file, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . pathinfo($file, PATHINFO_FILENAME) . '.blade.php';
    }

    private function isFileExpired(string $file, string $compiledFile): bool
    {
        if (!file_exists($file)) {
            return true;
        }

        if (!file_exists($compiledFile)) {
            return true;
        }

        return filemtime($file) > filemtime($compiledFile);
    }

    private function isStringExpired(string $string, string $compiledFile): bool
    {
        if (!file_exists($compiledFile)) {
            return true;
        }

        return $string !== file_get_contents($this->templateStringFile($compiledFile));
    }

    /**
     * Parse the tokens from the template.
     *
     * @param array $token
     *
     * @return string
     */
    private function parseToken(array $token): string
    {
        list($id, $content) = $token;

        if ($id == T_INLINE_HTML) {
            $content = $this->compileVerbatim($content);

            foreach ($this->compilers as $callable) {
                if (!is_callable($callable) and is_scalar($callable)) {
                    $callable = [$this, $callable];
                }

                $content = (string) call_user_func_array($callable, [$content]);
            }

            $content = $this->restoreVerbatim($content);
        }

        return $content;
    }

    private function parseOr(string $string): string
    {
        if (preg_match('#^(' . self::PHP_VAR_REGEX . ')\s+or\s+(.+)$#is', $string, $matches)) {
            $string = 'isset(' . $matches[1] . ') ? ' . $matches[1] . ' : ' . $matches[2];
        }

        return $string;
    }
}
