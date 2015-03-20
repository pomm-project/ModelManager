<?php
/*
 * This file is part of the PommProject/ModelManager package.
 *
 * (c) 2014 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager\ModelLayer;

use PommProject\Foundation\Client\ClientPooler;
use PommProject\Foundation\Client\ClientPoolerInterface;
use PommProject\ModelManager\Exception\ModelLayerException;

/**
 * ModelLayerPooler
 *
 * Pooler for ModelLayer session client.
 *
 * @package ModelManager
 * @copyright 2014 Grégoire HUBERT
 * @author Grégoire HUBERT
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 * @see ClientPooler
 */
class ModelLayerPooler extends ClientPooler
{
    /**
     * getPoolerType
     *
     * @see ClientPoolerInterface
     */
    public function getPoolerType()
    {
        return 'model_layer';
    }

    /**
     * createClient
     *
     * @see    ClientPooler
     * @return ModelLayer
     */
    protected function createClient($identifier)
    {
        try {
            $reflection = new \ReflectionClass($identifier);
            if (!$reflection->isSubClassOf('\PommProject\ModelManager\ModelLayer\ModelLayer')) {
                throw new ModelLayerException(sprintf("Class '%s' is not a subclass of ModelLayer.", $identifier));
            }
        } catch (\ReflectionException $e) {
            throw new ModelLayerException(sprintf("Error while loading class '%s' (%s).", $identifier, $e->getMessage()));
        }

        return new $identifier();
    }
}
