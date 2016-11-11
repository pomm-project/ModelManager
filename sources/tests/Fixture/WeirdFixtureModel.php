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

use PommProject\Foundation\Session\Session;
use PommProject\ModelManager\Model\RowStructure;
use PommProject\ModelManager\Model\ModelTrait\ReadQueries;
use PommProject\ModelManager\Model\Model;

class WeirdFixtureModel extends Model
{
    use ReadQueries;

    public function __construct()
    {
        $this->structure = (new RowStructure())
            ->setDefinition(['field_a' => 'int4', 'field_b' => 'bool', 'data_field' => 'varchar'])
            ->setRelation("(values (1, 't'::bool, 'one'), (2, 'f'::bool, 'two')) as weird_fixture (field_a, field_b, data_field)")
            ->setPrimaryKey(['field_a', 'field_b'])
            ;
        $this->flexible_entity_class =  '\PommProject\ModelManager\Test\Fixture\WeirdFixture';
    }
}
