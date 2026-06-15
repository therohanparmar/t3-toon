<?php

declare(strict_types=1);

namespace RRP\T3Toon\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RRP\T3Toon\Domain\Model\DecodeOptions;
use RRP\T3Toon\Domain\Model\EncodeOptions;
use RRP\T3Toon\Exception\ToonDecodeException;
use RRP\T3Toon\Service\Toon;
use RRP\T3Toon\Utility\ToonHelper;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ToonModuleController
{
    /** Official toonformat.dev "Hikes" preset (mixed structure). */
    private const HIKES_EXAMPLE = <<<JSON
{
  "context": {
    "task": "Our favorite hikes together",
    "location": "Boulder",
    "season": "spring_2025"
  },
  "friends": ["ana", "luis", "sam"],
  "hikes": [
    { "id": 1, "name": "Blue Lake Trail", "distanceKm": 7.5, "elevationGain": 320, "companion": "ana", "wasSunny": true },
    { "id": 2, "name": "Ridge Overlook", "distanceKm": 9.2, "elevationGain": 540, "companion": "luis", "wasSunny": false },
    { "id": 3, "name": "Wildflower Loop", "distanceKm": 5.1, "elevationGain": 180, "companion": "sam", "wasSunny": true }
  ]
}
JSON;

    /** TYPO3-flavoured sample (tabular + inline array). */
    public const TYPO3_EXAMPLE = <<<JSON
{
  "site": "TYPO3 Headless",
  "languages": ["en", "de", "fr"],
  "pages": [
    { "uid": 1, "title": "Home", "hidden": false },
    { "uid": 2, "title": "Products", "hidden": false },
    { "uid": 3, "title": "Draft", "hidden": true }
  ]
}
JSON;

    public function playgroundAction(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplateFactory = GeneralUtility::makeInstance(ModuleTemplateFactory::class);
        $moduleTemplate = $moduleTemplateFactory->create($request);

        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addCssFile('EXT:rrp_t3toon/Resources/Public/Css/Playground.css');
        $pageRenderer->addJsFooterFile('EXT:rrp_t3toon/Resources/Public/JavaScript/Playground.js');

        $formState = $this->defaultFormState();
        $input = '';
        $output = '';
        $error = '';
        $tokenEstimate = null;
        $sizeStats = ['inputChars' => 0, 'outputChars' => 0, 'reductionPercent' => null];

        $parsedBody = $request->getParsedBody();
        if (is_array($parsedBody) && isset($parsedBody['input'])) {
            $formState = $this->formStateFromRequest($parsedBody);
            $input = trim((string) ($parsedBody['input'] ?? ''));
            $mode = (string) ($parsedBody['mode'] ?? '');

            if ($input !== '') {
                try {
                    $toon = GeneralUtility::makeInstance(Toon::class);
                    if ($mode === 'decode') {
                        $decodeOptions = $formState['lenient_decode']
                            ? DecodeOptions::lenient()
                            : null;
                        $decoded = $toon->decode($input, $decodeOptions);
                        $output = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        $tokenEstimate = $toon->estimateTokens($input);
                    } else {
                        json_decode($input);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $encodeOptions = $this->buildEncodeOptions($formState, $mode);
                            $output = $toon->encode($input, $encodeOptions);
                            $tokenEstimate = $toon->estimateTokens($output);
                            $sizeStats = $this->computeEncodeSizeStats($input, $output);
                        } else {
                            $error = $this->getLanguageService()->sL(
                                'LLL:EXT:rrp_t3toon/Resources/Private/Language/locallang_mod.xlf:playground.error_invalid_json'
                            );
                        }
                    }
                } catch (ToonDecodeException $e) {
                    $error = $e->getMessage();
                } catch (\Throwable $e) {
                    $error = $e->getMessage();
                }
            }

            if ($mode === 'decode' && $input !== '' && $output !== '') {
                $sizeStats = [
                    'inputChars' => strlen($input),
                    'outputChars' => strlen($output),
                    'reductionPercent' => null,
                ];
            }
        } elseif (ToonHelper::getConfig()['show_default_example']) {
            $formState = $this->defaultFormState();
            $input = self::HIKES_EXAMPLE;
            try {
                $toon = GeneralUtility::makeInstance(Toon::class);
                $output = $toon->encode($input, $this->buildEncodeOptions($formState, 'encode'));
                $tokenEstimate = $toon->estimateTokens($output);
                $sizeStats = $this->computeEncodeSizeStats($input, $output);
            } catch (\Throwable) {
                $output = '';
            }
        }

        $tokenEstimateValue = null;
        if (is_array($tokenEstimate) && isset($tokenEstimate['tokens_estimate'])) {
            $tokenEstimateValue = (int) $tokenEstimate['tokens_estimate'];
        }

        $moduleTemplate->assignMultiple([
            'input' => $input,
            'output' => $output,
            'error' => $error,
            'tokenEstimate' => $tokenEstimateValue,
            'formState' => $formState,
            'inputChars' => $sizeStats['inputChars'],
            'outputChars' => $sizeStats['outputChars'],
            'reductionPercent' => $sizeStats['reductionPercent'],
            'showReduction' => $sizeStats['reductionPercent'] !== null,
            'presetHikes' => self::HIKES_EXAMPLE,
            'presetTypo3' => self::TYPO3_EXAMPLE,
        ]);

        return $moduleTemplate->renderResponse('ToonModule/Playground');
    }

    /**
     * @return array{
     *     indent: int,
     *     delimiter: string,
     *     key_folding: string,
     *     flatten_depth: int,
     *     lenient_decode: bool
     * }
     */
    private function defaultFormState(): array
    {
        $config = ToonHelper::getConfig();
        $flatten = $config['flatten_depth'];
        return [
            'indent' => (int) $config['indent'],
            'delimiter' => $this->delimiterTokenFromChar((string) $config['delimiter']),
            'key_folding' => (string) $config['key_folding'],
            'flatten_depth' => $flatten === null ? -1 : (int) $flatten,
            'lenient_decode' => false,
        ];
    }

    /**
     * @param array<string, mixed> $body
     * @return array{
     *     indent: int,
     *     delimiter: string,
     *     key_folding: string,
     *     flatten_depth: int,
     *     lenient_decode: bool
     * }
     */
    private function formStateFromRequest(array $body): array
    {
        $flattenRaw = $body['flatten_depth'] ?? -1;
        return [
            'indent' => max(1, (int) ($body['indent'] ?? 2)),
            'delimiter' => (string) ($body['delimiter'] ?? 'comma'),
            'key_folding' => ($body['key_folding'] ?? 'off') === 'safe' ? 'safe' : 'off',
            'flatten_depth' => (int) $flattenRaw,
            'lenient_decode' => !empty($body['lenient_decode']),
        ];
    }

    /**
     * @param array{
     *     indent: int,
     *     delimiter: string,
     *     key_folding: string,
     *     flatten_depth: int,
     *     lenient_decode: bool
     * } $formState
     */
    private function buildEncodeOptions(array $formState, string $mode): ?EncodeOptions
    {
        if ($mode === 'compact') {
            return EncodeOptions::compact();
        }

        $delimiter = match ($formState['delimiter']) {
            'tab' => EncodeOptions::DELIMITER_TAB,
            'pipe' => EncodeOptions::DELIMITER_PIPE,
            default => EncodeOptions::DELIMITER_COMMA,
        };
        $flattenDepth = $formState['flatten_depth'] < 0 ? null : $formState['flatten_depth'];

        return new EncodeOptions(
            indent: $formState['indent'],
            delimiter: $delimiter,
            keyFolding: $formState['key_folding'] === 'safe'
                ? EncodeOptions::KEY_FOLDING_SAFE
                : EncodeOptions::KEY_FOLDING_OFF,
            flattenDepth: $flattenDepth,
        );
    }

    /**
     * @return array{inputChars: int, outputChars: int, reductionPercent: float|null}
     */
    private function computeEncodeSizeStats(string $input, string $output): array
    {
        $inputChars = strlen($input);
        $outputChars = strlen($output);
        $reduction = null;

        $decoded = json_decode($input, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $baseline = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            if (is_string($baseline)) {
                $baselineChars = strlen($baseline);
                if ($baselineChars > 0) {
                    $reduction = round((($baselineChars - $outputChars) / $baselineChars) * 100, 1);
                }
            }
        }

        return [
            'inputChars' => $inputChars,
            'outputChars' => $outputChars,
            'reductionPercent' => $reduction,
        ];
    }

    private function delimiterTokenFromChar(string $delimiter): string
    {
        return match ($delimiter) {
            "\t" => 'tab',
            '|' => 'pipe',
            default => 'comma',
        };
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
