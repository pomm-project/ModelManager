<?php
/*
 * This file is part of the PommProject/ModelManager package.
 *
 * (c) 2014 - 2015 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager\Model\ModelTrait;

use PommProject\Foundation\Where;
use PommProject\ModelManager\Exception\ModelException;
use PommProject\ModelManager\Model\CollectionIterator;
use PommProject\ModelManager\Model\FlexibleEntity\FlexibleEntityInterface;
use PommProject\ModelManager\Model\Model;

/**
 * WriteQueries
 *
 * Basic write queries for model instances.
 *
 * @package   ModelManager
 * @copyright 2014 - 2015 Grégoire HUBERT
 * @author    Grégoire HUBERT
 * @license   X11 {@link http://opensource.org/licenses/mit-license.php}
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
        $values = $entity->fields(
            array_intersect(
                array_keys($this->getStructure()->getDefinition()),
                array_keys($entity->extract())
            )
        );
        $sql = strtr(
            "insert into :relation (:fields) values (:values) returning :projection",
            [
                ':relation'   => $this->getStructure()->getRelation(),
                ':fields'     => $this->getEscapedFieldList(array_keys($values)),
                ':projection' => $this->createProjection()->formatFieldsWithFieldAlias(),
                ':values'     => join(',', $this->getParametersList($values))
            ]);

        $entity = $this
            ->query($sql, array_values($values))
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
    public function updateOne(FlexibleEntityInterface &$entity, array $fields = [])
    {
        if (empty($fields)) {
            $fields = $entity->getModifiedColumns();
        }

        $entity = $this->updateByPk(
            $entity->fields($this->getStructure()->getPrimaryKey()),
            $entity->fields($fields)
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
     * @throws ModelException
     * @return FlexibleEntityInterface
     */
    public function updateByPk(array $primary_key, array $updates)
    {
        $where = $this
            ->checkPrimaryKey($primary_key)
            ->getWhereFrom($primary_key)
            ;
        $parameters = $this->getParametersList($updates);
        $update_strings = [];

        foreach ($updates as $field_name => $new_value) {
            $update_strings[] = sprintf(
                "%s = %s",
                $this->escapeIdentifier($field_name),
                $parameters[$field_name]
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

        $iterator = $this->query($sql, array_merge(array_values($updates), $where->getValues()));

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
        $entity = $this->deleteByPK($entity->fields($this->getStructure()->getPrimaryKey()));

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
     * @throws ModelException
     * @return FlexibleEntityInterface
     */
    public function deleteByPK(array $primary_key)
    {
        $where = $this
            ->checkPrimaryKey($primary_key)
            ->getWhereFrom($primary_key)
        ;

        return $this->deleteWhere($where)->current();
    }

    /**
     * deleteWhere
     *
     * Delete records by a given condition. A collection of all deleted entries is returned.
     *
     * @param        $where
     * @param  array $values
     * @return CollectionIterator
     */
    public function deleteWhere($where, array $values = [])
    {
        if (!$where instanceof Where) {
            $where = new Where($where, $values);
        }

        $sql = strtr(
            "delete from :relation where :condition returning :projection",
            [
                ':relation'   => $this->getStructure()->getRelation(),
                ':condition'  => (string) $where,
                ':projection' => $this->createProjection()->formatFieldsWithFieldAlias(),
            ]
        );

        $collection = $this->query($sql, $where->getValues());
        foreach ($collection as $entity) {
            $entity->status(FlexibleEntityInterface::STATUS_NONE);
        }
        $collection->rewind();

        return $collection;
    }

    /**
     * truncate
     *
     * Delete all records.
     *
     * @param  bool $cascade
     * @param  bool $restart
     * @return Model $this
     */
    public function truncate($cascade = false, $restart = false)
    {
        $type_truncate = 'RESTRICT';
        $identity = 'CONTINUE';

        if ($cascade) {
            $type_truncate = 'CASCADE';
        }

        if ($restart) {
            $identity = 'RESTART';
        }

        $sql = strtr(
            "truncate :relation :identity :type_truncate",
            [
                ':relation'   => $this->getStructure()->getRelation(),
                ':type_truncate'  => $type_truncate,
                ':identity' => $identity . ' IDENTITY '
            ]
        );

        $this->query($sql, []);

        return $this;
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

    /**
     * getParametersList
     *
     * Create a parameters list from values.
     *
     * @access protected
     * @param  array    $values
     * @return array    $escape codes
     */
    protected function getParametersList(array $values)
    {
        $parameters = [];

        foreach ($values as $name => $value) {
            $parameters[$name] = sprintf(
                "$*::%s",
                $this->getStructure()->getTypeFor($name)
            );
        }

        return $parameters;
    }
}
