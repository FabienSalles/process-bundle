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

namespace CleverAge\ProcessBundle\Transformer;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class WrapperTransformer
 *
 * @package Transformer
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class WrapperTransformer implements ConfigurableTransformerInterface
{

    /**
     * Must return the transformed $value
     *
     * @param mixed $input
     * @param array $options
     *
     * @throws \Exception
     *
     * @return mixed $value
     */
    public function transform($input, array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $options = $resolver->resolve($options);

        return [$options['wrapper_key'] => $input];
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'wrapper_key',
            ]
        );
        $resolver->setAllowedTypes('wrapper_key', ['string']);
    }

    /**
     * Returns the unique code to identify the transformer
     *
     * @return string
     */
    public function getCode()
    {
        return 'wrapper';
    }
}