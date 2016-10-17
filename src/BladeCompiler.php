<?php

namespace Greg\View;

use Greg\Support\File;
use Greg\Support\Regex\InNamespaceRegex;

class BladeCompiler implements CompilerInterface
{
    protected $compilationPath = null;

    protected $compilers = [
        'compileStatements',
        'compileComments',
        'compileRawEchos',
        'compileContentEchos',
    ];

    protected $statements = [
        'if'         => 'compileIf',
        'elseif'     => 'compileElseIf',
        'unless'     => 'compileUnless',
        'elseunless' => 'compileElseUnless',
        'for'        => 'compileFor',
        'foreach'    => 'compileForeach',
        'while'      => 'compileWhile',

        'switch' => 'compileSwitch',
        'case'   => 'compileCase',
    ];

    protected $emptyStatements = [
        'endif'      => 'compileEndIf',
        'endunless'  => 'compileEndUnless',
        'endfor'     => 'compileEndFor',
        'endforeach' => 'compileEndForeach',
        'endwhile'   => 'compileEndWhile',
        'forelse'    => 'compileForElse',
        'endforelse' => 'compileEndForElse',

        'default'   => 'compileDefault',
        'break'     => 'compileBreak',
        'endswitch' => 'compileEndSwitch',

        'else' => 'compileElse',
        'stop' => 'compileStop',
    ];

    protected $optionalStatements = [
        'break'     => 'compileBreak',
        'continue'     => 'compileContinue',
    ];

    protected $foreachK = 0;

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
        return preg_replace_callback('/(?<!@)@verbatim(.*?)@endverbatim/s', function ($matches) {
            $this->verbatim[] = $matches[1];

            return '@__verbatim__@';
        }, $content);
    }

    protected function restoreVerbatim($content)
    {
        return preg_replace_callback('/@__verbatim__@/', function () {
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

        return preg_replace_callback('#(@)?(' . $regex . ')#', function ($matches) {
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

        return preg_replace_callback('#(@)?(' . $regex . ')#', function ($matches) {
            return $matches[1] ? $matches[2] : $this->compileRawEcho($matches['captured']);
        }, $string);
    }

    protected function compileContentEchos($string)
    {
        $regex = $this->inNamespaceRegex('{{', '}}');

        return preg_replace_callback('#(@)?(' . $regex . ')#', function ($matches) {
            return $matches[1] ? $matches[2] : $this->compileContentEcho($matches['captured']);
        }, $string);
    }

    protected function compileRawEcho($string)
    {
        return '<?php echo ' . $string . '; ?>';
    }

    protected function compileContentEcho($string)
    {
        if (preg_match('#^(\$[a-z0-9_]+)\s+or\s+(.+)$#i', $string, $matches)) {
            $string = 'isset(' . $matches[1] . ') ? ' . $matches[1] . ' : ' . $matches[2];
        }

        return '<?php echo htmlentities(' . $string . ', ENT_QUOTES); ?>';
    }

    protected function compileStatements($value)
    {
        $statements = array_map('preg_quote', array_merge(
            array_keys($this->statements),
            array_keys($this->optionalStatements),
            array_keys($this->emptyStatements)
        ));

        $statements = implode('|', $statements);

        $exprNamespace = $this->inNamespaceRegex('(', ')');

        $exprNamespace->recursive(true);

        $pattern = '@(?\'statement\'' . $statements . ')' . '(?:[\s\t]*' . $exprNamespace . ')?;?';

        return preg_replace_callback('#' . $pattern . '#i', function ($matches) {
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
        ++$this->foreachK;

        $var = '$___foreachEmpty' . $this->foreachK;

        return '<?php ' . $var . ' = true; foreach(' . $expr . '): ' . $var . ' = false; ?>';
    }

    protected function compileForElse()
    {
        $var = '$___foreachEmpty' . $this->foreachK;

        --$this->foreachK;

        return '<?php endforeach; if(' . $var . '): ?>';
    }

    protected function compileEndForeach()
    {
        --$this->foreachK;

        return '<?php endforeach; ?>';
    }

    protected function compileEndForElse()
    {
        return '<?php endif; ?>';
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

    public function setCompilationPath($path)
    {
        $this->compilationPath = (string) $path;

        return $this;
    }

    public function getCompilationPath()
    {
        return $this->compilationPath;
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
}
