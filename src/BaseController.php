<?php

namespace ahmetbarut\PhpRouter;

abstract class BaseController
{
    public function getClassName()
    {
        return get_class($this);
    }
}
