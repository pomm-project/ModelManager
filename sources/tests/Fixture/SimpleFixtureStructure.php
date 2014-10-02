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
        $this->relation = <<<_
(values
    (1::int4,'one'::varchar, bool 't'),
    (2,'two', 'f'),
    (3,'three', 'f'),
    (4,'four', 't')
)
    simple_fixture (id, a_varchar, a_boolean)
_;
        $this
            ->addField('id', 'int4')
            ->addField('a_varchar', 'varchar')
            ->addField('a_boolean', 'bool')
            ->primary_key = ['id']
            ;
    }
}
