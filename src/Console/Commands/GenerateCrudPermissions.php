<?php

declare(strict_types=1);

namespace AshiqueAr\LaravelCrudGenerator\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GenerateCrudPermissions extends Command
{
    protected $signature = 'crud:permissions
                            {--resource= : Generate permissions for a specific resource only}
                            {--role= : Assign permissions to a specific role}
                            {--force : Recreate existing permissions}';

    protected $description = 'Generate CRUD permissions for configured resources';

    /** @var string[] */
    protected array $defaultOperations = ['create', 'read', 'update', 'delete'];

    /** @var string */
    protected string $defaultGuard = 'web';

    public function handle(): int
    {
        $this->info('Generating CRUD permissions...');

        $resources = $this->getResources();
        if (empty($resources)) {
            $this->warn('No resources found in configuration.');
            return Command::SUCCESS;
        }

        $this->generatePermissions($resources);

        // 1) If user passed --role, sync to that role
        if ($roleName = $this->option('role')) {
            $this->assignPermissionsToRole($roleName);
        }

        // 2) Always sync to the super-admin role by default
        $superAdminRole = config('crud.permissions.super_admin_role');
        if ($superAdminRole) {
            $this->assignPermissionsToRole($superAdminRole);
        }

        $this->info('✓ CRUD permissions generated successfully!');
        return Command::SUCCESS;
    }

    protected function getResources(): array
    {
        if ($res = $this->option('resource')) {
            return [$res];
        }

        return array_keys(config('crud.resources', []));
    }

    /**
     * @return string[]
     */
    protected function getGuards(): array
    {
        $guards = config('crud.permissions.guard', [$this->defaultGuard]);

        return is_array($guards) ? $guards : [$guards];
    }

    protected function getFormat(): string
    {
        return config('crud.permissions.format', '{action}.{resource}');
    }

    /**
     * @return string[]
     */
    protected function getActions(): array
    {
        return config('crud.permissions.actions', $this->defaultOperations);
    }

    protected function generatePermissions(array $resources): void
    {
        $created = 0;
        $skipped = 0;

        $guards = $this->getGuards();
        $format = $this->getFormat();
        $actions = $this->getActions();

        foreach ($resources as $resource) {
            $this->line("Processing resource: <comment>{$resource}</comment>");

            foreach ($actions as $action) {
                // build the permission name from your format string
                $name = strtr($format, [
                    '{action}' => $action,
                    '{resource}' => $resource,
                ]);

                foreach ($guards as $guard) {
                    $exists = Permission::where([
                        ['name', $name],
                        ['guard_name', $guard],
                    ])->exists();

                    if ($exists && !$this->option('force')) {
                        $this->warn("  ↳ Permission '{$name}' [{$guard}] already exists");
                        $skipped++;
                        continue;
                    }

                    if ($exists && $this->option('force')) {
                        Permission::where('name', $name)
                            ->where('guard_name', $guard)
                            ->delete();
                        $this->warn("  ↳ Deleted existing permission '{$name}' [{$guard}]");
                    }

                    Permission::create([
                        'name' => $name,
                        'guard_name' => $guard,
                    ]);

                    $this->info("  ↳ Created permission '{$name}' [{$guard}]");
                    $created++;
                }
            }
        }

        $this->newLine();
        $this->info("Summary: {$created} permissions created, {$skipped} skipped");
    }

    /**
     * Sync *all* permissions matching this role name (and its guard)
     * to the given role.
     */
    protected function assignPermissionsToRole(string $roleName): void
    {
        // if you want the role to exist on each guard, loop guards
        foreach ($this->getGuards() as $guard) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => $guard]
            );

            // grab every permission for this guard
            $perms = Permission::where('guard_name', $guard)
                ->pluck('name')
                ->all();

            $role->syncPermissions($perms);

            $this->info("✓ Assigned " . count($perms) . " [{$guard}] permissions to role '{$roleName}'");
        }
    }
}
