<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * Refuses a busy port before a server starts. Don't rely on the bind error: on Windows, `php -S` and
 * Workerman both report success on a port another process holds, and that process keeps answering.
 */
final class PortCheck
{
    /** @throws RuntimeException when anything answers on 127.0.0.1:$port */
    public static function ensureFree(int $port, string $variable): void
    {
        $socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, timeout: 0.5);
        if ($socket === false) {
            return;
        }
        fclose($socket);

        throw new RuntimeException("Port {$port} is already in use. Another copy of this app (another checkout or branch?) may be running. Stop it, or set {$variable}.");
    }
}
