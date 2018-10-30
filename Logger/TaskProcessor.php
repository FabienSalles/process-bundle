<?php

namespace CleverAge\ProcessBundle\Logger;

/**
 * Class TaskProcessor
 *
 * @package CleverAge\ProcessBundle\Logger
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class TaskProcessor extends AbstractProcessor
{
    /**
     * @param array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        $record = parent::__invoke($record);

        $recordExtra = array_key_exists('extra', $record) ? $record['extra'] : [];
        $this->addTaskInfoToRecord($recordExtra);
        $record['extra'] = $recordExtra;

        return $record;
    }
}
