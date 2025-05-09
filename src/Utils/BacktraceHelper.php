<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Utils;

final class BacktraceHelper
{
    /**
     * Determines the class and method from where the code was called.
     */
    public static function getApplicationCaller(): string
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        foreach ($backtrace as $i => $item) {
            $filePath = $item['file'] ?? null;

            if (!$filePath || str_contains($filePath, '/vendor/')) {
                continue;
            }

            $caller = $backtrace[$i + 1] ?? null;
            $class = $caller['class'] ?? null;
            $function = $caller['function'] ?? null;

            return $class && $function ? sprintf('%s::%s', $class, $function) : '';
        }

        return '';
    }
}
