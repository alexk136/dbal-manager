<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\Utils;

final class IdGenerator
{
    /**
     * Generates a unique ID based on PID and uniqid.
     */
    public static function generateUniqueId(): string
    {
        $pid = getmypid();
        $uniq = uniqid('', true); // true — adds extra entropy.

        // Take the last 5 digits of the PID and hash the uniqid.
        $pidPart = str_pad((string) ($pid % 100000), 5, '0', STR_PAD_LEFT);
        $uniqPart = substr(md5($uniq), -13); // You can use sha1/uuid, but md5 is shorter.

        return $pidPart . $uniqPart;
    }
}
