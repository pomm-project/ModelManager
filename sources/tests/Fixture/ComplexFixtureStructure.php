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

class ComplexFixtureStructure extends RowStructure
{
    public function __construct()
    {
        $this
            ->setRelation('complex_fixture')
            ->setPrimaryKey(['id', 'version_id'])
            ->addField('id', 'int4')
            ->addField('version_id', 'int4')
            ->addField('complex_number', 'pomm_test.complex_number')
            ->addField('complex_numbers', 'pomm_test.complex_number[]')
            ->addField('created_at', 'timestamptz')
            ->addField('updated_at', 'timestamptz[]')
            ;
    }
}
