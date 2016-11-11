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
 * Projection
 *
 * Define the content of SELECT or RETURNING (projection) statements.
 *
 * @package     ModelManager
 * @copyright   2014 - 2015 Grégoire HUBERT
 * @author      Grégoire HUBERT
 * @license     X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class Projection implements \IteratorAggregate
{
    protected $flexible_entity_class;
    protected $fields = [];
    protected $types = [];


    /**
     * __construct
     *
     * @access public
     * @param  $flexible_entity_class
     * @param  array  $structure list of field names with types.
     */
    public function __construct($flexible_entity_class, array $structure = null)
    {
        $this->flexible_entity_class = $flexible_entity_class;

        if ($structure != null) {
            foreach ($structure as $field_name => $type) {
                $this->setField($field_name, sprintf("%%:%s:%%", $field_name), $type);
            }
        }
    }

    /**
     * getIterator
     *
     * This returns an ArrayIterator using the name => type association of the
     * projection.
     * @see IteratorAggregate
     *
     * @access public
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->types);
    }

    /**
     * getFlexibleEntityClass
     *
     * Get the flexible entity class associated with this projection.
     *
     * @access public
     * @return string
     */
    public function getFlexibleEntityClass()
    {
        return $this->flexible_entity_class;
    }

    /**
     * setField
     *
     * Set a field with a content. This override previous definition if exist.
     *
     * @access public
     * @param  string     $name
     * @param  string     $content
     * @param  string     $type    (null)
     * @throws \InvalidArgumentException if $name or $content is null
     * @return Projection $this
     */
    public function setField($name, $content, $type = null)
    {
        if ($content === null) {
            throw new \InvalidArgumentException(sprintf("Content cannot be null for field '%s'.", $name));
        }

        $this->checkField($name)->fields[$name] = $content;
        $this->types[$name] = $type;

        return $this;
    }

    /**
     * setFieldType
     *
     * Set or override a field type definition.
     *
     * @access public
     * @param  string     $name
     * @param  string     $type
     * @throws ModelException if name is null or does not exist.
     * @return Projection $this
     */
    public function setFieldType($name, $type)
    {
        $this->checkFieldExist($name)->types[$name] = $type;

        return $this;
    }

    /**
     * unsetField
     *
     * Unset an existing field
     *
     * @access public
     * @param  string     $name
     * @throws ModelException if field $name does not exist.
     * @return Projection $this
     */
    public function unsetField($name)
    {
        $this->checkFieldExist($name);
        unset($this->fields[$name], $this->types[$name]);

        return $this;
    }

    /**
     * hasField
     *
     * Return if the given field exist.
     *
     * @access public
     * @param  string  $name
     * @return boolean
     */
    public function hasField($name)
    {
        return isset($this->checkField($name)->fields[$name]);
    }

    /**
     * getFieldType
     *
     * Return the type associated with the given field.
     *
     * @access public
     * @param  string $name
     * @throws ModelException if $name is null or field does not exist
     * @return string null if type is not set
     */
    public function getFieldType($name)
    {
        return $this->checkFieldExist($name)->types[$name] != null
            ? rtrim($this->types[$name], '[]')
            : null;
    }

    /**
     * isArray
     *
     * Tel if a field is an array.
     *
     * @access public
     * @param  string $name
     * @throws ModelException if $name does not exist.
     * @throws \InvalidArgumentException if $name is null
     * @return bool
     */
    public function isArray($name)
    {
        return (bool) preg_match('/\[\]$/', $this->checkFieldExist($name)->types[$name]);
    }

    /**
     * getFieldNames
     *
     * Return fields names list.
     *
     * @access public
     * @return array fields list
     */
    public function getFieldNames()
    {
        return array_keys($this->fields);
    }

    /**
     * getFieldTypes
     *
     * Return an array with the known types.
     *
     * @access public
     * @return array
     */
    public function getFieldTypes()
    {
        $fields = [];
        foreach ($this->fields as $name => $value) {
            $fields[$name] = isset($this->types[$name])
                ? $this->types[$name]
                : null
                ;
        }

        return $fields;
    }

    /**
     * getFieldWithTableAlias
     *
     * Prepend the field name with alias if given.
     *
     * @access public
     * @param  string $name
     * @param  string $table_alias
     * @throws ModelException if $name does not exist.
     * @throws \InvalidArgumentException if $name is null
     * @return string
     */
    public function getFieldWithTableAlias($name, $table_alias = null)
    {
        $replace = $table_alias === null ? '' : sprintf("%s.", $table_alias);

        return $this->replaceToken($this->checkFieldExist($name)->fields[$name], $replace);
    }

    /**
     * getFieldsWithTableAlias
     *
     * Return the array of fields with table aliases expanded.
     *
     * @access public
     * @param  string $table_alias (null)
     * @return array
     */
    public function getFieldsWithTableAlias($table_alias = null)
    {
        $vals = [];
        $replace = $table_alias === null ? '' : sprintf("%s.", $table_alias);

        foreach ($this->fields as $name => $definition) {
            $vals[$name] = $this->replaceToken($this->fields[$name], $replace);
        }

        return $vals;
    }

    /**
     * formatFields
     *
     * Return a formatted string with fields like
     * a.field1, a.field2, ..., a.fieldN
     *
     * @access public
     * @param  string $table_alias
     * @return string
     */
    public function formatFields($table_alias = null)
    {
        return join(', ', $this->getFieldsWithTableAlias($table_alias));
    }

    /**
     * formatFieldsWithFieldAlias
     *
     * Return a formatted string with fields like
     * a.field1 AS field1, a.field2 AS fields2, ...
     *
     * @access public
     * @param  string $table_alias
     * @return string
     */
    public function formatFieldsWithFieldAlias($table_alias = null)
    {
        $fields = $this->getFieldsWithTableAlias($table_alias);

        return join(
            ', ',
            array_map(
                function ($field_alias, $field_definition) {
                    return sprintf(
                        '%s as "%s"',
                        $field_definition,
                        addcslashes($field_alias, '"\\')
                    );
                },
                array_keys($fields),
                $fields
            )
        );
    }

    /**
     * __toString
     *
     * String representation = formatFieldsWithFieldAlias().
     *
     * @access public
     * @return string
     */
    public function __toString()
    {
        return $this->formatFieldsWithFieldAlias();
    }

    /**
     * checkField
     *
     * Check if $name is not null
     *
     * @access private
     * @param  string     $name
     * @throws \InvalidArgumentException if name is null
     * @return Projection $this
     */
    private function checkField($name)
    {
        if ($name === null) {
            throw new \InvalidArgumentException(sprintf("Field name cannot be null."));
        }

        return $this;
    }

    /**
     * checkFieldExist
     *
     * Check if a field exist.
     *
     * @access private
     * @param  string     $name
     * @throws ModelException if field does not exist
     * @return Projection $this
     */
    private function checkFieldExist($name)
    {
        if (!$this->checkField($name)->hasField($name)) {
            throw new ModelException(sprintf("Field '%s' does not exist. Available fields are {%s}.", $name, join(', ', $this->getFieldNames())));
        }

        return $this;
    }

    /**
     * replaceToken
     *
     * Replace placeholders with their quoted names.
     *
     * @access protected
     * @param  string $string field definition
     * @param  string $prefix  optional unquoted prefix
     * @return string
     */
    protected function replaceToken($string, $prefix = '')
    {
        return preg_replace_callback(
            '/%:(\w.*):%/U',
            function (array $matches) use ($prefix) {
                return sprintf('%s"%s"', $prefix, addcslashes($matches[1], '"\\'));
            },
            $string
        );
    }
}
