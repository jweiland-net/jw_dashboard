<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Represents a maintenance or hosting contract entry in the dashboard.
 */
class MaintenanceContract extends AbstractEntity
{
    protected string $title = '';

    protected string $clientName = '';

    protected string $clientNumber = '';

    protected ?\DateTimeImmutable $startDate = null;

    protected ?\DateTimeImmutable $endDate = null;

    protected string $status = 'active';

    protected string $contractType = 'maintenance';

    protected string $notes = '';

    protected int $renewalPromptDays = 30;

    /**
     * @var ObjectStorage<ContactPerson>
     */
    protected ObjectStorage $contactPersons;

    public function __construct()
    {
        $this->initializeObject();
    }

    public function initializeObject(): void
    {
        $this->contactPersons = new ObjectStorage();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getClientName(): string
    {
        return $this->clientName;
    }

    public function setClientName(string $clientName): void
    {
        $this->clientName = $clientName;
    }

    public function getClientNumber(): string
    {
        return $this->clientNumber;
    }

    public function setClientNumber(string $clientNumber): void
    {
        $this->clientNumber = $clientNumber;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeImmutable $startDate): void
    {
        $this->startDate = $startDate;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): void
    {
        $this->endDate = $endDate;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getContractType(): string
    {
        return $this->contractType;
    }

    public function setContractType(string $contractType): void
    {
        $this->contractType = $contractType;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function setNotes(string $notes): void
    {
        $this->notes = $notes;
    }

    public function getRenewalPromptDays(): int
    {
        return $this->renewalPromptDays;
    }

    public function setRenewalPromptDays(int $renewalPromptDays): void
    {
        $this->renewalPromptDays = $renewalPromptDays;
    }

    /**
     * @return ObjectStorage<ContactPerson>
     */
    public function getContactPersons(): ObjectStorage
    {
        return $this->contactPersons;
    }

    /**
     * @param ObjectStorage<ContactPerson> $contactPersons
     */
    public function setContactPersons(ObjectStorage $contactPersons): void
    {
        $this->contactPersons = $contactPersons;
    }

    /**
     * Days until the end date (negative when expired), null for contracts without end date
     */
    public function getDaysRemaining(\DateTimeImmutable $today): ?int
    {
        if ($this->endDate === null) {
            return null;
        }

        $interval = $today->setTime(0, 0)->diff($this->endDate->setTime(0, 0));

        return (int)$interval->format('%r%a');
    }

    /**
     * Elapsed share of the contract term in percent (0-100), null if start or end date is missing
     */
    public function getTermProgress(\DateTimeImmutable $today): ?float
    {
        if ($this->startDate === null || $this->endDate === null || $this->endDate <= $this->startDate) {
            return null;
        }

        $duration = $this->endDate->getTimestamp() - $this->startDate->getTimestamp();
        $elapsed = $today->getTimestamp() - $this->startDate->getTimestamp();

        return round(min(100, max(0, $elapsed / $duration * 100)), 1);
    }

    /**
     * The record specific warning period wins, $defaultWarningDays (extension setting) is the fallback
     */
    public function getRenewalState(\DateTimeImmutable $today, int $defaultWarningDays): ContractRenewalState
    {
        $daysRemaining = $this->getDaysRemaining($today);
        if ($daysRemaining === null) {
            return ContractRenewalState::Unlimited;
        }

        if ($daysRemaining < 0) {
            return ContractRenewalState::Expired;
        }

        $warningDays = $this->renewalPromptDays > 0 ? $this->renewalPromptDays : $defaultWarningDays;

        return $daysRemaining <= $warningDays ? ContractRenewalState::Expiring : ContractRenewalState::Active;
    }
}
