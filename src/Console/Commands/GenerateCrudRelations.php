<?php

declare(strict_types=1);

namespace AshiqueAr\LaravelCrudGenerator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class GenerateCrudRelations extends Command
{
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

    protected $description = 'Generate or merge relation configurations for existing CRUD resources';

    public function handle(): int
    {
        $resource = $this->argument('resource');
        if (!is_string($resource) || empty($resource)) {
            $this->error('Resource name is required.');
            return Command::FAILURE;
        }

        $configPath = config_path('crud.php');
        if (!File::exists($configPath)) {
            $this->error('CRUD configuration file not found. Run php artisan crud:install first.');
            return Command::FAILURE;
        }

        if ($this->option('interactive')) {
            return $this->runInteractiveMode($resource);
        }

        return $this->addSingleRelation($resource);
    }

    protected function runInteractiveMode(string $resource): int
    {
        $this->info("Interactive relation configuration for resource: {$resource}\n");
        $relations = [];

        while (true) {
            $field = $this->ask('Enter field name (e.g., category_id) or press Enter to finish');
            if (empty($field)) {
                break;
            }
            $relation = $this->collectRelationData($field);
            if ($relation) {
                $relations[$field] = $relation;
                $this->info("✓ Queued relation for field: {$field}\n");
            }
        }

        if (empty($relations)) {
            $this->warn('No relations configured.');
            return Command::SUCCESS;
        }

        return $this->updateResourceWithRelations($resource, $relations);
    }

    protected function addSingleRelation(string $resource): int
    {
        $field = $this->option('field');
        if (!$field) {
            $this->error('Field name is required. Use --field or --interactive mode.');
            return Command::FAILURE;
        }

        $relation = [
            'entity' => $this->option('entity') ?: $this->guessEntityFromField($field),
            'labelField' => $this->option('label-field'),
            'valueField' => $this->option('value-field'),
            'displayField' => $this->option('display-field') ?: $this->option('label-field'),
            'searchable' => (bool) $this->option('searchable'),
            'required' => !$this->option('nullable'),
        ];

        if ($depends = $this->option('depends-on')) {
            $relation['depends_on'] = $depends;
            $relation['filter_by'] = $this->option('filter-by') ?: $depends;
        }

        return $this->updateResourceWithRelations($resource, [$field => $relation]);
    }

    protected function collectRelationData(string $field): ?array
    {
        $entity = $this->ask('Entity name', $this->guessEntityFromField($field));
        $labelField = $this->ask('Label field', 'name');
        $valueField = $this->ask('Value field', 'id');
        $displayField = $this->ask('Display field (for tables)', $labelField);
        $searchable = $this->confirm('Make searchable?', false);
        $required = $this->confirm('Is this field required?', true);
        $dependsOn = $this->ask('Depends on field (optional)', null);

        $relation = compact('entity', 'labelField', 'valueField', 'displayField', 'searchable', 'required');

        if ($dependsOn) {
            $relation['depends_on'] = $dependsOn;
            $relation['filter_by'] = $this->ask('Filter by field', $dependsOn);
        }

        return $relation;
    }

    protected function guessEntityFromField(string $field): string
    {
        $base = Str::replaceLast('_id', '', $field);
        return Str::plural(Str::kebab($base));
    }

    protected function showRelationsSummary(array $relations): void
    {
        $this->line("\n<options=bold>Relations Summary:</>");
        foreach ($relations as $field => $rel) {
            $this->line("• <comment>{$field}</comment> → {$rel['entity']}");
            $this->line("  Label: {$rel['labelField']}, Display: {$rel['displayField']}");
            $this->line("  Required: " . ($rel['required'] ? 'Yes' : 'No') . ", Searchable: " . ($rel['searchable'] ? 'Yes' : 'No'));
            if (isset($rel['depends_on'])) {
                $this->line("  Depends on: {$rel['depends_on']} (filter by: {$rel['filter_by']})");
            }
        }
        $this->line("\n<options=bold>Next Steps:</>");
        $this->line('1. Review <comment>config/crud.php</comment>');
        $this->line('2. Test your updated forms & endpoints');
        $this->newLine();
    }

    protected function updateResourceWithRelations(string $resource, array $relations): int
    {
        $path = config_path('crud.php');
        $config = include $path;

        if (!isset($config['resources'][$resource])) {
            $this->error("Resource '{$resource}' not found in CRUD configuration.");
            return Command::FAILURE;
        }

        $existing = $config['resources'][$resource]['relationships'] ?? [];

        // Filter out duplicates
        $newRels = [];
        foreach ($relations as $field => $definition) {
            if (!array_key_exists($field, $existing)) {
                $newRels[$field] = $definition;
            }
        }

        if (empty($newRels)) {
            $this->info("No new relations to add for '{$resource}'.");
            return Command::SUCCESS;
        }

        // Decide insertion order
        $where = Arr::get($config, 'add_new_resource_to', 'bottom');
        $where = in_array($where, ['top', 'bottom'], true) ? $where : 'bottom';

        $merged = $where === 'top'
            ? $newRels + $existing
            : $existing + $newRels;

        $config['resources'][$resource]['relationships'] = $merged;

        $this->writeConfigFile($path, $config);

        $this->info("✓ Relations for '{$resource}' updated.");
        $this->showRelationsSummary($relations);

        return Command::SUCCESS;
    }

    protected function writeConfigFile(string $path, array $config): void
    {
        $php = "<?php\n\n";
        $php .= "declare(strict_types=1);\n\n";
        $php .= "return " . $this->arrayToShortPhp($config) . ";\n";

        File::put($path, $php);
    }

    protected function arrayToShortPhp(array $arr, int $lvl = 0): string
    {
        $indent = str_repeat('    ', $lvl);
        $lines = ["["];

        foreach ($arr as $key => $val) {
            $prefix = $indent . '    ';
            $line = $prefix;

            if (is_string($key)) {
                $line .= var_export($key, true) . ' => ';
            }

            $line .= $this->valueToPhp($val, $lvl + 1) . ',';
            $lines[] = $line;
        }

        $lines[] = $indent . "]";
        return implode("\n", $lines);
    }

    protected function valueToPhp($val, int $lvl): string
    {
        if (is_array($val)) {
            return $this->arrayToShortPhp($val, $lvl);
        }

        if (is_bool($val)) {
            return $val ? 'true' : 'false';
        }

        if (is_null($val)) {
            return 'null';
        }

        return var_export($val, true);
    }
}
