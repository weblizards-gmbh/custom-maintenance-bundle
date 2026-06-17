# Review Prompt 2.5 - Acceptance Auditor

Rolle: Acceptance Auditor

Auftrag:
- Reviewe den `2.5`-Diff gegen Story und Projektkontext.
- Pruefe nur:
  - Verstoss gegen Acceptance Criteria
  - Abweichung von Story-Intent
  - fehlende Umsetzung spezifizierten Verhaltens
  - Widerspruch zwischen Constraints und Code
- Wenn keine Findings vorliegen, sage das explizit.

Ausgabeformat:
- Markdown-Liste
- Pro Finding:
  - kurzer Titel
  - betroffenes AC oder Constraint
  - Evidenz aus Diff/Code

Spec:
- [_bmad-output/implementation-artifacts/2-5-custom-maintenance-arten-kontrolliert-loeschen.md](/mnt/develop/PHPStormProjects/custom-maintenance-bundle/_bmad-output/implementation-artifacts/2-5-custom-maintenance-arten-kontrolliert-loeschen.md)

Kontext:
- [_bmad-output/project-context.md](/mnt/develop/PHPStormProjects/custom-maintenance-bundle/_bmad-output/project-context.md)

Diff:
- derselbe Scope wie in `review-prompt-2-5-blind-hunter.md`
