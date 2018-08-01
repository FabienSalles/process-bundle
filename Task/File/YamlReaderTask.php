<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task\File;

use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\AbstractIterableOutputTask;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Yaml\Yaml;

/**
 * Reads a YAML file and iterate over its root elements
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class YamlReaderTask extends AbstractIterableOutputTask
{
    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \UnexpectedValueException
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setRequired(
            [
                'file_path',
            ]
        );
        $resolver->setAllowedTypes('file_path', ['string']);
        $resolver->setNormalizer(
            'file_path',
            function (Options $options, $value) {
                if (!file_exists($value)) {
                    throw new \UnexpectedValueException("File not found: {$value}");
                }

                return $value;
            }
        );
    }

    /**
     * @param ProcessState $state
     *
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     *
     * @return \Iterator
     */
    protected function initializeIterator(ProcessState $state): \Iterator
    {
        $filePath = $this->getOption($state, 'file_path');
        $content = Yaml::parseFile($filePath);
        if (!\is_array($content)) {
            throw new \InvalidArgumentException("File content is not an array: {$filePath}");
        }

        return new \ArrayIterator($content);
    }
}
