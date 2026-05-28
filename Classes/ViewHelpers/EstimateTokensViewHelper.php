<?php

declare(strict_types=1);

namespace RRP\T3Toon\ViewHelpers;

use RRP\T3Toon\Service\Toon;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Estimates token count for a TOON string (words/chars heuristic).
 *
 * Usage: <toon:estimateTokens toon="{toonString}" as="stats" /> or output directly.
 */
final class EstimateTokensViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('toon', 'string', 'TOON string to estimate', true);
        $this->registerArgument('as', 'string', 'Variable name to assign the result array (words, chars, tokens_estimate)', false, '');
    }

    public function render(): string
    {
        $toon = (string) $this->arguments['toon'];
        $as = (string) ($this->arguments['as'] ?? '');
        $result = Toon::estimateTokensStatic($toon);
        if ($as !== '') {
            $variableProvider = $this->renderingContext->getVariableProvider();
            $variableProvider->add($as, $result);
            $output = $this->renderChildren();
            $variableProvider->remove($as);
            return (string) $output;
        }
        return (string) ($result['tokens_estimate'] ?? 0);
    }
}
