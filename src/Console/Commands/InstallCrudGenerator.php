<?php

declare(strict_types=1);

namespace AshiqueAr\LaravelCrudGenerator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Installation command for Laravel CRUD Generator package.
 *
 * This command handles the initial setup of the package by:
 * - Publishing configuration files
 * - Creating necessary directories
 * - Running initial setup tasks
 * - Providing guidance for next steps
 */
class InstallCrudGenerator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:install
                            {--force : Overwrite existing files}
                            {--skip-permissions : Skip permission generation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the Laravel CRUD Generator package';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('Installing Laravel CRUD Generator...');

        try {
            // Publish configuration
            $this->publishConfiguration();

            // Create directories
            $this->createDirectories();

            // Generate permissions if not skipped
            if (!$this->option('skip-permissions')) {
                $this->call('crud:permissions');
            }

            // Show success message and next steps
            $this->showSuccessMessage();

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Installation failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Publish the configuration files.
     */
    protected function publishConfiguration(): void
    {
        $this->info('Publishing configuration...');

        $configExists = File::exists(config_path('crud.php'));

        if ($configExists && !$this->option('force')) {
            if (!$this->confirm('Configuration file already exists. Overwrite?')) {
                $this->warn('Skipped configuration publishing.');
                return;
            }
        }

        $this->call('vendor:publish', [
            '--provider' => 'AshiqueAr\LaravelCrudGenerator\CrudGeneratorServiceProvider',
            '--tag' => 'config',
            '--force' => $this->option('force'),
        ]);

        $this->info('✓ Configuration published');
    }

    /**
     * Create necessary directories.
     */
    protected function createDirectories(): void
    {
        $this->info('Creating directories...');

        $directories = [
            app_path('Services/Crud'),
            app_path('Http/Resources/Crud'),
        ];

        foreach ($directories as $directory) {
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
                $this->info("✓ Created directory: {$directory}");
            } else {
                $this->warn("Directory already exists: {$directory}");
            }
        }
    }

    /**
     * Show success message and next steps.
     */
    protected function showSuccessMessage(): void
    {
        $this->newLine();
        $this->info('🎉 Laravel CRUD Generator installed successfully!');
        $this->newLine();

        $this->line('<options=bold>Next Steps:</>');
        $this->line('1. Configure your CRUD resources in <comment>config/crud.php</comment>');
        $this->line('2. Generate custom logic classes: <comment>php artisan make:crud-logic</comment>');
        $this->line('3. Add new CRUD resources: <comment>php artisan make:crud-resource</comment>');
        $this->line('4. Review generated permissions in your admin panel');
        $this->newLine();

        $this->line('<options=bold>Documentation:</>');
        $this->line('Visit the README.md for detailed usage instructions and examples.');
        $this->newLine();
    }
}


