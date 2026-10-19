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
use JWeiland\JwDashboard\Domain\Repository\ContactPersonRepository;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Displays a configurable welcome message and the agency contact card.
 * If a contact person is marked as primary contact, it is shown as personal contact.
 */
#[AsTaggedItem(priority: 70)]
class EditorGuideWidget extends AbstractWidget
{
    public function __construct(
        private readonly ExtensionConfigurationManager $extConfManager,
        private readonly ContactPersonRepository $contactPersonRepository,
    ) {}

    public function getIdentifier(): string
    {
        return 'editorGuide';
    }

    public function getTitle(): string
    {
        return self::LANGUAGE_FILE . 'widget.editorGuide.title';
    }

    public function getIconIdentifier(): string
    {
        return 'actions-book';
    }

    public function isEnabled(DashboardExtConf $extConf): bool
    {
        return $extConf->enableWidgetEditorGuide;
    }

    public function getData(): WidgetData
    {
        $extConf = $this->extConfManager->get();

        return new WidgetData(
            widgetIdentifier: $this->getIdentifier(),
            available: true,
            payload: [
                'welcomeMessage' => $extConf->welcomeMessage,
                'agencyName' => $extConf->agencyName,
                'agencyEmail' => $extConf->agencyEmail,
                'agencyPhone' => $extConf->agencyPhone,
                'agencyPhoneUri' => preg_replace('/[^0-9+]/', '', $extConf->agencyPhone),
                'agencyWebsite' => $extConf->agencyWebsite,
                'agencyWebsiteLabel' => preg_replace('#^https?://(www\.)?#', '', rtrim($extConf->agencyWebsite, '/')),
                'showAgencyLogo' => $extConf->showAgencyLogo,
                'mainContact' => $this->contactPersonRepository->findMainContact(),
            ],
        );
    }
}
