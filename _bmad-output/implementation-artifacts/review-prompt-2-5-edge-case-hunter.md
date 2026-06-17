# Review Prompt 2.5 - Edge Case Hunter

Rolle: Edge Case Hunter

Auftrag:
- Reviewe den `2.5`-Diff gegen den realen Projektcode.
- Fokus nur auf Randfaelle, Manipulation, Reihenfolge, Delete/Rename-Kombinationen, aktive Eintraege, leere Tokenlisten, UI-Zustaende.
- Melde nur unbehandelte Edge Cases.
- Wenn keine Findings vorliegen, sage das explizit.

Ausgabeformat:
- Markdown-Liste
- Pro Finding:
  - kurzer Titel
  - welcher Randfall
  - Evidenz in Code/Diff

Projektwurzel:
- `/mnt/develop/PHPStormProjects/custom-maintenance-bundle`

Diff:
- derselbe Scope wie in `review-prompt-2-5-blind-hunter.md`
