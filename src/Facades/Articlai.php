<?php

namespace Articlai\Articlai\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Articlai\Articlai\Articlai
 */
class Articlai extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Articlai\Articlai\Articlai::class;
    }
}
