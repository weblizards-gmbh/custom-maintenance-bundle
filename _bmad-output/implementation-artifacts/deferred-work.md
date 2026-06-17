## Deferred from: code review of story-2.1-maintenance-arten-aus-der-kanonischen-konfiguration-im-admin-ui-darstellen.md (2026-06-17)

- Malformierte persistierte `custom`-Eintraege koennen `getAdminData()` bzw. den Admin-Load weiter hart abstuerzen lassen. Pre-existing Brownfield-Risiko ausserhalb dieses Test-only-Diffs.
- Reservierter Token `pimcore` wird in `custom` weiterhin nicht explizit abgewehrt. Pre-existing Datenvalidierungs-Luecke ausserhalb dieses Test-only-Diffs.
- Invalides UTF-8 in persistierten Textwerten kann den JSON-Admin-Load weiter mit 500 beenden. Pre-existing Serialisierungsrisiko ausserhalb dieses Test-only-Diffs.
