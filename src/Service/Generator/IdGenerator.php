<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Service\Generator;

final class IdGenerator
{
    /**
     * Генерирует уникальный ID на основе PID и uniqid.
     */
    public static function generateUniqueId(): string
    {
        $pid = getmypid();
        $uniq = uniqid('', true); // true — добавляет дополнительную энтропию

        // Возьмем последние 5 цифр PID, и хешируем uniqid
        $pidPart = str_pad((string) ($pid % 100000), 5, '0', STR_PAD_LEFT);
        $uniqPart = substr(md5($uniq), -13); // можно и sha1/uuid, но md5 короче

        return $pidPart . $uniqPart;
    }

    /**
     * Генерирует случайный ID потока в пределах заданного количества.
     */
    public static function generateThreadId(int $maxThread): int
    {
        return random_int(1, $maxThread); // безопаснее, чем rand()
    }

    /**
     * Определяет номер потока на основе ID.
     */
    public static function generateThreadById(int $maxThread, string $id): int
    {
        $num = preg_replace('/\D/', '', $id); // убрать нецифровые символы

        return ((int) $num % $maxThread) + 1;
    }
}
