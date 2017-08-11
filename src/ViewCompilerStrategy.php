<?php

namespace Greg\View;

interface ViewCompilerStrategy extends CompilerStrategy
{
    public function addViewDirective(string $name);
}
