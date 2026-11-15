<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Build;
use App\Support\PortCheck;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * `composer start` (and the server in `composer dev`): PHP's built-in server on APP_PORT, after checking
 * the port is free, with the checkout's build in SURVEYJS_BUILD for the pages' "Running …" line.
 * `php artisan serve` starts a child PHP that doesn't inherit -d flags, so this starts `php -S` itself
 * with upload limits that fit store-files' 5 MB files.
 */
class SurveyJsServe extends Command
{
    protected $signature = 'surveyjs:serve';

    protected $description = 'Serve the app on APP_PORT (default 8000) with PHP\'s built-in server, after checking the port is free';

    public function handle(): int
    {
        $port = (int) config('surveyjs.app_port');
        try {
            PortCheck::ensureFree($port, 'APP_PORT');
        } catch (RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $build = Build::current();
        $this->components->info('Serving '.base_path().($build === null ? '' : " ({$build})")." on http://127.0.0.1:{$port}");

        $server = Process::forever()->path(base_path())->env(['SURVEYJS_BUILD' => $build ?? ''])->start([
            PHP_BINARY, '-d', 'upload_max_filesize=10M', '-d', 'post_max_size=12M', '-d', 'session.upload_progress.enabled=0',
            '-S', "127.0.0.1:{$port}", '-t', 'public', 'server.php',
        ], fn (string $type, string $output) => $this->output->write($output));

        // Ctrl+C reaches the server too (same console). On Linux and macOS a SIGTERM to this command (composer dev,
        // Playwright) stops the server here; Windows has no signals, and those tools end the whole process tree.
        if (defined('SIGTERM')) {
            $this->trap([SIGINT, SIGTERM], fn () => $server->signal(SIGTERM));
        }

        return $server->wait()->successful() ? self::SUCCESS : self::FAILURE;
    }
}
