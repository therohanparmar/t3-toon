<?php

declare(strict_types=1);

namespace RRP\T3Toon\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RRP\T3Toon\Domain\Model\DecodeOptions;
use RRP\T3Toon\Domain\Model\EncodeOptions;
use RRP\T3Toon\Exception\ToonDecodeException;
use RRP\T3Toon\Service\Toon;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Localization\LanguageService;

class ToonModuleController
{
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
                        $data = json_decode($input, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                            $options = $mode === 'compact' ? EncodeOptions::compact() : null;
                            $output = $toon->encode($data, $options);
                            $tokenEstimate = $toon->estimateTokens($output);
                        } else {
                            $error = 'Invalid JSON input for encode.';
                        }
                    }
                } catch (ToonDecodeException $e) {
                    $error = $e->getMessage();
                } catch (\Throwable $e) {
                    $error = $e->getMessage();
                }
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
}
