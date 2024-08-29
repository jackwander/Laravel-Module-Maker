<?php

namespace Jackwander\ModuleMaker;

use Illuminate\Support\ServiceProvider;
use Jackwander\ModuleMaker\Commands\MakeModule;
use Jackwander\ModuleMaker\Commands\MakeMigration;

class ModuleServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            MakeModule::class,
            MakeMigration::class,
        ]);
    }

    public function boot()
    {
        //
    }
}
