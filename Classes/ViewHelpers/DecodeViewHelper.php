<?php

declare(strict_types=1);

namespace RRP\T3Toon\ViewHelpers;

use RRP\T3Toon\Domain\Model\DecodeOptions;
use RRP\T3Toon\Service\Toon;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Decodes a TOON string to a PHP array.
 *
 * Usage: <toon:decode toon="{toonString}" as="decoded" /> then use {decoded} in f:for etc.
 */
final class DecodeViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('toon', 'string', 'TOON-formatted string to decode', true);
        $this->registerArgument('as', 'string', 'Variable name to assign the decoded array to', false, '');
        $this->registerArgument('lenient', 'bool', 'If true, do not coerce scalar types', false, false);
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
        $lenient = (bool) ($arguments['lenient'] ?? false);
        $options = $lenient ? DecodeOptions::lenient() : null;
        $decoded = Toon::decodeStatic($toon, $options);
        if ($as !== '') {
            $variableProvider = $renderingContext->getVariableProvider();
            $variableProvider->add($as, $decoded);
            $output = $renderChildrenClosure();
            $variableProvider->remove($as);
            return (string) $output;
        }
        return is_array($decoded) ? json_encode($decoded) : (string) $decoded;
    }
}
