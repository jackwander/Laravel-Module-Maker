<?php

namespace Jackwander\ModuleMaker;

use Illuminate\Support\ServiceProvider;
use Jackwander\ModuleMaker\Commands\MakeModule;

class ModuleServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            MakeModule::class,
        ]);
    }

    public function boot()
    {
        //
    }
}
