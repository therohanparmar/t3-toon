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
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Localization\LanguageService;

class ToonModuleController
{
    /**
     * Sample input shown on first load of the Playground (when enabled in extension
     * configuration). Intentionally different from the toonformat.dev example, using
     * TYPO3-flavoured data that exercises nesting, a tabular array, and an inline array.
     */
    private const DEFAULT_EXAMPLE = <<<JSON
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

        $input = '';
        $output = '';
        $error = '';
        $tokenEstimate = null;
        $mode = '';

        $parsedBody = $request->getParsedBody();
        if (is_array($parsedBody) && isset($parsedBody['input'])) {
            $input = (string) ($parsedBody['input'] ?? '');
            $mode = (string) ($parsedBody['mode'] ?? '');
            $input = trim($input);

            if ($input !== '') {
                try {
                    $toon = GeneralUtility::makeInstance(Toon::class);
                    if ($mode === 'decode') {
                        $decoded = $toon->decode($input, DecodeOptions::lenient());
                        $output = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        $tokenEstimate = $toon->estimateTokens($input);
                    } else {
                        json_decode($input);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $options = $mode === 'compact' ? EncodeOptions::compact() : null;
                            // Pass the JSON string straight through: the encoder re-decodes it
                            // preserving the object-vs-array distinction (e.g. empty {} vs []).
                            $output = $toon->encode($input, $options);
                            $tokenEstimate = $toon->estimateTokens($output);
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
        } elseif (ToonHelper::getConfig()['show_default_example']) {
            // Initial load (no submission): prefill a sample and its encoded TOON output.
            $input = self::DEFAULT_EXAMPLE;
            try {
                $toon = GeneralUtility::makeInstance(Toon::class);
                $output = $toon->encode($input);
                $tokenEstimate = $toon->estimateTokens($output);
            } catch (\Throwable) {
                // If anything goes wrong, fall back to an empty Playground silently.
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
            'tokenEstimate' => $tokenEstimateValue
        ]);

        return $moduleTemplate->renderResponse('ToonModule/Playground');
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
