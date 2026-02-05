<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Base Classes
    |--------------------------------------------------------------------------
    |
    | These are the classes that your generated modules will extend.
    | By default, they extend the package's vendor base classes.
    | You can change these to your own Core files (e.g., App\Modules\Core\BaseService).
    |
    */
    'base_classes' => [
        'service'    => \Jackwander\ModuleMaker\Resources\BaseService::class,
        'api_controller' => \Jackwander\ModuleMaker\Resources\BaseApiController::class,
        'model'      => \Jackwander\ModuleMaker\Resources\BaseModel::class,
    ]
];
