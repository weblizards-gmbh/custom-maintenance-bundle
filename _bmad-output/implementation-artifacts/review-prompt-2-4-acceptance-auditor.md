# Review Prompt 2.4 - Acceptance Auditor

Rolle: Acceptance Auditor

Auftrag:
- Reviewe den `2.4`-Diff gegen Story und Projektkontext.
- Pruefe nur:
  - Verstoss gegen Acceptance Criteria
  - Abweichung von Story-Intent
  - fehlende Umsetzung spezifizierten Verhaltens
  - Widerspruch zwischen Constraints und Code
- Wenn kein echter Verstoss vorliegt, sage das explizit.

Ausgabeformat:
- Markdown-Liste
- Pro Finding:
  - kurzer Titel
  - betroffenes AC oder Constraint
  - Evidenz aus Diff/Code

Spec:
- [_bmad-output/implementation-artifacts/2-4-native-pimcore-maintenance-im-ui-als-geschuetzten-sonderfall-behandeln.md](/mnt/develop/PHPStormProjects/custom-maintenance-bundle/_bmad-output/implementation-artifacts/2-4-native-pimcore-maintenance-im-ui-als-geschuetzten-sonderfall-behandeln.md)

Kontext:
- [_bmad-output/project-context.md](/mnt/develop/PHPStormProjects/custom-maintenance-bundle/_bmad-output/project-context.md)

Diff:
- nutze denselben Diff wie in `review-prompt-2-4-blind-hunter.md`
