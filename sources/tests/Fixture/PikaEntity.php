<?php
/*
 * This file is part of the PommProject/ModelManager package.
 *
 * (c) 2014 - 2015 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager\Test\Fixture;

use PommProject\ModelManager\Model\FlexibleEntity as PommFlexibleEntity;

class PikaEntity extends PommFlexibleEntity
{
    public function getPika()
    {
        return strtoupper($this->get('pika'));
    }

    public function setChu($val)
    {
        $this->set('chu', strtolower($val));

        return $this;
    }

    public function getPikaHash()
    {
        return md5($this->get('pika'));
    }

    public function hasPikaHash()
    {
        return $this->has('pika');
    }
}
