<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Tests\Unit\CoreDashboard;

use JWeiland\JwDashboard\CoreDashboard\SupportContact;
use JWeiland\JwDashboard\Domain\Model\ContactPerson;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SupportContactTest extends TestCase
{
    private const AGENCY = [
        'agencyName' => 'jweiland.net',
        'agencyPhone' => '+49 711 94969-60',
        'agencyEmail' => 'projects@jweiland.net',
    ];

    #[Test]
    public function withoutContactPersonTheAgencyIsTheContact(): void
    {
        $subject = SupportContact::fromAgencyPayload(self::AGENCY + ['mainContact' => null]);

        self::assertFalse($subject->isPerson);
        self::assertSame('jweiland.net', $subject->name);
        self::assertSame('+49 711 94969-60', $subject->phone);
        self::assertSame('+497119496960', $subject->getPhoneUri());
        self::assertSame('projects@jweiland.net', $subject->email);
        self::assertFalse($subject->hasHotline());
        self::assertFalse($subject->hasDirectLine());
    }

    #[Test]
    public function contactPersonWithoutNameIsIgnored(): void
    {
        $person = new ContactPerson();
        $person->setEmail('someone@example.com');

        self::assertFalse(SupportContact::fromAgencyPayload(self::AGENCY + ['mainContact' => $person])->isPerson);
    }

    #[Test]
    public function contactPersonIsUsedWithAgencyAsHotline(): void
    {
        $subject = SupportContact::fromAgencyPayload(self::AGENCY + ['mainContact' => self::person()]);

        self::assertTrue($subject->isPerson);
        self::assertSame('Anna Becker', $subject->name);
        self::assertSame('Project Manager', $subject->role);
        self::assertSame('AB', $subject->initials);
        self::assertSame('+49 711 000000-10', $subject->phone);
        self::assertSame('+4971100000010', $subject->getPhoneUri());
        self::assertSame('anna.becker@example.com', $subject->email);
        self::assertSame('+49 711 94969-60', $subject->hotlinePhone);
        self::assertSame('+497119496960', $subject->getHotlinePhoneUri());
        self::assertSame('projects@jweiland.net', $subject->hotlineEmail);
        self::assertTrue($subject->hasHotline());
        self::assertTrue($subject->hasDirectLine());
    }

    #[Test]
    public function contactPersonWithoutPhoneFallsBackToMobileThenAgency(): void
    {
        $person = self::person();
        $person->setPhone('');
        $person->setMobile('+49 170 1234567');

        self::assertSame('+49 170 1234567', SupportContact::fromAgencyPayload(self::AGENCY + ['mainContact' => $person])->phone);

        $person->setMobile('');
        $subject = SupportContact::fromAgencyPayload(self::AGENCY + ['mainContact' => $person]);

        self::assertSame('+49 711 94969-60', $subject->phone);
        self::assertSame('', $subject->hotlinePhone, 'Hotline must not repeat the number of the call button');
    }

    #[Test]
    public function hotlineIsOmittedIfIdenticalToDirectContact(): void
    {
        $person = self::person();
        $person->setPhone('+49 711 94969-60');
        $person->setEmail('PROJECTS@jweiland.net');

        $subject = SupportContact::fromAgencyPayload(self::AGENCY + ['mainContact' => $person]);

        self::assertFalse($subject->hasHotline());
    }

    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function initialsDataProvider(): \Generator
    {
        yield 'first and last name' => ['Anna Becker', 'AB'];
        yield 'middle names are skipped' => ['Anna Maria von Becker', 'AB'];
        yield 'single name' => ['anna', 'A'];
        yield 'umlauts' => ['Özlem Ünal', 'ÖÜ'];
        yield 'surrounding whitespace' => ["  Anna\tBecker  ", 'AB'];
        yield 'empty' => ['', ''];
    }

    #[Test]
    #[DataProvider('initialsDataProvider')]
    public function buildInitialsReturnsFirstLettersOfFirstAndLastWord(string $name, string $expected): void
    {
        self::assertSame($expected, SupportContact::buildInitials($name));
    }

    #[Test]
    public function toPhoneUriKeepsDigitsAndPlusOnly(): void
    {
        self::assertSame('+497119496960', SupportContact::toPhoneUri('+49 711 / 94969-60'));
    }

    private static function person(): ContactPerson
    {
        $person = new ContactPerson();
        $person->setName('Anna Becker');
        $person->setRole('Project Manager');
        $person->setPhone('+49 711 000000-10');
        $person->setEmail('anna.becker@example.com');

        return $person;
    }
}
