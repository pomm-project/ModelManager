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

use PommProject\ModelManager\Model\FlexibleEntity\FlexibleEntityInterface;
use PommProject\ModelManager\Model\FlexibleEntity\StatefullEntityTrait;
use PommProject\ModelManager\Exception\ModelException;
use PommProject\Foundation\Inflector;

/**
 * FlexibleEntity
 *
 * Parent for entity classes.
 *
 * @abstract
 * @package ModelManager
 * @copyright 2014 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license MIT/X11 {@link http://opensource.org/licenses/mit-license.php}
 */
abstract class FlexibleEntity implements
    \ArrayAccess,
    \IteratorAggregate,
    FlexibleEntityInterface {

    use StatefullEntityTrait;

    public static $strict = true;

    protected static  $has_methods;
    private $fields = [];

    /**
     * __construct
     *
     * Instantiate the entity and hydrate it with the given values.
     *
     * @access public
     * @param  array $values Optional starting values.
     * @return void
     */
    public function __construct(array $values = null)
    {
        if (!is_null($values)) {
            $this->hydrate($values);
        }
    }

    /**
     * get
     *
     * Returns the $var value
     *
     * @final
     * @access public
     * @param  string|array $var Key(s) you want to retrieve value from.
     * @throw  ModelException if strict and the attribute does not exist.
     * @return mixed
     */
    final public function get($var)
    {
        if (is_scalar($var)) {
            if ($this->has($var)) {
                return $this->fields[$var];
            } elseif (static::$strict === true) {
                throw new ModelException(sprintf("No such key '%s'.", $var));
            }
        } elseif (is_array($var)) {
            return array_intersect_key($this->fields, array_flip($var));
        }
    }

    /**
     * has
     *
     * Returns true if the given key exists.
     *
     * @final
     * @access public
     * @param  string  $var
     * @return boolean
     */
    final public function has($var)
    {
        return array_key_exists($var, $this->fields);
    }

    /**
     * set
     *
     * Set a value in the varholder.
     *
     * @final
     * @access public
     * @param  String         $var   Attribute name.
     * @param  Mixed          $value Attribute value.
     * @return FlexibleEntity $this
     */
    final public function set($var, $value)
    {
        $this->fields[$var] = $value;
        $this->touch();

        return $this;
    }

    /**
     * add
     *
     * When the corresponding attribute is an array, call this method
     * to set values.
     *
     * @access public
     * @param  string         $var
     * @param  mixed          $value
     * @return FlexibleEntity $this
     */
    public function add($var, $value)
    {
        if ($this->has($var)) {
           if (is_array($this->fields[$var])) {
               $this->fields[$var][] = $value;
           } else {
               throw new ModelException(sprintf("Field '%s' exists and is not an array.", $var));
           }
        } else {
            $this->fields[$var] = array($value);
        }

        return $this;
    }

    /**
     * clear
     *
     * Drop an attribute from the varholder.
     *
     * @final
     * @access public
     * @param  String         $offset Attribute name.
     * @return FlexibleEntity $this
     */
    final public function clear($offset)
    {
        if ($this->has($offset)) {
            unset($this->fields[$offset]);
            $this->touch();
        }

        return $this;
    }

    /**
     * __call
     *
     * Allows dynamic methods getXxx, setXxx, hasXxx, addXxx or clearXxx.
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
            return $this->set($attribute, $arguments[0]);
        case 'get':
            return $this->get($attribute);
        case 'add':
            return $this->add($attribute, $arguments[0]);
        case 'has':
            return $this->has($attribute);
        case 'clear':
            return $this->clear($attribute);
        default:
            throw new ModelException(sprintf('No such method "%s:%s()"', get_class($this), $method));
        }
    }

    /**
     * hydrate
     *
     * @see FlexibleEntityInterface
     */
    public function hydrate(array $values)
    {
        $this->fields = array_merge($this->fields, $values);

        return $this;
    }

    /**
     * convert
     *
     * Make all keys lowercase and hydrate the object.
     *
     * @access public
     * @param  Array          $values
     * @return FlexibleEntity
     */
    public function convert(array $values)
    {
        $tmp = [];

        foreach ($values as $key => $value) {
            $tmp[strtolower($key)] = $value;
        }

        return $this->hydrate($tmp);
    }

    /**
     * extract
     *
     * Returns the fields flatten as arrays.
     *
     * The complex stuff in here is when there is an array, since all elements
     * in arrays are the same type, we check only its first value to know if we need
     * to traverse it or not.
     *
     * @see FlexibleEntityInterface
     */
    public function extract()
    {
        $array_recurse = function ($val) use (&$array_recurse) {
            if (is_scalar($val)) {
                return $val;
            }

            if (is_array($val)) {
                if (is_array(current($val)) || (is_object(current($val)) && current($val) instanceof FlexibleEntity)) {
                    return array_map($array_recurse, $val);
                } else {
                    return $val;
                }
            }

            if (is_object($val) && $val instanceof FlexibleEntity) {
                return $val->extract();
            }

            return $val;
        };

        return array_map($array_recurse, $this->getIterator()->getArrayCopy());
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
            return $this->fields;
        }

        $output = [];

        foreach ($fields as $name) {
            if (isset($this->fields[$name])) {
                $output[$name] = $this->fields[$name];
            } else {
                throw new \InvalidArgumentException(
                    sprintf(
                        "No such field '%s'. Existing fields are {%s}",
                        $name,
                        join(', ', array_keys($this->fields))
                    )
                );
            }
        }

        return $output;
    }

    /**
     * __set
     *
     * PHP magic to set attributes.
     *
     * @access public
     * @param  String         $var   Attribute name.
     * @param  Mixed          $value Attribute value.
     * @return FlexibleEntity $this
     */
    public function __set($var, $value)
    {
        $method_name = "set".Inflector::studlyCaps($var);
        $this->$method_name($value);

        return $this;
    }

    /**
     * __get
     *
     * PHP magic to get attributes.
     *
     * @access public
     * @param  String $var Attribute name.
     * @return Mixed  Attribute value.
     */
    public function __get($var)
    {
        $method_name = "get".Inflector::studlyCaps($var);

        return $this->$method_name();
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        $method_name = "has".Inflector::studlyCaps($offset);

        return $this->$method_name();
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->__set($offset, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $this->clear($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        if (static::$has_methods === null) {
            static::fillHasMethods($this);
        }

        $custom_fields = [];

        foreach (static::$has_methods as $method) {
            if (call_user_func([$this, sprintf("has%s", $method)]) === true) {
                $custom_fields[Inflector::underscore(lcfirst($method))] = call_user_func([$this, sprintf("get%s", $method)]);
            }
        }

        return new \ArrayIterator(array_merge($this->fields, $custom_fields));
    }


    /**
     * fillHasMethods
     *
     * When getIterator is called the first time, the list of "has" methods is
     * set in a static attribute to boost performances.
     *
     * @access protected
     * @param  FlexibleEntity   $entity
     * @return null
     */
    protected static function fillHasMethods(FlexibleEntity $entity)
    {
        static::$has_methods = [];

        foreach (get_class_methods($entity) as $method) {
            if (preg_match('/^has([A-Z].*)$/', $method, $matchs)) {
                static::$has_methods[] = $matchs[1];
            }
        }
    }
}
