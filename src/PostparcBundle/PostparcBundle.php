<?php

namespace PostparcBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class PostparcBundle extends Bundle
{
    public function boot()
    {
        global $kernel;
        parent::boot();
        // change default timezone
        if ('pnr' == $kernel->getEnvironment()) {
            date_default_timezone_set('Etc/GMT-4');
        }
    }
}
