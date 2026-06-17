## Blind Hunter Prompt

Rolle: `Blind Hunter`

Regeln:
- Nutze nur den untenstehenden Diff.
- Keine Projektrecherche.
- Kein Spec-Kontext.
- Suche nur nach Bugs, Regressionen, inkorrekten Annahmen, fragilen Tests, verdeckten Risiken.
- Ausgabeformat: Markdown-Liste.
- Jede Finding-Zeile: `- [Severity] Kurztitel — Evidenz/Grund`
- Wenn nichts gefunden: `Keine Findings.`

Diff:

```diff
diff --git a/_bmad-output/implementation-artifacts/sprint-status.yaml b/_bmad-output/implementation-artifacts/sprint-status.yaml
index 44b0dfc..f32e9ca 100644
--- a/_bmad-output/implementation-artifacts/sprint-status.yaml
+++ b/_bmad-output/implementation-artifacts/sprint-status.yaml
@@ -35,7 +35,7 @@
 # - Dev moves story to 'review', then runs code-review in a fresh context
 
 generated: 2026-05-27T13:43:51+0200
-last_updated: 2026-06-15T12:00:00+0200
+last_updated: 2026-06-17T00:30:00+0200
 project: Pimcore Custom Maintenance Bundle
 project_key: NOKEY
 tracking_system: file-system
@@ -49,8 +49,8 @@ development_status:
   1-4-rolling-migration-fuer-bestehende-installationen-absichern-und-dokumentieren: done
   1-5-opportunistische-pimcore-10-deprecations-im-basisschnitt-mitbehandeln: done
   epic-1-retrospective: done
-  epic-2: backlog
-  2-1-maintenance-arten-aus-der-kanonischen-konfiguration-im-admin-ui-darstellen: backlog
+  epic-2: in-progress
+  2-1-maintenance-arten-aus-der-kanonischen-konfiguration-im-admin-ui-darstellen: review
   2-2-neue-custom-maintenance-art-mit-defaults-anlegen: backlog
   2-3-bestehende-maintenance-art-bearbeiten-und-token-speichern: backlog
   2-4-native-pimcore-maintenance-im-ui-als-geschuetzten-sonderfall-behandeln: backlog
diff --git a/tests/Unit/Controller/AdminpanelControllerTest.php b/tests/Unit/Controller/AdminpanelControllerTest.php
index d636ed1..d19724c 100644
--- a/tests/Unit/Controller/AdminpanelControllerTest.php
+++ b/tests/Unit/Controller/AdminpanelControllerTest.php
@@ -22,6 +22,57 @@ final class AdminpanelControllerTest extends TestCase
         self::assertFalse(is_subclass_of(AdminpanelController::class, AdminController::class));
     }
 
+    public function testLoadActionReturnsCanonicalAdminDataFromConfigManager(): void
+    {
+        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
+        $settingsStore
+            ->expects(self::once())
+            ->method('load')
+            ->willReturn([
+                'frontend' => [
+                    'indication_upcoming' => [
+                        'de' => 'Store upcoming',
+                    ],
+                ],
+                'pimcore' => [
+                    'show_info' => 'automatic',
+                    'show_info_from' => ['date' => '01.01.2026', 'time' => '08:30'],
+                    'planned' => [
+                        'from' => ['date' => '02.01.2026', 'time' => '09:00'],
+                        'to' => ['date' => '02.01.2026', 'time' => '11:00'],
+                    ],
+                    'document' => '/de/pimcore',
+                ],
+                'custom' => [
+                    'prices' => [
+                        'active' => 'true',
+                        'fixed' => 'false',
+                        'description' => 'ERP',
+                        'show_info' => 'always',
+                        'show_info_from' => ['date' => '03.01.2026', 'time' => '08:00'],
+                        'planned' => [
+                            'from' => ['date' => '04.01.2026', 'time' => '10:00'],
+                            'to' => ['date' => '04.01.2026', 'time' => '12:00'],
+                        ],
+                        'document' => '/de/erp',
+                    ],
+                ],
+            ]);
+        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
+        $legacyLoader->expects(self::never())->method('load');
+
+        $configManager = new MaintenanceConfigManager($settingsStore, $legacyLoader);
+        $expectedPayload = $configManager->getAdminData();
+
+        $controller = new AdminpanelController();
+        $response = $controller->loadAction($configManager);
+
+        self::assertSame($expectedPayload, json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR));
+        self::assertSame(['prices'], $expectedPayload['tokens']);
+        self::assertSame('automatic', $expectedPayload['pimcore']['show_info']);
+        self::assertSame('ERP', $expectedPayload['custom']['prices']['description']);
+    }
+
     public function testSaveActionPersistsDecodedPayloadAndReturnsTranslatedSuccessMessage(): void
     {
         $persistedData = null;
diff --git a/tests/Unit/Service/MaintenanceConfigManagerTest.php b/tests/Unit/Service/MaintenanceConfigManagerTest.php
index f2d6798..8870a6d 100644
--- a/tests/Unit/Service/MaintenanceConfigManagerTest.php
+++ b/tests/Unit/Service/MaintenanceConfigManagerTest.php
@@ -121,6 +121,76 @@ final class MaintenanceConfigManagerTest extends TestCase
         self::assertSame('', $configSet->getPimcoreEntry()->getNoticeConfig()->getDocument());
     }
 
+    public function testGetAdminDataExposesCanonicalPayloadForPimcoreAndCustomEntries(): void
+    {
+        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
+        $settingsStore
+            ->expects(self::once())
+            ->method('load')
+            ->willReturn([
+                'frontend' => [
+                    'indication_upcoming' => [
+                        'de' => 'Store upcoming',
+                    ],
+                    'indication_current' => [
+                        'de' => 'Store current',
+                    ],
+                ],
+                'pimcore' => [
+                    'show_info' => 'always',
+                    'show_info_from' => ['date' => '01.01.2026', 'time' => '07:00'],
+                    'planned' => [
+                        'from' => ['date' => '02.01.2026', 'time' => '08:00'],
+                        'to' => ['date' => '02.01.2026', 'time' => '10:00'],
+                    ],
+                    'document' => '/de/pimcore',
+                ],
+                'custom' => [
+                    'prices' => [
+                        'active' => 'true',
+                        'fixed' => 'false',
+                        'description' => 'ERP',
+                        'show_info' => 'automatic',
+                        'show_info_from' => ['date' => '03.01.2026', 'time' => '09:00'],
+                        'planned' => [
+                            'from' => ['date' => '04.01.2026', 'time' => '10:00'],
+                            'to' => ['date' => '04.01.2026', 'time' => '12:00'],
+                        ],
+                        'document' => '/de/erp',
+                    ],
+                    'search' => [
+                        'active' => 'false',
+                        'fixed' => 'true',
+                        'description' => 'Search',
+                        'show_info' => 'never',
+                        'show_info_from' => ['date' => '05.01.2026', 'time' => '10:00'],
+                        'planned' => [
+                            'from' => ['date' => '06.01.2026', 'time' => '11:00'],
+                            'to' => ['date' => '06.01.2026', 'time' => '13:00'],
+                        ],
+                        'document' => '/de/search',
+                    ],
+                ],
+            ]);
+
+        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
+        $legacyLoader->expects(self::never())->method('load');
+
+        $manager = new MaintenanceConfigManager($settingsStore, $legacyLoader);
+        $adminData = $manager->getAdminData();
+
+        self::assertSame(['prices', 'search'], $adminData['tokens']);
+        self::assertSame(array_keys($adminData['custom']), $adminData['tokens']);
+        self::assertSame('Store upcoming', $adminData['frontend']['indication_upcoming']['de']);
+        self::assertSame('Store current', $adminData['frontend']['indication_current']['de']);
+        self::assertSame('always', $adminData['pimcore']['show_info']);
+        self::assertSame('/de/pimcore', $adminData['pimcore']['document']);
+        self::assertSame('ERP', $adminData['custom']['prices']['description']);
+        self::assertSame('false', $adminData['custom']['search']['active']);
+        self::assertSame('true', $adminData['custom']['search']['fixed']);
+        self::assertArrayNotHasKey('values', $adminData);
+    }
+
     public function testGetConfigSetRejectsStructurallyInvalidSettingsStorePayload(): void
     {
         $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
```
