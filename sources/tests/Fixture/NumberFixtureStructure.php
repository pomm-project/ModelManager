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

use PommProject\ModelManager\Model\RowStructure;

class NumberFixtureStructure extends RowStructure
{
    public function __construct()
    {
        $this
            ->setRelation('number_fixture')
            ->setPrimaryKey(['id'])
            ->addField('id', 'int4')
            ->addField('data', 'int4')
            ->addField('created_at', 'timestamptz')
            ;
    }
}
