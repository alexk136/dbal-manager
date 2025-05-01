<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Utils;

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
}
