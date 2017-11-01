<?php
/**
 * This file is part of the Atta package.
 *
 * (c) 2017 Media Televisi Indonesia
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Atta\Publisher;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class PublishConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('publisher');

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('repo')->isRequired()->end()
                ->scalarNode('heads')
                    ->defaultValue('master')
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('publishes')
                    ->useAttributeAsKey('source_path')
                    ->scalarPrototype()
                        ->isRequired()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
