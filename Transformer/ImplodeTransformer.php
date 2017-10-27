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
 * Implode multiple array values to a string, based on a split character
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 * @author Corentin Bouix <cbouix@clever-age.com>
 */
class ImplodeTransformer implements ConfigurableTransformerInterface
{
    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('separator');
        $resolver->setDefault('separator', '|');
        $resolver->setAllowedTypes('separator', 'string');
    }

    /**
     * {@inheritDoc}
     * @throws \UnexpectedValueException
     */
    public function transform($value, array $options = [])
    {
        if (!is_array($value)) {
            throw new \UnexpectedValueException('Given value is not an array');
        }

        return implode($options['separator'], $value);
    }

    /**
     * {@inheritDoc}
     */
    public function getCode()
    {
        return 'implode';
    }
}
