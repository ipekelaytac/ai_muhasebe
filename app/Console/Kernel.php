<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Load commands, excluding _archived directory (causes duplicate class errors).
     */
    protected function load($paths)
    {
        $paths = array_unique(Arr::wrap($paths));
        $paths = array_filter($paths, function ($path) {
            return is_dir($path);
        });

        if (empty($paths)) {
            return;
        }

        $namespace = $this->app->getNamespace();

        foreach ((new Finder)->in($paths)->files()->exclude('_archived') as $command) {
            $command = $namespace.str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::after($command->getRealPath(), realpath(app_path()).DIRECTORY_SEPARATOR)
            );

            if (is_subclass_of($command, \Illuminate\Console\Command::class) &&
                ! (new \ReflectionClass($command))->isAbstract()) {
                \Illuminate\Console\Application::starting(function ($artisan) use ($command) {
                    $artisan->resolve($command);
                });
            }
        }
    }
}
