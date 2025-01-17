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
 */

declare(strict_types=1);

namespace Espo\Core;

use Espo\Core\Interfaces\Factory;
use Espo\Core\Interfaces\Injectable;
use Espo\Entities\Portal;
use Espo\Entities\User;
use Treo\Core\ModuleManager\Manager as ModuleManager;

class Container
{
    protected array $data = [];

    protected array $classAliases
        = [
            'crypt'                    => \Espo\Core\Utils\Crypt::class,
            'cronManager'              => \Espo\Core\CronManager::class,
            'consoleManager'           => \Espo\Core\ConsoleManager::class,
            'slim'                     => \Espo\Core\Utils\Api\Slim::class,
            'thumbnail'                => \Espo\Core\Thumbnail\Image::class,
            'migration'                => \Espo\Core\Migration\Migration::class,
            'classParser'              => \Espo\Core\Utils\File\ClassParser::class,
            'fieldManager'             => \Espo\Core\Utils\FieldManager::class,
            'layout'                   => \Espo\Core\Utils\Layout::class,
            'acl'                      => \Espo\Core\Factories\Acl::class,
            'aclManager'               => \Espo\Core\Factories\AclManager::class,
            'clientManager'            => \Espo\Core\Factories\ClientManager::class,
            'controllerManager'        => \Espo\Core\ControllerManager::class,
            'dateTime'                 => \Espo\Core\Factories\DateTime::class,
            'entityManager'            => \Espo\Core\Factories\EntityManager::class,
            'entityManagerUtil'        => \Espo\Core\Factories\EntityManagerUtil::class,
            'fieldManagerUtil'         => \Espo\Core\Factories\FieldManagerUtil::class,
            'workflow'                 => \Espo\Core\Factories\Workflow::class,
            'filePathBuilder'          => \Espo\Core\Factories\FilePathBuilder::class,
            'fileStorageManager'       => \Espo\Core\Factories\FileStorageManager::class,
            'formulaManager'           => \Espo\Core\Factories\FormulaManager::class,
            'injectableFactory'        => \Espo\Core\Factories\InjectableFactory::class,
            'mailSender'               => \Espo\Core\Factories\MailSender::class,
            'number'                   => \Espo\Core\Factories\Number::class,
            'ormMetadata'              => \Espo\Core\Factories\OrmMetadata::class,
            'output'                   => \Espo\Core\Factories\Output::class,
            'preferences'              => \Espo\Core\Factories\Preferences::class,
            'scheduledJob'             => \Espo\Core\Factories\ScheduledJob::class,
            'schema'                   => \Espo\Core\Factories\Schema::class,
            'selectManagerFactory'     => \Espo\Core\Factories\SelectManagerFactory::class,
            'serviceFactory'           => \Espo\Core\Factories\ServiceFactory::class,
            'templateFileManager'      => \Espo\Core\Factories\TemplateFileManager::class,
            'themeManager'             => \Espo\Core\Factories\ThemeManager::class,
            'queueManager'             => \Espo\Core\QueueManager::class,
            'pseudoTransactionManager' => \Espo\Core\PseudoTransactionManager::class,
            'connection'               => \Espo\Core\Factories\Connection::class,
            'pdo'                      => \Espo\Core\Factories\Pdo::class,
            'eventManager'             => \Espo\Core\Factories\EventManager::class,
            'fileManager'              => \Espo\Core\Factories\FileManager::class,
            'log'                      => \Espo\Core\Factories\Log::class,
            'defaultLanguage'          => \Espo\Core\Factories\DefaultLanguage::class,
            'baseLanguage'             => \Espo\Core\Factories\BaseLanguage::class,
            'language'                 => \Espo\Core\Factories\Language::class,
            'dataManager'              => \Espo\Core\Factories\DataManager::class,
            'metadata'                 => \Espo\Core\Factories\Metadata::class,
            'config'                   => \Espo\Core\Factories\Config::class,
            'internalAclManager'       => \Espo\Core\Factories\InternalAclManager::class,
        ];

    public function __construct()
    {
        $this->data['moduleManager'] = new ModuleManager($this);
        foreach ($this->data['moduleManager']->getModules() as $module) {
            $module->onLoad();
        }
    }

    public function setClassAlias(string $alias, string $className): void
    {
        $this->classAliases[$alias] = $className;
    }

    /**
     * Get class
     *
     * @param string $name
     *
     * @return mixed
     */
    public function get(string $name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        // load itself
        if ($name === 'container') {
            $this->data[$name] = $this;
            return $this->data[$name];
        }

        $className = isset($this->classAliases[$name]) ? $this->classAliases[$name] : $name;
        if (class_exists($className)) {
            if (is_a($className, Factory::class, true)) {
                $this->data[$name] = (new $className())->create($this);
                return $this->data[$name];
            }

            $this->data[$name] = new $className();
            if (is_a($className, Injectable::class, true)) {
                foreach ($this->data[$name]->getDependencyList() as $dependency) {
                    $this->data[$name]->inject($dependency, $this->get($dependency));
                }
            }
            return $this->data[$name];
        }

        return null;
    }

    /**
     * Set User
     *
     * @param User $user
     */
    public function setUser(User $user): Container
    {
        $this->set('user', $user);

        return $this;
    }

    /**
     * Set portal
     *
     * @param Portal $portal
     *
     * @return Container
     */
    public function setPortal(Portal $portal): Container
    {
        $this->set('portal', $portal);

        $data = [];
        foreach ($this->get('portal')->getSettingsAttributeList() as $attribute) {
            $data[$attribute] = $this->get('portal')->get($attribute);
        }
        if (empty($data['theme'])) {
            unset($data['theme']);
        }
        if (empty($data['defaultCurrency'])) {
            unset($data['defaultCurrency']);
        }

        foreach ($data as $attribute => $value) {
            $this->get('config')->set($attribute, $value, true);
        }

        return $this;
    }

    /**
     * Set class
     */
    protected function set($name, $obj)
    {
        $this->data[$name] = $obj;
    }

    /**
     * Reload object
     *
     * @param string $name
     *
     * @return Container
     */
    public function reload(string $name): Container
    {
        // unset
        if (isset($this->data[$name])) {
            unset($this->data[$name]);
        }

        return $this;
    }
}
