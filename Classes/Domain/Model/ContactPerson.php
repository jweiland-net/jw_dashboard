<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Domain\Model;

use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Represents a jweiland contact person displayed in the dashboard widget.
 */
class ContactPerson extends AbstractEntity
{
    protected string $name = '';

    protected string $email = '';

    protected string $phone = '';

    protected string $mobile = '';

    protected string $role = '';

    protected ?FileReference $photo = null;

    protected bool $isMainContact = false;

    protected string $bio = '';

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    public function getMobile(): string
    {
        return $this->mobile;
    }

    public function setMobile(string $mobile): void
    {
        $this->mobile = $mobile;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): void
    {
        $this->role = $role;
    }

    public function getPhoto(): ?FileReference
    {
        return $this->photo;
    }

    public function setPhoto(?FileReference $photo): void
    {
        $this->photo = $photo;
    }

    public function isMainContact(): bool
    {
        return $this->isMainContact;
    }

    public function setIsMainContact(bool $isMainContact): void
    {
        $this->isMainContact = $isMainContact;
    }

    public function getBio(): string
    {
        return $this->bio;
    }

    public function setBio(string $bio): void
    {
        $this->bio = $bio;
    }
}
