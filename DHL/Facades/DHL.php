<?php
namespace DHL\Facades;

use Illuminate\Support\Facades\Facade;

class DHL extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected static function getFacadeAccessor()
    {
        return 'DHL';
    }
}
