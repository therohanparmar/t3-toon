<?php

declare(strict_types=1);

namespace RRP\T3Toon\ViewHelpers;

use RRP\T3Toon\Domain\Model\EncodeOptions;
use RRP\T3Toon\Service\Toon;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Encodes a value to TOON format.
 *
 * Usage: <toon:encode value="{data}" /> or <toon:encode value="{data}" options="readable" />
 */
final class EncodeViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('value', 'mixed', 'Data to encode (array, object, scalar)', true);
        $this->registerArgument('options', 'string', 'Preset: default, compact, readable, tabular', false, 'default');
    }

    /**
     * @param array<string, mixed> $arguments
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        $value = $arguments['value'];
        $optionsPreset = $arguments['options'] ?? 'default';
        $options = self::resolveOptions($optionsPreset);
        return Toon::encodeStatic($value, $options);
    }

    private static function resolveOptions(string $preset): ?EncodeOptions
    {
        return match (strtolower($preset)) {
            'compact' => EncodeOptions::compact(),
            'readable' => EncodeOptions::readable(),
            'tabular' => EncodeOptions::tabular(),
            default => null,
        };
    }
}
