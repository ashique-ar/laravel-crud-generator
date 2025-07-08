<?php

declare(strict_types=1);

namespace AshiqueAr\LaravelCrudGenerator\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Generate CRUD permissions command.
 *
 * This command automatically generates the standard CRUD permissions
 * (create, read, update, delete) for all configured resources in the
 * crud configuration file.
 */
class GenerateCrudPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:permissions
                            {--resource= : Generate permissions for specific resource only}
                            {--role= : Assign permissions to specific role}
                            {--force : Recreate existing permissions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate CRUD permissions for configured resources';

    /**
     * Standard CRUD operations that require permissions.
     *
     * @var array<string>
     */
    protected array $operations = ['create', 'read', 'update', 'delete'];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('Generating CRUD permissions...');

        try {
            $resources = $this->getResources();

            if (empty($resources)) {
                $this->warn('No resources found in configuration.');
                return Command::SUCCESS;
            }

            $this->generatePermissions($resources);

            if ($role = $this->option('role')) {
                $this->assignPermissionsToRole($role);
            }

            $this->info('✓ CRUD permissions generated successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to generate permissions: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Get resources from configuration or command option.
     *
     * @return array<string>
     */
    protected function getResources(): array
    {
        if ($resource = $this->option('resource')) {
            return [$resource];
        }

        $config = config('crud.resources', []);
        return array_keys($config);
    }

    /**
     * Generate permissions for the given resources.
     *
     * @param array<string> $resources
     */
    protected function generatePermissions(array $resources): void
    {
        $createdCount = 0;
        $skippedCount = 0;

        foreach ($resources as $resource) {
            $this->line("Processing resource: <comment>{$resource}</comment>");

            foreach ($this->operations as $operation) {
                $permissionName = "{$operation}-{$resource}";
                
                $exists = Permission::where('name', $permissionName)->exists();

                if ($exists && !$this->option('force')) {
                    $this->warn("  ↳ Permission '{$permissionName}' already exists");
                    $skippedCount++;
                    continue;
                }

                if ($exists && $this->option('force')) {
                    Permission::where('name', $permissionName)->delete();
                    $this->warn("  ↳ Deleted existing permission '{$permissionName}'");
                }

                Permission::create([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                ]);

                $this->info("  ↳ Created permission '{$permissionName}'");
                $createdCount++;
            }
        }

        $this->newLine();
        $this->info("Summary: {$createdCount} permissions created, {$skippedCount} skipped");
    }

    /**
     * Assign generated permissions to a specific role.
     *
     * @param string $roleName
     */
    protected function assignPermissionsToRole(string $roleName): void
    {
        $this->info("Assigning permissions to role: {$roleName}");

        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

        $resources = $this->getResources();
        $permissions = [];

        foreach ($resources as $resource) {
            foreach ($this->operations as $operation) {
                $permissions[] = "{$operation}-{$resource}";
            }
        }

        $existingPermissions = Permission::whereIn('name', $permissions)->get();
        $role->syncPermissions($existingPermissions);

        $this->info("✓ Assigned {$existingPermissions->count()} permissions to role '{$roleName}'");
    }
}


