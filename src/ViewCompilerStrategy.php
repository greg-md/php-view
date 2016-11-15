<?php

namespace Greg\View;

interface ViewCompilerStrategy extends CompilerStrategy
{
    public function directive($name, callable $callable);
}
