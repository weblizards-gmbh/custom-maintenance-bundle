<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Controller;

use Carbon\Carbon;
use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Weblizards\CustomMaintenanceBundle\Config;

/**
 * Class AdminpanelController.
 *
 * @Route("/adminpanel")
 */
class AdminpanelController extends AdminController
{
    public const DATE = 'date';

    public const TIME = 'time';

    /**
     * @Route("/load")
     */
    public function loadAction(Config $config): JsonResponse
    {
        $defaultData = [
            'frontend' => [
                'indication_upcoming' => [
                    'de' => 'Geplante Wartungsarbeiten von %s bis %s.',
                    'en' => 'Upcoming maintenance from %s to %s.',
                ],
                'indication_current' => [
                    'de' => 'Gegenwärtige Wartungsarbeiten von %s bis %s.',
                    'en' => 'Current maintenance from %s to %s.',
                ],
                'more' => [
                    'de' => 'Mehr Informationen...',
                    'en' => 'more...',
                ],
                'fulltimeformat' => [
                    'de' => 'd.m.Y H:i',
                    'en' => 'm/d/Y H:i',
                ],
            ],
        ];
        $data = array_merge($defaultData, $config->getData());
        $data['tokens'] = $tokens = array_keys($data['custom']);

        return new JsonResponse($data);
    }

    /**
     * @Route("/save")
     */
    public function saveAction(Request $request, Config $config): JsonResponse
    {
        try {
            $decoder = new JsonDecode();
            $values = $decoder->decode($request->get('data'), JsonEncoder::FORMAT, ['json_decode_associative' => true]);

            $data = $config->getData();

            $data['frontend']['indication_upcoming']['de'] = $values['frontend_indication_upcoming'];
            $data['frontend']['indication_current']['de'] = $values['frontend_indication_current'];
            $data['frontend']['more']['de'] = $values['frontend_more'];
            $data['frontend']['fulltimeformat']['de'] = $values['frontend_fulltimeformat'];

            // Pimcore
            $data['pimcore']['show_info'] = $values['pimcore_show_info'];
            $data['pimcore']['show_info_from']['date'] = $this->convertJSDateTime($values['pimcore_show_info_from_date'], self::DATE);
            $data['pimcore']['show_info_from']['time'] = $this->convertJSDateTime($values['pimcore_show_info_from_time'], self::TIME);

            $data['pimcore']['planned']['from']['date'] = $this->convertJSDateTime($values['pimcore_from_date'], self::DATE);
            $data['pimcore']['planned']['from']['time'] = $this->convertJSDateTime($values['pimcore_from_time'], self::TIME);
            $data['pimcore']['planned']['to']['date'] = $this->convertJSDateTime($values['pimcore_to_date'], self::DATE);
            $data['pimcore']['planned']['to']['time'] = $this->convertJSDateTime($values['pimcore_to_time'], self::TIME);

            $data['pimcore']['document'] = $values['pimcore_document'];

            // Custom
            $tokens = array_keys($data['custom']);

            foreach ($tokens as $token) {
                $custom = [
                    'active' => $values[$token . '_active'],
                    'fixed' => $values[$token . '_fixed'],
                    'description' => $values[$token . '_description'],
                    'show_info' => $values[$token . '_show_info'],
                    'show_info_from' => [
                        'date' => $this->convertJSDateTime($values[$token . '_show_info_from_date'], self::DATE),
                        'time' => $this->convertJSDateTime($values[$token . '_show_info_from_time'], self::TIME),
                    ],
                    'planned' => [
                        'from' => [
                            'date' => $this->convertJSDateTime($values[$token . '_from_date'], self::DATE),
                            'time' => $this->convertJSDateTime($values[$token . '_from_time'], self::TIME),
                        ],
                        'to' => [
                            'date' => $this->convertJSDateTime($values[$token . '_to_date'], self::DATE),
                            'time' => $this->convertJSDateTime($values[$token . '_to_time'], self::TIME),
                        ],
                    ],
                    'document' => $values[$token . '_document'],
                ];
                $data['custom'][$token] = $custom;
            }

            $config->setData($data);
            $config->save();

            $response_data = [
                'success' => true,
                'message' => $this->trans('custommaintenance_adminpanel_save_success'),
            ];
        } catch (\Exception $e) {
            $response_data = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        return new JsonResponse($response_data);
    }

    /**
     * @param string $dateTime
     * @param string $target
     *
     * @return string
     *
     * @throws \Exception
     */
    public function convertJSDateTime($dateTime, $target)
    {
        $_dateTime = new Carbon($dateTime);

        switch ($target) {
            case self::DATE:
                $format = 'd.m.Y';

                break;

            case self::TIME:
                $format = 'H:i';

                break;

            default:
                throw new \Exception('Invalid target');

                break;
        }

        return $_dateTime->format($format);
    }
}
