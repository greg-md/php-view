<?php

namespace Greg\View;

interface CompilerStrategy
{
    public function getCompiledFile(string $file): string;

    public function getCompiledFileFromString(string $id, string $string): string;

    public function removeCompiledFiles();
}
