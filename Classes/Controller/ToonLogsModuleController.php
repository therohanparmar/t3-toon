<?php

declare(strict_types=1);

namespace RRP\T3Toon\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RRP\T3Toon\Domain\Repository\UsageLogRepository;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\SlidingWindowPagination;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class ToonLogsModuleController
{
    private const ALLOWED_PER_PAGE = [25, 50, 100];
    private const DEFAULT_PER_PAGE = 25;

    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly UsageLogRepository $repository,
        private readonly UriBuilder $uriBuilder,
        private readonly FlashMessageService $flashMessageService,
        private readonly FormProtectionFactory $formProtectionFactory,
    ) {
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {
            return $this->deleteAction($request);
        }
        return $this->listAction($request);
    }

    private function listAction(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($request);

        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addCssFile('EXT:rrp_t3toon/Resources/Public/Css/Playground.css');
        $pageRenderer->addJsFooterFile('EXT:rrp_t3toon/Resources/Public/JavaScript/Logs.js');

        $queryParams = $request->getQueryParams();
        $filters = $this->extractFilters($queryParams);
        $perPage = $this->resolvePerPage($queryParams['perPage'] ?? null);
        $currentPage = max(1, (int) ($queryParams['page'] ?? 1));

        $allRows = $this->repository->findAll($filters);
        $paginator = new ArrayPaginator($allRows, $currentPage, $perPage);
        $pagination = new SlidingWindowPagination($paginator, 5);

        $formProtection = $this->formProtectionFactory->createFromRequest($request);
        $routeToken = $formProtection->generateToken('route', 'tools_t3toon.logs');

        $moduleTemplate->assignMultiple([
            'rows' => $paginator->getPaginatedItems(),
            'paginator' => $paginator,
            'pagination' => $pagination,
            'totalCount' => count($allRows),
            'filters' => $filters,
            'filterValues' => $this->filterValuesForForm($queryParams),
            'perPage' => $perPage,
            'perPageOptions' => self::ALLOWED_PER_PAGE,
            'returnUrl' => (string) $request->getUri(),
            'routeToken' => $routeToken,
            // The core callout markup changed in v13 (media/media-body -> callout-icon/callout-content).
            'typo3MajorVersion' => GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion(),
        ]);

        return $moduleTemplate->renderResponse('ToonLogsModule/List');
    }

    private function deleteAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = (array) $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $uids = [];
        if (isset($parsedBody['uids']) && is_array($parsedBody['uids'])) {
            $uids = $parsedBody['uids'];
        } elseif (isset($parsedBody['uid'])) {
            $uids = [$parsedBody['uid']];
        } elseif (isset($queryParams['uid'])) {
            $uids = [$queryParams['uid']];
        }

        $deleted = $this->repository->deleteByUids(array_map('intval', $uids));
        $this->enqueueDeleteMessage($deleted);

        $returnUrl = (string) ($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? '');
        if ($returnUrl === '') {
            $returnUrl = (string) $this->uriBuilder->buildUriFromRoute('tools_t3toon.logs');
        }

        return new RedirectResponse($returnUrl);
    }

    private function extractFilters(array $queryParams): array
    {
        $filters = [];

        $dateFrom = trim((string) ($queryParams['dateFrom'] ?? ''));
        if ($dateFrom !== '') {
            $ts = strtotime($dateFrom . ' 00:00:00');
            if ($ts !== false) {
                $filters['dateFrom'] = $ts;
            }
        }

        $dateTo = trim((string) ($queryParams['dateTo'] ?? ''));
        if ($dateTo !== '') {
            $ts = strtotime($dateTo . ' 23:59:59');
            if ($ts !== false) {
                $filters['dateTo'] = $ts;
            }
        }

        $enabled = $queryParams['enabled'] ?? '';
        if ($enabled === '0' || $enabled === '1') {
            $filters['enabled'] = (int) $enabled;
        }

        $minPct = $queryParams['minPct'] ?? '';
        if ($minPct !== '' && is_numeric($minPct)) {
            $filters['minPct'] = (float) $minPct;
        }

        return $filters;
    }

    private function filterValuesForForm(array $queryParams): array
    {
        return [
            'dateFrom' => (string) ($queryParams['dateFrom'] ?? ''),
            'dateTo' => (string) ($queryParams['dateTo'] ?? ''),
            'enabled' => (string) ($queryParams['enabled'] ?? ''),
            'minPct' => (string) ($queryParams['minPct'] ?? ''),
        ];
    }

    private function resolvePerPage(mixed $raw): int
    {
        $value = (int) $raw;
        return in_array($value, self::ALLOWED_PER_PAGE, true) ? $value : self::DEFAULT_PER_PAGE;
    }

    private function enqueueDeleteMessage(int $deleted): void
    {
        $lang = $this->getLanguageService();
        if ($deleted > 0) {
            $title = $lang->sL('LLL:EXT:rrp_t3toon/Resources/Private/Language/locallang_mod.xlf:logs.flash.deleted_title');
            $body = sprintf(
                $lang->sL('LLL:EXT:rrp_t3toon/Resources/Private/Language/locallang_mod.xlf:logs.flash.deleted_body'),
                $deleted
            );
            $severity = ContextualFeedbackSeverity::OK;
        } else {
            $title = $lang->sL('LLL:EXT:rrp_t3toon/Resources/Private/Language/locallang_mod.xlf:logs.flash.nothing_title');
            $body = $lang->sL('LLL:EXT:rrp_t3toon/Resources/Private/Language/locallang_mod.xlf:logs.flash.nothing_body');
            $severity = ContextualFeedbackSeverity::INFO;
        }

        $message = GeneralUtility::makeInstance(FlashMessage::class, $body, $title, $severity, true);
        $this->flashMessageService
            ->getMessageQueueByIdentifier('core.template.flashMessages')
            ->enqueue($message);
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
