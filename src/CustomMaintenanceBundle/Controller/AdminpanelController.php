<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Controller;

use Carbon\Carbon;
use Pimcore\Controller\UserAwareController;
use Pimcore\Translation\Translator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Weblizards\CustomMaintenanceBundle\Service\MaintenanceConfigManager;
use Weblizards\CustomMaintenanceBundle\Service\StatusService;

/**
 * Class AdminpanelController.
 */
#[Route("/adminpanel")]
class AdminpanelController extends UserAwareController
{
    #[Route("/load")]
    public function loadAction(MaintenanceConfigManager $configManager): JsonResponse
    {
        return new JsonResponse($configManager->getAdminData());
    }

    #[Route("/save")]
    public function saveAction(Request $request, MaintenanceConfigManager $configManager, Translator $translator): JsonResponse
    {
        try {
            $decoder = new JsonDecode();
            $values = $decoder->decode($request->get('data'), JsonEncoder::FORMAT, ['json_decode_associative' => true]);
            $configManager->saveFromAdminPayload($values);

            $response_data = [
                'success' => true,
                'message' => $translator->trans('custommaintenance_adminpanel_save_success', [], 'admin'),
            ];
        } catch (\Exception $e) {
            $response_data = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        return new JsonResponse($response_data);
    }

    #[Route("/diagnose")]
    public function diagnoseAction(Request $request, StatusService $statusService): JsonResponse
    {
        try {
            $decoder = new JsonDecode();
            $values = $decoder->decode($request->get('data'), JsonEncoder::FORMAT, ['json_decode_associative' => true]);
            $referenceTime = $this->parseDiagnosisReferenceTime((array) $values);

            return new JsonResponse([
                'success' => true,
                'diagnosis' => $statusService->getDiagnosis($referenceTime, (array) $values),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function parseDiagnosisReferenceTime(array $values): Carbon
    {
        $date = trim((string) ($values['diagnosis_reference_date'] ?? ''));
        $time = trim((string) ($values['diagnosis_reference_time'] ?? ''));
        if ($date === '' || $time === '') {
            throw new \InvalidArgumentException('Die Diagnose benötigt Datum und Uhrzeit für den Simulationszeitpunkt.');
        }

        $parsedDate = $this->parseUiDateTimeValue($date);
        $parsedTime = $this->parseUiDateTimeValue($time);

        return Carbon::create(
            (int) $parsedDate->format('Y'),
            (int) $parsedDate->format('m'),
            (int) $parsedDate->format('d'),
            (int) $parsedTime->format('H'),
            (int) $parsedTime->format('i'),
            0,
            date_default_timezone_get()
        );
    }

    private function parseUiDateTimeValue(string $value): Carbon
    {
        return Carbon::parse($value)->setTimezone(date_default_timezone_get());
    }
}
