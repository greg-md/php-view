<?php

namespace Greg\View;

interface CompilerInterface
{
    public function getCompiledFile($file);

    public function removeCompiledFiles();
}
