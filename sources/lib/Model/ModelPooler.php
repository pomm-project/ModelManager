<?php
/*
 * This file is part of the PommProject/ModelManager package.
 *
 * (c) 2014 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager\Model;

use PommProject\Foundation\Client\ClientPoolerInterface;
use PommProject\Foundation\Client\ClientPooler;

use PommProject\ModelManager\Exception\ModelException;

/**
 * ModelPooler
 *
 * Client pooler for model package.
 *
 * @package ModelManager
 * @copyright 2014 Grégoire HUBERT
 * @author Grégoire HUBERT
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 * @see ClientPooler
 */
class ModelPooler extends ClientPooler
{
    /**
     * @see ClientPoolerInterface
     */
    public function getPoolerType()
    {
        return 'model';
    }

    /**
     * getClientFromPool
     *
     * @see    ClientPooler
     * @return Model|null
     */
    protected function getClientFromPool($class)
    {
        return $this->getSession()->getClient($this->getPoolerType(), trim($class, "\\"));
    }

    /**
     * createModel
     *
     * @see    ClientPooler
     * @throws ModelException if incorrect
     * @return Model
     */
    protected function createClient($class)
    {
        try {
            $reflection = new \ReflectionClass($class);
        } catch (\ReflectionException $e) {
            throw new ModelException(sprintf(
                "Could not instantiate Model class '%s'. (Reason: '%s').",
                $class,
                $e->getMessage()
            ));
        }

        if (!$reflection->implementsInterface('\PommProject\Foundation\Client\ClientInterface')) {
            throw new ModelException(sprintf("'%s' class does not implement the ClientInterface interface.", $class));
        }

        if (!$reflection->isSubClassOf('\PommProject\ModelManager\Model\Model')) {
            throw new ModelException(sprintf("'%s' class does not extend \PommProject\ModelManager\Model.", $class));
        }

        return new $class();
    }
}
