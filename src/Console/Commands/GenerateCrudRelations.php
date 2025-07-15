<?php

declare(strict_types=1);

namespace AshiqueAr\LaravelCrudGenerator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Generate CRUD relations command.
 *
 * This command helps generate relation configurations for existing CRUD resources
 * by analyzing database foreign keys and suggesting relation configs.
 */
class GenerateCrudRelations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:relations
                            {resource : The name of the resource to generate relations for}
                            {--field= : Specific field to create relation for (e.g., category_id)}
                            {--entity= : Target entity name (e.g., categories)}
                            {--endpoint= : API endpoint for the relation (e.g., /api/crud/categories)}
                            {--label-field=name : Field to use as label (default: name)}
                            {--value-field=id : Field to use as value (default: id)}
                            {--type=single : Relation type: single or multiple (default: single)}
                            {--searchable : Make the relation searchable}
                            {--nullable : Make the relation nullable}
                            {--depends-on= : Field this relation depends on (for dependent dropdowns)}
                            {--interactive : Interactive mode to configure relations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate relation configurations for CRUD resources';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $resource = $this->argument('resource');
        
        if (!$resource || !is_string($resource)) {
            $this->error('Resource name is required.');
            return Command::FAILURE;
        }

        $configPath = config_path('crud.php');
        if (!File::exists($configPath)) {
            $this->error('CRUD configuration file not found. Run php artisan crud:install first.');
            return Command::FAILURE;
        }

        $config = include $configPath;
        if (!isset($config['resources'][$resource])) {
            $this->error("Resource '{$resource}' not found in CRUD configuration.");
            return Command::FAILURE;
        }

        if ($this->option('interactive')) {
            return $this->runInteractiveMode($resource);
        }

        return $this->addSingleRelation($resource);
    }

    /**
     * Run interactive mode to configure multiple relations.
     */
    protected function runInteractiveMode(string $resource): int
    {
        $this->info("Interactive relation configuration for resource: {$resource}");
        $this->newLine();

        $relations = [];
        
        while (true) {
            $field = $this->ask('Enter field name (e.g., category_id) or press Enter to finish');
            
            if (empty($field)) {
                break;
            }

            $relation = $this->collectRelationData($field);
            if ($relation) {
                $relations[$field] = $relation;
                $this->info("✓ Added relation for field: {$field}");
            }
        }

        if (empty($relations)) {
            $this->warn('No relations were configured.');
            return Command::SUCCESS;
        }

        return $this->updateResourceWithRelations($resource, $relations);
    }

    /**
     * Add a single relation based on command options.
     */
    protected function addSingleRelation(string $resource): int
    {
        $field = $this->option('field');
        
        if (!$field) {
            $this->error('Field name is required. Use --field option or --interactive mode.');
            return Command::FAILURE;
        }

        $relation = [
            'entity' => $this->option('entity') ?: $this->guessEntityFromField($field),
            'endpoint' => $this->option('endpoint') ?: $this->guessEndpointFromField($field),
            'labelField' => $this->option('label-field') ?: 'name',
            'valueField' => $this->option('value-field') ?: 'id',
            'type' => $this->option('type') ?: 'single',
            'searchable' => $this->option('searchable') ? true : false,
            'nullable' => $this->option('nullable') ? true : false,
        ];

        if ($dependsOn = $this->option('depends-on')) {
            $relation['dependsOn'] = $dependsOn;
        }

        return $this->updateResourceWithRelations($resource, [$field => $relation]);
    }

    /**
     * Collect relation data interactively.
     */
    protected function collectRelationData(string $field): ?array
    {
        $entity = $this->ask('Entity name', $this->guessEntityFromField($field));
        $endpoint = $this->ask('API endpoint', $this->guessEndpointFromField($field));
        $labelField = $this->ask('Label field', 'name');
        $valueField = $this->ask('Value field', 'id');
        $type = $this->choice('Relation type', ['single', 'multiple'], 'single');
        $searchable = $this->confirm('Make searchable?', true);
        $nullable = $this->confirm('Make nullable?', true);
        $dependsOn = $this->ask('Depends on field (optional)');

        $relation = [
            'entity' => $entity,
            'endpoint' => $endpoint,
            'labelField' => $labelField,
            'valueField' => $valueField,
            'type' => $type,
            'searchable' => $searchable,
            'nullable' => $nullable,
        ];

        if ($dependsOn) {
            $relation['dependsOn'] = $dependsOn;
        }

        return $relation;
    }

    /**
     * Update the resource configuration with new relations.
     */
    protected function updateResourceWithRelations(string $resource, array $relations): int
    {
        $configPath = config_path('crud.php');
        $content = File::get($configPath);

        // Find the specific resource block
        $pattern = "/('$resource'\s*=>\s*\[)([\s\S]*?)(\n\s*\],)/";
        
        if (!preg_match($pattern, $content, $matches)) {
            $this->error("Could not find resource '{$resource}' in configuration.");
            return Command::FAILURE;
        }

        $resourceBlock = $matches[2];
        
        // Check if relations key already exists
        if (strpos($resourceBlock, "'relations'") !== false) {
            // Update existing relations
            $relationsPattern = "/('relations'\s*=>\s*\[)([\s\S]*?)(\n\s*\],)/";
            if (preg_match($relationsPattern, $resourceBlock, $relMatches)) {
                $newRelationsContent = $this->generateRelationsContent($relations);
                $resourceBlock = preg_replace(
                    $relationsPattern,
                    "'relations' => [\n" . $newRelationsContent . "\n        ],",
                    $resourceBlock
                );
            }
        } else {
            // Add relations key
            $newRelationsContent = $this->generateRelationsContent($relations);
            $relationsBlock = "        'relations' => [\n" . $newRelationsContent . "\n        ],\n";
            
            // Insert before the closing bracket
            $resourceBlock = rtrim($resourceBlock) . "\n" . $relationsBlock;
        }

        // Replace the resource block in the content
        $newContent = preg_replace($pattern, "$1$resourceBlock$3", $content);
        File::put($configPath, $newContent);

        $this->info("✓ Updated relations for resource '{$resource}'");
        $this->showRelationsSummary($relations);

        return Command::SUCCESS;
    }

    /**
     * Generate the relations content string.
     */
    protected function generateRelationsContent(array $relations): string
    {
        $content = '';
        foreach ($relations as $field => $relation) {
            $content .= "            '$field' => [\n";
            $content .= "                'entity' => '{$relation['entity']}',\n";
            $content .= "                'endpoint' => '{$relation['endpoint']}',\n";
            $content .= "                'labelField' => '{$relation['labelField']}',\n";
            $content .= "                'valueField' => '{$relation['valueField']}',\n";
            $content .= "                'type' => '{$relation['type']}',\n";
            $content .= "                'searchable' => " . ($relation['searchable'] ? 'true' : 'false') . ",\n";
            $content .= "                'nullable' => " . ($relation['nullable'] ? 'true' : 'false') . ",\n";
            
            if (isset($relation['dependsOn'])) {
                $content .= "                'dependsOn' => '{$relation['dependsOn']}',\n";
            }
            
            $content .= "            ],\n";
        }
        
        return rtrim($content, ",\n");
    }

    /**
     * Guess entity name from field name.
     */
    protected function guessEntityFromField(string $field): string
    {
        // Remove _id suffix and pluralize
        $base = Str::replaceLast('_id', '', $field);
        return Str::plural(Str::kebab($base));
    }

    /**
     * Guess API endpoint from field name.
     */
    protected function guessEndpointFromField(string $field): string
    {
        $entity = $this->guessEntityFromField($field);
        return "/api/crud/{$entity}";
    }

    /**
     * Show a summary of configured relations.
     */
    protected function showRelationsSummary(array $relations): void
    {
        $this->newLine();
        $this->line('<options=bold>Relations Summary:</>');
        
        foreach ($relations as $field => $relation) {
            $this->line("• <comment>{$field}</comment> → {$relation['entity']} ({$relation['type']})");
            $this->line("  Endpoint: {$relation['endpoint']}");
            if (isset($relation['dependsOn'])) {
                $this->line("  Depends on: {$relation['dependsOn']}");
            }
        }
        
        $this->newLine();
        $this->line('<options=bold>Next Steps:</>');
        $this->line('1. Review the generated relations in <comment>config/crud.php</comment>');
        $this->line('2. Ensure the target resources exist and are accessible');
        $this->line('3. Test the dynamic frontend forms with the new relations');
        $this->newLine();
    }
}
