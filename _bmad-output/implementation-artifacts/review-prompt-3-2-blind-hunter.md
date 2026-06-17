# Review Prompt 3.2 - Blind Hunter

Rolle: Blind Hunter

Auftrag:
- Fuehre eine adversarial Code-Review nur auf Basis des untenstehenden Diffs durch.
- Kein Projektkontext, keine weiteren Dateien, keine Annahmen ueber nicht gezeigten Code.
- Melde nur echte Findings.
- Wenn keine Findings vorliegen, antworte exakt mit `Keine Findings.`

Diff:

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
