<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Running it locally: every start command refuses a port that is already in use (on Windows, `php -S` and
 * Workerman would start anyway while the other process keeps answering), and the pages say which checkout
 * answered.
 */
class RunCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public static function startCommands(): array
    {
        return [
            'composer start' => ['surveyjs:serve', 'surveyjs.app_port', 'APP_PORT'],
            'composer relay' => ['relay:fill', 'surveyjs.relay.fill_port', 'RELAY_PORT'],
            'composer edit-relay' => ['relay:edit', 'surveyjs.relay.edit_port', 'EDIT_RELAY_PORT'],
        ];
    }

    #[DataProvider('startCommands')]
    public function test_a_start_command_refuses_a_busy_port(string $command, string $config, string $variable): void
    {
        $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        $this->assertNotFalse($server, $errstr);
        $port = (int) substr(strrchr((string) stream_socket_get_name($server, false), ':'), 1);
        config([$config => $port]);

        try {
            $this->artisan($command)
                ->expectsOutputToContain("Port {$port} is already in use. Another copy of this app (another checkout or branch?) may be running. Stop it, or set {$variable}.")
                ->assertFailed();
        } finally {
            fclose($server);
        }
    }

    public function test_the_pages_show_the_build_when_it_is_set(): void
    {
        config(['surveyjs.build' => 'feature/x@abc1234']);

        $this->get('/')->assertOk()->assertSee('Running feature/x@abc1234');
        $this->get('/examples/save-response')->assertOk()->assertSee('Running feature/x@abc1234');
    }

    public function test_the_pages_leave_the_build_out_when_it_is_not_set(): void
    {
        config(['surveyjs.build' => null]);

        $this->get('/')->assertOk()->assertDontSee('Running ');
        $this->get('/examples/save-response')->assertOk()->assertDontSee('Running ');
    }
}
