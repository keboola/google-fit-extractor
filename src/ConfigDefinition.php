<?php

declare(strict_types=1);

namespace Keboola\Extractor\GoogleFit;

use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class ConfigDefinition extends BaseConfigDefinition
{
    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $parametersNode = parent::getParametersDefinition();
        // @formatter:off
        /** @noinspection NullPointerExceptionInspection */
        $parametersNode
            ->children()
                ->scalarNode('dataType')
                ->end()
                ->scalarNode('dataSourceId')
                ->end()
            ->end()
        ;
        // @formatter:on
        return $parametersNode;
    }
}
