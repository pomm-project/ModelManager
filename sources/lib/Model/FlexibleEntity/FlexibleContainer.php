<?php
/*
 * This file is part of the PommProject/ModelManager package.
 *
 * (c) 2014 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager\Model\FlexibleEntity;

use PommProject\ModelManager\Model\FlexibleEntity\StatefullEntityTrait;
use PommProject\ModelManager\Model\FlexibleEntity\FlexibleEntityInterface;
use PommProject\ModelManager\Exception\ModelException;
use PommProject\Foundation\Inflector;

/**
 * FlexibleContainerTrait
 *
 * Trait for being a flexible data container.
 *
 * @package ModelManager
 * @copyright 2014-2015 Grégoire HUBERT
 * @author Grégoire HUBERT
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
abstract class FlexibleContainer implements FlexibleEntityInterface, \IteratorAggregate
{
    use StatefullEntityTrait;

    protected $container = [];

    /**
     * hydrate
     *
     * @see FlexibleEntityInterface
     */
    public function hydrate(array $values)
    {
        $this->container = array_merge($this->container, $values);

        return $this;
    }

    /**
     * fields
     *
     * Return the fields array. If a given field does not exist, an exception
     * is thrown.
     *
     * @throw   InvalidArgumentException
     * @see     FlexibleEntityInterface
     */
    public function fields(array $fields = null)
    {
        if ($fields === null) {
            return $this->container;
        }

        $output = [];

        foreach ($fields as $name) {
            if (isset($this->container[$name])) {
                $output[$name] = $this->container[$name];
            } else {
                throw new \InvalidArgumentException(
                    sprintf(
                        "No such field '%s'. Existing fields are {%s}",
                        $name,
                        join(', ', array_keys($this->container))
                    )
                );
            }
        }

        return $output;
    }


    /**
     * extract
     *
     * @see FlexibleEntityInterface
     */
    public function extract()
    {
        return $this->fields();
    }

    /**
     * getIterator
     *
     * @see FlexibleEntityInterface
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->extract());
    }

    /**
     * __call
     *
     * Allows dynamic methods getXxx, setXxx, hasXxx or clearXxx.
     *
     * @access public
     * @throw  ModelException if method does not exist.
     * @param  mixed $method
     * @param  mixed $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        $split = preg_split('/(?=[A-Z])/', $method, 2);

        if (count($split) != 2) {
            throw new ModelException(sprintf('No such method "%s:%s()"', get_class($this), $method));
        }

        $operation = $split[0];
        $attribute = Inflector::underscore($split[1]);

        switch ($operation) {
        case 'set':
            return $this->container[$attribute] = $arguments[0];
        case 'get':
            return $this->container([$attribute]);
        case 'has':
            isset($this->container[$attribute]);
            return $this;
        case 'clear':
            if (!isset($this->container[$attribute])) {
                throw new ModelException(
                    sprintf(
                        "Could not unset unexisting attribute '%s'.",
                        $attribute
                    )
                );
            }

            unset($this->container[$attribute]);

            return $this;
        default:
            throw new ModelException(sprintf('No such method "%s:%s()"', get_class($this), $method));
        }
    }
}
