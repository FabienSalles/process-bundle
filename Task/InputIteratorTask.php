<?php

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;

/**
 * Class InputIteratorTask
 *
 * @package CleverAge\ProcessBundle\Task
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class InputIteratorTask extends AbstractConfigurableTask implements IterableTaskInterface
{
    /** @var \Iterator */
    protected $iterator;

    /**
     * @param ProcessState $state
     *
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function execute(ProcessState $state)
    {
        // Recreate an iterator with input if the current iterator is finish
        if (null === $this->iterator || (!$this->iterator->valid() && is_array($state->getInput()))) {
            $this->iterator = new \ArrayIterator($state->getInput());
        }

        $state->addErrorContextValue('iterate_on_array_key', $this->iterator->key());

        // If the initial value is already null, skip right now the next steps
        if ($this->iterator->valid()) {
            $state->setOutput($this->iterator->current());
        } else {
            $state->setSkipped(true);
        }
    }

    /**
     * Moves the internal pointer to the next element,
     * return true if the task has a next element
     * return false if the task has terminated it's iteration
     *
     * @param ProcessState $state
     *
     * @return bool
     */
    public function next(ProcessState $state)
    {
        $this->iterator->next();
        $state->removeErrorContext('iterate_on_array_key');

        return $this->iterator->valid();
    }
}
