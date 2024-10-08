<?php

namespace Jackwander\ModuleMaker;

use Illuminate\Support\ServiceProvider;
use Jackwander\ModuleMaker\Commands\MakeController;
use Jackwander\ModuleMaker\Commands\MakeMigration;
use Jackwander\ModuleMaker\Commands\MakeModel;
use Jackwander\ModuleMaker\Commands\MakeModule;
use Jackwander\ModuleMaker\Commands\MakeService;

class ModuleServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            MakeModule::class,
            MakeMigration::class,
            MakeModel::class,
            MakeService::class,
            MakeController::class,
        ]);
    }

    public function boot()
    {
        //
    }
}
