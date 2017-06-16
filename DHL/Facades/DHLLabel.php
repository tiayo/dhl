<?php
namespace DHL\Facades;

use Illuminate\Support\Facades\Facade;

class DHLLabel extends Facade
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
        return 'DHLLabel';
    }
}
