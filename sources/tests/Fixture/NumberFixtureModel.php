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

class NumberFixtureModel extends Model
{
    use WriteQueries;

    public function __construct()
    {
        $this->structure = new NumberFixtureStructure();
        $this->flexible_entity_class = 'PommProject\ModelManager\Test\Fixture\NumberFixture';
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
    id serial primary key,
		data int4,
    created_at timestamptz not null default now()
)
SQL;
        $this->executeAnonymousQuery(sprintf($sql, $this->getStructure()->getRelation()));

        $sql = <<<SQL
insert into %s (data, created_at) values
    (
        1,
        now()
    ),
    (
        2,
        now()
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
