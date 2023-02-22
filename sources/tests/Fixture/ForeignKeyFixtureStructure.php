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

class ForeignKeyFixtureStructure extends RowStructure
{
    public function __construct()
    {
        $this
            ->addField('id', 'int4')
            ->addField('truncate_id', 'int4')
            ->primary_key = ['id']
            ;
    }
}
