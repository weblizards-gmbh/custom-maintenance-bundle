<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Service;

use Carbon\Carbon;
use Weblizards\CustomMaintenanceBundle\Domain\Model\FrontendConfig;
use Weblizards\CustomMaintenanceBundle\Domain\Model\MaintenanceConfigSet;
use Weblizards\CustomMaintenanceBundle\Domain\Model\MaintenanceEntry;
use Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence\ConfigPersistenceInterface;
use Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence\LegacyConfigLoaderInterface;
use Weblizards\CustomMaintenanceBundle\Config;

final class MaintenanceConfigManager
{
    private const DEFAULT_FRONTEND = [
        'frontend' => [
            'indication_upcoming' => [
                'de' => 'Geplante Wartungsarbeiten von %s bis %s.',
                'en' => 'Upcoming maintenance from %s to %s.',
            ],
            'indication_current' => [
                'de' => 'Gegenwärtige Wartungsarbeiten von %s bis %s.',
                'en' => 'Current maintenance from %s to %s.',
            ],
            'more' => [
                'de' => 'Mehr Informationen...',
                'en' => 'more...',
            ],
            'fulltimeformat' => [
                'de' => 'd.m.Y H:i',
                'en' => 'm/d/Y H:i',
            ],
        ],
    ];

    private const DEFAULT_PIMCORE = [
        'pimcore' => [
            'show_info' => 'never',
            'show_info_from' => [
                'date' => '01.01.1970',
                'time' => '00:00',
            ],
            'planned' => [
                'from' => [
                    'date' => '01.01.1970',
                    'time' => '00:00',
                ],
                'to' => [
                    'date' => '01.01.1970',
                    'time' => '00:00',
                ],
            ],
            'document' => '',
        ],
        'custom' => [],
    ];

    private ConfigPersistenceInterface $configPersistence;

    private LegacyConfigLoaderInterface $legacyConfigLoader;

    private ?MaintenanceConfigSet $configSet = null;

    public function __construct(
        ConfigPersistenceInterface $configPersistence,
        LegacyConfigLoaderInterface $legacyConfigLoader
    )
    {
        $this->configPersistence = $configPersistence;
        $this->legacyConfigLoader = $legacyConfigLoader;
    }

    public function getConfigSet(): MaintenanceConfigSet
    {
        if ($this->configSet instanceof MaintenanceConfigSet) {
            return $this->configSet;
        }

        $rawData = $this->getCurrentRawData();
        $customEntries = [];
        foreach ($rawData['custom'] as $token => $entry) {
            $customEntries[$token] = MaintenanceEntry::fromLegacyCustomConfig($token, $entry);
        }

        $this->configSet = new MaintenanceConfigSet(
            MaintenanceEntry::fromLegacyPimcoreConfig($rawData[Config::TOKEN_PIMCORE]),
            $customEntries,
            FrontendConfig::fromLegacyArray($rawData['frontend'])
        );

        return $this->configSet;
    }

    public function getAdminData(): array
    {
        $configSet = $this->getConfigSet();
        $data = $configSet->toLegacyArray();
        $data['tokens'] = $configSet->getCustomTokens();

        return $data;
    }

    public function saveFromAdminPayload(array $values): void
    {
        $data = $this->getCurrentRawData();

        $data['frontend']['indication_upcoming']['de'] = (string) $values['frontend_indication_upcoming'];
        $data['frontend']['indication_current']['de'] = (string) $values['frontend_indication_current'];
        $data['frontend']['more']['de'] = (string) $values['frontend_more'];
        $data['frontend']['fulltimeformat']['de'] = (string) $values['frontend_fulltimeformat'];

        $data['pimcore']['show_info'] = (string) $values['pimcore_show_info'];
        $data['pimcore']['show_info_from']['date'] = $this->convertJsDateTime((string) $values['pimcore_show_info_from_date'], 'date');
        $data['pimcore']['show_info_from']['time'] = $this->convertJsDateTime((string) $values['pimcore_show_info_from_time'], 'time');
        $data['pimcore']['planned']['from']['date'] = $this->convertJsDateTime((string) $values['pimcore_from_date'], 'date');
        $data['pimcore']['planned']['from']['time'] = $this->convertJsDateTime((string) $values['pimcore_from_time'], 'time');
        $data['pimcore']['planned']['to']['date'] = $this->convertJsDateTime((string) $values['pimcore_to_date'], 'date');
        $data['pimcore']['planned']['to']['time'] = $this->convertJsDateTime((string) $values['pimcore_to_time'], 'time');
        $data['pimcore']['document'] = (string) $values['pimcore_document'];

        foreach (array_keys($data['custom']) as $token) {
            $data['custom'][$token]['active'] = (string) $values[$token . '_active'];
            $data['custom'][$token]['fixed'] = (string) $values[$token . '_fixed'];
            $data['custom'][$token]['description'] = (string) $values[$token . '_description'];
            $data['custom'][$token]['show_info'] = (string) $values[$token . '_show_info'];
            $data['custom'][$token]['show_info_from']['date'] = $this->convertJsDateTime((string) $values[$token . '_show_info_from_date'], 'date');
            $data['custom'][$token]['show_info_from']['time'] = $this->convertJsDateTime((string) $values[$token . '_show_info_from_time'], 'time');
            $data['custom'][$token]['planned']['from']['date'] = $this->convertJsDateTime((string) $values[$token . '_from_date'], 'date');
            $data['custom'][$token]['planned']['from']['time'] = $this->convertJsDateTime((string) $values[$token . '_from_time'], 'time');
            $data['custom'][$token]['planned']['to']['date'] = $this->convertJsDateTime((string) $values[$token . '_to_date'], 'date');
            $data['custom'][$token]['planned']['to']['time'] = $this->convertJsDateTime((string) $values[$token . '_to_time'], 'time');
            $data['custom'][$token]['document'] = (string) $values[$token . '_document'];
        }

        $this->persistLegacyData($data);
    }

    public function setCustomStatus(string $token, string $status): void
    {
        $data = $this->getCurrentRawData();
        if (!array_key_exists($token, $data['custom'])) {
            throw new \InvalidArgumentException('Invalid token: ' . $token);
        }

        $data['custom'][$token]['active'] = $status;
        $this->persistLegacyData($data);
    }

    private function persistLegacyData(array $data): void
    {
        $this->configPersistence->save($data);
        $this->configSet = null;
    }

    private function getCurrentRawData(): array
    {
        $settingsStoreData = $this->configPersistence->load();
        if (is_array($settingsStoreData)) {
            $this->assertValidSettingsStoreData($settingsStoreData);

            return $this->normalizeData($settingsStoreData);
        }

        $legacyData = $this->legacyConfigLoader->load();
        if (is_array($legacyData)) {
            return $this->normalizeData($legacyData);
        }

        return $this->normalizeData([]);
    }

    private function assertValidSettingsStoreData(array $settingsStoreData): void
    {
        $requiredArrayKeys = ['frontend', Config::TOKEN_PIMCORE, 'custom'];

        foreach ($requiredArrayKeys as $key) {
            if (!array_key_exists($key, $settingsStoreData) || !is_array($settingsStoreData[$key])) {
                throw new \UnexpectedValueException(
                    sprintf('Invalid settings store payload for custom maintenance config: key "%s" must exist and be an array.', $key)
                );
            }
        }
    }

    private function normalizeData(array $data): array
    {
        $data = array_replace_recursive(self::DEFAULT_FRONTEND, self::DEFAULT_PIMCORE, $data);

        if (!is_array($data['custom'])) {
            $data['custom'] = [];
        }

        return $data;
    }

    private function convertJsDateTime(string $dateTime, string $target): string
    {
        $convertedDateTime = new Carbon($dateTime);

        if ($target === 'date') {
            return $convertedDateTime->format('d.m.Y');
        }

        if ($target === 'time') {
            return $convertedDateTime->format('H:i');
        }

        throw new \InvalidArgumentException('Invalid target');
    }
}
