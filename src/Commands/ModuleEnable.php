<?php

namespace Jackwander\ModuleMaker\Commands;

class ModuleEnable extends ModuleDisable
{
    protected $signature = 'jw:module:enable {name}';
    protected $description = 'Enable a module that was disabled with jw:module:disable';

    protected bool $enable = true;
}
