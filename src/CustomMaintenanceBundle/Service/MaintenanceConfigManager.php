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
        'hard_fallback' => [
            'maintenance_document' => [
                'id' => null,
                'path' => '',
            ],
            'error_document' => [
                'id' => null,
                'path' => '',
            ],
        ],
    ];

    private ConfigPersistenceInterface $configPersistence;

    private LegacyConfigLoaderInterface $legacyConfigLoader;

    private ?MaintenanceConfigSet $configSet = null;

    private ?array $currentRawData = null;

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

        $this->configSet = $this->buildConfigSetFromRawData($this->getCurrentRawData());

        return $this->configSet;
    }

    public function getAdminData(): array
    {
        $configSet = $this->getConfigSet();
        $data = $configSet->toLegacyArray();
        $data['hard_fallback'] = $this->getCurrentRawData()['hard_fallback'];
        $data['tokens'] = $configSet->getCustomTokens();

        return $data;
    }

    public function getPreviewConfigSetFromAdminPayload(array $values): MaintenanceConfigSet
    {
        return $this->buildConfigSetFromRawData($this->buildRawDataFromAdminPayload($values));
    }

    /**
     * @return array{
     *   hard_fallback: array{
     *     maintenance_document_changed:bool,
     *     error_document_changed:bool,
     *     maintenance_document_id:int|null,
     *     error_document_id:int|null
     *   }
     * }
     */
    public function saveFromAdminPayload(array $values): array
    {
        $this->assertPimcoreEntryIsProtected($values);
        $previousData = $this->getCurrentRawData();
        $updatedData = $this->buildRawDataFromAdminPayload($values);
        $changeSet = $this->buildHardFallbackChangeSet($previousData['hard_fallback'], $updatedData['hard_fallback']);

        $this->persistLegacyData($updatedData);

        return [
            'hard_fallback' => $changeSet,
        ];
    }

    /**
     * @return array{
     *   maintenance_document: array{id:int|null,path:string},
     *   error_document: array{id:int|null,path:string}
     * }
     */
    public function getHardFallbackConfig(): array
    {
        return $this->getCurrentRawData()['hard_fallback'];
    }

    public function setCustomStatus(string $token, string $status): void
    {
        $data = $this->getCurrentRawData();
        if (!array_key_exists($token, $data['custom'])) {
            throw new \InvalidArgumentException('Invalid token: ' . $token);
        }

        if ($status === StatusService::STATUS_ACTIVE) {
            $data['custom'][$token]['temporary_active'] = StatusService::STATUS_ACTIVE;
            $this->persistLegacyData($data);

            return;
        }

        $data['custom'][$token]['temporary_active'] = StatusService::STATUS_INACTIVE;

        if (($data['custom'][$token]['active'] ?? StatusService::STATUS_INACTIVE) === StatusService::STATUS_ACTIVE) {
            $data['custom'][$token]['active'] = StatusService::STATUS_INACTIVE;
        } elseif ($this->isStartedOpenEndedSchedule($data['custom'][$token])) {
            $now = Carbon::now();
            $data['custom'][$token]['planned']['to']['date'] = $now->format('d.m.Y');
            $data['custom'][$token]['planned']['to']['time'] = $now->format('H:i');
        }

        $this->persistLegacyData($data);
    }

    private function buildConfigSetFromRawData(array $rawData): MaintenanceConfigSet
    {
        $customEntries = [];
        foreach ($rawData['custom'] as $token => $entry) {
            $customEntries[$token] = MaintenanceEntry::fromLegacyCustomConfig($token, $entry);
        }

        return new MaintenanceConfigSet(
            MaintenanceEntry::fromLegacyPimcoreConfig($rawData[Config::TOKEN_PIMCORE]),
            $customEntries,
            FrontendConfig::fromLegacyArray($rawData['frontend'])
        );
    }

    private function buildRawDataFromAdminPayload(array $values): array
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
        $data['hard_fallback']['maintenance_document'] = $this->resolveHardFallbackDocumentPayload(
            $this->getPayloadValue($values, 'hard_fallback_maintenance_document_id'),
            $this->getPayloadValue($values, 'hard_fallback_maintenance_document_path')
        );
        $data['hard_fallback']['error_document'] = $this->resolveHardFallbackDocumentPayload(
            $this->getPayloadValue($values, 'hard_fallback_error_document_id'),
            $this->getPayloadValue($values, 'hard_fallback_error_document_path')
        );

        $data['pimcore']['show_info'] = (string) $values['pimcore_show_info'];
        [$pimcoreShowInfoFromDate, $pimcoreShowInfoFromTime] = $this->resolveNoticeTimingPayload(
            (string) $values['pimcore_show_info'],
            (string) $values['pimcore_show_info_from_date'],
            (string) $values['pimcore_show_info_from_time']
        );
        $data['pimcore']['show_info_from']['date'] = $pimcoreShowInfoFromDate;
        $data['pimcore']['show_info_from']['time'] = $pimcoreShowInfoFromTime;
        $this->assertCompleteDateTimePair(
            (string) $values['pimcore_from_date'],
            (string) $values['pimcore_from_time'],
            'Die Pimcore-Zeitsteuerung benötigt einen Startzeitpunkt mit Datum und Uhrzeit.',
            true
        );
        $this->assertCompleteDateTimePair(
            (string) $values['pimcore_to_date'],
            (string) $values['pimcore_to_time'],
            'Die Pimcore-Zeitsteuerung benötigt einen Endzeitpunkt mit Datum und Uhrzeit.',
            true
        );
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

            $maintenanceMode = $this->getPayloadValue($values, $formToken . '_maintenance_mode');
            if ($maintenanceMode === '') {
                $entry['active'] = $this->getPayloadValue(
                    $values,
                    $formToken . '_active',
                    (string) ($entry['active'] ?? 'false')
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
            } else {
                [$activeStatus, $plannedFromDate, $plannedFromTime, $plannedToDate, $plannedToTime] = $this->resolveMaintenanceModePayload(
                    $maintenanceMode,
                    $this->getPayloadValue($values, $formToken . '_from_date'),
                    $this->getPayloadValue($values, $formToken . '_from_time'),
                    $this->getPayloadValue($values, $formToken . '_to_date'),
                    $this->getPayloadValue($values, $formToken . '_to_time')
                );

                $entry['active'] = $activeStatus;
                $entry['planned']['from']['date'] = $plannedFromDate;
                $entry['planned']['from']['time'] = $plannedFromTime;
                $entry['planned']['to']['date'] = $plannedToDate;
                $entry['planned']['to']['time'] = $plannedToTime;
            }

            $entry['temporary_active'] = $this->getPayloadValue(
                $values,
                $formToken . '_temporary_active',
                (string) ($entry['temporary_active'] ?? 'false')
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
            [$showInfoFromDate, $showInfoFromTime] = $this->resolveNoticeTimingPayload(
                $entry['show_info'],
                $this->getPayloadValue($values, $formToken . '_show_info_from_date'),
                $this->getPayloadValue($values, $formToken . '_show_info_from_time')
            );
            $entry['show_info_from']['date'] = $showInfoFromDate;
            $entry['show_info_from']['time'] = $showInfoFromTime;
            $entry['document'] = $this->getPayloadValue(
                $values,
                $formToken . '_document',
                (string) ($entry['document'] ?? '')
            );

            $updatedCustomData[$persistedToken] = $entry;
        }

        $data['custom'] = $updatedCustomData;

        return $data;
    }

    private function persistLegacyData(array $data): void
    {
        $this->configPersistence->save($data);
        $this->configSet = null;
        $this->currentRawData = null;
    }

    private function getCurrentRawData(): array
    {
        if (is_array($this->currentRawData)) {
            return $this->currentRawData;
        }

        $settingsStoreData = $this->configPersistence->load();
        if (is_array($settingsStoreData)) {
            $this->assertValidSettingsStoreData($settingsStoreData);

            $this->currentRawData = $this->normalizeData($settingsStoreData);

            return $this->currentRawData;
        }

        $legacyData = $this->legacyConfigLoader->load();
        if (is_array($legacyData)) {
            $this->currentRawData = $this->normalizeData($legacyData);

            return $this->currentRawData;
        }

        $this->currentRawData = $this->normalizeData([]);

        return $this->currentRawData;
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

        if (array_key_exists('hard_fallback', $settingsStoreData) && !is_array($settingsStoreData['hard_fallback'])) {
            throw new \UnexpectedValueException(
                'Invalid settings store payload for custom maintenance config: key "hard_fallback" must be an array if present.'
            );
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
            'temporary_active' => 'false',
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

    /**
     * @return array{id:int|null,path:string}
     */
    private function resolveHardFallbackDocumentPayload(string $id, string $path): array
    {
        $normalizedId = trim($id);
        $normalizedPath = trim($path);

        if ($normalizedId === '' || $normalizedPath === '') {
            return [
                'id' => null,
                'path' => '',
            ];
        }

        return [
            'id' => (int) $normalizedId,
            'path' => $normalizedPath,
        ];
    }

    /**
     * @param array{
     *   maintenance_document: array{id:int|null,path:string},
     *   error_document: array{id:int|null,path:string}
     * } $previous
     * @param array{
     *   maintenance_document: array{id:int|null,path:string},
     *   error_document: array{id:int|null,path:string}
     * } $current
     *
     * @return array{
     *   maintenance_document_changed:bool,
     *   error_document_changed:bool,
     *   maintenance_document_id:int|null,
     *   error_document_id:int|null
     * }
     */
    private function buildHardFallbackChangeSet(array $previous, array $current): array
    {
        $previousMaintenanceId = $previous['maintenance_document']['id'] ?? null;
        $currentMaintenanceId = $current['maintenance_document']['id'] ?? null;
        $previousErrorId = $previous['error_document']['id'] ?? null;
        $currentErrorId = $current['error_document']['id'] ?? null;

        return [
            'maintenance_document_changed' => $previousMaintenanceId !== $currentMaintenanceId,
            'error_document_changed' => $previousErrorId !== $currentErrorId,
            'maintenance_document_id' => $currentMaintenanceId,
            'error_document_id' => $currentErrorId,
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
        $convertedDateTime = Carbon::parse($dateTime)->setTimezone(date_default_timezone_get());

        if ($target === 'date') {
            return $convertedDateTime->format('d.m.Y');
        }

        if ($target === 'time') {
            return $convertedDateTime->format('H:i');
        }

        throw new \InvalidArgumentException('Invalid target');
    }

    /**
     * @return array{0:string,1:string,2:string,3:string,4:string}
     */
    private function resolveMaintenanceModePayload(
        string $maintenanceMode,
        string $fromDate,
        string $fromTime,
        string $toDate,
        string $toTime
    ): array {
        switch ($maintenanceMode) {
            case 'active':
                return ['true', '', '', '', ''];

            case 'scheduled':
                $this->assertCompleteDateTimePair(
                    $fromDate,
                    $fromTime,
                    'Zeitgesteuerte Maintenance benötigt einen Startzeitpunkt mit Datum und Uhrzeit.',
                    true
                );
                $this->assertCompleteDateTimePair(
                    $toDate,
                    $toTime,
                    'Das optionale Ende einer zeitgesteuerten Maintenance muss Datum und Uhrzeit vollständig enthalten.'
                );

                return [
                    'false',
                    $this->convertOptionalJsDateTime($fromDate, 'date'),
                    $this->convertOptionalJsDateTime($fromTime, 'time'),
                    $this->convertOptionalJsDateTime($toDate, 'date'),
                    $this->convertOptionalJsDateTime($toTime, 'time'),
                ];

            case 'inactive':
            default:
                return ['false', '', '', '', ''];
        }
    }

    /**
     * @return array{0:string,1:string}
     */
    private function resolveNoticeTimingPayload(string $showInfoMode, string $fromDate, string $fromTime): array
    {
        if ($showInfoMode !== 'automatic') {
            return ['', ''];
        }

        $this->assertCompleteDateTimePair(
            $fromDate,
            $fromTime,
            'Zeitgeplanter Hinweis benötigt einen Startzeitpunkt mit Datum und Uhrzeit.',
            true
        );

        return [
            $this->convertOptionalJsDateTime($fromDate, 'date'),
            $this->convertOptionalJsDateTime($fromTime, 'time'),
        ];
    }

    private function convertOptionalJsDateTime(string $dateTime, string $target): string
    {
        if (trim($dateTime) === '') {
            return '';
        }

        return $this->convertJsDateTime($dateTime, $target);
    }

    private function assertCompleteDateTimePair(
        string $dateTimeDate,
        string $dateTimeTime,
        string $message,
        bool $required = false
    ): void
    {
        $hasDate = trim($dateTimeDate) !== '';
        $hasTime = trim($dateTimeTime) !== '';

        if ($hasDate xor $hasTime) {
            throw new \InvalidArgumentException($message);
        }

        if ($required && !$hasDate && !$hasTime) {
            throw new \InvalidArgumentException($message);
        }
    }

    private function isStartedOpenEndedSchedule(array $entry): bool
    {
        $planned = (array) ($entry['planned'] ?? []);
        $from = (array) ($planned['from'] ?? []);
        $to = (array) ($planned['to'] ?? []);
        $fromDate = trim((string) ($from['date'] ?? ''));
        $fromTime = trim((string) ($from['time'] ?? ''));
        $toDate = trim((string) ($to['date'] ?? ''));
        $toTime = trim((string) ($to['time'] ?? ''));

        if ($fromDate === '' || $fromTime === '' || $toDate !== '' || $toTime !== '') {
            return false;
        }

        return Carbon::now()->greaterThanOrEqualTo(Carbon::createFromFormat('d.m.Y H:i', $fromDate . ' ' . $fromTime));
    }
}
