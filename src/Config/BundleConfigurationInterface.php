<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Config;

interface BundleConfigurationInterface
{
    public const string ID_NAME = 'id';
    public const string CREATED_AT_NAME = 'created_at';
    public const string UPDATED_AT_NAME = 'updated_at';
    public const string DELETED_AT_NAME = 'deleted_at';
}
