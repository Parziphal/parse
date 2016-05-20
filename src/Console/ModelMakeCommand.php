<?php

namespace Parziphal\Parse\Console;

use Illuminate\Support\Str;
use Illuminate\Console\GeneratorCommand;

class ModelMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'parse:model';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Parse Object model class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'ObjectModel';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/stubs/model.stub';
    }
}
