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
    protected $signature = 'make:crud-resource
                            {name : The name of the resource (e.g., posts, users)}
                            {--model= : The model class (e.g., User, User\\Profile, App\\Models\\Admin\\User)}
                            {--logic : Generate a custom logic class}
                            {--resource : Generate an API resource class}
                            {--permissions : Generate permissions for this resource}
                            {--force : Overwrite existing files}';

    protected $description = 'Create a new CRUD resource configuration';

    public function handle(): int
    {
        $name = $this->argument('name');
        $modelOption = $this->option('model');

        if (!$name || !is_string($name)) {
            $this->error('Resource name is required.');
            return Command::FAILURE;
        }

        $model = $modelOption
            ? $modelOption
            : Str::studly(Str::singular($name));

        $this->info("Creating CRUD resource: {$name}");

        try {
            $this->addToConfiguration($name, $model);

            if ($this->option('logic')) {
                $this->generateLogicClass($name, $model);
            }

            if ($this->option('resource')) {
                $this->generateApiResource($model);
            }

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
     * Add the resource to config/crud.php by inserting the new entry
     * into the existing 'resources' array, preserving all old content,
     * and honoring the add_new_resource_to setting (top|bottom).
     */
    protected function addToConfiguration(string $name, string $model): void
    {
        $configPath = config_path('crud.php');
        if (!File::exists($configPath)) {
            $this->error('CRUD configuration file not found. Run php artisan crud:install first.');
            return;
        }

        // Load the PHP config array so we can read the add_new_resource_to setting
        $config = include $configPath;
        $where = $config['add_new_resource_to'] ?? 'bottom';
        $where = in_array($where, ['top', 'bottom'], true) ? $where : 'bottom';

        // If resource already exists and no --force, confirm overwrite
        if (isset($config['resources'][$name]) && !$this->option('force')) {
            if (!$this->confirm("Resource '{$name}' already exists. Overwrite?")) {
                $this->warn('Skipped resource configuration.');
                return;
            }
        }

        // Build the new resource config
        $resourceConfig = [
            'model' => $this->getFullModelNamespace($model),
            'middleware' => ['auth:sanctum', 'crud.permissions'],
            'fillable' => [],
            'hidden' => [],
            'rules' => ['store' => [], 'update' => []],
            'pagination' => ['per_page' => 15, 'max_per_page' => 100],
            'searchable_fields' => [],
            'sortable_fields' => ['id', 'created_at', 'updated_at'],
            'filterable_fields' => [],
            'relationships' => [
                // Example:
                // 'category_id' => [
                //     'entity' => 'categories',
                //     'labelField' => 'name',
                //     'valueField' => 'id',
                //     'displayField' => 'name',
                //     'searchable' => true,
                //     'required' => false,
                //     'depends_on' => null,
                //     'filter_by' => null,
                // ],
            ],
            'soft_deletes' => false,
        ];

        // Read the raw file
        $raw = File::get($configPath);

        // 1) Find "'resources'" key
        $start = strpos($raw, "'resources'");
        if ($start === false) {
            $this->error("Couldn't find 'resources' key in config/crud.php");
            return;
        }

        // 2) Find the opening '[' of the resources array
        $openBracketPos = strpos($raw, '[', $start);
        if ($openBracketPos === false) {
            $this->error("Malformed 'resources' array");
            return;
        }

        // 3) Walk forward to find its matching closing ']'
        $level = 1;
        $pos = $openBracketPos + 1;
        $len = strlen($raw);
        while ($level > 0 && $pos < $len) {
            if ($raw[$pos] === '[') {
                $level++;
            } elseif ($raw[$pos] === ']') {
                $level--;
            }
            $pos++;
        }
        if ($level !== 0) {
            $this->error("Unbalanced brackets in 'resources' array");
            return;
        }
        $closeBracketPos = $pos - 1; // index of the matching ']'

        // 4) Slice into three parts
        $before = substr($raw, 0, $openBracketPos + 1);
        $between = substr($raw, $openBracketPos + 1, $closeBracketPos - ($openBracketPos + 1));
        $after = substr($raw, $closeBracketPos);

        // 5) Build your new entry text
        $indent = '    ';        // 4 spaces for top-level
        $itemIndent = $indent . '    '; // 8 spaces for items
        $newEntry = $this->generateResourceEntry($name, $resourceConfig, $itemIndent);

        // 6) Insert at top or bottom of the existing block
        $trimmed = rtrim($between, "\r\n");
        if ($where === 'top') {
            // put new entry before everything else
            $newBetween = "\n" . $newEntry . "\n" . $trimmed . "\n";
        } else {
            // append after existing entries
            $newBetween = $trimmed . "\n" . $newEntry . "\n";
        }

        // 7) Reassemble & write back
        $newRaw = $before . $newBetween . $after;
        File::put($configPath, $newRaw);

        $this->info("✓ Added resource '{$name}' to configuration ({$where})");
    }

    /**
     * Render a single resource entry as PHP source.
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
                            $entry .= "{$indent}        '{$subKey}' => "
                                . (is_array($subValue)
                                    ? '[]'
                                    : var_export($subValue, true))
                                . ",\n";
                        } else {
                            $entry .= "{$indent}        "
                                . var_export($subValue, true)
                                . ",\n";
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

    protected function generateLogicClass(string $name, string $model): void
    {
        $logicName = Str::studly($name) . 'Logic';
        $this->call('make:crud-logic', [
            'name' => $logicName,
            '--model' => $model,
            '--force' => $this->option('force'),
        ]);
    }

    protected function generateApiResource(string $model): void
    {
        $this->call('make:resource', [
            'name' => "Crud/{$model}Resource",
            '--force' => $this->option('force'),
        ]);
    }

    protected function showSuccessMessage(string $name, string $model): void
    {
        $fullModel = $this->getFullModelNamespace($model);

        $this->newLine();
        $this->info("✓ CRUD resource '{$name}' created successfully!");
        $this->newLine();
        $this->line('<options=bold>Next Steps:</>');
        $this->line("1. Review config/crud.php");
        $this->line("2. Customize validation, searchable fields, etc.");
        $this->line("3. Ensure <comment>{$fullModel}</comment> exists");
        $this->line("4. Register routes:");
        $this->line("   CrudGenerator::registerRoutes('api/v1', ['auth:sanctum']);");
        $this->line("5. Test endpoints: GET, POST, GET/{id}, PUT/{id}, DELETE/{id}");
        $this->line("6. View docs: GET /api/v1/docs and /api/v1/{$name}/docs");
        $this->newLine();
    }

    protected function getFullModelNamespace(string $model): string
    {
        return Str::contains($model, '\\')
            ? $model
            : "App\\Models\\{$model}";
    }
}
