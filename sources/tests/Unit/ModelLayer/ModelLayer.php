<?php
/*
 * This file is part of the PommProject/ModelManager package.
 *
 * (c) 2014 - 2015 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager\Test\Unit\ModelLayer;

use PommProject\Foundation\Session\Connection;
use PommProject\Foundation\Session\Session;
use PommProject\ModelManager\Tester\ModelSessionAtoum;

class ModelLayer extends ModelSessionAtoum
{
    public function setUp()
    {
        $this
            ->buildSession()
            ->getConnection()
            ->executeAnonymousQuery(<<<EOSQL
create schema pomm_test;
create table pomm_test.pika (id serial primary key);
create table pomm_test.chu (id serial primary key, pika_id int not null references pomm_test.pika (id) deferrable);
EOSQL
            )
        ;
    }

    public function tearDown()
    {
        $this
            ->buildSession()
            ->getConnection()
            ->executeAnonymousQuery('drop schema pomm_test cascade;')
            ;
    }

    public function afterTestMethod($method)
    {
        /*
         * This is to ensure the transaction is terminated even if a test fails
         * so the ClientHolder can shutdown correctly.
         */
        $this->getModelLayer()->rollbackTransaction();
    }

    protected function initializeSession(Session $session)
    {
    }

    public function getModelLayer()
    {
        $model_layer = $this->buildSession()->getModelLayer('PommProject\ModelManager\Test\Fixture\SimpleModelLayer');
        $this
            ->object($model_layer)
            ->isInstanceOf('\PommProject\ModelManager\ModelLayer\ModelLayer')
            ;

        return $model_layer;
    }

    public function testSetDeferrable()
    {
        $model_layer = $this->getModelLayer();
        $this
            ->object(
                $model_layer
                    ->setDeferrable(['pomm_test.chu_pika_id_fkey'], Connection::CONSTRAINTS_DEFERRED)
            )
            ->isEqualTo($model_layer)
            ->exception(function () use ($model_layer) {
                $model_layer->setDeferrable(['pomm_test.chu_pika_id_fkey'], 'chu');
            })
            ->isInstanceOf('\PommProject\ModelManager\Exception\ModelLayerException')
            ->message->contains("'chu' is not a valid constraint modifier")
            ;
    }

    public function testTransaction()
    {
        $model_layer = $this->getModelLayer();
        $this
            ->boolean($model_layer->startTransaction())
            ->isTrue()
            ->boolean($model_layer->setSavepoint('12 345'))
            ->isTrue()
            ->boolean($model_layer->releaseSavepoint('12 345'))
            ->isTrue()
            ->boolean($model_layer->setSavepoint('chu'))
            ->isTrue()
            ->boolean($model_layer->rollbackTransaction('chu'))
            ->isTrue()
            ->variable($model_layer->sendNotify('plop', 'whatever'))
            ->isNull()
            ->boolean($model_layer->isTransactionOk())
            ->isTrue()
            ->exception(function () use ($model_layer) { $model_layer->releaseSavepoint('not exist'); })
            ->isInstanceOf('\PommProject\Foundation\Exception\SqlException')
            ->boolean($model_layer->isInTransaction())
            ->isTrue()
            ->boolean($model_layer->isTransactionOk())
            ->isFalse()
            ->object($model_layer->commitTransaction())
            ->isIdenticalTo($model_layer)
            ->array($model_layer->sendNotify('plop', 'whatever'))
            ->contains('whatever')
            ;
    }

    protected function testSetTransactionIsolationLevelDataProvider()
    {
        return [
            Connection::ISOLATION_READ_COMMITTED,
            Connection::ISOLATION_REPEATABLE_READ,
            Connection::ISOLATION_SERIALIZABLE,
        ];
    }

    public function testSetTransactionIsolationLevel($level)
    {
        $model_layer = $this->getModelLayer();
        $model_layer->startTransaction();
        $this
            ->object($model_layer->setTransactionIsolationLevel($level))
            ->string($this->getTransactionIsolationLevel($model_layer))
            ->isEqualTo(strtolower($level))
            ;
        $model_layer->rollbackTransaction();
    }

    protected function getTransactionIsolationLevel($model_layer)
    {
        return $model_layer->getSession()
            ->getQueryManager()
            ->query('show transaction_isolation')
            ->current()['transaction_isolation']
            ;
    }

    public function testSetTransactionAccessMode()
    {
        $model_layer = $this->getModelLayer();
        $model_layer->startTransaction();
        $this
            ->object($model_layer->setTransactionAccessMode(Connection::ACCESS_MODE_READ_WRITE))
            ->string($this->getTransactionAccessModel($model_layer))
            ->isEqualTo('off')
            ->object($model_layer->setTransactionAccessMode(Connection::ACCESS_MODE_READ_ONLY))
            ->string($this->getTransactionAccessModel($model_layer))
            ->isEqualTo('on')
            ;
    }

    protected function getTransactionAccessModel($model_layer)
    {
        return $model_layer->getSession()
            ->getQueryManager()
            ->query('show transaction_read_only')
            ->current()['transaction_read_only']
            ;
    }
}
