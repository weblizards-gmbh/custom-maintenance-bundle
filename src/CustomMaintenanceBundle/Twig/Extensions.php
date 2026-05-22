<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Weblizards\CustomMaintenanceBundle\Service\StatusService;

/**
 * Create functions to use in templates.
 *
 * Class Extensions
 */
class Extensions extends AbstractExtension
{
    private Environment $engine;

    private StatusService $statusService;

    /**
     * Extensions constructor.
     */
    public function __construct(Environment $engine, StatusService $statusService)
    {
        $this->engine = $engine;
        $this->statusService = $statusService;
    }

    /**
     * @return array|TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('indicateCustomMaintenance', [$this, 'indicateCustomMaintenance'], ['is_safe' => ['html']]),
            new TwigFunction('indicateUpcomingMaintenance', [$this, 'indicateUpcomingMaintenance'], ['is_safe' => ['html']]),
            new TwigFunction('indicateCurrentMaintenance', [$this, 'indicateCurrentMaintenance'], ['is_safe' => ['html']]),
            new TwigFunction('isMaintenanceActive', [$this, 'isActive']),
        ];
    }

    public function indicateCustomMaintenance(): string
    {
        return $this->statusService->indicateCustomMaintenance($this->engine);
    }

    public function indicateUpcomingMaintenance(): string
    {
        return $this->statusService->indicateUpcomingMaintenance($this->engine);
    }

    public function indicateCurrentMaintenance(): string
    {
        return $this->statusService->indicateCurrentMaintenance($this->engine);
    }

    /**
     * @throws \Exception
     */
    public function isActive(?string $token = null): bool
    {
        return $this->statusService->isActive($token);
    }
}
