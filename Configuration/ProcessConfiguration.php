<?php
/*
 *    CleverAge/ProcessBundle
 *    Copyright (C) 2017 Clever-Age
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace CleverAge\ProcessBundle\Configuration;

use CleverAge\ProcessBundle\Exception\MissingTaskConfigurationException;

/**
 * Holds the processes configuration to launch a task
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ProcessConfiguration
{
    /** @var string */
    protected $code;

    /** @var array */
    protected $options = [];

    /** @var TaskConfiguration */
    protected $entryPoint;

    /** @var TaskConfiguration */
    protected $endPoint;

    /** @var TaskConfiguration[] */
    protected $taskConfigurations;

    /** @var array */
    protected $dependencyGroups;

    /** @var array */
    protected $mainTaskGroup;

    /**
     * @param string              $code
     * @param TaskConfiguration[] $taskConfigurations
     * @param array               $options
     * @param string              $entryPoint
     * @param string              $endPoint
     */
    public function __construct($code, array $taskConfigurations, array $options = [], $entryPoint = null, $endPoint = null)
    {
        $this->code = $code;
        $this->taskConfigurations = $taskConfigurations;
        $this->options = $options;
        $this->entryPoint = $entryPoint;
        $this->endPoint = $endPoint;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @throws \CleverAge\ProcessBundle\Exception\MissingTaskConfigurationException
     *
     * @return TaskConfiguration|null
     */
    public function getEntryPoint()
    {
        if (null === $this->entryPoint) {
            return null;
        }

        return $this->getTaskConfiguration($this->entryPoint);
    }

    /**
     * @throws \CleverAge\ProcessBundle\Exception\MissingTaskConfigurationException
     *
     * @return TaskConfiguration|null
     */
    public function getEndPoint()
    {
        if (null === $this->endPoint) {
            return null;
        }

        return $this->getTaskConfiguration($this->endPoint);
    }

    /**
     * @return TaskConfiguration[]
     */
    public function getTaskConfigurations(): array
    {
        return $this->taskConfigurations;
    }

    /**
     * @param string $taskCode
     *
     * @throws \CleverAge\ProcessBundle\Exception\MissingTaskConfigurationException
     *
     * @return TaskConfiguration
     */
    public function getTaskConfiguration(string $taskCode): TaskConfiguration
    {
        if (!array_key_exists($taskCode, $this->taskConfigurations)) {
            throw new MissingTaskConfigurationException($taskCode);
        }

        return $this->taskConfigurations[$taskCode];
    }

    /**
     * @param TaskConfiguration $currentTaskConfiguration
     *
     * @deprecated
     * @throws \CleverAge\ProcessBundle\Exception\MissingTaskConfigurationException
     *
     * @return TaskConfiguration[]
     */
    public function getNextTasks(TaskConfiguration $currentTaskConfiguration)
    {
        return $currentTaskConfiguration->getNextTasksConfigurations();
    }

    /**
     * @param TaskConfiguration $currentTaskConfiguration
     *
     * @deprecated
     * @throws \CleverAge\ProcessBundle\Exception\MissingTaskConfigurationException
     *
     * @return TaskConfiguration[]
     */
    public function getErrorTasks(TaskConfiguration $currentTaskConfiguration)
    {
        return $currentTaskConfiguration->getErrorTasksConfigurations();
    }

    /**
     * Group all task by dependencies
     *
     * If one task depend from another, it should come after
     *
     * @return array
     */
    public function getDependencyGroups(): array
    {
        if (!isset($this->dependencyGroups)) {
            $this->dependencyGroups = [];
            foreach ($this->getTaskConfigurations() as $taskConfiguration) {
                $isInBranch = false;
                foreach ($this->dependencyGroups as $branch) {
                    if (in_array($taskConfiguration->getCode(), $branch)) {
                        $isInBranch = true;
                        break;
                    }
                }

                if (!$isInBranch) {
                    $dependencies = $this->buildDependencies($taskConfiguration);

                    // Sort the tasks
                    usort($dependencies, function ($taskCode1, $taskCode2) {
                        $task1 = $this->getTaskConfiguration($taskCode1);
                        $task2 = $this->getTaskConfiguration($taskCode2);

                        if ($task2->hasAncestor($task1)) {
                            return -1;
                        } elseif ($task2->hasDescendant($task1)) {
                            return 1;
                        } else {
                            return 0;
                        }
                    });

                    $this->dependencyGroups[] = $dependencies;
                }
            }
        }

        return $this->dependencyGroups;
    }

    /**
     * Get the main task group that will be executed
     * It may be defined by the entry_point, or the end_point or simply the first task
     *
     * If one task depend from another, it should come after
     *
     * @return array
     */
    public function getMainTaskGroup(): array
    {
        if (!isset($this->mainTaskGroup)) {
            $mainTask = $this->getMainTask();

            foreach ($this->getDependencyGroups() as $branch) {
                if (in_array($mainTask->getCode(), $branch)) {
                    $this->mainTaskGroup = $branch;
                    break;
                }
            }
        }

        return $this->mainTaskGroup;
    }

    /**
     * Get the most important task (may be the entry or end task, or simply the first)
     * Used to check which tree should be used
     *
     * @return TaskConfiguration
     */
    public function getMainTask()
    {
        $entryTask = $this->getEntryPoint();
        if (!$entryTask) {
            $entryTask = $this->getEndPoint();
        }
        if (!$entryTask) {
            $entryTask = reset($this->taskConfigurations);
        }

        return $entryTask;
    }

    /**
     * Cross all relations of a task to find all dependencies, and append them to the given array
     *
     * @param TaskConfiguration $taskConfig
     * @param array             $dependencies
     *
     * @return array
     */
    protected function buildDependencies(TaskConfiguration $taskConfig, array &$dependencies = [])
    {
        $code = $taskConfig->getCode();

        // May have been added by previous task
        if (!in_array($code, $dependencies)) {
            $dependencies[] = $code;

            foreach ($taskConfig->getPreviousTasksConfigurations() as $previousTasksConfig) {
                $this->buildDependencies($previousTasksConfig, $dependencies);
            }

            foreach ($taskConfig->getNextTasksConfigurations() as $nextTasksConfig) {
                $this->buildDependencies($nextTasksConfig, $dependencies);
            }

            foreach ($taskConfig->getErrorTasksConfigurations() as $errorTasksConfig) {
                $this->buildDependencies($errorTasksConfig, $dependencies);
            }
        }

        return $dependencies;
    }
}
