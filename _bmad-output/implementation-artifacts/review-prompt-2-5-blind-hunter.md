# Review Prompt 2.5 - Blind Hunter

Rolle: Blind Hunter

Auftrag:
- Reviewe nur den untenstehenden Diff.
- Kein Projektzugriff.
- Kein Spec-Kontext.
- Melde nur echte Risiken, Bugs, Regressionen oder fragliche Annahmen.
- Wenn keine Findings vorliegen, sage das explizit.

Ausgabeformat:
- Markdown-Liste
- Pro Finding:
  - kurzer Titel
  - Risiko
  - konkrete Diff-Evidenz

Diff:

```diff
Siehe aktuelles `git diff HEAD` fuer:
- _bmad-output/implementation-artifacts/2-5-custom-maintenance-arten-kontrolliert-loeschen.md
- _bmad-output/implementation-artifacts/sprint-status.yaml
- src/CustomMaintenanceBundle/Resources/install/admin_translations.csv
- src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js
- src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php
- tests/Unit/Controller/AdminpanelControllerTest.php
- tests/Unit/Service/MaintenanceConfigManagerTest.php
```
