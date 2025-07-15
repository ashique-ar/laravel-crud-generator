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
                            {--label-field=name : Field to use as label (default: name)}
                            {--value-field=id : Field to use as value (default: id)}
                            {--display-field= : Field to display in tables (default: same as label-field)}
                            {--searchable : Make the relation searchable}
                            {--nullable : Make the relation optional (not required)}
                            {--depends-on= : Field this relation depends on (for dependent dropdowns)}
                            {--filter-by= : Field to filter by when depends-on is set (default: same as depends-on)}
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
            'labelField' => $this->option('label-field') ?: 'name',
            'valueField' => $this->option('value-field') ?: 'id',
            'displayField' => $this->option('display-field') ?: $this->option('label-field') ?: 'name',
            'searchable' => $this->option('searchable') ? true : false,
            'required' => !$this->option('nullable'),
        ];

        if ($dependsOn = $this->option('depends-on')) {
            $relation['depends_on'] = $dependsOn;
            $relation['filter_by'] = $this->option('filter-by') ?: $dependsOn;
        }

        return $this->updateResourceWithRelations($resource, [$field => $relation]);
    }

    /**
     * Collect relation data interactively.
     */
    protected function collectRelationData(string $field): ?array
    {
        $entity = $this->ask('Entity name', $this->guessEntityFromField($field));
        $labelField = $this->ask('Label field', 'name');
        $valueField = $this->ask('Value field', 'id');
        $displayField = $this->ask('Display field (for tables)', $labelField);
        $searchable = $this->confirm('Make searchable?', true);
        $required = $this->confirm('Is this field required?', false);
        $dependsOn = $this->ask('Depends on field (optional)');
        $filterBy = null;

        if ($dependsOn) {
            $filterBy = $this->ask('Filter by field', $dependsOn);
        }

        $relation = [
            'entity' => $entity,
            'labelField' => $labelField,
            'valueField' => $valueField,
            'displayField' => $displayField,
            'searchable' => $searchable,
            'required' => $required,
        ];

        if ($dependsOn) {
            $relation['depends_on'] = $dependsOn;
            $relation['filter_by'] = $filterBy;
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

        // 1) Capture the full resource block up to its closing "],"
        $resourceRegex = "/('{$resource}'\s*=>\s*\[)([\s\S]*?)(\n {8}\],)/";
        if (!preg_match($resourceRegex, $content, $m)) {
            $this->error("Resource '{$resource}' not found.");
            return Command::FAILURE;
        }

        $block = $m[2];

        // Build the new relationships snippet (12‑space indent)
        $newRel = $this->generateRelationshipsContent($relations);
        $relBlock =
            "            'relationships' => [\n" .
            $newRel . "\n" .
            "            ],\n";

        // 2) If an existing relationships block is present (12 spaces indent), replace it
        $hasExisting =
            preg_match(
                "/ {12}'relationships'\s*=>\s*\[[\s\S]*?\],\n/",
                $block
            );

        if ($hasExisting) {
            // Replace whatever’s inside relationships => [ … ],
            $block = preg_replace(
                "/ {12}'relationships'\s*=>\s*\[[\s\S]*?\],\n/",
                "\n" . $relBlock,
                $block
            );
        } else {
            // 3) Otherwise append our new block just before the resource's closing (8 spaces)
            $block = rtrim($block) . "\n" . $relBlock;
        }

        // 4) Reassemble and write back
        $newContent = preg_replace(
            $resourceRegex,
            "$1{$block}$3",
            $content
        );
        File::put($configPath, $newContent);

        $this->info("✓ Relations for '{$resource}' have been updated.");
        $this->showRelationsSummary($relations);

        return Command::SUCCESS;
    }



    /**
     * Generate the relationships content string.
     */
    protected function generateRelationshipsContent(array $relations): string
    {
        $content = '';
        foreach ($relations as $field => $relation) {
            $content .= "            '$field' => [\n";
            $content .= "                'entity' => '{$relation['entity']}',\n";
            $content .= "                'labelField' => '{$relation['labelField']}',\n";
            $content .= "                'valueField' => '{$relation['valueField']}',\n";
            $content .= "                'displayField' => '{$relation['displayField']}',\n";
            $content .= "                'searchable' => " . ($relation['searchable'] ? 'true' : 'false') . ",\n";
            $content .= "                'required' => " . ($relation['required'] ? 'true' : 'false') . ",\n";

            if (isset($relation['depends_on'])) {
                $content .= "                'depends_on' => '{$relation['depends_on']}',\n";
            }

            if (isset($relation['filter_by'])) {
                $content .= "                'filter_by' => '{$relation['filter_by']}',\n";
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
     * Show a summary of configured relations.
     */
    protected function showRelationsSummary(array $relations): void
    {
        $this->newLine();
        $this->line('<options=bold>Relations Summary:</>');

        foreach ($relations as $field => $relation) {
            $this->line("• <comment>{$field}</comment> → {$relation['entity']}");
            $this->line("  Label: {$relation['labelField']}, Display: {$relation['displayField']}");
            $this->line("  Required: " . ($relation['required'] ? 'Yes' : 'No') . ", Searchable: " . ($relation['searchable'] ? 'Yes' : 'No'));
            if (isset($relation['depends_on'])) {
                $this->line("  Depends on: {$relation['depends_on']} (filter by: {$relation['filter_by']})");
            }
        }

        $this->newLine();
        $this->line('<options=bold>Next Steps:</>');
        $this->line('1. Review the generated relationships in <comment>config/crud.php</comment>');
        $this->line('2. Ensure the target resources exist and are accessible');
        $this->line('3. Test the dynamic frontend forms with the new relationships');
        $this->newLine();
    }
}
