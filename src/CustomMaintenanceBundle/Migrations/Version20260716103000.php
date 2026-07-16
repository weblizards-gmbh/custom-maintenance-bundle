<?php

namespace Weblizards\CustomMaintenanceBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Model\Translation;

class Version20260716103000 extends AbstractTranslationMigration
{
    /**
     * @var array<string, array<string, string>>
     */
    protected array $translations = [
        'custommaintenance.scheduled' => [
            'de' => 'Zeitgesteuert',
            'en' => 'Scheduled',
        ],
        'custommaintenance.temporary_active' => [
            'de' => 'Temporärer Aktiv-Override',
            'en' => 'Temporary activation override',
        ],
        'custommaintenance.is_temporarily_active' => [
            'de' => 'Temporär aktiv',
            'en' => 'Temporarily active',
        ],
        'custommaintenance.is_not_temporarily_active' => [
            'de' => 'Nicht aktiv',
            'en' => 'Inactive',
        ],
        'custommaintenance.temporary_active.helptext' => [
            'de' => 'Technischer Aktiv-Override. Überlagert die Basis-Konfiguration vorübergehend, ohne sie zu zerstören.',
            'en' => 'Technical activation override. Temporarily overrides the base configuration without destroying it.',
        ],
        'custommaintenance.diagnosis' => [
            'de' => 'Diagnose',
            'en' => 'Diagnosis',
        ],
        'custommaintenance.diagnosis_help' => [
            'de' => 'Simuliert die aktuelle Konfiguration zu einem frei wählbaren Zeitpunkt, ohne etwas zu speichern.',
            'en' => 'Simulates the current configuration at a freely selectable point in time without saving anything.',
        ],
        'custommaintenance.diagnosis_reference' => [
            'de' => 'Simulationszeitpunkt',
            'en' => 'Simulation time',
        ],
        'custommaintenance.diagnosis_run' => [
            'de' => 'Diagnose aktualisieren',
            'en' => 'Refresh diagnosis',
        ],
        'custommaintenance.diagnosis_error' => [
            'de' => 'Die Diagnose konnte nicht gelesen werden.',
            'en' => 'The diagnosis response could not be read.',
        ],
        'custommaintenance.diagnosis_loading' => [
            'de' => 'Diagnose wird berechnet ...',
            'en' => 'Diagnosis is being calculated ...',
        ],
        'custommaintenance.diagnosis_reference_result' => [
            'de' => 'Ausgewertet für',
            'en' => 'Evaluated for',
        ],
        'custommaintenance.diagnosis_effective_maintenance' => [
            'de' => 'Maintenance effektiv',
            'en' => 'Effective maintenance',
        ],
        'custommaintenance.diagnosis_maintenance_reason' => [
            'de' => 'Maintenance-Grund',
            'en' => 'Maintenance reason',
        ],
        'custommaintenance.diagnosis_effective_notice' => [
            'de' => 'Hinweis effektiv',
            'en' => 'Effective notice',
        ],
        'custommaintenance.diagnosis_notice_reason' => [
            'de' => 'Hinweis-Grund',
            'en' => 'Notice reason',
        ],
        'custommaintenance.diagnosis_notice_current' => [
            'de' => 'Aktiver Hinweis',
            'en' => 'Current notice',
        ],
        'custommaintenance.diagnosis_notice_upcoming' => [
            'de' => 'Geplanter Hinweis',
            'en' => 'Upcoming notice',
        ],
        'custommaintenance.diagnosis_notice_inactive' => [
            'de' => 'Kein Hinweis',
            'en' => 'No notice',
        ],
        'custommaintenance.diagnosis_reason_temporary_activation' => [
            'de' => 'Temporärer Aktiv-Override',
            'en' => 'Temporary activation override',
        ],
        'custommaintenance.diagnosis_reason_manual_activation' => [
            'de' => 'Manuell aktiviert',
            'en' => 'Manually activated',
        ],
        'custommaintenance.diagnosis_reason_scheduled_window' => [
            'de' => 'Zeitfenster ist aktiv',
            'en' => 'Scheduled window is active',
        ],
        'custommaintenance.diagnosis_reason_inactive_scheduled' => [
            'de' => 'Zeitfenster derzeit nicht aktiv',
            'en' => 'Scheduled window is currently inactive',
        ],
        'custommaintenance.diagnosis_reason_inactive' => [
            'de' => 'Keine aktive Maintenance',
            'en' => 'No active maintenance',
        ],
        'custommaintenance.diagnosis_reason_active_maintenance' => [
            'de' => 'Aktive Maintenance schlägt die Hinweis-Planung',
            'en' => 'Active maintenance overrides notice scheduling',
        ],
        'custommaintenance.diagnosis_reason_always' => [
            'de' => 'Hinweis ist dauerhaft aktiviert',
            'en' => 'Notice is always active',
        ],
        'custommaintenance.diagnosis_reason_notice_schedule' => [
            'de' => 'Hinweis-Zeitplanung ist erreicht',
            'en' => 'Notice schedule has been reached',
        ],
        'custommaintenance.diagnosis_reason_notice_not_started' => [
            'de' => 'Hinweis-Zeitplanung hat noch nicht begonnen',
            'en' => 'Notice schedule has not started yet',
        ],
        'custommaintenance.diagnosis_reason_notice_not_upcoming' => [
            'de' => 'Keine künftige Maintenance für einen Upcoming-Hinweis',
            'en' => 'No upcoming maintenance for an upcoming notice',
        ],
        'custommaintenance.diagnosis_reason_notice_unconfigured' => [
            'de' => 'Hinweis-Zeitplanung ist unvollständig',
            'en' => 'Notice schedule is incomplete',
        ],
        'custommaintenance.diagnosis_reason_never' => [
            'de' => 'Hinweis ist deaktiviert',
            'en' => 'Notice is disabled',
        ],
    ];

    public function getDescription(): string
    {
        return 'Add diagnosis and scheduling translations for Custom Maintenance Bundle';
    }

    public function up(Schema $schema): void
    {
        $this->updateTranslations($this->translations, Translation::DOMAIN_ADMIN, true);
    }
}
