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
use PommProject\Foundation\Session\Session;
use PommProject\Foundation\Query\QueryPooler;
use PommProject\Foundation\Converter\ConverterPooler;
use PommProject\Foundation\PreparedQuery\PreparedQueryPooler;

use PommProject\ModelManager\Tester\ModelSessionAtoum;

use PommProject\ModelManager\Model\ModelPooler;
use PommProject\ModelManager\Model\Model                as PommModel;
use PommProject\ModelManager\Converter\PgEntity;
use PommProject\ModelManager\Test\Fixture\SimpleFixture;
use PommProject\ModelManager\Test\Fixture\ComplexFixture;
use PommProject\ModelManager\Test\Fixture\ModelSchemaClient;
use PommProject\ModelManager\Test\Fixture\ComplexNumberStructure;
use PommProject\ModelManager\Model\FlexibleEntity\FlexibleEntityInterface;

use Mock\PommProject\ModelManager\Model\FlexibleEntity\FlexibleEntity  as FlexibleEntityMock;
use Mock\PommProject\ModelManager\Model\RowStructure    as RowStructureMock;

class Model extends ModelSessionAtoum
{
    public function setUp()
    {
        $session = $this->buildSession();
        $sql =
            [
                "drop schema if exists pomm_test cascade",
                "begin",
                "create schema pomm_test",
                "create type pomm_test.complex_number as (real float8, imaginary float8)",
                "commit",
            ];

        try {
            $session->getConnection()->executeAnonymousQuery(join(';', $sql));
        } catch (SqlException $e) {
            $session->getConnection()->executeAnonymousQuery('rollback');
            throw $e;
        }
    }

    public function tearDown()
    {
        $this->buildSession()->getConnection()->executeAnonymousQuery('drop schema if exists pomm_test cascade');
    }

    protected function initializeSession(Session $session)
    {
        $session
            ->getPoolerForType('converter')
            ->getConverterHolder()
            ->registerConverter(
                'ComplexNumber',
                new PgEntity('\PommProject\ModelManager\Test\Fixture\ComplexNumber', new ComplexNumberStructure()),
                ['pomm_test.complex_number']
            )
            ;
    }

    protected function getSimpleFixtureModel(Session $session)
    {
        return $session
            ->getModel('PommProject\ModelManager\Test\Fixture\SimpleFixtureModel')
            ;
    }

    protected function getReadFixtureModel(Session $session)
    {
        return $session
            ->getModel('PommProject\ModelManager\Test\Fixture\ReadFixtureModel')
            ;
    }

    protected function getWriteFixtureModel(Session $session)
    {
        return $session
            ->getModel('PommProject\ModelManager\Test\Fixture\WriteFixtureModel')
            ;
    }

    protected function getComplexFixtureModel(Session $session)
    {
        return $session
            ->getModel('PommProject\ModelManager\Test\Fixture\ComplexFixtureModel');
    }

    public function testGetClientType()
    {
        $this
            ->string($this->getSimpleFixtureModel($this->buildSession())->getClientType())
            ->isEqualTo('model')
            ;
    }

    public function getClientIdentifier()
    {
        $this
            ->string($this->getSimpleFixtureModel($this->buildSession())->getClientIdentifier())
            ->isEqualTo('PommProject\ModelManager\Test\Fixture\SimpleFixtureModel')
            ;
    }

    public function testInitialize()
    {
        $session = $this->buildSession();
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
        $session = $this->buildSession();
        $model = $this->getSimpleFixtureModel($session);
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

    public function testFindAll()
    {
        $session = $this->buildSession();
        $model = $this->getReadFixtureModel($session);
        $this
            ->object($model->findAll())
            ->isInstanceOf('\PommProject\ModelManager\Model\CollectionIterator')
            ->array($model->findAll()->slice('id'))
            ->isIdenticalTo([1, 2, 3, 4])
            ->array($model->findAll('order by id desc')->slice('id'))
            ->isIdenticalTo([4, 3 ,2 ,1])
            ->array($model->findAll('limit 3')->slice('id'))
            ->isIdenticalTo([1, 2, 3,])
            ;
        $complex_model = $this->getComplexFixtureModel($session);
        $entity = $complex_model->findAll('order by id asc limit 1')->current();
        $this
            ->object($entity)
            ->isInstanceOf('\PommProject\ModelManager\Test\Fixture\ComplexFixture')
            ;

    }

    public function testFindWhere()
    {
        $model = $this->getReadFixtureModel($this->buildSession());
        $condition = 'id % $* = 0';
        $where = new Where($condition, [2]);
        $this
            ->object($model->findWhere('true'))
            ->isInstanceOf('\PommProject\ModelManager\Model\CollectionIterator')
            ->array($model->findWhere($condition, [2])->slice('id'))
            ->isIdenticalTo($model->findWhere($where)->slice('id'))
            ->integer($model->findWhere($where)->count())
            ->isEqualTo(2)
            ->array($model->findWhere($condition, [1], 'order by id desc limit 3')->slice('id'))
            ->isIdenticalTo([4, 3, 2])
            ;
    }

    public function testFindByPK()
    {
        $model = $this->getReadFixtureModel($this->buildSession());
        $this
            ->object($model->findByPK(['id' => 1]))
            ->isInstanceOf('\PommProject\ModelManager\Test\Fixture\SimpleFixture')
            ->integer($model->findByPK(['id' => 2])['id'])
            ->isEqualTo(2)
            ->variable($model->findByPK(['id' => 5]))
            ->isNull()
            ->integer($model->findByPK(['id' => 3])->status())
            ->isEqualTo(FlexibleEntityInterface::STATUS_EXIST)
            ;
    }

    public function testUseIdentityMapper()
    {
        $model = $this->getReadFixtureModel($this->buildSession());
        $this
            ->object($model->findByPK(['id' => 1]))
            ->isIdenticalTo($model->findByPK(['id' => 1]))
            ;
    }

    public function testCountWhere()
    {
        $model = $this->getReadFixtureModel($this->buildSession());
        $condition = 'id % $* = 0';
        $where = new Where($condition, [2]);
        $this
            ->integer($model->countWhere('true'))
            ->isEqualTo(4)
            ->integer($model->countWhere($condition, [2]))
            ->isEqualTo(2)
            ->integer($model->countWhere($where))
            ->isEqualTo(2)
            ;
    }

    public function testExistWhere()
    {
        $model = $this->getReadFixtureModel($this->buildSession());
        $condition = 'a_varchar = $*';
        $this
            ->boolean($model->existWhere('true'))
            ->isTrue()
            ->boolean($model->existWhere($condition, ['one']))
            ->isTrue()
            ->boolean($model->existWhere($condition, ['aqwzxedc']))
            ->isFalse()
            ->boolean($model->existWhere(new Where($condition, ['two'])))
            ->isTrue()
            ;
    }


    public function testPaginateFindWhere()
    {
        $model = $this->getReadFixtureModel($this->buildSession());
        $pager = $model->paginateFindWhere(new Where, 2);
        $this
            ->object($pager)
            ->isInstanceOf('\PommProject\Foundation\Pager')
            ->array($pager->getIterator()->slice('id'))
            ->isIdenticalTo([1, 2])
            ->array($model->paginateFindWhere(new Where, 2, 2, 'order by id desc')->getIterator()->slice('id'))
            ->isIdenticalTo([2, 1])
            ;
    }

    public function testInsertOne()
    {
        $model = $this->getWriteFixtureModel($this->buildSession());
        $entity = new SimpleFixture(['a_varchar' => 'e']);
        $this
            ->object($model->insertOne($entity))
            ->isIdenticalTo($model)
            ->boolean($entity->hasId())
            ->isTrue()
            ->boolean($entity->status() === FlexibleEntityInterface::STATUS_EXIST)
            ->isTrue()
            ;
    }

    public function testUpdateOne()
    {
        $model = $this->getWriteFixtureModel($this->buildSession());
        $entity = $model->createAndSave(['a_varchar' => 'qwerty', 'a_boolean' => false]);
        $entity->set('a_varchar', 'azerty')->set('a_boolean', true);
        $this
            ->assert('Simple update')
            ->object($model->updateOne($entity, ['a_varchar']))
            ->isIdenticalTo($model)
            ->string($entity->get('a_varchar'))
            ->isEqualTo('azerty')
            ->boolean($entity->get('a_boolean'))
            ->isFalse()
            ->boolean($entity->status() === FlexibleEntityInterface::STATUS_EXIST)
            ->isTrue()
            ;
        $entity->set('a_boolean', ! $entity->get('a_boolean'));
        $model->updateOne($entity, ['a_boolean']);
        $this
            ->boolean($entity->get('a_boolean'))
            ->isTrue()
            ;
    }

    public function testUpdateByPK()
    {
        $model = $this->getWriteFixtureModel($this->buildSession());
        $entity = $model->createAndSave(['a_varchar' => 'qwerty', 'a_boolean' => false]);
        $updated_entity = $model->updateByPk(['id' => $entity['id']], ['a_boolean' => true]);
        $this
            ->object($updated_entity)
            ->isInstanceOf('PommProject\ModelManager\Test\Fixture\SimpleFixture')
            ->boolean($updated_entity['a_boolean'])
            ->isTrue()
            ->integer($updated_entity->status())
            ->isEqualTo(FlexibleEntityInterface::STATUS_EXIST)
            ->variable($model->updateByPk(['id' => 999999], ['a_varchar' => 'whatever']))
            ->isNull()
            ->object($entity)
            ->isIdenticalTo($updated_entity)
            ;
    }

    public function testDeleteOne()
    {
        $model = $this->getWriteFixtureModel($this->buildSession());
        $entity = $model->createAndSave(['a_varchar' => 'mlkjhgf']);
        $this
            ->object($model->deleteOne($entity))
            ->isInstanceOf('\PommProject\ModelManager\Test\Fixture\WriteFixtureModel')
            ->variable($model->findByPK(['id' => $entity['id']]))
            ->isNull()
            ->integer($entity->status())
            ->isEqualTo(FlexibleEntityInterface::STATUS_NONE)
            ;
    }

    public function testDeleteByPK()
    {
        $model = $this->getWriteFixtureModel($this->buildSession());
        $entity = $model->createAndSave(['a_varchar' => 'qwerty', 'a_boolean' => false]);
        $deleted_entity = $model->deleteByPK(['id' => $entity['id']]);
        $this
            ->object($deleted_entity)
            ->isInstanceOf('\PommProject\ModelManager\Test\Fixture\SimpleFixture')
            ->integer($deleted_entity->status())
            ->isEqualTo(FlexibleEntityInterface::STATUS_NONE)
            ->variable($model->deleteByPK(['id' => $entity['id']]))
            ->isNull()
            ->object($entity)
            ->isIdenticalTo($deleted_entity)
            ->integer($entity->status())
            ->isEqualTo(FlexibleEntityInterface::STATUS_NONE)
            ;
    }

    public function testCreateAndSave()
    {
        $session = $this->buildSession();
        $model   = $this->getWriteFixtureModel($session);
        $entity  = $model->createAndSave(['a_varchar' => 'abcdef', 'a_boolean' => true]);
        $this
            ->boolean($entity->has('id'))
            ->isTrue()
            ->string($entity->get('a_varchar'))
            ->isEqualTo('abcdef')
            ->integer($entity->status())
            ->isEqualTo(FlexibleEntityInterface::STATUS_EXIST)
            ->object($model->findWhere('id = $*', [$entity['id']])->current())
            ->isIdenticalTo($entity)
            ;
    }

    public function testCreateEntity()
    {
        $session = $this->buildSession();
        $model   = $this->getSimpleFixtureModel($session);
        $entity  = $model->createEntity();
        $this
            ->object($entity)
            ->isInstanceOf('PommProject\ModelManager\Test\Fixture\SimpleFixture');
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
