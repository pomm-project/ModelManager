<?php
/*
 * This file is part of the PommProject/ModelManager package.
 *
 * (c) 2014 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager;

class ModelManagerDatabaseConfiguration extends DatabaseConfiguration
{
    protected function initalize()
    {
        parent::__initialize();
        $default_poolers = array_merge(
            $this
                ->getParameterHolder()
                ->getParameter('default:client_poolers'),
            [
                'model'       => '\PommProject\ModelManager\Model\ModelPooler',
                'model_layer' => '\PommProject\ModelManager\ModelLayer\ModelLayerPooler',
            ]
        );
        $this
            ->getParameterHolder()
            ->setParameter('default:client_poolers', $default_poolers)
            ;

        return $this;
    }
}
