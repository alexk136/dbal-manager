<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\DBAL;

enum DbalParameterType
{
    /**
     * Represents the SQL NULL data type.
     */
    case NULL;

    /**
     * Represents the SQL INTEGER data type.
     */
    case INTEGER;

    /**
     * Represents the SQL FLOAT data type.
     */
    case FLOAT;

    /**
     * Represents the SQL CHAR, VARCHAR, or other string data type.
     */
    case STRING;

    /**
     * Represents the SQL large object data type.
     */
    case LARGE_OBJECT;

    /**
     * Represents a boolean data type.
     */
    case BOOLEAN;

    /**
     * Represents a binary string data type.
     */
    case BINARY;

    /**
     * Represents an ASCII string data type.
     */
    case ASCII;

    /**
     * Represents the SQL JSON data type.
     */
    case JSON;

    /**
     * Represents the SQL JSONB data type.
     */
    case JSONB;

    /**
     * Represents the SQL UUID data type.
     */
    case UUID;

    /**
     * Represents the SQL TIMESTAMP data type.
     */
    case TIMESTAMP;

    /**
     * Represents the SQL ARRAY data type.
     */
    case ARRAY;

    /**
     * Represents the SQL FLOAT[] or FLOAT4[] array data type.
     */
    case FLOAT_ARRAY;

    /**
     * Represents the DEFAULT for Postgres ID type.
     * !IMPORTANT it's iternal parameter.
     */
    case DEFAULT;

    public static function default(): array
    {
        return [self::DEFAULT->name, self::DEFAULT];
    }
}
