<?php
/*
 * This file is part of the PommProject/ModelManager package.
 *
 * (c) 2014 - 2015 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager\Model;

use PommProject\Foundation\Inflector;
use PommProject\ModelManager\Exception\ModelException;
use PommProject\ModelManager\Model\FlexibleEntity\FlexibleContainer;
use PommProject\ModelManager\Model\FlexibleEntity\FlexibleEntityInterface;

/**
 * FlexibleEntity
 *
 * Parent for entity classes.
 *
 * @abstract
 * @package   ModelManager
 * @copyright 2014 - 2015 Grégoire HUBERT
 * @author    Grégoire HUBERT <hubert.greg@gmail.com>
 * @license   MIT/X11 {@link http://opensource.org/licenses/mit-license.php}
 */
abstract class FlexibleEntity extends FlexibleContainer implements \ArrayAccess
{
    public static $strict = true;
    protected static $has_methods;

    /**
     * __construct
     *
     * Instantiate the entity and hydrate it with the given values.
     *
     * @access public
     * @param  array $values Optional starting values.
     */
    public function __construct(array $values = null)
    {
        if ($values !== null) {
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
     * @throws  ModelException if strict and the attribute does not exist.
     * @return mixed
     */
    final public function get($var)
    {
        if (is_scalar($var)) {
            if ($this->has($var)) {
                return $this->container[$var];
            } elseif (static::$strict === true) {
                throw new ModelException(sprintf("No such key '%s'.", $var));
            }
        } elseif (is_array($var)) {
            return array_intersect_key($this->container, array_flip($var));
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
        return isset($this->container[$var]) || array_key_exists($var, $this->container);
    }

    /**
     * set
     *
     * Set a value in the var holder.
     *
     * @final
     * @access public
     * @param  String         $var   Attribute name.
     * @param  Mixed          $value Attribute value.
     * @return FlexibleEntity $this
     */
    final public function set($var, $value)
    {
        $this->container[$var] = $value;
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
     * @throws ModelException
     */
    public function add($var, $value)
    {
        if ($this->has($var)) {
            if (is_array($this->container[$var])) {
                $this->container[$var][] = $value;
            } else {
                throw new ModelException(sprintf("Field '%s' exists and is not an array.", $var));
            }
        } else {
            $this->container[$var] = [$value];
        }

        return $this;
    }

    /**
     * clear
     *
     * Drop an attribute from the var holder.
     *
     * @final
     * @access public
     * @param  String         $offset Attribute name.
     * @return FlexibleEntity $this
     */
    final public function clear($offset)
    {
        if ($this->has($offset)) {
            unset($this->container[$offset]);
            $this->touch();
        }

        return $this;
    }

    /**
     * __call
     *
     * Allows dynamic methods getXxx, setXxx, hasXxx, addXxx or clearXxx.
     *
     * @access  public
     * @throws  ModelException if method does not exist.
     * @param   mixed $method
     * @param   mixed $arguments
     * @return  mixed
     */
    public function __call($method, $arguments)
    {
        list($operation, $attribute) = $this->extractMethodName($method);

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
     * convert
     *
     * Make all keys lowercase and hydrate the object.
     *
     * @access  public
     * @param   Array          $values
     * @return  FlexibleEntity
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
                if (is_array(current($val)) || (is_object(current($val)) && current($val) instanceof FlexibleEntityInterface)) {
                    return array_map($array_recurse, $val);
                } else {
                    return $val;
                }
            }

            if (is_object($val) && $val instanceof FlexibleEntityInterface) {
                return $val->extract();
            }

            return $val;
        };


        return array_map($array_recurse, array_merge($this->container, $this->getCustomFields()));
    }

    /**
     * getCustomFields
     *
     * Return a list of custom methods with has() accessor.
     *
     * @access  private
     * @return  array
     */
    private function getCustomFields()
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

        return $custom_fields;
    }

    /**
     * getIterator
     *
     * @see FlexibleEntityInterface
     */
    public function getIterator()
    {
        return new \ArrayIterator(array_merge($this->container, $this->getCustomFields()));
    }

    /**
     * __set
     *
     * PHP magic to set attributes.
     *
     * @access  public
     * @param   String         $var   Attribute name.
     * @param   Mixed          $value Attribute value.
     * @return  FlexibleEntity $this
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
     * @access  public
     * @param   String $var Attribute name.
     * @return  Mixed  Attribute value.
     */
    public function __get($var)
    {
        $method_name = "get".Inflector::studlyCaps($var);

        return $this->$method_name();
    }

    /**
     * __isset
     *
     * Easy value check.
     *
     * @access  public
     * @param   string $var
     * @return  bool
     */
    public function __isset($var)
    {
        $method_name = "has".Inflector::studlyCaps($var);

        return $this->$method_name;
    }

    /**
     * __unset
     *
     * Clear an attribute.
     *
     * @access  public
     * @param   string $var
     * @return  FlexibleEntity   $this
     */
    public function __unset($var)
    {
        $method_name = "clear".Inflector::studlyCaps($var);

        return $this->$method_name;
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
     * fillHasMethods
     *
     * When getIterator is called the first time, the list of "has" methods is
     * set in a static attribute to boost performances.
     *
     * @access  protected
     * @param   FlexibleEntity   $entity
     * @return  null
     */
    protected static function fillHasMethods(FlexibleEntity $entity)
    {
        static::$has_methods = [];

        foreach (get_class_methods($entity) as $method) {
            if (preg_match('/^has([A-Z].*)$/', $method, $matches)) {
                static::$has_methods[] = $matches[1];
            }
        }
    }
}
