<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Service;

use Carbon\Carbon;
use Weblizards\CustomMaintenanceBundle\Config;
use Weblizards\CustomMaintenanceBundle\Domain\Model\FrontendConfig;
use Weblizards\CustomMaintenanceBundle\Domain\Model\MaintenanceConfigSet;
use Weblizards\CustomMaintenanceBundle\Domain\Model\MaintenanceEntry;
use Weblizards\CustomMaintenanceBundle\Domain\Model\MaintenanceToken;
use Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence\ConfigPersistenceInterface;
use Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence\LegacyConfigLoaderInterface;

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
        $this->assertPimcoreEntryIsProtected($values);

        $data = $this->getCurrentRawData();
        $customTokenMap = $this->resolveCustomTokenMap($values, array_keys($data['custom']));
        $originalCustomData = $data['custom'];
        $updatedCustomData = [];

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

        foreach ($customTokenMap as $formToken => $persistedToken) {
            if (
                $persistedToken !== $formToken
                && array_key_exists($persistedToken, $originalCustomData)
                && !array_key_exists($persistedToken, $customTokenMap)
            ) {
                throw new \InvalidArgumentException('Duplicate custom maintenance token: ' . $persistedToken);
            }

            $entry = array_key_exists($formToken, $originalCustomData)
                ? $originalCustomData[$formToken]
                : $this->createDefaultCustomEntry();

            $entry['active'] = $this->getPayloadValue(
                $values,
                $formToken . '_active',
                (string) ($entry['active'] ?? 'false')
            );
            $entry['fixed'] = $this->getPayloadValue(
                $values,
                $formToken . '_fixed',
                (string) ($entry['fixed'] ?? 'false')
            );
            $entry['description'] = $this->getPayloadValue(
                $values,
                $formToken . '_description',
                (string) ($entry['description'] ?? '')
            );
            $entry['show_info'] = $this->getPayloadValue(
                $values,
                $formToken . '_show_info',
                (string) ($entry['show_info'] ?? 'never')
            );
            $entry['show_info_from']['date'] = $this->convertOptionalJsDateTime(
                $this->getPayloadValue($values, $formToken . '_show_info_from_date'),
                'date'
            );
            $entry['show_info_from']['time'] = $this->convertOptionalJsDateTime(
                $this->getPayloadValue($values, $formToken . '_show_info_from_time'),
                'time'
            );
            $entry['planned']['from']['date'] = $this->convertOptionalJsDateTime(
                $this->getPayloadValue($values, $formToken . '_from_date'),
                'date'
            );
            $entry['planned']['from']['time'] = $this->convertOptionalJsDateTime(
                $this->getPayloadValue($values, $formToken . '_from_time'),
                'time'
            );
            $entry['planned']['to']['date'] = $this->convertOptionalJsDateTime(
                $this->getPayloadValue($values, $formToken . '_to_date'),
                'date'
            );
            $entry['planned']['to']['time'] = $this->convertOptionalJsDateTime(
                $this->getPayloadValue($values, $formToken . '_to_time'),
                'time'
            );
            $entry['document'] = $this->getPayloadValue(
                $values,
                $formToken . '_document',
                (string) ($entry['document'] ?? '')
            );

            $updatedCustomData[$persistedToken] = $entry;
        }

        $data['custom'] = $updatedCustomData;
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

    /**
     * @param string[] $existingTokens
     *
     * @return array<string,string>
     */
    private function resolveCustomTokenMap(array $values, array $existingTokens): array
    {
        if (!array_key_exists('custom_tokens', $values)) {
            return array_combine($existingTokens, $existingTokens) ?: [];
        }

        $rawTokens = explode(',', (string) $values['custom_tokens']);
        $tokenMap = [];
        $seen = [];

        foreach ($rawTokens as $rawToken) {
            $formToken = trim($rawToken);
            if ($formToken === '') {
                continue;
            }

            $persistedToken = trim($this->getPayloadValue($values, $formToken . '_token', $formToken));

            if (array_key_exists($persistedToken, $seen)) {
                throw new \InvalidArgumentException('Duplicate custom maintenance token: ' . $persistedToken);
            }

            $requiresValidation = !in_array($formToken, $existingTokens, true) || $persistedToken !== $formToken;
            if ($requiresValidation) {
                $newToken = MaintenanceToken::fromNewCustomToken($persistedToken);
                if ($newToken->isPimcore()) {
                    throw new \InvalidArgumentException('Custom maintenance token "pimcore" is reserved.');
                }
            }

            $tokenMap[$formToken] = $persistedToken;
            $seen[$persistedToken] = true;
        }

        return $tokenMap;
    }

    private function assertPimcoreEntryIsProtected(array $values): void
    {
        if ($this->isTruthyPayloadValue($values, 'pimcore_delete')) {
            throw new \InvalidArgumentException('Native Pimcore maintenance cannot be deleted.');
        }
    }

    private function createDefaultCustomEntry(): array
    {
        return [
            'active' => 'false',
            'fixed' => 'false',
            'description' => '',
            'show_info' => 'never',
            'show_info_from' => [
                'date' => '',
                'time' => '',
            ],
            'planned' => [
                'from' => [
                    'date' => '',
                    'time' => '',
                ],
                'to' => [
                    'date' => '',
                    'time' => '',
                ],
            ],
            'document' => '',
        ];
    }

    private function getPayloadValue(array $values, string $key, string $default = ''): string
    {
        if (!array_key_exists($key, $values)) {
            return $default;
        }

        return (string) $values[$key];
    }

    private function isTruthyPayloadValue(array $values, string $key): bool
    {
        if (!array_key_exists($key, $values)) {
            return false;
        }

        return in_array(strtolower(trim((string) $values[$key])), ['1', 'true', 'yes', 'on'], true);
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

    private function convertOptionalJsDateTime(string $dateTime, string $target): string
    {
        if (trim($dateTime) === '') {
            return '';
        }

        return $this->convertJsDateTime($dateTime, $target);
    }
}
