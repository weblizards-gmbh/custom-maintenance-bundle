<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Service;

use Carbon\Carbon;
use Weblizards\CustomMaintenanceBundle\Domain\Model\MaintenanceEntry;
use Pimcore\Twig\Extension\Templating\HeadLink;
use Twig\Environment;
use Weblizards\CustomMaintenanceBundle\Config;

class StatusService
{
    public const STATUS_ACTIVE = 'true';

    public const STATUS_INACTIVE = 'false';

    private MaintenanceConfigManager $configManager;

    private HeadLink $headLink;

    public function __construct(MaintenanceConfigManager $configManager, HeadLink $headLink)
    {
        $this->configManager = $configManager;
        $this->headLink = $headLink;
        $headLink->appendStylesheet('/bundles/weblizardscustommaintenance/css/frontend.css');
    }

    /**
     * Returns an array of valid tokens of the existing custom maintenances.
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
     * @throws \Exception
     */
    public function setStatus(string $token, string $status): void
    {
        if (!in_array($token, $this->getValidTokens())) {
            throw new \Exception('Invalid token: ' . $token);
        }
        if (!in_array($status, $this->getValidStates())) {
            throw new \Exception('Invalid status: ' . $status);
        }

        $this->configManager->setCustomStatus($token, $status);
    }

    /**
     * @throws \Exception
     */
    public function getStatus(string $token): string
    {
        if (!in_array($token, $this->getValidTokens())) {
            throw new \Exception('Invalid token: ' . $token);
        }

        return $this->getMaintenanceEntry($token)->getActiveStatus();
    }

    /**
     * @param array|string $token
     *
     * @throws \Exception if token is invalid
     */
    public function isActive($token = null): bool
    {
        if ($token) {
            if (is_array($token)) {
                $tokens = $token;
            } else {
                $tokens = [$token];
            }
        } else {
            $tokens = $this->configManager->getConfigSet()->getAllTokens();
        }

        $result = false;

        foreach ($tokens as $token) {
            // token pimcore has no maintenance status
            if (Config::TOKEN_PIMCORE == $token) {
                continue;
            }

            $entry = $this->getMaintenanceEntry($token);
            if ($entry->isMarkedActive() || $this->isTimeslotEntered($entry)) {
                $result = true;

                break;
            }
        }

        return $result;
    }

    /**
     * @throws \Exception if token is invalid
     */
    public function isFixedMode(string $token): bool
    {
        return $this->getMaintenanceEntry($token)->isFixedMode();
    }

    /**
     * Whether external tools should keep their hands off regarding maintenance control.
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
     * @throws \Exception
     */
    public function showUpcoming(string $token): bool
    {
        $noticeConfig = $this->getMaintenanceEntry($token)->getNoticeConfig();

        $result = false;

        switch ($noticeConfig->getShowInfo()) {
            case 'always':
                $result = true;

                break;

            case 'never':
                $result = false;

                break;

            case 'automatic':
                $now = Carbon::now();
                $from = $noticeConfig->getShowInfoFrom()->toCarbon();
                $result = $now->greaterThan($from);

                break;
        }

        return $result;
    }

    /**
     * @return bool|string
     *
     * @throws \Exception if token is invalid
     */
    public function getDocumentPath(string $token)
    {
        return $this->getMaintenanceEntry($token)->getNoticeConfig()->getDocument();
    }

    /**
     * @throws \Exception if token doesn't exist
     */
    public function getMaintenanceFrom(string $token): Carbon
    {
        return $this->getMaintenanceEntry($token)->getSchedule()->getFrom()->toCarbon();
    }

    /**
     * @throws \Exception
     */
    public function getMaintenanceTo(string $token): Carbon
    {
        return $this->getMaintenanceEntry($token)->getSchedule()->getTo()->toCarbon();
    }

    /**
     * @throws \Exception if token is invalid
     */
    public function getConfigForToken(string $token): array
    {
        return $this->getMaintenanceEntry($token)->toLegacyArray();
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
        $earliest = Carbon::now()->addDays(365);
        $upcoming = null;
        $configSet = $this->configManager->getConfigSet();
        $tokens = $configSet->getAllTokens();

        $now = Carbon::now();
        $output = '';

        try {
            foreach ($tokens as $token) {
                if ($this->showUpcoming($token)) {
                    $from = $this->getMaintenanceFrom($token);
                    $end = $this->getMaintenanceTo($token);
                    if ($from->lessThan($earliest) && $end->greaterThan($now)) {
                        $earliest = $from;
                        $upcoming = $token;
                    }
                }
            }

            if ($upcoming) {
                $from = $this->getMaintenanceFrom($upcoming);
                $to = $this->getMaintenanceTo($upcoming);
                $frontendConfig = $configSet->getFrontendConfig();
                $text = $frontendConfig->getUpcomingMessage('de');
                $message = sprintf(
                    $text,
                    $from->format($frontendConfig->getFulltimeFormat('de')),
                    $to->format($frontendConfig->getFulltimeFormat('de'))
                );

                $link = '';
                $document_path = $this->getDocumentPath($upcoming);
                if ($document_path) {
                    $link = [
                        'url' => $document_path,
                        'caption' => $frontendConfig->getMoreCaption('de'),
                    ];
                }

                $output = $engine->render('@WeblizardsCustomMaintenance/partials/indicateupcoming.html.twig', [
                    'message' => $message,
                    'link' => $link,
                ]);
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

        $earliest = Carbon::tomorrow();
        $current = null;

        $output = '';

        try {

            foreach ($tokens as $token) {
                $entry = $this->getMaintenanceEntry($token);

                if ('never' != $entry->getNoticeConfig()->getShowInfo() && $this->isActive($token)) {
                    $from = $this->getMaintenanceFrom($token);
                    if ($from->lessThan($earliest)) {
                        $earliest = $from;
                        $current = $token;
                    }
                }
            }

            if ($current) {
                $from = $this->getMaintenanceFrom($current);
                $to = $this->getMaintenanceTo($current);
                $frontendConfig = $configSet->getFrontendConfig();

                $text = $frontendConfig->getCurrentMessage('de');
                $message = sprintf(
                    $text,
                    $from->format($frontendConfig->getFulltimeFormat('de')),
                    $to->format($frontendConfig->getFulltimeFormat('de'))
                );

                $link = '';
                $document_path = $this->getDocumentPath($current);
                if ($document_path) {
                    $link = [
                        'url' => $document_path,
                        'caption' => $frontendConfig->getMoreCaption('de'),
                    ];
                }

                $output = $engine->render('@WeblizardsCustomMaintenance/partials/indicatecurrent.html.twig', [
                    'message' => $message,
                    'link' => $link,
                ]);
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
        $from = $entry->getSchedule()->getFrom()->toCarbon();
        $to = $entry->getSchedule()->getTo()->toCarbon();

        return Carbon::now()->between($from, $to);
    }

    /**
     * @throws \Exception
     */
    private function getMaintenanceEntry(string $token): MaintenanceEntry
    {
        try {
            return $this->configManager->getConfigSet()->getEntry($token);
        } catch (\InvalidArgumentException $exception) {
            throw new \Exception($exception->getMessage(), 0, $exception);
        }
    }
}
