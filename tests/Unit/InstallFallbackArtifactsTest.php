<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Test\Unit;

use PHPUnit\Framework\TestCase;

final class InstallFallbackArtifactsTest extends TestCase
{
    public function testInitialFallbackArtifactsExistAndRemainStandalone(): void
    {
        $maintenancePath = dirname(__DIR__, 2) . '/src/CustomMaintenanceBundle/Resources/install/fallback/maintenance.html';
        $errorPath = dirname(__DIR__, 2) . '/src/CustomMaintenanceBundle/Resources/install/fallback/error.html';

        self::assertFileExists($maintenancePath);
        self::assertFileExists($errorPath);

        $maintenanceMarkup = (string) file_get_contents($maintenancePath);
        $errorMarkup = (string) file_get_contents($errorPath);

        self::assertStringContainsString('<!DOCTYPE html>', $maintenanceMarkup);
        self::assertStringContainsString('<!DOCTYPE html>', $errorMarkup);
        self::assertStringContainsString('<html lang="de">', $maintenanceMarkup);
        self::assertStringContainsString('<html lang="de">', $errorMarkup);
        self::assertStringContainsString('<style>', $maintenanceMarkup);
        self::assertStringContainsString('<style>', $errorMarkup);
        self::assertStringNotContainsString('http://', $maintenanceMarkup);
        self::assertStringNotContainsString('https://', $maintenanceMarkup);
        self::assertStringNotContainsString('http://', $errorMarkup);
        self::assertStringNotContainsString('https://', $errorMarkup);
        self::assertStringContainsString('<title>Wartungsarbeiten</title>', $maintenanceMarkup);
        self::assertStringContainsString('<title>Technische Störung</title>', $errorMarkup);
    }

    public function testFallbackDocumentTemplatesExistForManualPimcoreAssignment(): void
    {
        $maintenanceTemplatePath = dirname(__DIR__, 2) . '/src/CustomMaintenanceBundle/Resources/views/fallback/maintenance_document.html.twig';
        $errorTemplatePath = dirname(__DIR__, 2) . '/src/CustomMaintenanceBundle/Resources/views/fallback/error_document.html.twig';

        self::assertFileExists($maintenanceTemplatePath);
        self::assertFileExists($errorTemplatePath);

        $maintenanceTemplate = (string) file_get_contents($maintenanceTemplatePath);
        $errorTemplate = (string) file_get_contents($errorTemplatePath);

        self::assertStringContainsString('pimcore_input("headline"', $maintenanceTemplate);
        self::assertStringContainsString('pimcore_input("headline"', $errorTemplate);
        self::assertStringContainsString('pimcore_wysiwyg("content"', $maintenanceTemplate);
        self::assertStringContainsString('pimcore_wysiwyg("content"', $errorTemplate);
    }

    public function testWebserverSnippetExamplesExistForApacheAndNginx(): void
    {
        $apacheSnippetPath = dirname(__DIR__, 2) . '/src/CustomMaintenanceBundle/Resources/install/fallback/apache-hard-fallback.conf.dist';
        $nginxSnippetPath = dirname(__DIR__, 2) . '/src/CustomMaintenanceBundle/Resources/install/fallback/nginx-hard-fallback.conf.dist';

        self::assertFileExists($apacheSnippetPath);
        self::assertFileExists($nginxSnippetPath);

        $apacheSnippet = (string) file_get_contents($apacheSnippetPath);
        $nginxSnippet = (string) file_get_contents($nginxSnippetPath);

        self::assertStringContainsString('maintenance-active.flag', $apacheSnippet);
        self::assertStringContainsString('session-%2.flag', $apacheSnippet);
        self::assertStringContainsString('ErrorDocument 503 /_maintenance/maintenance.html', $apacheSnippet);

        self::assertStringContainsString('maintenance-active.flag', $nginxSnippet);
        self::assertStringContainsString('session-$cookie_PHPSESSID.flag', $nginxSnippet);
        self::assertStringContainsString('error_page 503 /_maintenance/maintenance.html;', $nginxSnippet);
    }
}
