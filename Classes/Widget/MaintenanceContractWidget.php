<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Widget;

use JWeiland\JwDashboard\Configuration\DashboardExtConf;
use JWeiland\JwDashboard\Configuration\ExtensionConfigurationManager;
use JWeiland\JwDashboard\Domain\Model\ContractRenewalState;
use JWeiland\JwDashboard\Domain\Model\MaintenanceContract;
use JWeiland\JwDashboard\Domain\Repository\MaintenanceContractRepository;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use TYPO3\CMS\Backend\Routing\UriBuilder;

#[AsTaggedItem(priority: 80)]
class MaintenanceContractWidget extends AbstractWidget
{
    public function __construct(
        private readonly MaintenanceContractRepository $contractRepository,
        private readonly ExtensionConfigurationManager $extConfManager,
        private readonly UriBuilder $uriBuilder,
    ) {}

    public function getIdentifier(): string
    {
        return 'maintenanceContract';
    }

    public function getTitle(): string
    {
        return self::LANGUAGE_FILE . 'widget.maintenanceContract.title';
    }

    public function getIconIdentifier(): string
    {
        return 'actions-certificate';
    }

    public function isEnabled(DashboardExtConf $extConf): bool
    {
        return $extConf->enableWidgetMaintenanceContract;
    }

    public function getData(): WidgetData
    {
        $extConf = $this->extConfManager->get();
        $today = new \DateTimeImmutable();

        $contracts = [];
        foreach ($this->contractRepository->findCurrentOrderedByExpiry() as $contract) {
            $state = $contract->getRenewalState($today, $extConf->contractRenewalWarningDays);
            $contracts[] = [
                'contract' => $contract,
                'state' => $state->value,
                'severity' => $state->getSeverity(),
                'daysRemaining' => $contract->getDaysRemaining($today),
                'progress' => $contract->getTermProgress($today),
                'hasProgress' => $contract->getTermProgress($today) !== null,
            ];
        }

        return new WidgetData(
            widgetIdentifier: $this->getIdentifier(),
            available: true,
            payload: [
                'contracts' => $this->sortByUrgency($contracts),
                'expiringCount' => count(array_filter(
                    $contracts,
                    static fn(array $item): bool => $item['state'] !== ContractRenewalState::Active->value
                        && $item['state'] !== ContractRenewalState::Unlimited->value,
                )),
                'showSignupPrompt' => $extConf->showContractSignupPrompt && $contracts === [],
                'signupUrl' => $extConf->contractSignupUrl,
                'manageUrl' => $this->getManageUrl(),
            ],
        );
    }

    /**
     * Contracts with end date first (nearest expiry on top), contracts without end date last
     *
     * @param list<array{contract: MaintenanceContract, state: string, severity: string, daysRemaining: int|null, progress: float|null, hasProgress: bool}> $contracts
     * @return list<array{contract: MaintenanceContract, state: string, severity: string, daysRemaining: int|null, progress: float|null, hasProgress: bool}>
     */
    private function sortByUrgency(array $contracts): array
    {
        usort(
            $contracts,
            static fn(array $a, array $b): int => ($a['daysRemaining'] ?? PHP_INT_MAX) <=> ($b['daysRemaining'] ?? PHP_INT_MAX),
        );

        return $contracts;
    }

    /**
     * Contract records live on root level, which is editable by admins only
     */
    private function getManageUrl(): string
    {
        if ($this->getBackendUser()?->isAdmin() !== true) {
            return '';
        }

        return (string)$this->uriBuilder->buildUriFromRoute('records', ['id' => 0, 'table' => 'tx_jwdashboard_contract']);
    }
}
