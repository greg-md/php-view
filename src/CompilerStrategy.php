<?php

namespace Greg\View;

interface CompilerStrategy
{
    public function getCompiledFile($file);

    public function removeCompiledFiles();
}
