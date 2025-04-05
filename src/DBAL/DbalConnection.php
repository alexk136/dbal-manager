<?php

declare(strict_types=1);

namespace ITech\Bundle\DbalBundle\DBAL;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
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

        return parent::executeStatement($sql, $params, $types);
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
            'applicationCaller' => $this->getApplicationCaller(),
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

    private function getApplicationCaller(): string
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
