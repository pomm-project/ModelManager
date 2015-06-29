<?php
/*
 * This file is part of the PommProject/ModelManager package.
 *
 * (c) 2014 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager\Test\Fixture;

use PommProject\ModelManager\ModelLayer\ModelLayer;

/**
 * SimpleModelLayer
 *
 * This class is NOT the right example of how ModelLayer is to be used. Good
 * practices are to handle complete transaction within a single method.
 * Transactions are split in several methods here to be tested properly.
 *
 * @package Pomm
 * @copyright 2014 Grégoire HUBERT
 * @author Grégoire HUBERT
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 * @see ModelLayer
 */
class SimpleModelLayer extends ModelLayer
{
    public function startTransaction()
    {
        return parent::startTransaction()
            ->isInTransaction()
            ;
    }

    public function rollbackTransaction($name = null)
    {
        return parent::rollbackTransaction($name)
            ->isInTransaction()
            ;
    }

    public function setSavepoint($name)
    {
        return parent::setSavepoint($name)
            ->isInTransaction()
            ;
    }

    public function releaseSavepoint($name)
    {
        return parent::releaseSavepoint($name)
            ->isInTransaction()
            ;
    }

    public function commitTransaction()
    {
        return parent::commitTransaction();
    }

    public function sendNotify($channel, $data = '')
    {
        $observer = $this
            ->getSession()
            ->getObserver($channel)
            ->RestartListening()
            ;
        parent::sendNotify($channel, $data);
        sleep(0.3);

        return $observer
            ->getNotification()
            ;
    }

    public function isInTransaction()
    {
        return parent::isInTransaction();
    }

    public function isTransactionOk()
    {
        return parent::isTransactionOk();
    }

    public function setDeferrable(array $keys, $state)
    {
        return parent::setDeferrable($keys, $state);
    }
}
