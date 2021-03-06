<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Command\DatabaseIntegrity;

use Claroline\AppBundle\Logger\ConsoleLogger;
use Claroline\CoreBundle\Manager\Resource\RightsManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RightsIntegrityCheckerCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('claroline:rights:check')
            ->setDescription('Checks the rights integrity of the platform.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $consoleLogger = ConsoleLogger::get($output);
        /** @var RightsManager $rightsManager */
        $rightsManager = $this->getContainer()->get('claroline.manager.rights_manager');
        $rightsManager->setLogger($consoleLogger);
        $rightsManager->checkIntegrity();
    }
}
