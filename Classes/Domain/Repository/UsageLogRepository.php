<?php

declare(strict_types=1);

namespace RRP\T3Toon\Domain\Repository;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use RRP\T3Toon\Service\UsageLogger;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

/**
 * Read/delete access to the TOON usage log table.
 *
 * Filters (passed as an associative array) accept:
 *   - dateFrom (int unix)      — crdate >= dateFrom
 *   - dateTo   (int unix)      — crdate <= dateTo (end-of-day caller's responsibility)
 *   - enabled  (int 0|1|null)  — settings_enabled match (null = no filter)
 *   - minPct   (float|null)    — optimization_pct >= minPct
 */
final class UsageLogRepository
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findAll(array $filters): array
    {
        $qb = $this->createQueryBuilder();
        $this->applyFilters($qb, $filters);

        return $qb
            ->select('*')
            ->from(UsageLogger::TABLE)
            ->orderBy('crdate', 'DESC')
            ->addOrderBy('uid', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @param int[] $uids
     */
    public function deleteByUids(array $uids): int
    {
        $clean = array_values(array_unique(array_filter(array_map('intval', $uids), static fn (int $v) => $v > 0)));
        if ($clean === []) {
            return 0;
        }

        $connection = $this->connectionPool->getConnectionForTable(UsageLogger::TABLE);
        $qb = $connection->createQueryBuilder();
        return (int) $qb
            ->delete(UsageLogger::TABLE)
            ->where($qb->expr()->in('uid', $qb->createNamedParameter($clean, ArrayParameterType::INTEGER)))
            ->executeStatement();
    }

    private function createQueryBuilder(): QueryBuilder
    {
        return $this->connectionPool->getQueryBuilderForTable(UsageLogger::TABLE);
    }

    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        $expr = $qb->expr();

        if (!empty($filters['dateFrom'])) {
            $qb->andWhere($expr->gte('crdate', $qb->createNamedParameter((int) $filters['dateFrom'], ParameterType::INTEGER)));
        }
        if (!empty($filters['dateTo'])) {
            $qb->andWhere($expr->lte('crdate', $qb->createNamedParameter((int) $filters['dateTo'], ParameterType::INTEGER)));
        }
        if (isset($filters['enabled']) && $filters['enabled'] !== null && $filters['enabled'] !== '') {
            $qb->andWhere($expr->eq('settings_enabled', $qb->createNamedParameter((int) $filters['enabled'], ParameterType::INTEGER)));
        }
        if (isset($filters['minPct']) && $filters['minPct'] !== '' && $filters['minPct'] !== null) {
            $qb->andWhere($expr->gte('optimization_pct', $qb->createNamedParameter((float) $filters['minPct'])));
        }
    }
}
