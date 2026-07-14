<?php

namespace Weblizards\CustomMaintenanceBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Model\Translation;

class Version20260713114100 extends AbstractTranslationMigration
{
    /**
     * @var array[]
     */
    protected array $translations = [
        'custommaintenance.active' => [
            'de' => 'Aktiv',
            'en' => 'Active',
        ],
        'custommaintenance.always' => [
            'de' => 'Immer',
            'en' => 'Always',
        ],
        'custommaintenance.automatic' => [
            'de' => 'Zeitgesteuert ab',
            'en' => 'Scheduled from',
        ],
        'custommaintenance.datum_bis' => [
            'de' => 'Datum bis',
            'en' => 'Date until',
        ],
        'custommaintenance.datum_von' => [
            'de' => 'Datum von',
            'en' => 'Date from',
        ],
        'custommaintenance.delete' => [
            'de' => 'Maintenance-Art löschen',
            'en' => 'Delete maintenance type',
        ],
        'custommaintenance.delete_confirm_message' => [
            'de' => 'Soll die Maintenance-Art "%s" wirklich gelöscht werden?',
            'en' => 'Do you really want to delete the maintenance type "%s"?',
        ],
        'custommaintenance.delete_confirm_title' => [
            'de' => 'Maintenance-Art löschen',
            'en' => 'Delete maintenance type',
        ],
        'custommaintenance.fixed' => [
            'de' => 'Vorrang für Einstellung',
            'en' => 'Setting has priority',
        ],
        'custommaintenance.fixed.helptext' => [
            'de' => 'Sollen externe Akteure die Einstellungen für &quot;Aktiv&quot; verändern dürfen?',
            'en' => 'Should external actors be allowed to change the settings for &quot;Active&quot;?',
        ],
        'custommaintenance.frontend' => [
            'de' => 'Frontend',
            'en' => 'Frontend',
        ],
        'custommaintenance.frontend_hinweis' => [
            'de' => 'Hinweis im Frontend',
            'en' => 'Notice in frontend',
        ],
        'custommaintenance.frontend_more' => [
            'de' => 'Mehr-Link im Frontend',
            'en' => 'More link in frontend',
        ],
        'custommaintenance.fulltimeformat' => [
            'de' => 'Zeitformat',
            'en' => 'Time format',
        ],
        'custommaintenance.hinweis_zeigen' => [
            'de' => 'Hinweis anzeigen',
            'en' => 'Show notice',
        ],
        'custommaintenance.inactive' => [
            'de' => 'Inaktiv',
            'en' => 'Inactive',
        ],
        'custommaintenance.indication_current' => [
            'de' => 'Gegenwärtige Wartung zeigen',
            'en' => 'Show current maintenance',
        ],
        'custommaintenance.indication_upcoming' => [
            'de' => 'Kommende Wartung zeigen',
            'en' => 'Show upcoming maintenance',
        ],
        'custommaintenance.info_document' => [
            'de' => 'Info Dokument',
            'en' => 'Info document',
        ],
        'custommaintenance.is_fixed' => [
            'de' => 'Einstellung hat Vorrang',
            'en' => 'Setting has priority',
        ],
        'custommaintenance.is_not_fixed' => [
            'de' => 'Einstellung darf verändert werden',
            'en' => 'Setting may be changed',
        ],
        'custommaintenance.never' => [
            'de' => 'Niemals',
            'en' => 'Never',
        ],
        'custommaintenance.pimcore' => [
            'de' => 'Pimcore',
            'en' => 'Pimcore',
        ],
        'custommaintenance.pimcore_protected_notice' => [
            'de' => 'Dieser native Pimcore-Eintrag ist permanent geschützt und kann nicht gelöscht werden.',
            'en' => 'This native Pimcore entry is permanently protected and cannot be deleted.',
        ],
        'custommaintenance.pimcore_protected_title' => [
            'de' => 'Pimcore (geschützter Sondereintrag)',
            'en' => 'Pimcore (protected special entry)',
        ],
        'custommaintenance.display' => [
            'de' => 'Anzeige Hinweis',
            'en' => 'Display notice',
        ],
        'custommaintenance.description' => [
            'de' => 'Beschreibung',
            'en' => 'Description',
        ],
        'custommaintenance.timecontrol' => [
            'de' => 'Zeitsteuerung',
            'en' => 'Time control',
        ],
        'custommaintenance.token' => [
            'de' => 'Technischer Token',
            'en' => 'Technical token',
        ],
        'custommaintenance.token_exists' => [
            'de' => 'Token existiert bereits',
            'en' => 'Token already exists',
        ],
        'custommaintenance.token_invalid' => [
            'de' => 'Token darf nur alphanumerische Zeichen enthalten',
            'en' => 'Token may only contain alphanumeric characters',
        ],
        'custommaintenance.token_reserved' => [
            'de' => 'Token pimcore ist reserviert',
            'en' => 'Token pimcore is reserved',
        ],
        'custommaintenance_adminpanel_add' => [
            'de' => 'Neue Maintenance-Art',
            'en' => 'New maintenance type',
        ],
        'custommaintenance_adminpanel_save' => [
            'de' => 'Adminpanel speichern',
            'en' => 'Save admin panel',
        ],
        'custommaintenance_adminpanel_save_success' => [
            'de' => 'Speichern erfolgreich',
            'en' => 'Saved successfully',
        ],
    ];

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Add translations for Custom Maintenance Bundle';
    }

    public function up(Schema $schema): void
    {
        $this->updateTranslations($this->translations, Translation::DOMAIN_ADMIN, true);
    }
}
