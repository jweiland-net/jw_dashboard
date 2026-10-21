<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Command;

use JWeiland\JwDashboard\Service\SampleDataService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Bootstrap;

#[AsCommand(
    name: 'jwdashboard:sample-data',
    description: 'Creates (or removes) demo maintenance contracts and contact persons for the JW Dashboard.',
)]
final class SampleDataCommand extends Command
{
    public function __construct(
        private readonly SampleDataService $sampleDataService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('remove', 'r', InputOption::VALUE_NONE, 'Remove the sample records instead of creating them')
            ->setHelp(
                'Creates four maintenance contracts (renewal due, active, expired, unlimited) and three fictional '
                . 'contact persons on root level. Existing sample records are replaced. Other records are never touched.',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // DataHandler requires an authenticated (CLI admin) backend user
        Bootstrap::initializeBackendAuthentication();

        if ($input->getOption('remove')) {
            $result = $this->sampleDataService->remove();
            $message = sprintf('Removed %d sample contracts and %d sample contact persons.', $result['contracts'], $result['persons']);
        } else {
            $result = $this->sampleDataService->create(new \DateTimeImmutable());
            $message = sprintf('Created %d sample contracts and %d sample contact persons.', $result['contracts'], $result['persons']);
        }

        if ($result['errors'] !== []) {
            $io->error($result['errors']);

            return Command::FAILURE;
        }

        $io->success($message);

        return Command::SUCCESS;
    }
}
