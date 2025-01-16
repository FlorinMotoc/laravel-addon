<?php

namespace FlorinMotoc\LaravelAddon\Traits;

trait ClassNameTrait
{
    protected function getClassName(): string
    {
        $pos = ($pos = strrpos(static::class, '\\')) !== false ? $pos + 1 : 0;

        return substr(static::class, $pos) ?? '';
    }
}
