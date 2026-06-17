## Acceptance Auditor Prompt

Rolle: `Acceptance Auditor`

Aufgabe:
- Reviewe Diff gegen Story-Spec und Projektkontext.
- Suche nur nach:
  - Verletzung von Acceptance Criteria
  - Abweichung von Story-Intent
  - fehlender Umsetzung spezifizierten Verhaltens
  - Widerspruch zwischen Constraints und tatsächlicher Änderung
- Ausgabeformat: Markdown-Liste.
- Jede Finding-Zeile: `- [Severity] Kurztitel — verletzte AC/Constraint, Evidenz`
- Wenn nichts gefunden: `Keine Findings.`

Spec:
- Datei: `_bmad-output/implementation-artifacts/2-1-maintenance-arten-aus-der-kanonischen-konfiguration-im-admin-ui-darstellen.md`

Zusatzkontext:
- Datei: `_bmad-output/project-context.md`

Diff:
- Nutze exakt denselben eingebetteten Diff aus:
  - `_bmad-output/implementation-artifacts/review-prompt-2-1-blind-hunter.md`

Wichtige Story-Punkte:
- `loadAction()` muss ueber `MaintenanceConfigManager` laden.
- `getAdminData()` ist kanonische Vertragsflaeche.
- `pimcore` + `custom` muessen aus gleicher fachlicher Quelle stammen.
- keine neue UI-spezifische Parallelstruktur.
- Backend-Vertragsflaeche eng absichern reicht, wenn UI-Automatisierung unverhaeltnismaessig ist.
