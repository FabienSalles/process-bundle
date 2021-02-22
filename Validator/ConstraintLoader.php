<?php

namespace CleverAge\ProcessBundle\Validator;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Loader\AbstractLoader;

class ConstraintLoader extends AbstractLoader
{
    public function loadClassMetadata(ClassMetadata $metadata)
    {
        return false;
    }

    /**
     * Build constraints from textual data
     * @see \Symfony\Component\Validator\Mapping\Loader\YamlFileLoader::parseNodes
     *
     * @param array $nodes
     *
     * @return array
     */
    public function buildConstraints(array $nodes): array
    {
        $values = [];

        foreach ($nodes as $name => $childNodes) {
            if (is_numeric($name) && \is_array($childNodes) && 1 === \count($childNodes)) {
                $options = current($childNodes);

                if (\is_array($options)) {
                    $options = $this->buildConstraints($options);
                }

                $values[] = $this->newConstraint(key($childNodes), $options);
            } else {
                if (\is_array($childNodes)) {
                    $childNodes = $this->buildConstraints($childNodes);
                }

                $values[$name] = $childNodes;
            }
        }

        return $values;
    }
}