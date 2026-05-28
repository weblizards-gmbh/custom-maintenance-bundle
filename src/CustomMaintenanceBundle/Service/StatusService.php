<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Service;

use Carbon\Carbon;
use Pimcore\Twig\Extension\Templating\HeadLink;
use Twig\Environment;
use Weblizards\CustomMaintenanceBundle\Config;
use Weblizards\CustomMaintenanceBundle\Config as CustomMaintenanceConfig;

class StatusService
{
    public const STATUS_ACTIVE = 'true';

    public const STATUS_INACTIVE = 'false';

    private CustomMaintenanceConfig $config;

    private HeadLink $headLink;

    public function __construct(CustomMaintenanceConfig $config, HeadLink $headLink)
    {
        $this->config = $config;
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
        $config = $this->config->getData();

        return array_keys($config['custom']);
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

        $config = $this->config->getData();
        $config['custom'][$token]['active'] = $status;
        $this->config->setData($config);
        $this->config->save();
    }

    /**
     * @throws \Exception
     */
    public function getStatus(string $token): string
    {
        if (!in_array($token, $this->getValidTokens())) {
            throw new \Exception('Invalid token: ' . $token);
        }

        $config = $this->config->getData();

        return $config['custom'][$token]['active'];
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
            $tokens = $this->config->getAllTokens();
        }

        $result = false;

        foreach ($tokens as $token) {
            // token pimcore has no maintenance status
            if (Config::TOKEN_PIMCORE == $token) {
                continue;
            }

            $cm = $this->getConfigForToken($token);
            if (self::STATUS_ACTIVE == $cm['active'] || $this->isTimeslotEntered($token)) {
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
        $cm = $this->getConfigForToken($token);

        return array_key_exists('fixed', $cm) && self::STATUS_ACTIVE == $cm['fixed'];
    }

    /**
     * Whether external tools should keep their hands off regarding maintenance control.
     *
     * @throws \Exception
     */
    public function isHandsOff(string $token): bool
    {
        if ($this->isFixedMode($token)) {
            return true;
        }

        return (bool) $this->isTimeslotEntered($token);
    }

    /**
     * @throws \Exception
     */
    public function showUpcoming(string $token): bool
    {
        $cm = $this->getConfigForToken($token);

        $result = false;

        switch ($cm['show_info']) {
            case 'always':
                $result = true;

                break;

            case 'never':
                $result = false;

                break;

            case 'automatic':
                $now = Carbon::now();
                $from = $this->toCarbon($cm['show_info_from']);
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
        $cm = $this->getConfigForToken($token);

        return $cm['document'];
    }

    /**
     * @throws \Exception if token doesn't exist
     */
    public function getMaintenanceFrom(string $token): Carbon
    {
        $cm = $this->getConfigForToken($token);

        return $this->toCarbon($cm['planned']['from']);
    }

    /**
     * @throws \Exception
     */
    public function getMaintenanceTo(string $token): Carbon
    {
        $cm = $this->getConfigForToken($token);

        return $this->toCarbon($cm['planned']['to']);
    }

    /**
     * @throws \Exception if token is invalid
     */
    public function getConfigForToken(string $token): array
    {
        $config = $this->config->getData();
        if (Config::TOKEN_PIMCORE == $token) {
            $cm = $config[Config::TOKEN_PIMCORE];
        } else {
            if (!array_key_exists($token, $config['custom'])) {
                throw new \Exception('Invalid Token: ' . $token);
            }
            $cm = $config['custom'][$token];
        }

        return $cm;
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
        $tokens = $this->config->getAllTokens();

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
                $config = $this->config->getData();
                $from = $this->getMaintenanceFrom($upcoming);
                $to = $this->getMaintenanceTo($upcoming);
                $text = $config['frontend']['indication_upcoming']['de'];
                $message = sprintf(
                    $text,
                    $from->format($config['frontend']['fulltimeformat']['de']),
                    $to->format($config['frontend']['fulltimeformat']['de'])
                );

                $link = '';
                $document_path = $this->getDocumentPath($upcoming);
                if ($document_path) {
                    $link = [
                        'url' => $document_path,
                        'caption' => $config['frontend']['more']['de'],
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
        $tokens = $this->config->getAllTokens();

        $earliest = Carbon::tomorrow();
        $current = null;

        $output = '';

        try {

            foreach ($tokens as $token) {
                $cm = $this->getConfigForToken($token);

                if ('never' != $cm['show_info'] && $this->isActive($token)) {
                    $from = $this->getMaintenanceFrom($token);
                    if ($from->lessThan($earliest)) {
                        $earliest = $from;
                        $current = $token;
                    }
                }
            }

            if ($current) {
                $config = $this->config->getData();
                $from = $this->getMaintenanceFrom($current);
                $to = $this->getMaintenanceTo($current);

                $text = $config['frontend']['indication_current']['de'];
                $message = sprintf(
                    $text,
                    $from->format($config['frontend']['fulltimeformat']['de']),
                    $to->format($config['frontend']['fulltimeformat']['de'])
                );

                $link = '';
                $document_path = $this->getDocumentPath($current);
                if ($document_path) {
                    $link = [
                        'url' => $document_path,
                        'caption' => $config['frontend']['more']['de'],
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
    protected function isTimeslotEntered(string $token): bool
    {
        if (!in_array($token, $this->getValidTokens())) {
            throw new \Exception("Invalid token: {$token}");
        }

        $cm = $this->getConfigForToken($token);

        $from = $this->toCarbon($cm['planned']['from']);
        $to = $this->toCarbon($cm['planned']['to']);

        return Carbon::now()->between($from, $to);
    }
}
