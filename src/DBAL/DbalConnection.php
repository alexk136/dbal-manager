<?php

declare(strict_types=1);

namespace Elrise\Bundle\DbalBundle\DBAL;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Elrise\Bundle\DbalBundle\Utils\ArraySerializer;
use Elrise\Bundle\DbalBundle\Utils\BacktraceHelper;
use JsonException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpFoundation\RequestStack;

class DbalConnection extends Connection
{
    private ?Command $command = null;
    private ?RequestStack $requestStack = null;
    private bool $additionalSqlCommentEnable = false;

    /**
     * {@inheritDoc}
     *
     * @throws JsonException
     */
    public function executeQuery($sql, array $params = [], $types = [], ?QueryCacheProfile $qcp = null): Result
    {
        $sql = $this->addSqlComment($sql);

        return parent::executeQuery($sql, $params, $types, $qcp);
    }

    /**
     * {@inheritDoc}
     *
     * @throws JsonException
     */
    public function executeStatement($sql, array $params = [], array $types = []): int|string
    {
        $sql = $this->addSqlComment($sql);

        [$normalizedData, $normalizedTypes] = $this->normalizeData($params, $types);

        return parent::executeStatement($sql, $normalizedData, $normalizedTypes);
    }

    /**
     * {@inheritDoc}
     *
     * @throws JsonException
     */
    public function prepare(string $sql): Statement
    {
        $sql = $this->addSqlComment($sql);

        return parent::prepare($sql);
    }

    /**
     * @internal
     */
    public function setAdditionalSqlCommentEnable(bool $enabled): void
    {
        $this->additionalSqlCommentEnable = $enabled;
    }

    /**
     * @internal
     */
    public function setCommand(?Command $command = null): void
    {
        $this->command = $command;
    }

    /**
     * @internal
     */
    public function setRequestStack(?RequestStack $requestStack = null): void
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Добавляет SQL комментарий с контекстом исполнения, если это разрешено и комментарий ещё не добавлен.
     *
     * @throws JsonException
     */
    private function addSqlComment(string $sql): string
    {
        if (
            !$this->additionalSqlCommentEnable
            || str_starts_with(trim($sql), '/*') // уже содержит комментарий
        ) {
            return $sql;
        }

        $context = [
            'applicationCaller' => BacktraceHelper::getApplicationCaller(),
            'entryPointController' => $this->getEntryPointClass(),
        ];

        return sprintf('/* %s */ %s', json_encode($context, JSON_THROW_ON_ERROR), $sql);
    }

    private function getEntryPointClass(): string
    {
        $mainRequest = $this->requestStack?->getMainRequest();
        $requestController = $mainRequest?->attributes->get('_controller');

        if (is_array($requestController)) {
            return implode('::', $requestController);
        }

        if (is_string($requestController)) {
            return $requestController;
        }

        if ($this->command !== null) {
            return get_class($this->command);
        }

        return '';
    }

    /**
     * @throws JsonException
     */
    private function normalizeData(array $data, array $types = []): array
    {
        try {
            $platform = $this->getDatabasePlatform();
        } catch (Exception) {
            return [$data, $types];
        }

        $normalized = [];
        $normalizedTypes = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $normalized[$key] = ArraySerializer::serialize($value, $platform::class);
                $normalizedTypes[$key] = ParameterType::STRING;
            } else {
                $normalized[$key] = $value;

                if (isset($types[$key])) {
                    $normalizedTypes[$key] = $types[$key];
                }
            }
        }

        return [$normalized, $normalizedTypes];
    }
}
