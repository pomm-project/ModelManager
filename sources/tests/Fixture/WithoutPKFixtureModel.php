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

use PommProject\Foundation\Session\Session;
use PommProject\ModelManager\Model\Model;
use PommProject\ModelManager\Model\ModelTrait\WriteQueries;

class WithoutPKFixtureModel extends Model
{
    use WriteQueries;

    public function __construct()
    {
        $this->structure = new WithoutPKFixtureStructure();
        $this->flexible_entity_class = '\PommProject\ModelManager\Test\Fixture\WithoutPKFixture';
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
        $this->executeAnonymousQuery(
            sprintf(
                "create temporary table %s (id int4, a_varchar varchar, a_boolean boolean)",
                $this->getStructure()->getRelation()
            )
        );
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
