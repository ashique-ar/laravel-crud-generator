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

    /* 
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

        $addNewResourceTo = $config['add_new_resource_to'] ?? 'bottom';

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

        // Read config file content
        $content = File::get($configPath);
        // Find the top-level 'resources' array
        $search = "'resources' => [";
        $pos = strpos($content, $search);
        if ($pos === false) {
            $this->error("Resources array not found in configuration. Please add resource manually.");
            $this->showResourceManualAddition($name, $resourceConfig);
            return;
        }
        // Determine indent by capturing whitespace before 'resources'
        $lineStart = strrpos(substr($content, 0, $pos), "\n") + 1;
        $line = substr($content, $lineStart, $pos - $lineStart);
        preg_match('/^(\s*)/', $line, $m);
        $baseIndent = $m[1] ?? '';
        // Locate matching closing bracket for resources array
        $posOpen = strpos($content, '[', $pos);
        $depth = 1;
        $len = strlen($content);
        for ($i = $posOpen + 1; $i < $len; $i++) {
            if ($content[$i] === '[') {
                $depth++;
            } elseif ($content[$i] === ']') {
                $depth--;
                if ($depth === 0) {
                    $posClose = $i;
                    break;
                }
            }
        }
        if (!isset($posClose)) {
            $this->error("Could not find end of resources array.");
            $this->showResourceManualAddition($name, $resourceConfig);
            return;
        }
        // Generate resource entry with correct indent
        $resourceEntry = $this->generateResourceEntry($name, $resourceConfig, $baseIndent . '    ');
        // Insert before closing bracket
        $newContent = substr($content, 0, $posClose)
            . "\n" . $resourceEntry . "\n"
            . substr($content, $posClose);
        File::put($configPath, $newContent);
        $this->info("✓ Added resource '{$name}' to configuration");
        return;
    }
    
    /**
     * Show instructions for manually adding a resource.
     *
     * @param string $name
     * @param array $resourceConfig
     */
    protected function showResourceManualAddition(string $name, array $resourceConfig): void
    {
        // Show the generated resource entry for manual addition
        $this->line("\nPlease add the following resource manually to your config/crud.php file:");
        $this->line("\n'" . $name . "' => [");
        foreach ($resourceConfig as $key => $value) {
            if (is_array($value) && empty($value)) {
                $this->line("    '" . $key . "' => [],");
            } elseif (is_string($value)) {
                $this->line("    '" . $key . "' => '" . $value . "',");
            } elseif (is_bool($value)) {
                $this->line("    '" . $key . "' => " . ($value ? 'true' : 'false') . ",");
            } else {
                $this->line("    '" . $key . "' => " . var_export($value, true) . ",");
            }
        }
        $this->line("],");
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
                $entry .= $this->formatArrayValue($value, $indent . '    ');
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
     * Format an array value recursively.
     *
     * @param array $array
     * @param string $indent
     * @return string
     */
    protected function formatArrayValue(array $array, string $indent): string
    {
        if (empty($array)) {
            return '[]';
        }
        
        $result = "[\n";
        
        foreach ($array as $key => $value) {
            $result .= $indent . '    ';
            
            if (is_string($key)) {
                $result .= "'{$key}' => ";
            }
            
            if (is_array($value)) {
                $result .= $this->formatArrayValue($value, $indent . '    ');
            } elseif (is_string($value)) {
                $result .= "'{$value}'";
            } elseif (is_bool($value)) {
                $result .= $value ? 'true' : 'false';
            } else {
                $result .= var_export($value, true);
            }
            
            $result .= ",\n";
        }
        
        $result .= "{$indent}]";
        
        return $result;
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
