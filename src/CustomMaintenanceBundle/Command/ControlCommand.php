<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Command;

use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Weblizards\CustomMaintenanceBundle\Service\StatusService;

#[AsCommand(
    name: 'weblizards:custommaintenance:control',
    description: 'control custom maintenances, e.g. (de)activation',
    aliases: ['maintenance']
)]
class ControlCommand extends AbstractCommand
{
    public function __construct(private readonly StatusService $statusService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('task', InputArgument::REQUIRED, 'The task to fulfill. Valid tasks are list-tokens, show-status, activate, deactivate')
            ->addOption('token', null, InputOption::VALUE_REQUIRED, 'The token of the maintenance')
            ->addOption('porcelain', 'p', InputOption::VALUE_NONE, 'Produce machine-readable output')
            ->addOption('override-fixed', 'o', InputOption::VALUE_NONE, 'Apply changes even if "fixed" flag has been activated in the settings.')
            ->addUsage('show-status --token=prices')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $task = $input->getArgument('task');
            $machineReadable = $input->getOption('porcelain');
            $overrideFixed = $input->getOption('override-fixed');

            switch ($task) {
                case 'list-tokens':
                    $validTokens = $this->statusService->getValidTokens();
                    if (!$machineReadable) {
                        $output->writeln('Valid tokens are: ');
                    }
                    $output->writeln($validTokens);

                    break;

                case 'show-status':
                    $token = $input->getOption('token');
                    $status = $this->statusService->getStatus($token);
                    $result = '';

                    switch ($status) {
                        case StatusService::STATUS_ACTIVE:
                            $result = 'active';

                            break;

                        case StatusService::STATUS_INACTIVE:
                            $result = 'inactive';

                            break;
                    }
                    if ($machineReadable) {
                        $output->writeln($result);
                    } else {
                        $output->writeln('<info>Custom Maintenance ' . $token . ' is ' . $result . '</info>');
                    }

                    break;

                case 'activate':
                    $token = $input->getOption('token');
                    $this->statusService->setStatus($token, StatusService::STATUS_ACTIVE, $overrideFixed);
                    if ($machineReadable) {
                        $output->writeln('OK');
                    } else {
                        $output->writeln('<info>Custom Maintenance ' . $token . ' has been activated.</info>');
                    }

                    break;

                case 'deactivate':
                    $token = $input->getOption('token');
                    $this->statusService->setStatus($token, StatusService::STATUS_INACTIVE, $overrideFixed);
                    if ($machineReadable) {
                        $output->writeln('OK');
                    } else {
                        $output->writeln('<info>Custom Maintenance ' . $token . ' has been deactivated.</info>');
                    }

                    break;

                default:
                    throw new \Exception('Unknown task: ' . $task);
            }
        } catch (\Exception $e) {
            $this->writeError($e->getMessage());

            return 1;
        }

        return 0;
    }
}
