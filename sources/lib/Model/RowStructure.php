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

use PommProject\ModelManager\Exception\ModelException;

/**
 * RowStructure
 *
 * Represent a composite structure like table or row.
 *
 * @package   ModelManager
 * @copyright 2014 - 2015 Grégoire HUBERT
 * @author    Grégoire HUBERT <hubert.greg@gmail.com>
 * @license   MIT/X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class RowStructure implements \ArrayAccess
{
    protected $primary_key       = [];
    protected $field_definitions = [];
    protected $relation;

    /**
     * setDefinition
     *
     * Add a complete definition.
     *
     * @access public
     * @param  array        $definition
     * @return RowStructure $this
     */
    public function setDefinition(array $definition)
    {
        $this->field_definitions = $definition;

        return $this;
    }

    /**
     * inherits
     *
     * Add inherited structure.
     *
     * @access public
     * @param  RowStructure $structure
     * @return RowStructure $this
     */
    public function inherits(RowStructure $structure)
    {
        foreach ($structure->getDefinition() as $field => $type) {
            $this->addField($field, $type);
        }

        return $this;
    }

    /**
     * setRelation
     *
     * Set or change the relation.
     *
     * @access public
     * @param  string       $relation
     * @return RowStructure $this
     */
    public function setRelation($relation)
    {
        $this->relation = $relation;

        return $this;
    }

    /**
     * setPrimaryKey
     *
     * Set or change the primary key definition.
     *
     * @access public
     * @param  array        $primary_key
     * @return RowStructure $this
     */
    public function setPrimaryKey(array $primary_key)
    {
        $this->primary_key = $primary_key;

        return $this;
    }

    /**
     * addField
     *
     * Add a new field structure.
     *
     * @access public
     * @param  string       $name
     * @param  string       $type
     * @throws ModelException if type or name is null
     * @return RowStructure $this
     */
    public function addField($name, $type)
    {
        $this->checkNotNull($type, 'type')
            ->checkNotNull($name, 'name')
            ->field_definitions[$name] = $type;

        return $this;
    }

    /**
     * getFieldNames
     *
     * Return an array of all field names
     *
     * @access public
     * @return array
     */
    public function getFieldNames()
    {
        return array_keys($this->field_definitions);
    }

    /**
     * hasField
     *
     * Check if a field exist in the structure
     *
     * @access public
     * @param  string $name
     * @throws ModelException if $name is null
     * @return bool
     */
    public function hasField($name)
    {
        return array_key_exists($name, $this->checkNotNull($name, 'name')->field_definitions);
    }

    /**
     * getTypeFor
     *
     * Return the type associated with the field
     *
     * @access public
     * @param  string $name
     * @throws ModelException if $name is null or name does not exist.
     * @return string $type
     */
    public function getTypeFor($name)
    {
        return $this->checkExist($name)->field_definitions[$name];
    }

    /**
     * getDefinition
     *
     * Return all fields and types
     *
     * @return array
     */
    public function getDefinition()
    {
        return $this->field_definitions;
    }

    /**
     * getRelation
     *
     * Return the relation name.
     *
     * @access public
     * @return string
     */
    public function getRelation()
    {
        return $this->relation;
    }

    /**
     * getPrimaryKey
     *
     * Return the primary key definition.
     *
     * @access public
     * @return array
     */
    public function getPrimaryKey()
    {
        return $this->primary_key;
    }

    /**
     * checkNotNull
     *
     * Test if given value is null.
     *
     * @access              private
     * @param  string       $val
     * @param  string       $name
     * @throws \InvalidArgumentException if $val is null
     * @return RowStructure $this
     */
    private function checkNotNull($val, $name)
    {
        if ($val === null) {
            throw new \InvalidArgumentException(sprintf("'%s' cannot be null in '%s'.", $name, get_class($this)));
        }

        return $this;
    }

    /**
     * checkExist
     *
     * Test if a field exist.
     *
     * @access private
     * @param  string       $name
     * @throws ModelException if $name does not exist.
     * @return RowStructure $this
     */
    private function checkExist($name)
    {
        if (!$this->hasField($name)) {
            throw new ModelException(
                sprintf(
                    "Field '%s' is not defined in structure '%s'. Defined fields are {%s}",
                    $name,
                    get_class($this),
                    join(', ', array_keys($this->field_definitions))
                )
            );
        }

        return $this;
    }

    /**
     * @see \ArrayAccess
     */
    public function offsetSet($name, $type)
    {
        $this->addField($name, $type);
    }

    /**
     * @see \ArrayAccess
     */
    public function offsetGet($name)
    {
        return $this->getTypeFor($name);
    }

    /**
     * @see \ArrayAccess
     */
    public function offsetExists($name)
    {
        return $this->hasField($name);
    }

    /**
     * @see \ArrayAccess
     */
    public function offsetUnset($name)
    {
        throw new ModelException(sprintf("Cannot unset a structure field ('%s').", $name));
    }
}
