<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Process;
use Throwable;

/**
 * Which checkout is answering: `<branch>@<short commit>`, shown by the start commands and, through
 * SURVEYJS_BUILD, on the pages. Hosted demos set SURVEYJS_BUILD at deploy; without it and without git, null.
 */
final class Build
{
    public static function current(): ?string
    {
        if (is_string($build = config('surveyjs.build')) && $build !== '') {
            return $build;
        }
        try {
            $branch = Process::path(base_path())->run(['git', 'rev-parse', '--abbrev-ref', 'HEAD']);
            $commit = Process::path(base_path())->run(['git', 'rev-parse', '--short', 'HEAD']);
        } catch (Throwable) {
            return null;   // no git on PATH
        }

        return $branch->successful() && $commit->successful() ? trim($branch->output()).'@'.trim($commit->output()) : null;
    }

    /** The start line's "(<build>)", or nothing without one. */
    public static function label(): string
    {
        $build = self::current();

        return $build === null ? '' : " ({$build})";
    }
}
