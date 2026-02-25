<?php

declare(strict_types=1);

namespace RRP\T3Toon\ViewHelpers;

use RRP\T3Toon\Service\Toon;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Estimates token count for a TOON string (words/chars heuristic).
 *
 * Usage: <toon:estimateTokens toon="{toonString}" as="stats" /> or output directly.
 */
final class EstimateTokensViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('toon', 'string', 'TOON string to estimate', true);
        $this->registerArgument('as', 'string', 'Variable name to assign the result array (words, chars, tokens_estimate)', false, '');
    }

    /**
     * @param array<string, mixed> $arguments
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        $toon = (string) $arguments['toon'];
        $as = (string) ($arguments['as'] ?? '');
        $result = Toon::estimateTokensStatic($toon);
        if ($as !== '') {
            $variableProvider = $renderingContext->getVariableProvider();
            $variableProvider->add($as, $result);
            $output = $renderChildrenClosure();
            $variableProvider->remove($as);
            return (string) $output;
        }
        return (string) ($result['tokens_estimate'] ?? 0);
    }
}
