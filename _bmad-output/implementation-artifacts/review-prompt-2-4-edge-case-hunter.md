# Review Prompt 2.4 - Edge Case Hunter

Rolle: Edge Case Hunter

Auftrag:
- Reviewe den untenstehenden Diff gegen den realen Projektcode.
- Fokus nur auf Randfaelle, Pfadabzweige, Manipulation, Regressionen.
- Melde nur unbehandelte Edge Cases.
- Wenn kein echter Edge Case offen ist, sage das explizit.

Ausgabeformat:
- Markdown-Liste
- Pro Finding:
  - kurzer Titel
  - welcher Randfall
  - Evidenz im Code/Diff

Projektwurzel:
- `/mnt/develop/PHPStormProjects/custom-maintenance-bundle`

Diff:
- nutze denselben Diff wie in `review-prompt-2-4-blind-hunter.md`
