<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\CoreDashboard;

use JWeiland\JwDashboard\Domain\Model\ContactPerson;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

/**
 * Contact card of the core dashboard widget: the primary contact person or, if none is
 * configured, the agency team. The agency phone/e-mail is offered as "hotline" next to a person.
 */
final readonly class SupportContact
{
    public function __construct(
        public bool $isPerson,
        public string $name,
        public string $role,
        public string $initials,
        public ?FileReference $photo,
        public string $phone,
        public string $email,
        public string $hotlinePhone,
        public string $hotlineEmail,
    ) {}

    /**
     * @param array<string, mixed> $agency Payload of the EditorGuideWidget
     */
    public static function fromAgencyPayload(array $agency): self
    {
        $agencyPhone = trim((string)($agency['agencyPhone'] ?? ''));
        $agencyEmail = trim((string)($agency['agencyEmail'] ?? ''));
        $contact = $agency['mainContact'] ?? null;

        if (!$contact instanceof ContactPerson || trim($contact->getName()) === '') {
            return new self(
                isPerson: false,
                name: trim((string)($agency['agencyName'] ?? '')),
                role: '',
                initials: '',
                photo: null,
                phone: $agencyPhone,
                email: $agencyEmail,
                hotlinePhone: '',
                hotlineEmail: '',
            );
        }

        $phone = trim($contact->getPhone()) ?: trim($contact->getMobile());
        $email = trim($contact->getEmail());

        return new self(
            isPerson: true,
            name: trim($contact->getName()),
            role: trim($contact->getRole()),
            initials: self::buildInitials($contact->getName()),
            photo: $contact->getPhoto(),
            phone: $phone !== '' ? $phone : $agencyPhone,
            email: $email !== '' ? $email : $agencyEmail,
            // Hotline is only worth a line if it differs from what the call/e-mail buttons already use
            hotlinePhone: $phone !== '' && $agencyPhone !== $phone ? $agencyPhone : '',
            hotlineEmail: $email !== '' && strcasecmp($agencyEmail, $email) !== 0 ? $agencyEmail : '',
        );
    }

    public static function buildInitials(string $name): string
    {
        $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($words === []) {
            return '';
        }

        $initials = mb_substr($words[0], 0, 1);
        if (count($words) > 1) {
            $initials .= mb_substr($words[count($words) - 1], 0, 1);
        }

        return mb_strtoupper($initials);
    }

    public static function toPhoneUri(string $phone): string
    {
        return (string)preg_replace('/[^0-9+]/', '', $phone);
    }

    public function getPhoneUri(): string
    {
        return self::toPhoneUri($this->phone);
    }

    public function getHotlinePhoneUri(): string
    {
        return self::toPhoneUri($this->hotlinePhone);
    }

    public function hasHotline(): bool
    {
        return $this->hotlinePhone !== '' || $this->hotlineEmail !== '';
    }

    public function hasDirectLine(): bool
    {
        return $this->isPerson && ($this->phone !== '' || $this->email !== '');
    }
}
