<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class RunServices extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:services';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all required services (Reverb, Queue, Nutgram) concurrently';

    /**
     * Execute the console command.
     */
    public function handle() {
        $this->info('Starting all services...');

        $processes = [
            'reverb' => new Process(['php', 'artisan', 'reverb:start']),
            'queue'  => new Process(['php', 'artisan', 'queue:listen', '--tries=1']),
            'nutgram' => new Process(['php', 'artisan', 'nutgram:listen']),
        ];

        foreach ($processes as $name => $process) {
            $process->setTimeout(null);
            $process->start();
            $this->line("<info>[{$name}]</info> started.");
        }

        // Loop and check status
        while (true) {
            foreach ($processes as $name => $process) {
                if (!$process->isRunning()) {
                    $this->error("[{$name}] service stopped unexpectedly!");
                    $this->line($process->getErrorOutput());

                    // Terminate all others
                    foreach ($processes as $p) {
                        $p->stop();
                    }
                    return 1;
                }

                // Optional: Output the buffer
                $output = $process->getIncrementalOutput();
                if ($output) {
                    $this->line("<comment>[{$name}]</comment> " . trim($output));
                }

                $errOutput = $process->getIncrementalErrorOutput();
                if ($errOutput) {
                    $this->line("<error>[{$name} ERR]</error> " . trim($errOutput));
                }
            }
            usleep(100000); // 100ms
        }
    }
}
