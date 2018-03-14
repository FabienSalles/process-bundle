<?php
 /*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use CleverAge\ProcessBundle\Transformer\ConfigurableTransformerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Transform an array of data based on mapping and sub-transformers
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class TransformerTask extends AbstractConfigurableTask
{
    const DEFAULT_TRANSFORMER = 'mapping';
    const ACTIVE_TRANSFORMER = 'transformer';

    /** @var TransformerRegistry */
    protected $transformerRegistry;

    /** @var ConfigurableTransformerInterface */
    protected $transformer;

    /**
     * @param TransformerRegistry $transformerRegistry
     *
     * @throws \CleverAge\ProcessBundle\Exception\MissingTransformerException
     * @throws \UnexpectedValueException
     */
    public function __construct(TransformerRegistry $transformerRegistry)
    {
        $this->transformerRegistry = $transformerRegistry;
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $transformerCodes = array_keys($this->transformerRegistry->getTransformers());
        $resolver->setDefault(static::ACTIVE_TRANSFORMER, static::DEFAULT_TRANSFORMER);
        $resolver->setAllowedValues(static::ACTIVE_TRANSFORMER, $transformerCodes);
    }

    /**
     * @param ProcessState $state
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     *
     * @return array
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\OptionDefinitionException
     * @throws \Symfony\Component\OptionsResolver\Exception\NoSuchOptionException
     * @throws \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \CleverAge\ProcessBundle\Exception\MissingTransformerException
     * @throws \UnexpectedValueException
     */
    protected function getOptions(ProcessState $state)
    {
        if (null === $this->options) {
            $resolver = new OptionsResolver();
            $this->configureOptions($resolver);

            $options = $state->getTaskConfiguration()->getOptions();
            if (!array_key_exists(static::ACTIVE_TRANSFORMER, $options)) {
                $options[static::ACTIVE_TRANSFORMER] = static::DEFAULT_TRANSFORMER;
            }

            $this->transformer = $this->transformerRegistry->getTransformer($options[static::ACTIVE_TRANSFORMER]);
            if (!$this->transformer instanceof ConfigurableTransformerInterface) {
                throw new \UnexpectedValueException(
                    "Transformer {$options[static::ACTIVE_TRANSFORMER]} must be a ConfigurableTransformerInterface"
                );
            }
            $this->transformer->configureOptions($resolver);

            $this->options = $resolver->resolve($state->getTaskConfiguration()->getOptions());
        }

        return $this->options;
    }

    /**
     * @param ProcessState $state
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \CleverAge\ProcessBundle\Exception\MissingTransformerException
     * @throws \UnexpectedValueException
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\NoSuchOptionException
     * @throws \Symfony\Component\OptionsResolver\Exception\OptionDefinitionException
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    public function execute(ProcessState $state)
    {
        $output = null;
        $options = $this->getOptions($state);
        $transformerOptions = $options;
        unset(
            $transformerOptions[self::ERROR_STRATEGY],
            $transformerOptions[self::LOG_ERRORS],
            $transformerOptions[self::ACTIVE_TRANSFORMER]
        );

        try {
            $output = $this->transformer->transform(
                $state->getInput(),
                $transformerOptions
            );
        } catch (\Exception $e) {
            $state->setError($state->getInput());
            if ($options[self::LOG_ERRORS]) {
                $state->log('PropertySetter exception: '.$e->getMessage(), LogLevel::ERROR);
            }
            if ($options[self::ERROR_STRATEGY] === self::STRATEGY_SKIP) {
                $state->setSkipped(true);
            } elseif ($options[self::ERROR_STRATEGY] === self::STRATEGY_STOP) {
                $state->stop($e);
            }
        }
        $state->setOutput($output);
    }
}
