<?php

namespace Greg\View;

interface ViewerContract extends \ArrayAccess
{
    public function render($name, array $params = []);

    public function renderIfExists($name, array $params = []);

    public function renderString($id, $string, array $params = []);

    public function renderStringIfExists($id, $string, array $params = []);

    public function getCompiledFile($name);

    public function getCompiledFileFromString($id, $string);

    public function assign($key, $value = null);

    public function assigned($key = null);

    public function hasAssigned($key = null);

    public function deleteAssigned($key = null);

    public function setPaths(array $paths);

    public function addPaths(array $paths);

    public function addPath($path);

    public function getPaths();

    public function addExtension($extension, $compiler = null);

    public function getExtensions();

    public function getSortedExtensions();

    public function hasCompiler($extension);

    public function getCompiler($extension);

    public function getCompilers();

    public function getCompilersExtensions();

    public function removeCompiledFiles();

    public function directive($name, callable $callable);

    public function hasDirective($name);

    public function format($name, ...$args);
}
