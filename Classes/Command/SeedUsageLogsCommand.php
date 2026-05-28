<?php

declare(strict_types=1);

namespace RRP\T3Toon\Command;

use RRP\T3Toon\Service\UsageLogger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;

#[AsCommand(
    name: 't3toon:seed-logs',
    description: 'Insert random TOON usage log entries for development/testing.'
)]
final class SeedUsageLogsCommand extends Command
{
    public function __construct(private readonly ConnectionPool $connectionPool)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('count', InputArgument::OPTIONAL, 'Number of rows to insert', '500')
            ->addArgument('days', InputArgument::OPTIONAL, 'Spread timestamps over the last N days', '30');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = max(0, (int) $input->getArgument('count'));
        $days = max(1, (int) $input->getArgument('days'));

        if ($count === 0) {
            $output->writeln('<comment>Nothing to insert (count=0).</comment>');
            return Command::SUCCESS;
        }

        $connection = $this->connectionPool->getConnectionForTable(UsageLogger::TABLE);
        $now = time();
        $earliest = $now - ($days * 86400);

        $output->writeln(sprintf('<info>Inserting %d rows into %s…</info>', $count, UsageLogger::TABLE));

        $connection->beginTransaction();
        try {
            for ($i = 0; $i < $count; $i++) {
                $inputSize = random_int(120, 50000);
                $enabled = random_int(0, 10) > 1 ? 1 : 0; // ~80% enabled

                if ($enabled === 1) {
                    // Realistic compression: 25–75%, with a few outliers
                    $pct = random_int(2500, 7500) / 100;
                    $outputSize = max(1, (int) round($inputSize * (1 - $pct / 100)));
                } else {
                    // Disabled: passthrough, sizes equal
                    $outputSize = $inputSize;
                }
                $optimizationPct = $inputSize > 0
                    ? round((($inputSize - $outputSize) / $inputSize) * 100, 2)
                    : 0.0;

                $connection->insert(UsageLogger::TABLE, [
                    'crdate' => random_int($earliest, $now),
                    'input_size' => $inputSize,
                    'output_size' => $outputSize,
                    'optimization_pct' => $optimizationPct,
                    'settings_enabled' => $enabled,
                ]);
            }
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            $output->writeln(sprintf('<error>Failed: %s</error>', $e->getMessage()));
            return Command::FAILURE;
        }

        $output->writeln(sprintf('<info>Done. %d rows inserted.</info>', $count));
        return Command::SUCCESS;
    }
}
