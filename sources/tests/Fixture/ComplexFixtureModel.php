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
use PommProject\ModelManager\Model\Model;
use PommProject\ModelManager\Model\ModelTrait\WriteQueries;

class ComplexFixtureModel extends Model
{
    use WriteQueries;

    public function __construct()
    {
        $this->structure = new ComplexFixtureStructure();
        $this->flexible_entity_class = 'PommProject\ModelManager\Test\Fixture\ComplexFixture';
    }

    public function initialize(Session $session)
    {
        parent::initialize($session);
        $this->dropTable();
        $this->createTable();
    }

    public function shutdown()
    {
        $this->dropTable();
    }

    protected function createTable()
    {
        $sql = <<<SQL
create temporary table %s (
    id int4,
    version_id int4,
    complex_number pomm_test.complex_number,
    complex_numbers pomm_test.complex_number[],
    created_at timestamptz not null default now(),
    updated_at timestamptz[],
    primary key (id, version_id)
)
SQL;
        $this->executeAnonymousQuery(sprintf($sql, $this->getStructure()->getRelation()));

        $sql = <<<SQL
insert into %s (id, version_id, complex_number, complex_numbers, updated_at) values
    (
        1,
        1,
        '(1.233,2.344)'::pomm_test.complex_number,
        array['(3.455, 4.566)'::pomm_test.complex_number, '(5.677, 6.788)'::pomm_test.complex_number]::pomm_test.complex_number[],
        array[timestamptz '1982-04-21 23:12:43']::timestamptz[]
    ),
    (
        1,
        2,
        '(2.234,3.345)'::pomm_test.complex_number,
        array['(3.455, 4.566)'::pomm_test.complex_number, '(5.677, 6.788)'::pomm_test.complex_number]::pomm_test.complex_number[],
        array[timestamptz '1982-04-21 23:12:43', '1986-12-21 10:13:02']::timestamptz[]
    )
SQL;
        $this->executeAnonymousQuery(sprintf($sql, $this->getStructure()->getRelation()));

        return $this;
    }

    protected function dropTable()
    {
        $this
            ->executeAnonymousQuery(
                sprintf(
                    "drop table if exists %s",
                    $this->getStructure()->getRelation()
                )
            )
            ;

        return $this;
    }
}
