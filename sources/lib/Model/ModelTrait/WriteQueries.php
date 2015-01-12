<?php
/*
 * This file is part of the PommProject/ModelManager package.
 *
 * (c) 2014 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager\Model\ModelTrait;

use PommProject\ModelManager\Model\FlexibleEntity\FlexibleEntityInterface;
use PommProject\ModelManager\Model\Model;

use PommProject\Foundation\RawString;
use PommProject\Foundation\Where;

/**
 * WriteQueries
 *
 * Basic write queries for model instances.
 *
 * @package ModelManager
 * @copyright 2014 Grégoire HUBERT
 * @author Grégoire HUBERT
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
trait WriteQueries
{
    use ReadQueries;

    /**
     * insertOne
     *
     * Insert a new entity in the database. The entity is passed by reference.
     * It is updated with values returned by the database (ie, default values).
     *
     * @access public
     * @param  FlexibleEntityInterface  $entity
     * @return Model                    $this
     */
    public function insertOne(FlexibleEntityInterface &$entity)
    {
        $values = [];

        foreach ($this->getStructure()->getDefinition() as $name => $type) {
            if ($entity->has($name)) {
                $values[$name] = $entity->get($name) instanceOf RawString
                    ? $entity->get($name)->__toString()
                    : $this->convertValueToPg($entity->get($name), $type)
                    ;
            }
        }

        $sql = strtr(
            "insert into :relation (:fields) values (:values) returning :projection",
            [
                ':relation'   => $this->getStructure()->getRelation(),
                ':fields'     => $this->getEscapedFieldList(array_keys($values)),
                ':projection' => $this->createProjection()->formatFieldsWithFieldAlias(),
                ':values'     => join(', ', $values),
            ]);

        $entity = $this
            ->query($sql)
            ->current()
            ->status(FlexibleEntityInterface::STATUS_EXIST);

        return $this;
    }

    /**
     * updateOne
     *
     * Update the entity. ONLY the fields indicated in the $fields array are
     * updated. The entity is passed by reference and its values are updated
     * with the values from the database. This means all changes not updated
     * are lost. The update is made upon a condition on the primary key. If the
     * primary key is not fully set, an exception is thrown.
     *
     * @access public
     * @param  FlexibleEntityInterface  $entity
     * @param  array                    $fields
     * @return Model                    $this
     */
    public function updateOne(FlexibleEntityInterface &$entity, array $fields)
    {
        $entity = $this->updateByPk(
            $entity->get($this->getStructure()->getPrimaryKey()),
            $entity->get($fields)
        );

        return $this;
    }

    /**
     * updateByPk
     *
     * Update a record and fetch it with its new values. If no records match
     * the given key, null is returned.
     *
     * @access public
     * @param  array          $primary_key
     * @param  array          $updates
     * @return FlexibleEntityInterface
     */
    public function updateByPk(array $primary_key, array $updates)
    {
        $where = $this->getWhereFrom($primary_key);
        $update_strings = [];

        foreach ($updates as $field_name => $new_value) {
            $update_strings[] = sprintf(
                "%s = %s",
                $this->escapeIdentifier($field_name),
                $new_value instanceOf RawString
                ? $new_value->__toString()
                : $this->convertValueToPg($new_value, $this->getStructure()->getTypeFor($field_name))
            );
        }

        $sql = strtr(
            "update :relation set :update where :condition returning :projection",
            [
                ':relation'   => $this->getStructure()->getRelation(),
                ':update'     => join(', ', $update_strings),
                ':condition'  => (string) $where,
                ':projection' => $this->createProjection()->formatFieldsWithFieldAlias(),
            ]
        );

        $iterator = $this->query($sql, $where->getValues());

        if ($iterator->isEmpty()) {
            return null;
        }

        return $iterator->current()->status(FlexibleEntityInterface::STATUS_EXIST);
    }

    /**
     * deleteOne
     *
     * Delete an entity from a table. Entity is passed by reference and is
     * updated with the values fetched from the deleted record.
     *
     * @access public
     * @param  FlexibleEntityInterface  $entity
     * @return Model                    $this
     */
    public function deleteOne(FlexibleEntityInterface &$entity)
    {
        $entity = $this->deleteByPK($entity->get($this->getStructure()->getPrimaryKey()));

        return $this;
    }

    /**
     * deleteByPK
     *
     * Delete a record from its primary key. The deleted entity is returned or
     * null if not found.
     *
     * @access public
     * @param  array          $primary_key
     * @return FlexibleEntityInterface
     */
    public function deleteByPK(array $primary_key)
    {
        $where = $this->getWhereFrom($primary_key);
        $sql = strtr(
            "delete from :relation where :condition returning :projection",
            [
                ':relation'   => $this->getStructure()->getRelation(),
                ':condition'  => (string) $where,
                ':projection' => $this->createProjection()->formatFieldsWithFieldAlias(),
            ]
        );

        $entity = $this->query($sql, $where->getValues())->current();

        if ($entity !== null) {
            $entity->status(FlexibleEntityInterface::STATUS_NONE);
        }

        return $entity;
    }

    /**
     * createAndSave
     *
     * Create a new entity from given values and save it in the database.
     *
     * @access public
     * @param  array          $values
     * @return FlexibleEntityInterface
     */
    public function createAndSave(array $values)
    {
        $entity = $this->createEntity($values);
        $this->insertOne($entity);

        return $entity;
    }

    /**
     * convertValueToPg
     *
     * Return the converted value given the type. It detects if the type is an
     * array or not and call the converter pooler.
     *
     * @access protected
     * @param  mixed  $value
     * @param  string $type
     * @return string
     */
    protected function convertValueToPg($value, $type)
    {
        if (preg_match('/^(.+)\[\]$/', $type, $matchs)) {
            return $this
                    ->getSession()
                    ->getClientUsingPooler('converter', 'array')
                    ->toPg($value, $matchs[1])
                    ;
        } else {
            return $this
                    ->getSession()
                    ->getClientUsingPooler('converter', $type)
                    ->toPg($value)
                    ;
        }
    }

    /**
     * getEscapedFieldList
     *
     * Return a comma separated list with the given escaped field names.
     *
     * @access protected
     * @param  array  $fields
     * @return string
     */
    public function getEscapedFieldList(array $fields)
    {
        return join(
            ', ',
            array_map(
                function ($field) { return $this->escapeIdentifier($field); },
                $fields
            ));
    }
}
