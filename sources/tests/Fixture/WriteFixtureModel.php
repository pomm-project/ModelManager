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

use PommProject\Foundation\Session;
use PommProject\ModelManager\Test\Fixture\SimpleFixtureModel;
use PommProject\ModelManager\Model\ReadModelTrait;
use PommProject\ModelManager\Model\WriteModelTrait;

class WriteFixtureModel extends SimpleFixtureModel
{
    use ReadModelTrait;
    use WriteModelTrait;

    public function getRelation()
    {
        return 'pomm_test.write_fixture';
    }

    public function createTable()
    {
        $sql = sprintf("create table %s (id int, some_data varchar)", $this->getRelation());
        $this
            ->getSession()
            ->getConnection()
            ->executeAnonymousQuery($sql)
            ;
    }

    public function dropTable()
    {
        $sql = sprintf("drop table %s cascade", $this->getRelation());
        $this
            ->getSession()
            ->getConnection()
            ->executeAnonymousQuery($sql)
            ;
    }

    public function initialize(Session $session)
    {
        parent::initialize($session);
        $this->createTable();
    }

    public function shutdown()
    {
        $this->dropTable();
    }
}


