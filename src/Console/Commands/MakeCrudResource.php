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

        // Read the config file content
        $content = File::get($configPath);

        // Find the resources array and add the new resource
        // This pattern is designed to handle complex config files with comments
        $resourcesPattern = "/([\s]*)(['|\"]resources['|\"]\s*=>\s*\[)(.*?)(\n\s*\][,;]?\s*(?:\n|$))/s";

        if (preg_match($resourcesPattern, $content, $matches)) {
            $this->info("✓ Found resources array in configuration");
            $indent = $matches[1];
            $resourceEntry = $this->generateResourceEntry($name, $resourceConfig, $indent . '    ');

            // Make sure we're adding at the top level of the resources array, not inside another resource
            $existingResources = trim($matches[3]);
            $newResourcesContent = $matches[2]; // 'resources' => [

            // Parse existing resources by using proper parsing that respects nested structures
            if (!empty($existingResources)) {
                // Check if resources is empty or only has comments
                $onlyComments = preg_match('/^\s*(?:\/\/[^\n]*\n)*\s*$/s', $existingResources);
                
                if ($onlyComments) {
                    // Resources array is effectively empty or just has comments
                    if ($addNewResourceTo === 'top' || $addNewResourceTo === 'bottom') {
                        $newResourcesContent .= "\n" . $resourceEntry;
                    }
                } else {
                    // Resources array has content
                    if ($addNewResourceTo === 'top') {
                        $newResourcesContent .= "\n" . $resourceEntry;
                        $newResourcesContent .= "\n" . $existingResources;
                    } else {
                        $newResourcesContent .= "\n" . $existingResources;
                        $newResourcesContent .= "\n" . $resourceEntry;
                    }
                }
            } else {
                // No existing resources
                $newResourcesContent .= "\n" . $resourceEntry;
            }

            $newResourcesContent .= $matches[4]; // \n];

            $content = str_replace($matches[0], $newResourcesContent, $content);

            File::put($configPath, $content);
            $this->info("✓ Added resource '{$name}' to configuration");
        } else {
            $this->warn("Could not automatically update configuration. Trying alternative approach...");
            
            // Try with a more specific pattern for heavily commented files
            $commentAwarePattern = "/'resources'\s*=>\s*\[\s*(?:\/\/[^\n]*\n|\s*\n)*?(.*?)(\n\s*\],)/s";
            if (preg_match($commentAwarePattern, $content, $matches)) {
                $this->info("✓ Found resources array using comment-aware pattern");
                $resourceEntry = $this->generateResourceEntry($name, $resourceConfig, '    ');
                
                $existingResources = $matches[1];
                
                // Add resource at the beginning or end based on configuration
                if ($addNewResourceTo === 'top') {
                    $newContent = str_replace(
                        "'resources' => [", 
                        "'resources' => [\n" . $resourceEntry, 
                        $content
                    );
                } else {
                    $newContent = str_replace(
                        $matches[1] . $matches[2], 
                        $matches[1] . "\n" . $resourceEntry . $matches[2], 
                        $content
                    );
                }
                
                File::put($configPath, $newContent);
                $this->info("✓ Added resource '{$name}' to configuration using comment-aware approach");
            }
            // Alternative approach using a simpler pattern
            else if (preg_match("/(['|\"]resources['|\"]\s*=>\s*\[)(.*?)(\]\s*,)/s", $content, $matches)) {
                $this->info("✓ Found resources array using alternative pattern");
                $resourceEntry = $this->generateResourceEntry($name, $resourceConfig, '    ');
                
                $existingResources = trim($matches[2]);
                $newResourcesContent = $matches[1];
                
                if ($addNewResourceTo === 'top') {
                    $newResourcesContent .= "\n" . $resourceEntry;
                    if (!empty($existingResources)) {
                        $newResourcesContent .= "\n" . $existingResources;
                    }
                } else {
                    if (!empty($existingResources)) {
                        $newResourcesContent .= "\n" . $existingResources;
                    }
                    $newResourcesContent .= "\n" . $resourceEntry;
                }
                
                $newResourcesContent .= $matches[3];
                
                $content = str_replace($matches[0], $newResourcesContent, $content);
                
                File::put($configPath, $content);
                $this->info("✓ Added resource '{$name}' to configuration using alternative approach");
            } else {
                // Last resort: Find the last closing bracket of resources array by position search
                $resourcesStart = strpos($content, "'resources' => [");
                if ($resourcesStart !== false) {
                    $this->info("✓ Found resources array start position");
                    
                    // Find the matching closing bracket by counting open/close brackets
                    $pos = $resourcesStart + strlen("'resources' => [");
                    $openBrackets = 1;
                    $closingPos = null;
                    
                    while ($pos < strlen($content) && $openBrackets > 0) {
                        if ($content[$pos] === '[') {
                            $openBrackets++;
                        } elseif ($content[$pos] === ']') {
                            $openBrackets--;
                            if ($openBrackets === 0) {
                                $closingPos = $pos;
                                break;
                            }
                        }
                        $pos++;
                    }
                    
                    if ($closingPos !== null) {
                        $this->info("✓ Found resources array closing position");
                        $resourceEntry = $this->generateResourceEntry($name, $resourceConfig, '    ');
                        
                        // Insert resource before the closing bracket
                        $newContent = substr($content, 0, $closingPos);
                        $newContent .= "\n" . $resourceEntry;
                        $newContent .= substr($content, $closingPos);
                        
                        File::put($configPath, $newContent);
                        $this->info("✓ Added resource '{$name}' to configuration using positional approach");
                    } else {
                        $this->error("Could not find matching closing bracket for resources array.");
                        $this->showResourceManualAddition($name, $resourceConfig);
                    }
                } else {
                    $this->error("Could not automatically update configuration. Please add the resource manually.");
                    $this->showResourceManualAddition($name, $resourceConfig);
                }
            }
        }
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
