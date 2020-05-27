<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\CleanupBundle\Command;

use Contao\StringUtil;

class ModulesCommand extends AbstractCleanupCommand
{
    protected static $contentModules;
    protected static $contentTexts;
    protected static $contentHtmls;
    protected static $layoutModules;
    protected static $blockModules;

    public function existsAsInsertTagInTemplate()
    {
        // TODO: search twig and html5
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('cleanup:modules')->setDescription('Finds and cleans unused modules. Be careful while using!');
    }

    protected function cleanup()
    {
        $modules = $this->databaseUtil->findResultsBy('tl_module', null, null, [
            'order' => 'tl_module.id ASC',
        ]);

        if ($modules->numRows < 1) {
            $this->io->warning('No records in tl_module found. Exiting.');

            return 1;
        }

        $places = [
            'tl_content.module',
            'tl_content.text (insert tag)',
            'tl_content.html (insert tag)',
            'tl_layout.modules',
        ];

        if (class_exists('\HeimrichHannot\Blocks\ModuleBlock')) {
            $places[] = 'tl_block_module.module';
        }

        $places = implode(', ', $places);

        $this->initCaches();

        while ($modules->next()) {
            if ($this->existsAsContentModule($modules->id)) {
                continue;
            }

            if ($this->existsAsInsertTagInContentText($modules->id)) {
                continue;
            }

            if ($this->existsAsInsertTagInContentHtml($modules->id)) {
                continue;
            }

            if ($this->existsInLayout($modules->id)) {
                continue;
            }

            if (class_exists('\HeimrichHannot\Blocks\ModuleBlock')) {
                if ($this->existsAsBlockModule($modules->id)) {
                    continue;
                }
            }

            $this->io->note("Unused module found: ID $modules->id -> \"$modules->name\"\n\nnot found in any of the places: $places");
        }

        return 0;
    }

    protected function initCaches()
    {
        $this->initContentCaches();
        $this->initLayoutCache();
        $this->initBlockModuleCache();
    }

    protected function initContentCaches()
    {
        // get tl_content
        $contentRows = $this->db->prepare('SELECT id, module, text, html, type FROM tl_content')->execute();

        if ($contentRows->numRows < 1) {
            $this->io->warning('No records in tl_content found. Skipping the search there.');

            $contentRows = [];
        } else {
            $contentRows = $contentRows->fetchAllAssoc();
        }

        static::$contentModules = [];

        // tl_content.module
        foreach ($contentRows as $row) {
            if ('module' !== $row['type']) {
                continue;
            }

            if (!isset(static::$contentModules[$row['module']])) {
                static::$contentModules[$row['module']] = [];
            }

            static::$contentModules[$row['module']][] = $row;
        }

        // inserttag in tl_content.text
        static::$contentTexts = [];

        foreach ($contentRows as $row) {
            if (!\in_array($row['type'], ['text', 'accordionSingle'])) {
                continue;
            }

            static::$contentTexts[] = $row;
        }

        // inserttag in tl_content.html
        static::$contentHtmls = [];

        foreach ($contentRows as $row) {
            if ('html' !== $row['type']) {
                continue;
            }

            static::$contentHtmls[$row['module']] = $row;
        }
    }

    protected function initBlockModuleCache()
    {
        // get tl_block
        if (class_exists('\HeimrichHannot\Blocks\ModuleBlock')) {
            $blockModuleRows = $this->db->prepare('SELECT id, module FROM tl_block_module')->execute();

            if ($blockModuleRows->numRows < 1) {
                $this->io->warning('No records in tl_block_module found. Skipping the search there.');

                $blockModuleRows = [];
            } else {
                $blockModuleRows = $blockModuleRows->fetchAllAssoc();
            }

            static::$blockModules = [];

            foreach ($blockModuleRows as $row) {
                if ('default' !== $row['type']) {
                    continue;
                }

                static::$contentHtmls[$row['module']] = $row;
            }
        }
    }

    protected function initLayoutCache()
    {
        // get tl_layout
        $layoutRows = $this->db->prepare('SELECT id, modules FROM tl_layout')->execute();

        if ($layoutRows->numRows < 1) {
            $this->io->warning('No records in tl_content found. Skipping the search there.');

            $layoutRows = [];
        } else {
            $layoutRows = $layoutRows->fetchAllAssoc();
        }

        static::$layoutModules = [];

        // tl_layout.modules
        foreach ($layoutRows as $row) {
            $modules = StringUtil::deserialize($layoutRows['modules'], true);

            if (empty($modules)) {
                continue;
            }

            foreach ($modules as $module) {
                if (!isset(static::$layoutModules[$module['mod']])) {
                    static::$layoutModules[$module['mod']] = [];
                }

                static::$layoutModules[$module['mod']][] = $row;
            }
        }
    }

    protected function existsAsContentModule(int $module): bool
    {
        return isset(static::$contentModules[$module]);
    }

    protected function existsAsInsertTagInContentText(int $module): bool
    {
        foreach (static::$contentTexts as $row) {
            if (false !== strpos($row['text'], '{{insert_module::'.$module.'}}')) {
                return true;
            }
        }

        return false;
    }

    protected function existsAsInsertTagInContentHtml(int $module): bool
    {
        foreach (static::$contentHtmls as $row) {
            if (false !== strpos($row['text'], '{{insert_module::'.$module.'}}')) {
                return true;
            }
        }

        return false;
    }

    protected function existsInLayout(int $module)
    {
        return isset(static::$layoutModules[$module]);
    }

    protected function existsAsBlockModule(int $module): bool
    {
        return isset(static::$blockModules[$module]);
    }
}
