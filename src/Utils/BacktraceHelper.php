<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Utils;

final class BacktraceHelper
{
    /**
     * Определяет класс и метод, откуда был вызван код.
     */
    public static function getApplicationCaller(): string
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        foreach ($backtrace as $i => $item) {
            $filePath = $item['file'] ?? null;

            // Пропускаем vendor
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
