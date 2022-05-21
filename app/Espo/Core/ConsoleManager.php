<?php
/*
 * This file is part of EspoCRM and/or AtroCore.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * AtroCore is EspoCRM-based Open Source application.
 * Copyright (C) 2020 AtroCore UG (haftungsbeschränkt).
 *
 * AtroCore as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * AtroCore as well as EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word
 * and "AtroCore" word.
 *
 * This software is not allowed to be used in Russia and Belarus.
 */

declare(strict_types=1);

namespace Espo\Core;

use Espo\Console;
use Espo\Console\AbstractConsole;
use Espo\Core\Utils\Metadata;

class ConsoleManager extends Injectable
{
    public function __construct()
    {
        $this->addDependency('container');
    }

    /**
     * Run console command
     *
     * @param string $command
     */
    public function run(string $command)
    {
        if (!empty($data = $this->getRouteHandler($command))) {
            if (is_a($data['handler'], AbstractConsole::class, true)) {
                (new $data['handler']($this->getInjection('container')))->run($data['data']);
                die();
            }
            AbstractConsole::show('No such console handler as ' . $data['handler'], 2, true);
        } else {
            AbstractConsole::show('No such console command!', 2, true);
        }
    }

    /**
     * Get route handler
     *
     * @param string $command
     *
     * @return array
     */
    protected function getRouteHandler(string $command): array
    {
        // prepare result
        $result = [];

        foreach (self::loadRoutes() as $route => $handler) {
            if ($route == $command) {
                $result = [
                    'handler' => $handler,
                    'data'    => []
                ];
            } elseif (preg_match_all("/\<(.+?)\>/is", $route, $matches)) {
                // prepare parameters
                $parameters = $matches[1];

                // prepare pattern
                $pattern = "/^{$route}$/";
                foreach ($parameters as $parameter) {
                    $pattern = str_replace("<$parameter>", "(.*)", $pattern);
                }

                if (preg_match_all($pattern, $command, $matches)) {
                    $data = [];
                    foreach ($parameters as $k => $name) {
                        $data[$name] = $matches[$k + 1][0];
                    }

                    $result = [
                        'handler' => $handler,
                        'data'    => $data
                    ];
                }
            }
        }

        return $result;
    }

    public static function loadRoutes(): array
    {
        return [
            "refresh translations"         => Console\RefreshTranslations::class,
            "list"                         => Console\ListCommand::class,
            "install demo-project"         => Console\InstallDemoProject::class,
            "clear cache"                  => Console\ClearCache::class,
            "cleanup"                      => Console\Cleanup::class,
            "sql diff --show"              => Console\SqlDiff::class,
            "sql diff --run"               => Console\SqlDiffRun::class,
            "cron"                         => Console\Cron::class,
            "store --refresh"              => Console\StoreRefresh::class,
            "migrate <module> <from> <to>" => Console\Migrate::class,
            "qm <stream> --run"            => Console\QueueManager::class,
            "qm item <id> --run"           => Console\QueueItem::class,
            "notifications --refresh"      => Console\Notification::class,
            "kill daemons"                 => Console\KillDaemons::class,
            "daemon <name> <id>"           => Console\Daemon::class,
            "check updates"                => Console\CheckUpdates::class,
            "pt --run"                     => Console\PseudoTransactionManager::class,
        ];
    }

    /**
     * Get metadata
     *
     * @return Metadata
     */
    protected function getMetadata(): Metadata
    {
        return $this->getInjection('container')->get('metadata');
    }
}