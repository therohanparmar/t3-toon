<?php

declare(strict_types=1);

namespace RRP\T3Toon\ViewHelpers;

use RRP\T3Toon\Domain\Model\DecodeOptions;
use RRP\T3Toon\Service\Toon;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Decodes a TOON string to a PHP array.
 *
 * Usage: <toon:decode toon="{toonString}" as="decoded" /> then use {decoded} in f:for etc.
 */
final class DecodeViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('toon', 'string', 'TOON-formatted string to decode', true);
        $this->registerArgument('as', 'string', 'Variable name to assign the decoded array to', false, '');
        $this->registerArgument('lenient', 'bool', 'If true, decode in lenient (non-strict) mode', false, false);
    }

    public function render(): string
    {
        $toon = (string) $this->arguments['toon'];
        $as = (string) ($this->arguments['as'] ?? '');
        $lenient = (bool) ($this->arguments['lenient'] ?? false);
        $options = $lenient ? DecodeOptions::lenient() : null;
        $decoded = Toon::decodeStatic($toon, $options);
        if ($as !== '') {
            $variableProvider = $this->renderingContext->getVariableProvider();
            $variableProvider->add($as, $decoded);
            $output = $this->renderChildren();
            $variableProvider->remove($as);
            return (string) $output;
        }
        return is_array($decoded) ? (string) json_encode($decoded) : (string) $decoded;
    }
}
