<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Service;

use Carbon\Carbon;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Weblizards\CustomMaintenanceBundle\Domain\Model\MaintenanceEntry;
use Pimcore\Twig\Extension\Templating\HeadLink;
use Twig\Environment;
use Weblizards\CustomMaintenanceBundle\Config;

class StatusService
{
    public const STATUS_ACTIVE = 'true';

    public const STATUS_INACTIVE = 'false';

    private const IMMEDIATE_NOTICE_CAPTION = 'ab sofort';

    private const OPEN_ENDED_NOTICE_CAPTION = 'bis auf Weiteres';

    private const DEFAULT_UPCOMING_NOTICE_TEMPLATE = '@WeblizardsCustomMaintenance/partials/indicateupcoming.html.twig';

    private const DEFAULT_CURRENT_NOTICE_TEMPLATE = '@WeblizardsCustomMaintenance/partials/indicatecurrent.html.twig';

    private MaintenanceConfigManager $configManager;

    private HeadLink $headLink;

    private LoggerInterface $logger;

    private string $upcomingNoticeTemplate;

    private string $currentNoticeTemplate;

    public function __construct(
        MaintenanceConfigManager $configManager,
        HeadLink $headLink,
        ?LoggerInterface $logger = null,
        ?string $upcomingNoticeTemplate = null,
        ?string $currentNoticeTemplate = null
    )
    {
        $this->configManager = $configManager;
        $this->headLink = $headLink;
        $this->logger = $logger ?? new NullLogger();
        $this->upcomingNoticeTemplate = $upcomingNoticeTemplate ?? self::DEFAULT_UPCOMING_NOTICE_TEMPLATE;
        $this->currentNoticeTemplate = $currentNoticeTemplate ?? self::DEFAULT_CURRENT_NOTICE_TEMPLATE;
        $headLink->appendStylesheet('/bundles/weblizardscustommaintenance/css/frontend.css');
    }

    /**
     * Returns the valid custom-maintenance tokens for the runtime API.
     *
     * The reserved token {@see Config::TOKEN_PIMCORE} is intentionally not part of this list.
     *
     * @return string[]
     */
    public function getValidTokens(): array
    {
        return $this->configManager->getConfigSet()->getCustomTokens();
    }

    /**
     * @return string[]
     */
    public function getValidStates(): array
    {
        return [
            self::STATUS_INACTIVE,
            self::STATUS_ACTIVE,
        ];
    }

    /**
     * Sets the stored status for a custom-maintenance token.
     *
     * Only tokens returned by {@see getValidTokens()} are accepted here. The reserved
     * token {@see Config::TOKEN_PIMCORE} is not writable through this API.
     *
     * @throws \Exception
     */
    public function setStatus(string $token, string $status, bool $overrideFixed = false): void
    {
        if (!in_array($token, $this->getValidTokens(), true)) {
            throw new \Exception('Invalid token: ' . $token);
        }
        if (!in_array($status, $this->getValidStates(), true)) {
            throw new \Exception('Invalid status: ' . $status);
        }
        if ($this->isFixedMode($token) && !$overrideFixed) {
            throw new \Exception('Admin priority mode is active, nothing changed');
        }

        $this->configManager->setCustomStatus($token, $status);
    }

    /**
     * Returns the stored status for a custom-maintenance token.
     *
     * Only custom tokens are valid here. The reserved token {@see Config::TOKEN_PIMCORE}
     * has no runtime status in this API and is rejected as invalid.
     *
     * @throws \Exception
     */
    public function getStatus(string $token): string
    {
        if ($token === Config::TOKEN_PIMCORE) {
            throw new \Exception('Invalid token: ' . $token);
        }

        return $this->getMaintenanceEntry($token)->getActiveStatus();
    }

    /**
     * Evaluates whether at least one maintenance is currently active.
     *
     * Accepted inputs:
     * - `null`: evaluate all configured tokens
     * - `string`: evaluate one token
     * - `string[]`: evaluate multiple tokens
     *
     * The reserved token {@see Config::TOKEN_PIMCORE} is tolerated in this method but is
     * skipped because it has no custom runtime status.
     *
     * @param array|string $token
     *
     * @throws \Exception if token is invalid
     */
    public function isActive($token = null): bool
    {
        return $this->isActiveAt($token);
    }

    /**
     * @param array|string $token
     *
     * @throws \Exception if token is invalid
     */
    public function isActiveAt($token = null, ?Carbon $referenceTime = null): bool
    {
        if (null !== $token) {
            if (is_array($token)) {
                $tokens = $token;
            } else {
                $tokens = [$token];
            }
        } else {
            $tokens = $this->configManager->getConfigSet()->getAllTokens();
        }

        $effectiveReferenceTime = $this->resolveReferenceTime($referenceTime);
        $result = false;

        foreach ($tokens as $token) {
            // token pimcore has no maintenance status
            if (Config::TOKEN_PIMCORE == $token) {
                continue;
            }

            $entry = $this->getMaintenanceEntry($token);
            if ($this->isEntryEffectivelyActiveAt($entry, $effectiveReferenceTime)) {
                $result = true;

                break;
            }
        }

        return $result;
    }

    /**
     * Returns whether a custom-maintenance token is fixed and should resist status changes.
     *
     * Accepts custom tokens and the reserved token {@see Config::TOKEN_PIMCORE}, because
     * fixed-mode semantics exist on the underlying maintenance entry as well.
     *
     * @throws \Exception if token is invalid
     */
    public function isFixedMode(string $token): bool
    {
        return $this->getMaintenanceEntry($token)->isFixedMode();
    }

    /**
     * Whether external tools should keep their hands off regarding maintenance control.
     *
     * Accepts custom tokens and the reserved token {@see Config::TOKEN_PIMCORE}. Unknown
     * or deleted custom tokens remain invalid and are logged centrally.
     *
     * @throws \Exception
     */
    public function isHandsOff(string $token): bool
    {
        $entry = $this->getMaintenanceEntry($token);

        if ($entry->isFixedMode()) {
            return true;
        }

        return $this->isTimeslotEntered($entry);
    }

    /**
     * Returns whether an upcoming notice should be shown for a maintenance token.
     *
     * Accepts custom tokens and the reserved token {@see Config::TOKEN_PIMCORE}. Unknown
     * or deleted custom tokens remain invalid and are logged centrally.
     *
     * @throws \Exception
     */
    public function showUpcoming(string $token): bool
    {
        return $this->showsUpcomingAt($this->getMaintenanceEntry($token), $this->resolveReferenceTime());
    }

    /**
     * Returns the configured notice document path for a maintenance token.
     *
     * Accepts custom tokens and the reserved token {@see Config::TOKEN_PIMCORE}. Unknown
     * or deleted custom tokens remain invalid and are logged centrally.
     *
     * @return bool|string
     *
     * @throws \Exception if token is invalid
     */
    public function getDocumentPath(string $token)
    {
        return $this->getMaintenanceEntry($token)->getNoticeConfig()->getDocument();
    }

    /**
     * Returns the configured maintenance start timestamp for a maintenance token.
     *
     * Accepts custom tokens and the reserved token {@see Config::TOKEN_PIMCORE}. Unknown
     * or deleted custom tokens remain invalid and are logged centrally.
     *
     * @throws \Exception if token doesn't exist
     */
    public function getMaintenanceFrom(string $token): Carbon
    {
        return $this->getMaintenanceEntry($token)->getSchedule()->getFrom()->toCarbon();
    }

    /**
     * Returns the configured maintenance end timestamp for a maintenance token.
     *
     * Accepts custom tokens and the reserved token {@see Config::TOKEN_PIMCORE}. Unknown
     * or deleted custom tokens remain invalid and are logged centrally.
     *
     * @throws \Exception
     */
    public function getMaintenanceTo(string $token): Carbon
    {
        return $this->getMaintenanceEntry($token)->getSchedule()->getTo()->toCarbon();
    }

    /**
     * Returns the legacy config payload for a maintenance token.
     *
     * Accepts custom tokens and the reserved token {@see Config::TOKEN_PIMCORE}. Unknown
     * or deleted custom tokens remain invalid and are logged centrally.
     *
     * @throws \Exception if token is invalid
     */
    public function getConfigForToken(string $token): array
    {
        return $this->getMaintenanceEntry($token)->toLegacyArray();
    }

    public function getDiagnosis(?Carbon $referenceTime = null, ?array $adminPayload = null): array
    {
        $effectiveReferenceTime = $this->resolveReferenceTime($referenceTime);
        $configSet = null === $adminPayload
            ? $this->configManager->getConfigSet()
            : $this->configManager->getPreviewConfigSetFromAdminPayload($adminPayload);

        $entries = [];
        foreach ($configSet->getAllTokens() as $token) {
            $entries[] = $this->buildEntryDiagnosis($configSet->getEntry($token), $effectiveReferenceTime);
        }

        return [
            'evaluated_at' => $effectiveReferenceTime->format('d.m.Y H:i'),
            'entries' => $entries,
        ];
    }

    public function toCarbon(array $date_time): Carbon
    {
        return Carbon::createFromFormat('d.m.Y H:i', $date_time['date'] . ' ' . $date_time['time']);
    }

    public function indicateCustomMaintenance(Environment $engine): string
    {
        try {
            $current = $this->indicateCurrentMaintenance($engine);

            if (strlen($current) > 0) {
                return $current;
            }

            return $this->indicateUpcomingMaintenance($engine);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function indicateUpcomingMaintenance(Environment $engine): string
    {
        $referenceTime = $this->resolveReferenceTime();
        $configSet = $this->configManager->getConfigSet();
        $tokens = $configSet->getAllTokens();
        $candidate = null;

        $output = '';

        try {
            foreach ($tokens as $token) {
                $entry = $this->getMaintenanceEntry($token);
                if (!$this->showsUpcomingAt($entry, $referenceTime)) {
                    continue;
                }

                $entryCandidate = $this->buildUpcomingNoticeCandidate($entry, $referenceTime);
                if (null === $entryCandidate) {
                    continue;
                }

                if ($this->shouldPreferUpcomingNoticeCandidate($entryCandidate, $candidate)) {
                    $candidate = $entryCandidate;
                }
            }

            if (null !== $candidate) {
                $entry = $candidate['entry'];
                $frontendConfig = $configSet->getFrontendConfig();

                if ($candidate['state'] === 'upcoming') {
                    $from = $entry->getSchedule()->getFrom()->toCarbon();
                    $to = $this->getConfiguredScheduleEnd($entry);
                    $text = $frontendConfig->getUpcomingMessage('de');
                    $message = sprintf(
                        $text,
                        $from->format($frontendConfig->getFulltimeFormat('de')),
                        $this->formatOptionalMaintenanceEnd($to, $frontendConfig->getFulltimeFormat('de'))
                    );
                    $output = $this->renderNoticeTemplate(
                        $engine,
                        $this->upcomingNoticeTemplate,
                        $message,
                        $this->buildNoticeLink($entry->getNoticeConfig()->getDocument(), $frontendConfig->getMoreCaption('de'))
                    );
                } else {
                    $text = $frontendConfig->getUpcomingMessage('de');
                    $message = sprintf(
                        $text,
                        $this->formatVisibleNoticeStartAt($entry, $frontendConfig->getFulltimeFormat('de'), $referenceTime),
                        self::OPEN_ENDED_NOTICE_CAPTION
                    );
                    $output = $this->renderNoticeTemplate(
                        $engine,
                        $this->upcomingNoticeTemplate,
                        $message,
                        $this->buildNoticeLink($entry->getNoticeConfig()->getDocument(), $frontendConfig->getMoreCaption('de'))
                    );
                }
            }
        } catch (\Exception $e) {
            $output = $e->getMessage();
        }

        return $output;
    }

    public function indicateCurrentMaintenance(Environment $engine): string
    {
        $configSet = $this->configManager->getConfigSet();
        $tokens = $configSet->getAllTokens();
        $referenceTime = $this->resolveReferenceTime();

        $earliest = $referenceTime->copy()->addDay();
        $current = null;

        $output = '';

        try {

            foreach ($tokens as $token) {
                $entry = $this->getMaintenanceEntry($token);

                if ('never' != $entry->getNoticeConfig()->getShowInfo() && $this->isActiveAt($token, $referenceTime)) {
                    $from = $this->getCurrentMaintenanceSortStartAt($entry, $referenceTime);
                    if ($from->lessThan($earliest)) {
                        $earliest = $from;
                        $current = $token;
                    }
                }
            }

            if ($current) {
                $entry = $this->getMaintenanceEntry($current);
                $to = $this->getConfiguredScheduleEnd($entry);
                $frontendConfig = $configSet->getFrontendConfig();

                $text = $frontendConfig->getCurrentMessage('de');
                $message = sprintf(
                    $text,
                    $this->formatCurrentMaintenanceStartAt($entry, $frontendConfig->getFulltimeFormat('de'), $referenceTime),
                    $this->formatOptionalMaintenanceEnd($to, $frontendConfig->getFulltimeFormat('de'))
                );
                $output = $this->renderNoticeTemplate(
                    $engine,
                    $this->currentNoticeTemplate,
                    $message,
                    $this->buildNoticeLink($this->getDocumentPath($current), $frontendConfig->getMoreCaption('de'))
                );
            }
        } catch (\Exception $e) {
            $output = $e->getMessage();
        }

        return $output;
    }

    /**
     * Whether we fall into the configured maintenance timeslot.
     *
     * @throws \Exception
     */
    protected function isTimeslotEntered(MaintenanceEntry $entry): bool
    {
        return $this->isTimeslotEnteredAt($entry, $this->resolveReferenceTime());
    }

    protected function isTimeslotEnteredAt(MaintenanceEntry $entry, Carbon $referenceTime): bool
    {
        $schedule = $entry->getSchedule();
        $fromDateTime = $schedule->getFrom();
        if (!$fromDateTime->isConfigured()) {
            return false;
        }

        $from = $fromDateTime->toCarbon();
        $toDateTime = $schedule->getTo();
        if (!$toDateTime->isConfigured()) {
            return $referenceTime->greaterThanOrEqualTo($from);
        }

        return $referenceTime->between($from, $toDateTime->toCarbon());
    }

    private function isEntryEffectivelyActiveAt(MaintenanceEntry $entry, Carbon $referenceTime): bool
    {
        return $entry->isTemporaryActivationActive() || $entry->isMarkedActive() || $this->isTimeslotEnteredAt($entry, $referenceTime);
    }

    private function getConfiguredScheduleStart(MaintenanceEntry $entry): ?Carbon
    {
        $fromDateTime = $entry->getSchedule()->getFrom();
        if (!$fromDateTime->isConfigured()) {
            return null;
        }

        return $fromDateTime->toCarbon();
    }

    private function getConfiguredScheduleEnd(MaintenanceEntry $entry): ?Carbon
    {
        $toDateTime = $entry->getSchedule()->getTo();
        if (!$toDateTime->isConfigured()) {
            return null;
        }

        return $toDateTime->toCarbon();
    }

    private function getCurrentMaintenanceSortStartAt(MaintenanceEntry $entry, Carbon $referenceTime): Carbon
    {
        $from = $this->getConfiguredScheduleStart($entry);
        if (null === $from) {
            return $referenceTime->copy();
        }

        if ($entry->isTemporaryActivationActive() && $from->greaterThan($referenceTime)) {
            return $referenceTime->copy();
        }

        return $from;
    }

    private function formatCurrentMaintenanceStartAt(MaintenanceEntry $entry, string $format, Carbon $referenceTime): string
    {
        $from = $this->getConfiguredScheduleStart($entry);
        if (null === $from) {
            return self::IMMEDIATE_NOTICE_CAPTION;
        }

        if ($entry->isTemporaryActivationActive() && $from->greaterThan($referenceTime)) {
            return self::IMMEDIATE_NOTICE_CAPTION;
        }

        return $from->format($format);
    }

    private function formatVisibleNoticeStartAt(MaintenanceEntry $entry, string $format, Carbon $referenceTime): string
    {
        $from = $entry->getNoticeConfig()->getShowInfoFrom();
        if (!$from->isConfigured()) {
            return self::IMMEDIATE_NOTICE_CAPTION;
        }

        $fromCarbon = $from->toCarbon();
        if ($fromCarbon->greaterThan($referenceTime)) {
            return $fromCarbon->format($format);
        }

        return self::IMMEDIATE_NOTICE_CAPTION;
    }

    private function formatOptionalMaintenanceEnd(?Carbon $to, string $format): string
    {
        if (null === $to) {
            return self::OPEN_ENDED_NOTICE_CAPTION;
        }

        return $to->format($format);
    }

    /**
     * @throws \Exception
     */
    private function getMaintenanceEntry(string $token): MaintenanceEntry
    {
        try {
            return $this->configManager->getConfigSet()->getEntry($token);
        } catch (\InvalidArgumentException $exception) {
            $this->logger->error(
                sprintf('Maintenance token "%s" was not found. It may be unknown or deleted.', $token),
                ['token' => $token, 'exception' => $exception]
            );
        }

        throw new \Exception('Invalid token: ' . $token, 0, $exception);
    }

    /**
     * @return array{url:string, caption:string}|null
     */
    private function buildNoticeLink($documentPath, string $caption): ?array
    {
        if (!$documentPath) {
            return null;
        }

        return [
            'url' => (string) $documentPath,
            'caption' => $caption,
        ];
    }

    /**
     * @param array{url:string, caption:string}|null $link
     */
    private function renderNoticeTemplate(Environment $engine, string $template, string $message, ?array $link): string
    {
        return $engine->render($template, [
            'message' => $message,
            'link' => $link,
        ]);
    }

    private function resolveReferenceTime(?Carbon $referenceTime = null): Carbon
    {
        return ($referenceTime ?? Carbon::now())->copy();
    }

    private function showsUpcomingAt(MaintenanceEntry $entry, Carbon $referenceTime): bool
    {
        $noticeConfig = $entry->getNoticeConfig();

        switch ($noticeConfig->getShowInfo()) {
            case 'always':
                return true;

            case 'never':
                return false;

            case 'automatic':
                if (!$noticeConfig->getShowInfoFrom()->isConfigured()) {
                    return false;
                }

                return $referenceTime->greaterThan($noticeConfig->getShowInfoFrom()->toCarbon());
        }

        return false;
    }

    private function buildEntryDiagnosis(MaintenanceEntry $entry, Carbon $referenceTime): array
    {
        $maintenance = $this->buildMaintenanceDiagnosis($entry, $referenceTime);
        $notice = $this->buildNoticeDiagnosis($entry, $referenceTime, $maintenance['active']);

        return [
            'token' => $entry->getToken()->toString(),
            'description' => $entry->getDescription(),
            'fixed' => $entry->isFixedMode(),
            'maintenance' => $maintenance,
            'notice' => $notice,
        ];
    }

    /**
     * @return array{
     *     mode:string,
     *     effective:string,
     *     reason:string,
     *     active:bool,
     *     temporary_active:bool,
     *     scheduled_from:?string,
     *     scheduled_to:?string
     * }
     */
    private function buildMaintenanceDiagnosis(MaintenanceEntry $entry, Carbon $referenceTime): array
    {
        $reason = 'inactive';
        if ($entry->isTemporaryActivationActive()) {
            $reason = 'temporary_activation';
        } elseif ($entry->isMarkedActive()) {
            $reason = 'manual_activation';
        } elseif ($this->isTimeslotEnteredAt($entry, $referenceTime)) {
            $reason = 'scheduled_window';
        }

        return [
            'mode' => $this->deriveMaintenanceMode($entry),
            'effective' => $reason === 'inactive' ? 'inactive' : 'active',
            'reason' => $reason,
            'active' => $reason !== 'inactive',
            'temporary_active' => $entry->isTemporaryActivationActive(),
            'scheduled_from' => $this->formatConfiguredDateTime($entry->getSchedule()->getFrom()),
            'scheduled_to' => $this->formatConfiguredDateTime($entry->getSchedule()->getTo()),
        ];
    }

    /**
     * @return array{
     *     mode:string,
     *     effective:string,
     *     reason:string,
     *     visible:bool,
     *     show_from:?string,
     *     document:string
     * }
     */
    private function buildNoticeDiagnosis(MaintenanceEntry $entry, Carbon $referenceTime, bool $maintenanceActive): array
    {
        $noticeConfig = $entry->getNoticeConfig();
        $mode = $noticeConfig->getShowInfo();
        $effective = 'inactive';
        $reason = 'inactive';

        if ($mode === 'never') {
            $reason = 'never';
        } elseif ($maintenanceActive) {
            $effective = 'current';
            $reason = 'active_maintenance';
        } elseif ($mode === 'always') {
            if ($this->isMaintenanceUpcomingAt($entry, $referenceTime)) {
                $effective = 'upcoming';
                $reason = 'always';
            } else {
                $effective = 'visible';
                $reason = 'always';
            }
        } elseif ($mode === 'automatic') {
            if (!$noticeConfig->getShowInfoFrom()->isConfigured()) {
                $reason = 'notice_unconfigured';
            } elseif (!$this->showsUpcomingAt($entry, $referenceTime)) {
                $reason = 'notice_window_not_started';
            } elseif ($this->isMaintenanceUpcomingAt($entry, $referenceTime)) {
                $effective = 'upcoming';
                $reason = 'notice_schedule';
            } else {
                $effective = 'visible';
                $reason = 'notice_schedule';
            }
        }

        return [
            'mode' => $mode,
            'effective' => $effective,
            'reason' => $reason,
            'visible' => $effective !== 'inactive',
            'show_from' => $this->formatConfiguredDateTime($noticeConfig->getShowInfoFrom()),
            'document' => $noticeConfig->getDocument(),
        ];
    }

    private function deriveMaintenanceMode(MaintenanceEntry $entry): string
    {
        if ($entry->isMarkedActive()) {
            return 'active';
        }

        if ($entry->getSchedule()->getFrom()->isConfigured()) {
            return 'scheduled';
        }

        return 'inactive';
    }

    private function isMaintenanceUpcomingAt(MaintenanceEntry $entry, Carbon $referenceTime): bool
    {
        $from = $this->getConfiguredScheduleStart($entry);
        if (null === $from || !$from->greaterThan($referenceTime)) {
            return false;
        }

        $to = $this->getConfiguredScheduleEnd($entry);

        return null === $to || $to->greaterThan($referenceTime);
    }

    /**
     * @return array{entry:MaintenanceEntry,state:string,sort_at:Carbon}|null
     */
    private function buildUpcomingNoticeCandidate(MaintenanceEntry $entry, Carbon $referenceTime): ?array
    {
        if ($this->isMaintenanceUpcomingAt($entry, $referenceTime)) {
            $from = $this->getConfiguredScheduleStart($entry);
            if (null === $from) {
                return null;
            }

            return [
                'entry' => $entry,
                'state' => 'upcoming',
                'sort_at' => $from,
            ];
        }

        $noticeStart = $entry->getNoticeConfig()->getShowInfoFrom();
        $sortAt = $noticeStart->isConfigured() ? $noticeStart->toCarbon() : $referenceTime->copy();

        return [
            'entry' => $entry,
            'state' => 'visible',
            'sort_at' => $sortAt,
        ];
    }

    /**
     * @param array{entry:MaintenanceEntry,state:string,sort_at:Carbon}|null $current
     * @param array{entry:MaintenanceEntry,state:string,sort_at:Carbon} $candidate
     */
    private function shouldPreferUpcomingNoticeCandidate(array $candidate, ?array $current): bool
    {
        if (null === $current) {
            return true;
        }

        if ($candidate['state'] !== $current['state']) {
            return $candidate['state'] === 'upcoming';
        }

        return $candidate['sort_at']->lessThan($current['sort_at']);
    }

    private function formatConfiguredDateTime($dateTime): ?string
    {
        if (!$dateTime->isConfigured()) {
            return null;
        }

        return $dateTime->toCarbon()->format('d.m.Y H:i');
    }
}
