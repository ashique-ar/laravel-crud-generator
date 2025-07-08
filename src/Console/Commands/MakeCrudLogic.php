<?php

declare(strict_types=1);

namespace AshiqueAr\LaravelCrudGenerator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

/**
 * Make CRUD logic class command.
 *
 * This command generates a custom CRUD logic class that extends
 * the base CRUD logic, allowing developers to customize behavior
 * for specific resources.
 */
class MakeCrudLogic extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:crud-logic
                            {name : The name of the CRUD logic class}
                            {--model= : The model class to associate with this logic}
                            {--force : Overwrite existing file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new CRUD logic class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'CRUD Logic';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(): string
    {
        return __DIR__ . '/../../../resources/stubs/crud-logic.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\Services\Crud';
    }

    /**
     * Build the class with the given name.
     *
     * @param string $name
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name): string
    {
        $stub = $this->files->get($this->getStub());

        $stub = $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);
        $stub = $this->replaceModel($stub);
        $stub = $this->replaceModelClass($stub);

        return $stub;
    }

    /**
     * Replace the model variable in the stub.
     *
     * @param string $stub
     * @return string
     */
    protected function replaceModel(string $stub): string
    {
        $model = $this->option('model');
        
        if ($model) {
            $modelVariable = Str::camel(class_basename($model));
            $stub = str_replace('{{ modelVariable }}', $modelVariable, $stub);
            $stub = str_replace('{{modelVariable}}', $modelVariable, $stub);
        } else {
            $stub = str_replace('{{ modelVariable }}', 'model', $stub);
            $stub = str_replace('{{modelVariable}}', 'model', $stub);
        }

        return $stub;
    }

    /**
     * Replace the model class in the stub.
     *
     * @param string $stub
     * @return string
     */
    protected function replaceModelClass(string $stub): string
    {
        $model = $this->option('model');
        
        if ($model) {
            $modelClass = class_basename($model);
            $modelNamespace = 'App\Models\\' . $modelClass;
            
            $stub = str_replace('{{ modelClass }}', $modelClass, $stub);
            $stub = str_replace('{{modelClass}}', $modelClass, $stub);
            $stub = str_replace('{{ modelNamespace }}', $modelNamespace, $stub);
            $stub = str_replace('{{modelNamespace}}', $modelNamespace, $stub);
        } else {
            $stub = str_replace('{{ modelClass }}', 'Model', $stub);
            $stub = str_replace('{{modelClass}}', 'Model', $stub);
            $stub = str_replace('{{ modelNamespace }}', 'Illuminate\Database\Eloquent\Model', $stub);
            $stub = str_replace('{{modelNamespace}}', 'Illuminate\Database\Eloquent\Model', $stub);
        }

        return $stub;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $result = parent::handle();

        if ($result === false) {
            return Command::FAILURE;
        }

        $name = $this->qualifyClass($this->getNameInput());
        $model = $this->option('model');

        $this->info("CRUD logic class created: {$name}");
        
        if ($model) {
            $this->info("Associated with model: {$model}");
            $this->newLine();
            $this->line('<options=bold>Next steps:</>');
            $this->line("1. Register this logic class in <comment>config/crud.php</comment>");
            $this->line("2. Customize the logic methods as needed");
            $this->line("3. Test your CRUD operations");
        } else {
            $this->warn('No model specified. Remember to update the class with your model.');
        }

        return Command::SUCCESS;
    }
}


