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
     * getClient
     *
     * Get an existing ModelLayer from the pool. If the given ModelLayer does
     * not exist, it is instanciated and registered to the pool.
     *
     * @see ClientPoolerInterface
     */
    public function getClient($identifier)
    {
        $model_layer = $this
            ->getSession()
            ->getClient($this->getPoolerType(), $identifier)
            ;

        if ($model_layer === null) {
            $model_layer = $this->createModelLayerClass($identifier);
            $this->getSession()->registerClient($model_layer);
        }

        return $model_layer;
    }

    /**
     * createModelLayerClass
     *
     * Create an instance of the given class.
     *
     * @access protected
     * @param  string $identifier
     * @return ModelLayer
     */
    protected function createModelLayerClass($identifier)
    {
        try {
            $reflection = new \ReflectionClass($identifier);
            if (!$reflection->isSubClassOf('\PommProject\ModelManager\ModelLayer\ModelLayer')) {
                throw new ModeLayerException(sprintf("Class '%s' is not a subclass of ModelLayer.", $identifier));
            }
        } catch (\ReflectionException $e) {
            throw new ModeLayerException(sprintf("Error while loading class '%s' (%s).", $identifier, $e->getMessage()));
        }

        return new $identifier();
    }
}
