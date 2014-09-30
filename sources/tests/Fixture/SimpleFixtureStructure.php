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

use PommProject\ModelManager\Model\RowStructure;

class SimpleFixtureStructure extends RowStructure
{
    protected function initialize()
    {
        $this->relation = "(values (1,'one'),(2,'two'),(3,'three'),(4,'four')) simple_fixture (id,some_data)";
        $this
            ->addField('id', 'int4')
            ->addField('some_data', 'varchar')
            ->primary_key = ['id']
            ;
    }
}
