<?php

namespace Greg\View;

interface CompilerStrategy
{
    public function getCompiledFile($file);

    public function getCompiledFileFromString($id, $string);

    public function removeCompiledFiles();
}
