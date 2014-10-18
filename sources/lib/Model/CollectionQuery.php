<?php
/*
 * This file is part of the PommProject's ModelManager package.
 *
 * (c) 2014 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager\Model;

use PommProject\Foundation\Query\ListenerAwareInterface;
use PommProject\Foundation\Query\ListenerTrait;
use PommProject\Foundation\Client\Client;

use PommProject\ModelManager\Model\Projection;

/**
 * CollectionQuery
 *
 * Query manager for CollectionIterator.
 *
 * @package ModelManager.
 * @copyright 2014 Grégoire HUBERT
 * @author Grégoire HUBERT
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 * @see Client
 * @see ListenerAwareInterface
 */
class CollectionQuery extends Client implements ListenerAwareInterface
{
    use ListenerTrait;

    public function getClientType()
    {
        return 'query';
    }

    public function getClientIdentifier()
    {
        return get_class($this);
    }

    /**
     * query
     *
     * Perform a query and return a CollectionIterator.
     *
     * @access public
     * @param  string $sql
     * @param  array $parameters
     * @param  Projection $projection
     * @return CollectionIterator
     */
    public function query($sql, array $parameters = [], Projection $projection)
    {
        $this->sendNotification(
            'pre',
            [
                'sql'        => $sql,
                'parameters' => $parameters,
                'types'      => $projection->getFieldTypes(),
            ]
        );

        $start = microtime(true);
        $result = $this->doQuery($sql, $parameters);
        $end = microtime(true);

        $collection = new CollectionIterator(
            $result,
            $this->getSession(),
            $projection
        );

        $this->sendNotification(
            'post',
            [
                'result_count'      => $collection->count(),
                'time_ms'           => sprintf("%03.1f", ($end - $start) * 1000),
                'flexible_entity'   => $projection->getFlexibleEntityClass(),
            ]
        );

        return $collection;
    }

    /**
     * doQuery
     *
     * How this service performs the query.
     *
     * @access protected
     * @param  string $sql
     * @param  array $parameters
     * @return ResultHandler
     */
    protected function doQuery($sql, array $parameters)
    {
        return $this
            ->GetSession()
            ->getClientUsingPooler('prepared_query', $sql)
            ->execute($parameters)
            ;
    }
}
