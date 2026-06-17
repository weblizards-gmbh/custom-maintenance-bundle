# Review Prompt 2.4 - Blind Hunter

Rolle: Blind Hunter

Auftrag:
- Reviewe nur den untenstehenden Diff.
- Kein Projektzugriff.
- Keine Spekulation ueber nicht sichtbaren Code.
- Finde nur echte Risiken, Bugs, Regressionen, schwache Annahmen.
- Wenn nichts Kritisches auffaellt, sage das explizit.

Ausgabeformat:
- Markdown-Liste
- Pro Finding:
  - kurzer Titel
  - Risiko
  - konkrete Diff-Evidenz

Diff:

```diff
diff --git a/_bmad-output/implementation-artifacts/sprint-status.yaml b/_bmad-output/implementation-artifacts/sprint-status.yaml
index a78396d..85798d3 100644
--- a/_bmad-output/implementation-artifacts/sprint-status.yaml
+++ b/_bmad-output/implementation-artifacts/sprint-status.yaml
@@ -35,7 +35,7 @@
 # - Dev moves story to 'review', then runs code-review in a fresh context
 
 generated: 2026-05-27T13:43:51+0200
-last_updated: 2026-06-17T14:59:37+0200
+last_updated: 2026-06-17T15:57:38+0200
 project: Pimcore Custom Maintenance Bundle
 project_key: NOKEY
 tracking_system: file-system
@@ -52,8 +52,8 @@ development_status:
   epic-2: in-progress
   2-1-maintenance-arten-aus-der-kanonischen-konfiguration-im-admin-ui-darstellen: done
   2-2-neue-custom-maintenance-art-mit-defaults-anlegen: done
-  2-3-bestehende-maintenance-art-bearbeiten-und-token-speichern: backlog
-  2-4-native-pimcore-maintenance-im-ui-als-geschuetzten-sonderfall-behandeln: backlog
+  2-3-bestehende-maintenance-art-bearbeiten-und-token-speichern: done
+  2-4-native-pimcore-maintenance-im-ui-als-geschuetzten-sonderfall-behandeln: review
   2-5-custom-maintenance-arten-kontrolliert-loeschen: backlog
   epic-2-retrospective: optional
   epic-3: backlog
diff --git a/src/CustomMaintenanceBundle/Resources/install/admin_translations.csv b/src/CustomMaintenanceBundle/Resources/install/admin_translations.csv
index 9a34430..161714d 100644
--- a/src/CustomMaintenanceBundle/Resources/install/admin_translations.csv
+++ b/src/CustomMaintenanceBundle/Resources/install/admin_translations.csv
@@ -21,6 +21,8 @@
 "custommaintenance.manualmode";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";""
 "custommaintenance.never";"";"Niemals";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";;
 "custommaintenance.pimcore";"";"Pimcore";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";;
+"custommaintenance.pimcore_protected_notice";"";"Dieser native Pimcore-Eintrag ist permanent geschuetzt und kann nicht geloescht werden.";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";""
+"custommaintenance.pimcore_protected_title";"";"Pimcore (geschuetzter Sondereintrag)";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";""
 "custommaintenance.display";"";"Anzeige";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";""
 "custommaintenance.description";"";"Beschreibung";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";""
 "custommaintenance.timecontrol";"";"Zeitsteuerung";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";"";""
diff --git a/src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js b/src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js
index 0cabb9e..c5d7935 100644
--- a/src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js
+++ b/src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js
@@ -125,7 +125,10 @@ custommaintenance.AdminPanel = Class.create({
                     },
                     {
                         xtype:'fieldset',
-                        title: t('custommaintenance.pimcore'),
+                        title: this.translateWithFallback(
+                            "custommaintenance.pimcore_protected_title",
+                            "Pimcore (geschuetzter Sondereintrag)"
+                        ),
                         collapsible: true,
                         collapsed: false,
                         autoHeight:true,
@@ -133,6 +136,14 @@ custommaintenance.AdminPanel = Class.create({
                             labelWidth: 250
                         },
                         items: [
+                            {
+                                xtype: "displayfield",
+                                value: this.translateWithFallback(
+                                    "custommaintenance.pimcore_protected_notice",
+                                    "Dieser native Pimcore-Eintrag ist permanent geschuetzt und kann nicht geloescht werden."
+                                ),
+                                cls: "x-form-display-field"
+                            },
                             {
                                 xtype: 'fieldset',
                                 title: t("custommaintenance.timecontrol"),
diff --git a/src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php b/src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php
index c803870..11b0673 100644
--- a/src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php
+++ b/src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php
@@ -105,6 +105,8 @@ final class MaintenanceConfigManager
     public function saveFromAdminPayload(array $values): void
     {
+        $this->assertPimcoreEntryIsProtected($values);
+
         $data = $this->getCurrentRawData();
@@ -281,6 +283,13 @@ final class MaintenanceConfigManager
         return $tokenMap;
     }
 
+    private function assertPimcoreEntryIsProtected(array $values): void
+    {
+        if ($this->isTruthyPayloadValue($values, 'pimcore_delete')) {
+            throw new \InvalidArgumentException('Native Pimcore maintenance cannot be deleted.');
+        }
+    }
+
     private function createDefaultCustomEntry(): array
@@ -312,6 +321,15 @@ final class MaintenanceConfigManager
         return (string) $values[$key];
     }
 
+    private function isTruthyPayloadValue(array $values, string $key): bool
+    {
+        if (!array_key_exists($key, $values)) {
+            return false;
+        }
+
+        return in_array(strtolower(trim((string) $values[$key])), ['1', 'true', 'yes', 'on'], true);
+    }
+
     private function convertJsDateTime(string $dateTime, string $target): string
diff --git a/tests/Unit/Controller/AdminpanelControllerTest.php b/tests/Unit/Controller/AdminpanelControllerTest.php
index 81b179e..b05addc 100644
--- a/tests/Unit/Controller/AdminpanelControllerTest.php
+++ b/tests/Unit/Controller/AdminpanelControllerTest.php
@@ -372,4 +372,35 @@ final class AdminpanelControllerTest extends TestCase
     }
+
+    public function testSaveActionReturnsFailurePayloadForTechnicalPimcoreDeleteAttempt(): void
+    {
+        // test added
+    }
 }
diff --git a/tests/Unit/Service/MaintenanceConfigManagerTest.php b/tests/Unit/Service/MaintenanceConfigManagerTest.php
index 04ee74a..bbd301f 100644
--- a/tests/Unit/Service/MaintenanceConfigManagerTest.php
+++ b/tests/Unit/Service/MaintenanceConfigManagerTest.php
@@ -604,6 +604,27 @@ final class MaintenanceConfigManagerTest extends TestCase
         ]);
     }
 
+    public function testSaveFromAdminPayloadRejectsTechnicalDeleteAttemptForNativePimcoreEntry(): void
+    {
+        // test added
+    }
 }
```
