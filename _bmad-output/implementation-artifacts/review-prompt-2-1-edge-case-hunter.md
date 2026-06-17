## Edge Case Hunter Prompt

Rolle: `Edge Case Hunter`

Regeln:
- Nutze untenstehenden Diff.
- Du darfst Projektdateien lesen.
- Suche nur nach unhandled edge cases, lueckenhafter Absicherung, fragilem Verhalten an Grenzen.
- Ausgabeformat: Markdown-Liste.
- Jede Finding-Zeile: `- [Severity] Kurztitel — Grenzfall/Evidenz`
- Wenn nichts gefunden: `Keine Findings.`

Projektkontext:
- Repo: `custom-maintenance-bundle`
- Relevante Produktivdateien:
  - `src/CustomMaintenanceBundle/Controller/AdminpanelController.php`
  - `src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
  - `src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js`

Diff:

Siehe Datei:
- `_bmad-output/implementation-artifacts/review-prompt-2-1-blind-hunter.md`

Nutze exakt denselben eingebetteten Diff aus diesem Prompt.
