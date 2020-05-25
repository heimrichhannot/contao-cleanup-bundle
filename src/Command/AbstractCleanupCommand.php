<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\CleanupBundle\Command;

use Contao\CoreBundle\Command\AbstractLockedCommand;
use Contao\CoreBundle\Framework\ContaoFramework;
use HeimrichHannot\UtilsBundle\Database\DatabaseUtil;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractCleanupCommand extends AbstractLockedCommand
{
    /**
     * @var ContaoFramework
     */
    protected $framework;

    /**
     * @var SymfonyStyle
     */
    protected $io;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var DatabaseUtil
     */
    protected $databaseUtil;

    public function __construct(
        DatabaseUtil $databaseUtil,
        $name = null
    ) {
        parent::__construct($name);

        $this->databaseUtil = $databaseUtil;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeLocked(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->input = new $input();
        $this->rootDir = $this->getContainer()->getParameter('kernel.project_dir');

        $this->framework->initialize();

        return $this->cleanup();
    }

    abstract protected function cleanup();
}
