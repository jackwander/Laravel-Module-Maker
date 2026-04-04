<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Package Paths & Namespaces
    |--------------------------------------------------------------------------
    |
    | Configure the root directory and root namespace for your modules.
    | By default, they will be placed in the `app/Modules` directory.
    |
    */
    'paths' => [
        'modules' => base_path('app/Modules'),
        'api_prefix' => 'api/v1',
    ],

    'namespaces' => [
        'root' => 'App\\Modules',
    ],

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
        'service'        => \Jackwander\ModuleMaker\Base\BaseService::class,
        'api_controller' => \Jackwander\ModuleMaker\Base\BaseApiController::class,
        'model'          => \Jackwander\ModuleMaker\Base\BaseModel::class,
    ]
];
