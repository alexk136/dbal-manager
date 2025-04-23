<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\Config;

interface BundleConfigurationInterface
{
    public const string ID_NAME = 'id';
    public const string CREATED_AT_NAME = 'createdAt';
    public const string UPDATED_AT_NAME = 'updatedAt';
    public const string DELETED_AT_NAME = 'deletedAt';
}
