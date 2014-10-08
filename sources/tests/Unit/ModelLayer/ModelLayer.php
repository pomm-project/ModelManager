<?php
/*
 * This file is part of the PommProject/ModelManager package.
 *
 * (c) 2014 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager\Test\Unit\ModelLayer;

use PommProject\Foundation\Session;
use PommProject\Foundation\Observer\ObserverPooler;
use PommProject\Foundation\Test\Unit\SessionAwareAtoum;
use PommProject\ModelManager\ModelLayer\ModelLayerPooler;

class ModelLayer extends SessionAwareAtoum
{
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
        $session
            ->registerClientPooler(new ObserverPooler())
            ->registerClientPooler(new ModelLayerPooler())
            ;
    }

    public function getModelLayer()
    {
        $model_layer = $this->getSession()->getModelLayer('PommProject\ModelManager\Test\Fixture\SimpleModelLayer');
        $this
            ->object($model_layer)
            ->isInstanceOf('\PommProject\ModelManager\ModelLayer\ModelLayer')
            ;

        return $model_layer;
    }

    public function testTransaction()
    {
        $model_layer = $this->getModelLayer();
        $this
            ->boolean($model_layer->startTransaction())
            ->isTrue()
            ->boolean($model_layer->setSavepoint('pika'))
            ->isTrue()
            ->boolean($model_layer->releaseSavepoint('pika'))
            ->isTrue()
            ->boolean($model_layer->setSavepoint('chu'))
            ->isTrue()
            ->boolean($model_layer->rollbackTransaction('chu'))
            ->isTrue()
            ->variable($model_layer->sendNotify('plop', 'whatever'))
            ->isNull()
            ->boolean($model_layer->isTransactionOk())
            ->isTrue()
            ->exception(function() use ($model_layer) { $model_layer->releaseSavepoint('not exist'); })
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
}
