<?php

namespace Greg\View;

class Renderer
{
    private $viewer;

    private $file;

    private $params = [];

    private $extended;

    private $content;

    private $sections = [];

    private $currentSection;

    private $stacks = [];

    private $currentStack;

    public function __construct(Viewer $viewer, string $file, array $params = [])
    {
        $this->viewer = $viewer;

        $this->file = $file;

        $this->params = $params;
    }

    public function render(string $name, array $params = []): string
    {
        return $this->partial($name, $params + $this->params);
    }

    public function renderIfExists(string $name, array $params = []): ?string
    {
        return $this->partialIfExists($name, $params + $this->params);
    }

    public function renderString(string $id, string $string, array $params = []): string
    {
        return $this->partialString($id, $string, $params + $this->params);
    }

    public function renderStringIfExists(string $id, string $string, array $params = []): ?string
    {
        return $this->partialStringIfExists($id, $string, $params + $this->params);
    }

    public function partial(string $name, array $params = []): string
    {
        if ($file = $this->viewer->getCompiledFile($name)) {
            return $this->partialFile($file, $params);
        }

        throw new ViewException('View file `' . $name . '` does not exist in view paths.');
    }

    public function partialIfExists(string $name, array $params = []): ?string
    {
        if ($file = $this->viewer->getCompiledFile($name)) {
            return $this->partialFile($file, $params);
        }

        return null;
    }

    public function partialString(string $id, string $string, array $params = []): string
    {
        if ($file = $this->viewer->getCompiledFileFromString($id, $string)) {
            return $this->partialFile($file, $params);
        }

        throw new ViewException('Could not find a compiler for view `' . $id . '`.');
    }

    public function partialStringIfExists(string $id, string $string, array $params = []): ?string
    {
        if ($file = $this->viewer->getCompiledFileFromString($id, $string)) {
            return $this->partialFile($file, $params);
        }

        return null;
    }

    protected function partialFile(string $file, array $params = []): string
    {
        $renderer = (new self($this->viewer, $file, $params + $this->viewer->assigned()));

        return (new Loader($renderer))->_l_o_a_d_();
    }

    public function each(string $name, array $values, array $params = [], string $valueKeyName = null, string $emptyName = null): string
    {
        if ($file = $this->viewer->getCompiledFile($name)) {
            $emptyFile = $emptyName ? $this->viewer->getCompiledFile($emptyName) : null;

            return $this->eachFile($file, $values, $params, $valueKeyName, $emptyFile);
        }

        throw new ViewException('View file `' . $name . '` does not exist in view paths.');
    }

    public function eachIfExists(string $name, array $values, array $params = [], string $valueKeyName = null, string $emptyName = null): ?string
    {
        if ($file = $this->viewer->getCompiledFile($name)) {
            $emptyFile = $emptyName ? $this->viewer->getCompiledFile($emptyName) : null;

            return $this->eachFile($file, $values, $params, $valueKeyName, $emptyFile);
        }

        return null;
    }

    public function eachString(string $id, string $string, array $values, array $params = [], string $valueKeyName = null, string $emptyId = null, string $emptyString = null): string
    {
        if ($file = $this->viewer->getCompiledFileFromString($id, $string)) {
            $emptyFile = $emptyId ? $this->viewer->getCompiledFileFromString($emptyId, $emptyString) : null;

            return $this->eachFile($file, $values, $params, $valueKeyName, $emptyFile);
        }

        throw new ViewException('Could not find a compiler for view `' . $id . '`.');
    }

    public function eachStringIfExists(string $id, string $string, array $values, array $params = [], string $valueKeyName = null, string $emptyId = null, string $emptyString = null): ?string
    {
        if ($file = $this->viewer->getCompiledFileFromString($id, $string)) {
            $emptyFile = $emptyId ? $this->viewer->getCompiledFileFromString($emptyId, $emptyString) : null;

            return $this->eachFile($file, $values, $params, $valueKeyName, $emptyFile);
        }

        return null;
    }

    protected function eachFile(string $file, array $values, array $params = [], string $valueKeyName = null, string $emptyFile = null): string
    {
        $content = [];

        foreach ($values as $key => $value) {
            $content[] = $this->partialFile($file, $params + [
                $valueKeyName ?: 'value' => $value,
            ]);
        }

        if (!$content and $emptyFile) {
            $content[] = $this->partialFile($emptyFile, $params);
        }

        return implode('', $content);
    }

    public function extend(string $name)
    {
        $this->extended = [
            'name' => $name,
        ];

        return $this;
    }

    public function extendString(string $id, string $string)
    {
        $this->extended = [
            'id'        => $id,
            'string'    => $string,
        ];

        return $this;
    }

    public function content(): ?string
    {
        return $this->content;
    }

    public function section(string $name, string $content = null)
    {
        if ($this->currentSection) {
            ob_get_clean();

            throw new ViewException('You cannot have a section in another section.');
        }

        if (func_num_args() > 1) {
            $this->sections[$name] = $content;
        } else {
            $this->currentSection = $name;

            ob_start();
        }

        return $this;
    }

    public function parent(): ?string
    {
        return $this->getSection($this->currentSection);
    }

    public function endSection()
    {
        if (!$this->currentSection) {
            throw new ViewException('You cannot end an undefined section.');
        }

        $this->sections[$this->currentSection] = ob_get_clean();

        $this->currentSection = null;

        return $this;
    }

    public function show(): ?string
    {
        if (!$this->currentSection) {
            throw new ViewException('You cannot end an undefined section.');
        }

        $content = $this->getSection($this->currentSection, ob_get_clean());

        $this->currentSection = null;

        return $content;
    }

    public function getSection(string $name, string $else = null): ?string
    {
        return $this->hasSection($name) ? $this->sections[$name] : $else;
    }

    public function push(string $name, string $content = null)
    {
        if ($this->currentStack) {
            ob_get_clean();

            throw new ViewException('You cannot have a stack in another stack.');
        }

        if (func_num_args() > 1) {
            $this->stacks[$name][] = $content;
        } else {
            $this->currentStack = $name;

            ob_start();
        }

        return $this;
    }

    public function endPush()
    {
        if (!$this->currentStack) {
            throw new ViewException('You cannot end an undefined stack.');
        }

        $this->stacks[$this->currentStack][] = ob_get_clean();

        $this->currentStack = null;

        return $this;
    }

    public function stack(string $name, string $else = null): ?string
    {
        return $this->hasStack($name) ? implode('', $this->stacks[$name]) : $else;
    }

    public function format(string $name, ...$args): ?string
    {
        return $this->viewer->format($name, ...$args);
    }

    public function viewer(): Viewer
    {
        return $this->viewer;
    }

    public function params(): array
    {
        return $this->params;
    }

    public function file(): string
    {
        return $this->file;
    }

    public function extended(): ?array
    {
        return $this->extended;
    }

    public function setContent(string $content)
    {
        $this->content = $content;

        return $this;
    }

    public function setSections(array $sections)
    {
        $this->sections = $sections;

        return $this;
    }

    public function getSections(): array
    {
        return $this->sections;
    }

    public function hasSection(string $name): bool
    {
        return array_key_exists($name, $this->sections);
    }

    public function setStacks(array $stacks)
    {
        $this->stacks = $stacks;

        return $this;
    }

    public function getStacks(): array
    {
        return $this->stacks;
    }

    public function hasStack(string $name): bool
    {
        return array_key_exists($name, $this->stacks);
    }

    public function __call($name, $arguments)
    {
        return $this->format($name, ...$arguments);
    }
}
