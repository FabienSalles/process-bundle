<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Transformer;

use CleverAge\ProcessBundle\Exception\TransformerException;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Trait TransformerTrait
 *
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
trait TransformerTrait
{
    /** @var LoggerInterface */
    private $logger;

    /** @var PropertyAccessorInterface */
    private $accessor;

    /** @var TransformerRegistry */
    private $transformerRegistry;

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @return PropertyAccessorInterface
     */
    public function getAccessor(): PropertyAccessorInterface
    {
        return $this->accessor;
    }

    /**
     * @return TransformerRegistry
     */
    public function getTransformerRegistry(): TransformerRegistry
    {
        return $this->transformerRegistry;
    }

    /**
     * @param array $transformers
     * @param mixed $value
     *
     * @throws \CleverAge\ProcessBundle\Exception\TransformerException
     *
     * @return mixed
     */
    protected function applyTransformers(array $transformers, $value)
    {
        /** @noinspection ForeachSourceInspection */
        foreach ($transformers as $transformerCode => $transformerOptions) {
            try {
                $transformerCode = $this->getCleanedTransfomerCode($transformerCode);
                $transformer = $this->getTransformerRegistry()->getTransformer($transformerCode);
                $value = $transformer->transform(
                    $value,
                    $transformerOptions ?: []
                );
            } catch (\Throwable $exception) {
                throw new TransformerException($transformerCode, 0, $exception);
            }
        }

        return $value;
    }

    /**
     * This allows to use transformer codes suffixes to avoid limitations to the "transformers" option using codes as
     * keys This way you can chain multiple times the same transformer. Without this, it would silently call only the
     * 1st one.
     *
     * @param string $transformerCode
     *
     * @throws \CleverAge\ProcessBundle\Exception\MissingTransformerException
     *
     * @return string
     * @example
     *         transformers:
     *         callback#1:
     *         callback: array_filter
     *         callback#2:
     *         callback: array_reverse
     *
     *
     */
    protected function getCleanedTransfomerCode(string $transformerCode)
    {
        $match = preg_match('/([^#]+)(#[\d]+)?/', $transformerCode, $parts);

        if (1 === $match && $this->getTransformerRegistry()->hasTransformer($parts[1])) {
            return $parts[1];
        }

        return $transformerCode;
    }

    /**
     * @param mixed $value
     * @param array $options
     *
     * @return mixed
     */
    protected function transformValue($value, array $options = [])
    {
        $transformedValue = null;

        if (null !== $options['constant']) {
            $transformedValue = $options['constant'];
        } elseif (null !== $options['code']) {
            $sourceProperty = $options['code'];
            if (\is_array($sourceProperty)) {
                $transformedValue = [];
                /** @var array $sourceProperty */
                foreach ($sourceProperty as $destKey => $srcKey) {
                    try {
                        $transformedValue[$destKey] = $this->getAccessor()->getValue($value, $srcKey);
                    } catch (\RuntimeException $missingPropertyError) {
                        $this->getLogger()->debug(
                            'Mapping exception',
                            [
                                'srcKey' => $srcKey,
                                'message' => $missingPropertyError->getMessage(),
                            ]
                        );
                        throw $missingPropertyError;
                    }
                }
            } else {
                try {
                    $transformedValue = $this->getAccessor()->getValue($value, $sourceProperty);
                } catch (\RuntimeException $missingPropertyError) {
                    $this->getLogger()->debug(
                        'Mapping exception',
                        [
                            'message' => $missingPropertyError->getMessage(),
                        ]
                    );
                    throw $missingPropertyError;
                }
            }
        } else {
            $transformedValue = $value;
        }

        try {
            $transformedValue = $this->applyTransformers($options['transformers'], $transformedValue);
        } catch (TransformerException $exception) {
            $exception->setTargetProperty('key');
            $this->logger->debug(
                'Transformation exception',
                [
                    'message' => $exception->getPrevious()->getMessage(),
                    'file' => $exception->getPrevious()->getFile(),
                    'line' => $exception->getPrevious()->getLine(),
                    'trace' => $exception->getPrevious()->getTraceAsString(),
                ]
            );

            throw $exception;
        }

        return $transformedValue;
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     *
     * @throws \CleverAge\ProcessBundle\Exception\MissingTransformerException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\OptionDefinitionException
     * @throws \Symfony\Component\OptionsResolver\Exception\NoSuchOptionException
     * @throws \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    protected function configureTransformersOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('transformers', []);
        $resolver->setAllowedTypes('transformers', ['array']);
        /** @noinspection PhpUnusedParameterInspection */
        $resolver->setNormalizer( // This logic is duplicated from the array_map transformer @todo fix me
            'transformers',
            function (Options $options, $transformers) {
                /** @var array $transformers */
                foreach ($transformers as $transformerCode => &$transformerOptions) {
                    $transformerOptionsResolver = new OptionsResolver();
                    $transformerCode = $this->getCleanedTransfomerCode($transformerCode);
                    $transformer = $this->getTransformerRegistry()->getTransformer($transformerCode);
                    if ($transformer instanceof ConfigurableTransformerInterface) {
                        $transformer->configureOptions($transformerOptionsResolver);
                        $transformerOptions = $transformerOptionsResolver->resolve(
                            $transformerOptions ?? []
                        );
                    }
                }

                return $transformers;
            }
        );
    }

}
