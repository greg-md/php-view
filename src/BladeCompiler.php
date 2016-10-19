<?php

namespace Greg\View;

use Greg\Support\Arr;
use Greg\Support\File;
use Greg\Support\Regex\InNamespaceRegex;

class BladeCompiler implements CompilerInterface
{
    const PHP_VAR_REGEX = '\$+[a-z_][a-z0-9_]*';

    protected $compilationPath = null;

    protected $compilers = [
        'compileStatements',
        'compileComments',
        'compileRawEchos',
        'compileContentEchos',
    ];

    protected $statements = [
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

    protected $emptyStatements = [
        'endif'     => 'compileEndIf',
        'endunless' => 'compileEndUnless',
        'endfor'    => 'compileEndFor',
        'endwhile'  => 'compileEndWhile',

        'forelse'       => 'compileForElse',
        'endforelse'    => 'compileEndForElse',
        'endforeach'    => 'compileEndForeach',

        // Fallback blade template syntax
        'forelseloop'       => 'compileForElse',
        'endforelseloop'    => 'compileEndForElse',
        'endforeachloop'    => 'compileEndForeach',

        'default'   => 'compileDefault',
        'endswitch' => 'compileEndSwitch',

        'else' => 'compileElse',
        'stop' => 'compileStop',
    ];

    protected $optionalStatements = [
        'break'     => 'compileBreak',
        'continue'  => 'compileContinue',
    ];

    protected $foreachEmptyVars = [];

    protected $foreachLoopVars = [];

    protected $verbatim = [];

    public function __construct($compilationPath)
    {
        $this->setCompilationPath($compilationPath);

        return $this;
    }

    public function getCompiledFile($file)
    {
        if ($this->expiredFile($file)) {
            $this->save($file, $this->compileFile($file));
        }

        return $this->getCompilationFile($file);
    }

    protected function getCompilationFileName($id)
    {
        return md5($id) . '.php';
    }

    protected function getCompilationFile($id)
    {
        return $this->getCompilationPath() . DIRECTORY_SEPARATOR . $this->getCompilationFileName($id);
    }

    protected function expiredFile($file)
    {
        if (!file_exists($file)) {
            return true;
        }

        $compilationFile = $this->getCompilationFile($file);

        if (!file_exists($compilationFile)) {
            return true;
        }

        return filemtime($file) > filemtime($compilationFile);
    }

    protected function save($id, $string)
    {
        $file = $this->getCompilationFile($id);

        File::fixFileDirRecursive($file);

        file_put_contents($file, $string);

        return $this;
    }

    protected function compileFile($file)
    {
        if (!file_exists($file)) {
            throw new \Exception('Blade file `' . $file . '` not found.');
        }

        return $this->compileString(file_get_contents($file));
    }

    protected function compileString($string)
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

    /**
     * Parse the tokens from the template.
     *
     * @param array $token
     *
     * @return string
     */
    protected function parseToken(array $token)
    {
        list($id, $content) = $token;

        if ($id == T_INLINE_HTML) {
            $content = $this->compileVerbatim($content);

            foreach ($this->compilers as $callable) {
                if (!is_callable($callable) and is_scalar($callable)) {
                    $callable = [$this, $callable];
                }

                $content = $this->callCallable($callable, $content);
            }

            $content = $this->restoreVerbatim($content);
        }

        return $content;
    }

    protected function compileVerbatim($content)
    {
        return preg_replace_callback('#(?<!@)@verbatim(.*?)@endverbatim#is', function ($matches) {
            $this->verbatim[] = $matches[1];

            return '@__verbatim__@';
        }, $content);
    }

    protected function restoreVerbatim($content)
    {
        return preg_replace_callback('#@__verbatim__@#', function () {
            return array_shift($this->verbatim);
        }, $content);
    }

    protected function callCallable(callable $callable, ...$args)
    {
        return call_user_func_array($callable, $args);
    }

    protected function compileComments($string)
    {
        $regex = $this->inNamespaceRegex('{{--', '--}}');

        return preg_replace_callback('#(@)?(' . $regex . ')#s', function ($matches) {
            return $matches[1] ? $matches[2] : $this->compileComment($matches['captured']);
        }, $string);
    }

    protected function compileComment($string)
    {
        return '<?php /* ' . $string . ' */ ?>';
    }

    protected function compileRawEchos($string)
    {
        $regex = $this->inNamespaceRegex('{!!', '!!}');

        return preg_replace_callback('#(@)?(' . $regex . ')#s', function ($matches) {
            return $matches[1] ? $matches[2] : $this->compileRawEcho($matches['captured']);
        }, $string);
    }

    protected function compileContentEchos($string)
    {
        $regex = $this->inNamespaceRegex('{{', '}}');

        return preg_replace_callback('#(@)?(' . $regex . ')#s', function ($matches) {
            return $matches[1] ? $matches[2] : $this->compileContentEcho($matches['captured']);
        }, $string);
    }

    protected function compileRawEcho($string)
    {
        return '<?php echo ' . $string . '; ?>';
    }

    protected function compileContentEcho($string)
    {
        if (preg_match('#^(' . self::PHP_VAR_REGEX . ')\s+or\s+(.+)$#is', $string, $matches)) {
            $string = 'isset(' . $matches[1] . ') ? ' . $matches[1] . ' : ' . $matches[2];
        }

        return '<?php echo htmlentities(' . $string . '); ?>';
    }

    protected function compileStatements($value)
    {
        $statements = array_map('preg_quote', array_merge(
            array_keys($this->statements),
            array_keys($this->optionalStatements),
            array_keys($this->emptyStatements)
        ));

        usort($statements, function ($a, $b) {
            return gmp_cmp(mb_strlen($a), mb_strlen($b)) * -1;
        });

        $statements = implode('|', $statements);

        $exprNamespace = $this->inNamespaceRegex('(', ')');

        $exprNamespace->recursive(true);

        $pattern = '@(?\'statement\'' . $statements . ')' . '(?:[\s\t]*' . $exprNamespace . ')?;?';

        return preg_replace_callback('#' . $pattern . '#is', function ($matches) {
            if (isset($this->statements[$matches['statement']])) {
                $callable = $this->statements[$matches['statement']];

                $args = [$matches['captured']];
            } elseif (isset($this->optionalStatements[$matches['statement']])) {
                $callable = $this->optionalStatements[$matches['statement']];

                $args = [$matches['captured']];
            } else {
                $callable = $this->emptyStatements[$matches['statement']];

                $args = [];
            }

            if (!is_callable($callable) and is_scalar($callable)) {
                $callable = [$this, $callable];
            }

            return $this->callCallable($callable, ...$args);
        }, $value);
    }

    protected function compileIf($expr)
    {
        return '<?php if(' . $expr . '): ?>';
    }

    protected function compileElseIf($expr)
    {
        return '<?php elseif(' . $expr . '): ?>';
    }

    protected function compileEndIf()
    {
        return '<?php endif; ?>';
    }

    protected function compileUnless($expr)
    {
        return '<?php if(!(' . $expr . ')): ?>';
    }

    protected function compileElseUnless($expr)
    {
        return '<?php elseif(!(' . $expr . ')): ?>';
    }

    protected function compileEndUnless()
    {
        return '<?php endif; ?>';
    }

    protected function compileElse()
    {
        return '<?php else: ?>';
    }

    protected function compileFor($expr)
    {
        return '<?php for(' . $expr . '): ?>';
    }

    protected function compileEndFor()
    {
        return '<?php endfor; ?>';
    }

    protected function compileForeach($expr)
    {
        $this->foreachEmptyVars[] = $emptyVar = $this->uniqueVar('foreachEmpty');

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

    protected function compileForElse()
    {
        array_shift($this->foreachLoopVars);

        return '<?php endforeach; if(' . array_shift($this->foreachEmptyVars) . '): ?>';
    }

    protected function compileEndForElse()
    {
        return '<?php endif; ?>';
    }

    protected function compileEndForeach()
    {
        array_shift($this->foreachLoopVars);

        array_shift($this->foreachEmptyVars);

        return '<?php endforeach; ?>';
    }

    protected function compileWhile($expr)
    {
        return '<?php while(' . $expr . '): ?>';
    }

    protected function compileEndWhile()
    {
        return '<?php endwhile; ?>';
    }

    protected function compileStop()
    {
        return '<?php return; ?>';
    }

    protected function compileSwitch($expr)
    {
        return '<?php switch(' . $expr . '): case uniqid(null, true): ?>';
    }

    protected function compileCase($expr)
    {
        return '<?php break; case ' . $expr . ': ?>';
    }

    protected function compileContinue($expr = null)
    {
        if ($expr) {
            return '<?php if(' . $expr . ') continue; ?>';
        }

        return '<?php continue; ?>';
    }

    protected function compileBreak($expr = null)
    {
        if ($expr) {
            return '<?php if(' . $expr . ') break; ?>';
        }

        return '<?php break; ?>';
    }

    protected function compileDefault()
    {
        return '<?php break; default: ?>';
    }

    protected function compileEndSwitch()
    {
        return '<?php endswitch; ?>';
    }

    protected function inNamespaceRegex($start, $end = null)
    {
        $pattern = new InNamespaceRegex($start, $end ?: $start);

        $pattern->recursive(false);

        $pattern->setCapturedKey('captured');

        $pattern->disableInQuotes();

        $pattern->newLines();

        $pattern->trim();

        return $pattern;
    }

    protected function uniqueVar($name)
    {
        return '$' . $name . str_replace('.', '_', uniqid(null, true));
    }

    public function setCompilationPath($path)
    {
        $this->compilationPath = (string) $path;

        return $this;
    }

    public function getCompilationPath()
    {
        return $this->compilationPath;
    }

    public function clearCompiledFiles()
    {
        foreach(glob($this->getCompilationPath() . '/*.php') as $file) {
            unlink($file);
        }

        return $this;
    }

    public function addStatements(array $statements)
    {
        $this->statements = array_merge($this->statements, $statements);

        return $this;
    }

    public function addEmptyStatements(array $statements)
    {
        $this->emptyStatements = array_merge($this->emptyStatements, $statements);

        return $this;
    }

    public function addOptionalStatements(array $statements)
    {
        $this->optionalStatements = array_merge($this->optionalStatements, $statements);

        return $this;
    }

    public function addOptionalStatement($name, $compiler)
    {
        $this->optionalStatements[$name] = $compiler;

        return $this;
    }
}
