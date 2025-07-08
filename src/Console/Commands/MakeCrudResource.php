<?php

declare(strict_types=1);

namespace AshiqueAr\LaravelCrudGenerator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Make CRUD resource command.
 *
 * This command generates a new CRUD resource configuration entry
 * and optionally creates the associated logic class and API resource.
 */
class MakeCrudResource extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:crud-resource
                            {name : The name of the resource (e.g., posts, users)}
                            {--model= : The model class (e.g., User, User\\Profile, App\\Models\\Admin\\User)}
                            {--logic : Generate a custom logic class}
                            {--resource : Generate an API resource class}
                            {--permissions : Generate permissions for this resource}
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new CRUD resource configuration';

    /*     */
    public function handle():      *
     * @return int
     */
    public function handle(): int
    {
        /** @var string|null $name */
        $name = $this->argument('name');
        /** @var string|null $modelOption */
        $modelOption = $this->option('model');
        
        if (!$name || !is_string($name)) {
            $this->error('Resource name is required.');
            return Command::FAILURE;
        }
        
        $model = $modelOption && is_string($modelOption) 
            ? $modelOption 
            : Str::studly(Str::singular($name));

        $this->info("Creating CRUD resource: {$name}");

        try {
            // Add resource to configuration
            $this->addToConfiguration($name, $model);

            // Generate logic class if requested
            if ($this->option('logic')) {
                $this->generateLogicClass($name, $model);
            }

            // Generate API resource if requested
            if ($this->option('resource')) {
                $this->generateApiResource($model);
            }

            // Generate permissions if requested
            if ($this->option('permissions')) {
                $this->call('crud:permissions', ['--resource' => $name]);
            }

            $this->showSuccessMessage($name, $model);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to create CRUD resource: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Add the resource to the CRUD configuration.
     *
     * @param string $name
     * @param string $model
     */
    protected function addToConfiguration(string $name, string $model): void
    {
        $configPath = config_path('crud.php');

        if (!File::exists($configPath)) {
            $this->error('CRUD configuration file not found. Run php artisan crud:install first.');
            return;
        }

        $config = include $configPath;
        
        if (isset($config['resources'][$name]) && !$this->option('force')) {
            if (!$this->confirm("Resource '{$name}' already exists. Overwrite?")) {
                $this->warn('Skipped resource configuration.');
                return;
            }
        }

        $resourceConfig = [
            'model' => $this->getFullModelNamespace($model),
            'middleware' => ['auth:sanctum', 'crud.permissions'],
            'rules' => [
                'store' => [],
                'update' => [],
            ],
            'pagination' => [
                'per_page' => 15,
                'max_per_page' => 100,
            ],
            'searchable_fields' => [],
            'sortable_fields' => ['id', 'created_at', 'updated_at'],
            'filterable_fields' => [],
            'relationships' => [],
            'soft_deletes' => false,
        ];

        // Read the config file content
        $content = File::get($configPath);
        
        // Find the resources array and add the new resource
        $resourcesPattern = '/(\s+)(\'resources\'\s*=>\s*\[)(.*?)(\n\s+\],)/s';
        
        if (preg_match($resourcesPattern, $content, $matches)) {
            $indent = $matches[1];
            $resourceEntry = $this->generateResourceEntry($name, $resourceConfig, $indent . '    ');
            $newResourcesContent = $matches[2] . $matches[3] . "\n" . $resourceEntry . $matches[4];
            $content = preg_replace($resourcesPattern, $newResourcesContent, $content);
            
            File::put($configPath, $content);
            $this->info("✓ Added resource '{$name}' to configuration");
        } else {
            $this->warn("Could not automatically update configuration. Please add the resource manually.");
        }
    }

    /**
     * Generate the resource configuration entry as a string.
     *
     * @param string $name
     * @param array $config
     * @param string $indent
     * @return string
     */
    protected function generateResourceEntry(string $name, array $config, string $indent): string
    {
        $entry = "{$indent}'{$name}' => [\n";
        
        foreach ($config as $key => $value) {
            $entry .= "{$indent}    '{$key}' => ";
            
            if (is_array($value)) {
                if (empty($value)) {
                    $entry .= "[]";
                } else {
                    $entry .= "[\n";
                    foreach ($value as $subKey => $subValue) {
                        if (is_string($subKey)) {
                            $entry .= "{$indent}        '{$subKey}' => ";
                            $entry .= is_array($subValue) ? '[]' : var_export($subValue, true);
                            $entry .= ",\n";
                        } else {
                            $entry .= "{$indent}        " . var_export($subValue, true) . ",\n";
                        }
                    }
                    $entry .= "{$indent}    ]";
                }
            } elseif (is_string($value)) {
                $entry .= "'{$value}'";
            } elseif (is_bool($value)) {
                $entry .= $value ? 'true' : 'false';
            } else {
                $entry .= var_export($value, true);
            }
            
            $entry .= ",\n";
        }
        
        $entry .= "{$indent}],";
        
        return $entry;
    }

    /**
     * Generate a custom logic class for the resource.
     *
     * @param string $name
     * @param string $model
     */
    protected function generateLogicClass(string $name, string $model): void
    {
        $logicName = Str::studly($name) . 'Logic';
        
        $this->call('make:crud-logic', [
            'name' => $logicName,
            '--model' => $model,
            '--force' => $this->option('force'),
        ]);
    }

    /**
     * Generate an API resource class.
     *
     * @param string $model
     */
    protected function generateApiResource(string $model): void
    {
        $this->call('make:resource', [
            'name' => "Crud/{$model}Resource",
            '--force' => $this->option('force'),
        ]);
    }

    /**
     * Show success message with next steps.
     *
     * @param string $name
     * @param string $model
     */
    protected function showSuccessMessage(string $name, string $model): void
    {
        $fullModelNamespace = $this->getFullModelNamespace($model);
        
        $this->newLine();
        $this->info("✓ CRUD resource '{$name}' created successfully!");
        $this->newLine();

        $this->line('<options=bold>Next Steps:</>');
        $this->line("1. Review the configuration in <comment>config/crud.php</comment>");
        $this->line("2. Customize validation rules, searchable fields, etc.");
        $this->line("3. Ensure the <comment>{$fullModelNamespace}</comment> model exists");
        $this->line("4. Register routes in your <comment>routes/api.php</comment> or <comment>RouteServiceProvider</comment>:");
        $this->line("   <comment>CrudGenerator::registerRoutes('api/v1', ['auth:sanctum']);</comment>");
        $this->line("5. Test the API endpoints:");
        $this->line("   - GET /api/v1/{$name}");
        $this->line("   - POST /api/v1/{$name}");
        $this->line("   - GET /api/v1/{$name}/{id}");
        $this->line("   - PUT /api/v1/{$name}/{id}");
        $this->line("   - DELETE /api/v1/{$name}/{id}");
        $this->line("6. View API documentation:");
        $this->line("   - GET /api/v1/docs (all resources)");
        $this->line("   - GET /api/v1/{$name}/docs (this resource)");
        $this->newLine();
    }

    /**
     * Get the full model namespace.
     *
     * @param string $model
     * @return string
     */
    protected function getFullModelNamespace(string $model): string
    {
        // If the model contains backslashes, it's already a fully qualified namespace
        if (str_contains($model, '\\')) {
            return $model;
        }
        
        // If it's just a class name, assume it's in App\Models
        return "App\\Models\\{$model}";
    }
}
