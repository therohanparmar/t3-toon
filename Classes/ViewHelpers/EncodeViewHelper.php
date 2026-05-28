<?php

declare(strict_types=1);

namespace RRP\T3Toon\ViewHelpers;

use RRP\T3Toon\Domain\Model\EncodeOptions;
use RRP\T3Toon\Service\Toon;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Encodes a value to TOON format.
 *
 * Usage: <toon:encode value="{data}" /> or <toon:encode value="{data}" options="readable" />
 */
final class EncodeViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('value', 'mixed', 'Data to encode (array, object, scalar)', true);
        $this->registerArgument('options', 'string', 'Preset: default, compact, readable, tabular', false, 'default');
    }

    public function render(): string
    {
        $value = $this->arguments['value'];
        $optionsPreset = (string) ($this->arguments['options'] ?? 'default');
        return Toon::encodeStatic($value, $this->resolveOptions($optionsPreset));
    }

    private function resolveOptions(string $preset): ?EncodeOptions
    {
        return match (strtolower($preset)) {
            'compact' => EncodeOptions::compact(),
            'readable' => EncodeOptions::readable(),
            'tabular' => EncodeOptions::tabular(),
            default => null,
        };
    }
}
