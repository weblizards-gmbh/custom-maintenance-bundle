<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Controller;

use Pimcore\Controller\UserAwareController;
use Pimcore\Translation\Translator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Weblizards\CustomMaintenanceBundle\Service\MaintenanceConfigManager;

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
}
