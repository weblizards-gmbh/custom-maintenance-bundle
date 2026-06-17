# Review Prompt 3.2 - Acceptance Auditor

Rolle: Acceptance Auditor

Auftrag:
- Reviewe den Diff gegen Story und Kontext.
- Pruefe gezielt auf:
  - Verletzungen der Acceptance Criteria
  - Abweichungen von der Story-Intention
  - fehlende Umsetzung spezifizierten Verhaltens
  - Widersprueche zwischen Story-Constraints und Diff
- Output als Markdown-Liste.
- Jede Finding-Zeile soll enthalten:
  - kurzer Titel
  - verletztes AC oder Constraint
  - Evidenz aus Diff oder Story
- Wenn keine Findings vorliegen, antworte exakt mit `Keine Findings.`

Story-Spec:

```md
# Story 3.2: Geplante Zeitfenster in die Statusbewertung integrieren

Status: review

## Story

As a Betreiber,
I want geplante Start- und Endzeitpunkte fuer Maintenances automatisch auswerten lassen,
so that Wartungsfenster ohne manuellen Eingriff wirksam werden.

## Acceptance Criteria

1. **Given** eine Maintenance-Art mit hinterlegtem Start- und Endzeitpunkt  
   **When** sich die aktuelle Zeit innerhalb des geplanten Zeitfensters befindet  
   **Then** wird die Maintenance als aktiv behandelt.
2. **Given** ein geplantes Zeitfenster, das noch nicht begonnen hat oder bereits abgelaufen ist  
   **When** der Status ausgewertet wird  
   **Then** wird die Maintenance entsprechend ausserhalb des Zeitfensters nicht ueber die Planungslogik aktiviert.
3. **Given** Datums- und Zeitwerte im bestehenden Bundle-Format  
   **When** sie verarbeitet werden  
   **Then** bleiben die fachlich erwarteten Formate `d.m.Y` und `H:i` kompatibel.

## Story Intent

Story `3.2` zieht den bereits vorhandenen Planungszweig der Statusauswertung explizit unter Test. Ziel ist nicht neue Scheduling-Infrastruktur, sondern die fachliche Absicherung, dass gespeicherte Zeitfenster in der Laufzeitbewertung korrekt wirken.
```

Projektkontext:

```md
- Carbon fuer Datums- und Zeitfensterlogik
- Datumswerte im Bundleformat halten:
  - Datum: `d.m.Y`
  - Zeit: `H:i`
- Fuer Zeitfenster und Vergleiche vorhandene Carbon-Nutzung fortsetzen
- Kleine, gezielte Aenderungen bevorzugen
- Testaenderungen sollen gewolltes Verhalten absichern
```

Review-Diff:

```diff
diff --git a/_bmad-output/implementation-artifacts/sprint-status.yaml b/_bmad-output/implementation-artifacts/sprint-status.yaml
index a0b85a0..c4742f3 100644
--- a/_bmad-output/implementation-artifacts/sprint-status.yaml
+++ b/_bmad-output/implementation-artifacts/sprint-status.yaml
@@ -35,7 +35,7 @@
 # - Dev moves story to 'review', then runs code-review in a fresh context
 
 generated: 2026-05-27T13:43:51+0200
-last_updated: 2026-06-17T18:29:26+0200
+last_updated: 2026-06-17T18:45:00+0200
 project: Pimcore Custom Maintenance Bundle
 project_key: NOKEY
 tracking_system: file-system
@@ -58,7 +58,7 @@ development_status:
   epic-2-retrospective: optional
   epic-3: in-progress
   3-1-manuellen-aktiv-inaktiv-status-ueber-die-neue-konfigurationsbasis-auswerten: done
-  3-2-geplante-zeitfenster-in-die-statusbewertung-integrieren: backlog
+  3-2-geplante-zeitfenster-in-die-statusbewertung-integrieren: review
   3-3-cli-steuerung-auf-die-gemeinsame-status-und-konfigurationslogik-ausrichten: backlog
   3-4-schutz-vor-ungewollter-rueckschaltung-beibehalten: backlog
   3-5-laufzeit-api-fuer-statuspruefungen-stabil-halten: backlog
diff --git a/tests/Unit/Service/StatusServiceTest.php b/tests/Unit/Service/StatusServiceTest.php
index 5e38495..ff89d2b 100644
--- a/tests/Unit/Service/StatusServiceTest.php
+++ b/tests/Unit/Service/StatusServiceTest.php
@@ -14,6 +14,70 @@ use Weblizards\CustomMaintenanceBundle\Service\StatusService;
 
 final class StatusServiceTest extends TestCase
 {
+    public function testPlannedTimeslotActivatesMaintenanceWithinWindowEvenIfManualStatusIsInactive(): void
+    {
+        Carbon::setTestNow('2026-02-01 10:30');
+
+        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
+        $settingsStore
+            ->expects(self::once())
+            ->method('load')
+            ->willReturn($this->buildStatusServiceConfig(StatusService::STATUS_INACTIVE));
+
+        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
+        $legacyLoader->expects(self::never())->method('load');
+
+        $service = new StatusService(new MaintenanceConfigManager($settingsStore, $legacyLoader), $this->createHeadLinkMock());
+
+        self::assertSame(StatusService::STATUS_INACTIVE, $service->getStatus('prices'));
+        self::assertTrue($service->isActive('prices'));
+
+        Carbon::setTestNow();
+    }
+
+    public function testPlannedTimeslotDoesNotActivateMaintenanceOutsideWindow(): void
+    {
+        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
+        $settingsStore
+            ->expects(self::once())
+            ->method('load')
+            ->willReturn($this->buildStatusServiceConfig(StatusService::STATUS_INACTIVE));
+
+        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
+        $legacyLoader->expects(self::never())->method('load');
+
+        $service = new StatusService(new MaintenanceConfigManager($settingsStore, $legacyLoader), $this->createHeadLinkMock());
+
+        Carbon::setTestNow('2026-02-01 08:59');
+        self::assertFalse($service->isActive('prices'));
+
+        Carbon::setTestNow('2026-02-01 11:01');
+        self::assertFalse($service->isActive('prices'));
+
+        Carbon::setTestNow();
+    }
+
+    public function testConfiguredTimeslotFormatsRemainCompatibleWithBundleFormat(): void
+    {
+        Carbon::setTestNow('2026-02-01 10:30');
+
+        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
+        $settingsStore
+            ->expects(self::once())
+            ->method('load')
+            ->willReturn($this->buildStatusServiceConfig(StatusService::STATUS_INACTIVE));
+
+        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
+        $legacyLoader->expects(self::never())->method('load');
+
+        $service = new StatusService(new MaintenanceConfigManager($settingsStore, $legacyLoader), $this->createHeadLinkMock());
+
+        self::assertSame('01.02.2026 09:00', $service->getMaintenanceFrom('prices')->format('d.m.Y H:i'));
+        self::assertSame('01.02.2026 11:00', $service->getMaintenanceTo('prices')->format('d.m.Y H:i'));
+
+        Carbon::setTestNow();
+    }
+
     public function testManualActiveStatusIsEvaluatedFromCanonicalConfigOutsideTimeslot(): void
     {
         Carbon::setTestNow('2026-02-01 12:30');
```
