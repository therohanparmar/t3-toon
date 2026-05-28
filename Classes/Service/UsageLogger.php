<?php

declare(strict_types=1);

namespace RRP\T3Toon\Service;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Persists one row per successful Toon encode/convert call.
 *
 * Designed to never break the caller: every database interaction is wrapped
 * in try/catch so a missing table, failed connection, or schema drift cannot
 * propagate an exception out of Toon::encode()/Toon::convert().
 */
final class UsageLogger
{
    public const TABLE = 'tx_rrpt3toon_log';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly ExtensionConfiguration $extensionConfiguration,
    ) {
    }

    /**
     * Record a single successful encode/convert call.
     *
     * @param mixed  $input  Original input passed to Toon (string/array/object).
     * @param string $output TOON string produced by the encoder.
     */
    public function logEncoded(mixed $input, string $output): void
    {
        try {
            $inputSize = $this->sizeOf($input);
            $outputSize = mb_strlen($output, '8bit');
            $this->connectionPool
                ->getConnectionForTable(self::TABLE)
                ->insert(self::TABLE, [
                    'crdate' => $GLOBALS['EXEC_TIME'] ?? time(),
                    'input_size' => $inputSize,
                    'output_size' => $outputSize,
                    'optimization_pct' => $this->percent($inputSize, $outputSize),
                    'settings_enabled' => $this->isEnabled() ? 1 : 0,
                ]);
        } catch (\Throwable) {
            // Swallow all failures — logging must never break Toon.
        }
    }

    private function sizeOf(mixed $value): int
    {
        if (is_string($value)) {
            return mb_strlen($value, '8bit');
        }
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        return is_string($json) ? mb_strlen($json, '8bit') : 0;
    }

    private function percent(int $inputSize, int $outputSize): float
    {
        if ($inputSize <= 0) {
            return 0.0;
        }
        return round((($inputSize - $outputSize) / $inputSize) * 100, 2);
    }

    private function isEnabled(): bool
    {
        try {
            return (bool) $this->extensionConfiguration->get('rrp_t3toon', 'enabled');
        } catch (\Throwable) {
            return true;
        }
    }
}
