<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RunBotCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all bot services (Laravel Server, Queue Worker, and Vite Frontend) in a single command';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting all Telegram Bot & Dashboard services (Server, Queue Worker, Vite)...');

        $command = 'npx concurrently -c "#93c5fd,#c4b5fd,#fb7185" ' .
            '"php artisan serve" ' .
            '"php artisan queue:listen --tries=1 --timeout=0" ' .
            '"npx vite" ' .
            '--names=server,queue,vite --kill-others';

        passthru($command);

        return Command::SUCCESS;
    }
}
