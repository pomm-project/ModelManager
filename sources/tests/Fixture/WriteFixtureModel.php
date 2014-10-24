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
use PommProject\Foundation\Exception\SqlException;
use PommProject\ModelManager\Test\Fixture\SimpleFixtureModel;
use PommProject\ModelManager\Model\ModelTrait\WriteQueries;

class WriteFixtureModel extends SimpleFixtureModel
{
    use WriteQueries;

    public function __construct()
    {
        parent::__construct();
        $this->getStructure()->setRelation('write_fixture');
    }

    public function initialize(Session $session)
    {
        parent::initialize($session);
        $this
            ->dropTable()
            ->createTable()
            ;
    }

    public function shutdown()
    {
        $this->dropTable();
    }

    protected function createTable()
    {
        $this->executeAnonymousQuery(
            sprintf(
                "create temporary table %s (id serial primary key, a_varchar varchar, a_boolean boolean)",
                $this->getStructure()->getRelation()
            )
        );

        return $this;
    }

    public function truncate()
    {
        $this->executeAnonymousQuery(sprintf("truncate %s", $this->getStructure()->getRelation()));
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
