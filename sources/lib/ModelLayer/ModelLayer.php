<?php
/*
 * This file is part of the PommProject/ModelManager package.
 *
 * (c) 2014 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager\ModelLayer;

use PommProject\Foundation\Connection;
use PommProject\Foundation\Client\Client;
use PommProject\Foundation\Client\ClientInterface;

/**
 * ModelLayer
 *
 * ModelLayer handles mechanisms around model method calls (transactions,
 * events etc.).
 *
 * @package ModelManager
 * @copyright 2014 Grégoire HUBERT
 * @author Grégoire HUBERT
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 * @see Client
 */
abstract class ModelLayer extends Client
{
    /**
     * getClientType
     *
     * @see ClientInterface
     */
    public function getClientType()
    {
        return 'model_layer';
    }

    /**
     * getClientIdentifier
     *
     * @see ClientInterface
     */
    public function getClientIdentifier()
    {
        return get_class($this);
    }

    /**
     * shutdown
     *
     * @see ClientInterface
     */
    public function shutdown()
    {
    }

    /**
     * startTransaction
     *
     * Start a new transaction.
     *
     * @access protected
     * @return ModelLayer $this
     */
    protected function startTransaction()
    {
        $this->executeAnonymousQuery('begin transaction');

        return $this;
    }

    /**
     * setDeferrable
     *
     * Set given constraints to deferred/immediate in the current transaction.
     * This applies to constraints being deferrable or deferred by default.
     * If the keys is an empty arrays, ALL keys will be set at the given state.
     * @see http://www.postgresql.org/docs/9.0/static/sql-set-constraints.html
     *
     * @access protected
     * @param  array      $keys
     * @param  string     $state
     * @throw  ModelLayerException if not valid state
     * @return ModelLayer $this
     */
    protected function setDeferrable(array $keys = [], $state)
    {
        if (count($keys) === 0) {
            $string = 'ALL';
        } else {
            $string = join(
                ', ',
                array_map(function($key) { $this->escapeIdentifier($key); }, $keys)
            );
        }

        if (!in_array($state, [ Connection::CONSTRAINTS_DEFERRED, Connection::CONSTRAINTS_IMMEDIATE ])) {
            throw new ModelLayerException(sprintf("'%s' is not a valid constraints modifier.", $state));
        }

        $this->executeAnonymousQuery(
            sprintf(
                "set constraints %s %s",
                $string,
                $state
            )
        );

        return $this;
    }

    /**
     * setTransactionIsolationLevel
     *
     * Transaction isolation level tells postgresql how to manage with the
     * current transaction. The default is "READ COMMITED".
     * @see http://www.postgresql.org/docs/9.0/static/sql-set-transaction.html
     *
     * @access protected
     * @param  string $state
     * @throw  ModelLayerException if not valid isolation level
     * @return ModelLayer $this
     */
    protected function setTransactionIsolationLevel($isolaton_level)
    {
        if (!in_array(
            $isolaton_level,
            [Connection::ISOLATION_READ_COMMITTED, Connection::ISOLATION_READ_REPEATABLE, Connection::ISOLATION_SERIALIZABLE]
        )) {
            throw new ModelLayerException(sprintf("'%s' is not a valid transaction isolation level."));
        }

        return $this->sendParameter(
            "set transaction isolation level %s",
            '',
            $isolaton_level
        );
    }

    /**
     * setTransactionAccessMode
     *
     * Transaction access modes tell Postgresql if transaction are able to
     * write or read only.
     * @see http://www.postgresql.org/docs/9.0/static/sql-set-transaction.html
     *
     * @access protected
     * @param  string $access_mode
     * @throw  ModelLayerException if not valid access mode
     * @return ModelLayer $this
     */
    protected function setTransactionAccessMode($access_mode)
    {
        if (!in_array(
            $access_mode,
            [Connection::ACCESS_MODE_READ_ONLY, Connection::ACCESS_MODE_READ_WRITE]
        )) {
            throw new ModelLayerException(sprintf("'%s' is not a valid transaction access mode.", $access_mode));
        }

        return $this->sendParameter(
            "set transaction %s",
            '',
            $access_mode
        );
    }

    /**
     * setSavePoint
     *
     * Set a savepoint in a transaction.
     *
     * @access protected
     * @param  string     $name
     * @return ModelLayer $this
     */
    protected function setSavepoint($name)
    {
        return $this->sendParameter(
            "savepoint %s",
            $name
        );
    }

    /**
     * releaseSavepoint
     *
     * Drop a savepoint.
     *
     * @access protected
     * @param  string     $name
     * @return ModelLayer $this
     */
    protected function releaseSavepoint($name)
    {
        return $this->sendParameter(
            "release savepoint %s",
            $name
        );
    }

    /**
     * rollbackTransaction
     *
     * Rollback a transaction. If a name is specified, the transaction is
     * rollback to the given savepoint. Otherwise, the whole transaction is
     * rollback.
     *
     * @access protected
     * @param  string|null $name
     * @return ModelLayer  $this
     */
    protected function rollbackTransaction($name = null)
    {
        if ($name !== null) {
            $sql = sprintf("rollback to savepoint %s", $this->escapeIdentifier($name));
        } else {
            $sql = "rollback transaction";
        }

        $this->executeAnonymousQuery($sql);

        return $this;
    }

    /**
     * commitTransaction
     *
     * Commit a transaction.
     *
     * @access protected
     * @return ModelLayer $this
     */
    protected function commitTransaction()
    {
        $this->executeAnonymousQuery('commit transaction');

        return $this;
    }

    /**
     * isInTransaction
     *
     * Tell if a transaction is open or not.
     *
     * @see    Cient
     * @access protected
     * @return bool
     */
    protected function isInTransaction()
    {
        $status = $this
            ->getSession()
            ->getConnection()
            ->getTransactionStatus()
            ;

        return (bool) ($status === \PGSQL_TRANSACTION_INTRANS || $status === \PGSQL_TRANSACTION_INERROR || $status === \PGSQL_TRANSACTION_ACTIVE);
    }

    /**
     * isTransactionOk
     *
     * In Postgresql, an error during a transaction cancels all the queries and
     * rollback the transaction on commit. This method returns the current
     * transaction's status. If no transactions are open, it returns null.
     *
     * @access public
     * @return bool|null
     */
    protected function isTransactionOk()
    {
        if (!$this->isInTransaction()) {
            return null;
        }

        $status = $this
            ->getSession()
            ->getConnection()
            ->getTransactionStatus()
            ;

        return (bool) ($status === \PGSQL_TRANSACTION_INTRANS);
    }

    /**
     * sendNotify
     *
     * Send a NOTIFY event to the database server. An optional data can be sent
     * with the notification.
     *
     * @access protected
     * @param  string     $channel
     * @param  string     $data
     * @return ModelLayer $this
     */
    protected function sendNotify($channel, $data = '')
    {
        return $this->sendParameter(
            'notify %s, %s',
            $channel,
            $this->escapeLiteral($data)
        );
    }

    /**
     * executeAnonymousQuery
     *
     * Proxy to Connection::executeAnonymousQuery()
     *
     * @access protected
     * @param  string $sql
     * @return ResultHandler
     */
    protected function executeAnonymousQuery($sql)
    {
        return $this
            ->getSession()
            ->getConnection()
            ->executeAnonymousQuery($sql)
            ;
    }

    /**
     * escapeIdentifier
     *
     * Proxy to Connection::escapeIdentifier()
     *
     * @access protected
     * @param  string $string
     * @return string
     */
    protected function escapeIdentifier($string)
    {
        return $this
            ->getSession()
            ->getConnection()
            ->escapeIdentifier($string)
            ;
    }

    /**
     * escapeLiteral
     *
     * Proxy to Connection::escapeLiteral()
     *
     * @access protected
     * @param  string $string
     * @return string
     */
    protected function escapeLiteral($string)
    {
        return $this
            ->getSession()
            ->getConnection()
            ->escapeLiteral($string)
            ;
    }

    /**
     * sendParameter
     *
     * Send a parameter to the server.
     * The parameter MUST have been properly checked and escpaed if needed as
     * it is going to be passed AS IS to the server. Sending untrusted
     * parameters may lead to potential SQL injection.
     *
     * @access private
     * @param  string     $sql
     * @param  string     $identifier
     * @param  string     $parameter
     * @return ModelLayer $this
     */
    private function sendParameter($sql, $identifier, $parameter = null)
    {
        $this
            ->executeAnonymousQuery(
                sprintf(
                    $sql,
                    $this->escapeIdentifier($identifier),
                    $parameter
                )
            );

        return $this;
    }
}
