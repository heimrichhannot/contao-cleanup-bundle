<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\CleanupBundle\Command;

abstract class ModulesCommand extends AbstractCleanupCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('cleanup:modules')->setDescription('Finds and cleans unused modules. Be careful while using!');
    }

    protected function cleanup() {
        $modules = $this->databaseUtil->findResultsBy('tl_module', [], [], [
            'order' => 'id ASC'
        ]);

        if ($modules->numRows < 1) {
            $this->io->warning('No modules found.');

            return 1;
        }

        while ($modules->next()) {
            $this->io->note("Checking module \"$modules->name\" (ID $modules->id) ...");
        }

        return 0;
    }
}
