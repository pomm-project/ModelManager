<?php
/*
 * This file is part of the PommProject/ModelManager package.
 *
 * (c) 2014 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager\Test\Unit\Model;

use PommProject\Foundation\Where;
use PommProject\Foundation\PreparedQuery\PreparedQueryPooler;
use PommProject\Foundation\Test\Unit\Converter\BaseConverter;
use PommProject\ModelManager\Test\Fixture\SimpleFixtureModel;
use PommProject\ModelManager\Model\Model as PommModel;
use Mock\PommProject\ModelManager\Model\RowStructure as RowStructureMock;

class Model extends BaseConverter
{
    protected function registerClientPoolers()
    {
        parent::registerClientPoolers();
        $this->session->registerClientPooler(new PreparedQueryPooler());
    }

    protected function getSimpleFixtureModel()
    {
        $model = new SimpleFixtureModel();
        $model->initialize($this->getSession());

        return $model;
    }

    public function testGetClientType()
    {
        $this
            ->string($this->getSimpleFixtureModel()->getClientType())
            ->isEqualTo('model')
            ;
    }

    public function getClientIdentifier()
    {
        $this
            ->string($this->getSimpleFixtureModel()->getClientIdentifier())
            ->isEqualTo('PommProject\ModelManager\Test\Fixture\SimpleFixture')
            ;
    }

    public function testInitialize()
    {
        $session = $this->getSession();
        $this
            ->exception(function() use ($session) {
                    $model = new NoStructureNoFlexibleEntityModel();
                    $model->initialize($session);
                })
            ->isInstanceOf('\PommProject\ModelManager\Exception\ModelException')
            ->exception(function() use ($session) {
                    $model = new NoFlexibleEntityModel();
                    $model->initialize($session);
                })
            ->isInstanceOf('\PommProject\ModelManager\Exception\ModelException')
            ->exception(function() use ($session) {
                    $model = new NoStructureModel();
                    $model->initialize($session);
                })
            ->isInstanceOf('\PommProject\ModelManager\Exception\ModelException')
            ;
    }

    public function testQuery()
    {
        $model = $this->getSimpleFixtureModel();
        $where = new Where('id % $* = 0', [2]);
        $this
            ->object($model->doSimpleQuery())
            ->isInstanceOf('\PommProject\ModelManager\Model\CollectionIterator')
            ->integer($model->doSimpleQuery()->count())
            ->isEqualTo(4)
            ->integer($model->doSimpleQuery()->count())
            ->isEqualTo(4)
            ->integer($model->doSimpleQuery($where)->count())
            ->isEqualTo(2)
            ;
    }

    public function testGetPrimaryKey()
    {
        $this
            ->array($this->getSimpleFixtureModel()->getPrimaryKey())
            ->isIdenticalTo(['id'])
            ;
    }

    public function testCreateProjection()
    {
        $this
            ->object($this->getSimpleFixtureModel()->createProjection())
            ->isInstanceOf('\PommProject\ModelManager\Model\Projection')
            ;
    }
}


class NoStructureNoFlexibleEntityModel extends PommModel
{
}

class NoStructureModel extends PommModel
{
    public function __construct()
    {
        $this->flexible_entity_class = 'something';
    }
}

class NoFlexibleEntityModel extends PommModel
{
    public function __construct()
    {
        $this->structure = new RowStructureMock();
    }
}
