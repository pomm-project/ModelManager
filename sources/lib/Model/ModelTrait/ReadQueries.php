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

use PommProject\ModelManager\Exception\ModelException;
use PommProject\ModelManager\Model\Projection;
use PommProject\ModelManager\Model\Model;

use PommProject\Foundation\Pager;
use PommProject\Foundation\Where;

/**
 * ReadQueries
 *
 * Basic read queries for model instances.
 *
 * @package ModelManager
 * @copyright 2014 Grégoire HUBERT
 * @author Grégoire HUBERT
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
trait ReadQueries
{
    use BaseTrait;

    /**
     * findAll
     *
     * Return all elements from a relation. If a suffix is given, it is append
     * to the query. This is mainly useful for "order by" statements.
     * NOTE: suffix is inserted as is with NO ESCAPING. DO NOT use it to place
     * "where" condition nor any untrusted params.
     *
     * @access public
     * @param  string             $suffix
     * @return CollectionIterator
     */
    public function findAll($suffix = null)
    {
        $sql = strtr(
            "select :fields from :table :suffix",
            [
                ':fields' => $this->createProjection()->formatFieldsWithFieldAlias(),
                ':table'  => $this->getStructure()->getRelation(),
                ':suffix' => $suffix,
            ]
        );

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
     * @param  mixed              $where
     * @param  array              $values
     * @param  string             $suffix order by, limit, etc.
     * @return CollectionIterator
     */
    public function findWhere($where, array $values = [], $suffix = '')
    {
        if (!$where instanceof Where) {
            $where = new Where($where, $values);
        }

        return $this->query($this->getFindWhereSql($where, $this->createProjection(), $suffix), $where->getValues());
    }

    /**
     * findByPK
     *
     * Return an entity upon its primary key. If no entities are found, null is
     * returned.
     *
     * @access public
     * @param  array          $primary_key
     * @return FlexibleEntityInterface
     */
    public function findByPK(array $primary_key)
    {
        $where = $this
            ->checkPrimaryKey($primary_key)
            ->getWhereFrom($primary_key)
            ;

        $iterator = $this->findWhere($where);

        return $iterator->isEmpty() ? null : $iterator->current();
    }

    /**
     * countWhere
     *
     * Return the number of records matching a condition.
     *
     * @access public
     * @param  string|Where $where
     * @param  array        $values
     * @return int
     */
    public function countWhere($where, array $values = [])
    {
        $sql = sprintf(
            "select count(*) as result from %s where :condition",
            $this->getStructure()->getRelation()
        );

        return $this->fetchSingleValue($sql, $where, $values);
    }

    /**
     * existWhere
     *
     * Check if rows matching the given condition do exist or not.
     *
     * @access public
     * @param  mixed $where
     * @param  array $values
     * @return bool
     */
    public function existWhere($where, array $values = [])
    {
        $sql = sprintf(
            "select exists (select true from %s where :condition) as result",
            $this->getStructure()->getRelation()
        );

        return $this->fetchSingleValue($sql, $where, $values);
    }

    /**
     * fetchSingleValue
     *
     * Fetch a single value named « result » from a query.
     * The query must be formated with ":condition" as WHERE condition
     * placeholder. If the $where argument is a string, it is turned into a
     * Where instance.
     *
     * @access protected
     * @param  string       $sql
     * @param  mixed        $where
     * @param  array        $values
     * @return mixed
     */
    protected function fetchSingleValue($sql, $where, array $values)
    {
        if (!$where instanceof Where) {
            $where = new Where($where, $values);
        }

        $sql = str_replace(":condition", (string) $where, $sql);

        return $this
            ->getSession()
            ->getClientUsingPooler('query_manager', '\PommProject\Foundation\PreparedQuery\PreparedQueryManager')
            ->query($sql, $where->getValues())
            ->current()['result']
            ;
    }

    /**
     * paginateFindWhere
     *
     * Paginate a query.
     *
     * @access public
     * @param  Where    $where
     * @param  int      $item_per_page
     * @param  int      $page
     * @param  string   $suffix
     * @return Pager
     */
    public function paginateFindWhere(Where $where, $item_per_page, $page = 1, $suffix = '')
    {
        $projection = $this->createProjection();

        return $this->paginateQuery(
            $this->getFindWhereSql($where, $projection, $suffix),
            $where->getValues(),
            $this->countWhere($where),
            $item_per_page,
            $page,
            $projection
        );
    }

    /**
     * paginateQuery
     *
     * Paginate a SQL query.
     * It is important to note it adds limit and offset at the end of the given
     * query.
     *
     * @access protected
     * @param  string       $sql
     * @param  array        $values parameters
     * @param  int          $count
     * @param  int          $item_per_page
     * @param  int          $page
     * @param  Projection   $projection
     * @throw  \InvalidArgumentException if pager args are invalid.
     * @return Pager
     */
    protected function paginateQuery($sql, array $values, $count, $item_per_page, $page = 1, Projection $projection = null)
    {
        if ($page < 1) {
            throw new \InvalidArgumentException(
                sprintf("Page cannot be < 1. (%d given)", $page)
            );
        }

        if ($item_per_page <= 0) {
            throw new \InvalidArgumentException(
                sprintf("'item_per_page' must be strictly positive (%d given).", $item_per_page)
            );
        }

        $offset = $item_per_page * ($page - 1);
        $limit  = $item_per_page;

        return new Pager(
            $this->query(
                sprintf("%s offset %d limit %d", $sql, $offset, $limit),
                $values,
                $projection
            ),
            $count,
            $item_per_page,
            $page
        );
    }

    /**
     * getFindWhereSql
     *
     * This is the standard SQL query to fetch instances from the current
     * relation.
     *
     * @access protected
     * @param  Where        $where
     * @param  Projection   $projection
     * @param  string       $suffix
     * @return string
     */
    protected function getFindWhereSql(Where $where, Projection $projection, $suffix = '')
    {
        return strtr(
            'select :projection from :relation where :condition :suffix',
            [
                ':projection' => $projection->formatFieldsWithFieldAlias(),
                ':relation'   => $this->getStructure()->getRelation(),
                ':condition'  => $where->__toString(),
                ':suffix'     => $suffix,
            ]
        );
    }

    /**
     * checkPrimaryKey
     *
     * Check if the given values fully describe a primary key. Throw a
     * ModelException if not.
     *
     * @access private
     * @param  array $values
     * @return Model $this
     */
    private function checkPrimaryKey(array $values)
    {
        foreach ($this->getStructure()->getPrimaryKey() as $key) {
            if (!isset($values[$key])) {
                throw new ModelException(
                    sprintf(
                        "Key '%s' is missing to fully describes the primary key {%s}.",
                        $key,
                        join(', ', $this->getStructure()->getPrimaryKey())
                    )
                );
            }
        }

        return $this;
    }

    /**
     * getWhereFrom
     *
     * Build a condition on given values.
     *
     * @access protected
     * @param  array $values
     * @return Where
     */
    protected function getWhereFrom(array $values)
    {
        $where = new Where();

        foreach ($values as $field => $value) {
            $where->andWhere(sprintf("%s = $*", $this->escapeIdentifier($field)), [$value]);
        }

        return $where;
    }
}
