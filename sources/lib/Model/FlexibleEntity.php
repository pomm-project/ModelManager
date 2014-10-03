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
abstract class FlexibleEntity implements \ArrayAccess, \IteratorAggregate
{
    const NONE     = 0;
    const EXIST    = 1;
    const MODIFIED = 2;

    public static $strict = true;

    private $fields = [];
    private $status = self::NONE;

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
     * @param  string $var Key you want to retrieve value from.
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
        $this->status = $this->status | self::MODIFIED;

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
            $this->status = $this->status | self::MODIFIED;
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
     * Merge internal values with given $values in the object.
     *
     * @access public
     * @param  Array          $values
     * @return FlexibleEntity $this
     */
    final public function hydrate(array $values)
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
     * @access public
     * @return Array
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
     * Return the fields array.
     *
     * @access public
     * @return Array
     */
    public function fields()
    {
        return $this->fields;
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
     * status
     *
     * Return or set the current status of the instance. Status can be
     * self::NONE, self::EXIST and SELF::MODIFIED.
     * If a status is set, it returns itself. If no status are provided, it
     * returns the current status.
     *
     * @access public
     * @param  int (null)
     * @return int|FlexibleEntity
     */
    public function status($status = null)
    {
        if ($status !== null) {
            $this->status = $status;

            return $this;
        }

        return $this->status;
    }

    /**
     * isNew
     *
     * is the current object self::NEW (does not it exist in the database already ?).
     *
     * @access public
     * @return boolean
     */
    public function isNew()
    {
        return (boolean) !($this->status & self::EXIST);
    }

    /**
     * isModified
     *
     * Has the object been modified since we know it ?
     *
     * @access public
     * @return boolean
     */
    public function isModified()
    {
        return (boolean) ($this->status & self::MODIFIED);
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
        $custom_methods = array_filter(get_class_methods(get_class($this)), function ($val) { return preg_match('/^has[A-Z]/', $val); });
        $custom_fields = array();

        foreach ($custom_methods as $method) {
            if (call_user_func(array($this, $method)) === true) {
                preg_match('/^has([A-Z].*)/', $method, $matchs);
                $field = Inflector::underscore($matchs[1]);
                $custom_fields[$field] = $this[$matchs[1]];
            }
        }

        return new \ArrayIterator(array_merge($this->fields, $custom_fields));
    }
}
