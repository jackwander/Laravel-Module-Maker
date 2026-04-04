<?php

namespace Jackwander\ModuleMaker\Tests\Feature;

use Jackwander\ModuleMaker\Tests\TestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class MakeModuleTest extends TestCase
{
    protected function tearDown(): void
    {
        File::deleteDirectory(app_path('Modules'));
        parent::tearDown();
    }

    public function test_can_make_module()
    {
        // Assert that the module directory is created
        $modulePath = app_path('Modules/Demo');
        $this->assertFalse(File::exists($modulePath));

        Artisan::call('jw:make-module', ['name' => 'Demo']);

        $this->assertTrue(File::exists($modulePath));
        $this->assertTrue(File::exists("{$modulePath}/Controllers/DemosController.php"));
        $this->assertTrue(File::exists("{$modulePath}/Models/Demo.php"));
        $this->assertTrue(File::exists("{$modulePath}/Services/DemoService.php"));
    }
}
