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

use PommProject\ModelManager\Model\BaseModelTrait;
use PommProject\ModelManager\Model\Model;
use PommProject\Foundation\Where;

/**
 * ReadModelTrait
 *
 * Basic read queries for model instances.
 *
 * @package ModelManager
 * @copyright 2014 Grégoire HUBERT
 * @author Grégoire HUBERT
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
trait ReadModelTrait
{
    use BaseModelTrait;

    /**
     * findAll
     *
     * Return all elements from a relation. If a suffix is given, it is append
     * to the query. This is mainly useful for "order by" statements.
     * NOTE: suffix is inserted as is with NO ESCAPING. DO NOT use it to place
     * "where" condition nor any untrusted params.
     *
     * @access public
     * @param  string     $suffix
     * @return CollectionIterator
     */
    public function findAll($suffix = null)
    {
        $sql = "select :fields from :table :suffix";
        $sql = strtr($sql, [
            ':fields' => $this->createProjection()->formatFields(),
            ':table'  => $this->getRelation(),
            ':suffix' => $suffix
        ]);

        return $this->query($sql);
    }

    /**
     * findWhere
     *
     * Perform a simple select on a given condition
     * NOTE: suffix is inserted as is with NO ESCAPING. DO NOT use it to place
     * "where" condition nor any untrusted params.
     *
     * @access public
     * @param  mixed      $where
     * @param  array      $values
     * @param  string     $suffix order by, limit, etc.
     * @return CollectionIterator
     */
    public function findWhere($where, array $values = [], $suffix = '')
    {
        if ($where instanceof Where) {
            $values = $where->getValues();
        }

        $sql = sprintf("select :fields from :table where %s %s", (string) $where, $suffix);
        $sql = strtr($sql, [
            ':fields' => $this->createProjection()->formatFieldsWithFieldAlias(),
            ':table'  => $this->getRelation()
            ]);

        return $this->query($sql, $values);
    }

    /**
     * countWhere
     *
     * Return the number of records matching a condition.
     *
     * @access public
     * @param  string|Where $where
     * @param  array $values
     * @return int
     */
    public function countWhere($where, array $values = [])
    {
        if ($where instanceof Where) {
            $values = $where->getValues();
        }

        $sql = sprintf("select count(*) as count from :table where %s", (string) $where);
        $sql = strtr($sql, [
            ':table' => $this->getRelation(),
            ]);

        return (int) $this
            ->session
            ->getClientUsingPooler('prepared_statement', $sql)
            ->execute($values)
            ->fetchColumn('count')[0];
    }
}
