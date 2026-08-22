<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;

abstract class Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     * 
     * @return array
     */
    public static function middleware(): array
    {
        return [];
    }
}
