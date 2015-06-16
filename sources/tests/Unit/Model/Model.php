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

use PommProject\ModelManager\Test\Unit\BaseTest;
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

class Model extends BaseTest
{
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

    protected function getWithoutPKFixtureModel(Session $session)
    {
        return $session
            ->getModel('PommProject\ModelManager\Test\Fixture\WithoutPKFixtureModel')
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

    public function testCreateProjection()
    {
        $session = $this->buildSession();
        $model = $this->getSimpleFixtureModel($session);

        $this
            ->object($model->createProjection())
            ->isInstanceOf('\PommProject\ModelManager\Model\Projection')
            ->array($model->createProjection()->getFieldTypes())
            ->isIdenticalTo(['id' => 'int4', 'a_varchar' => 'varchar', 'a_boolean' => 'bool'])
            ;
    }

    public function testGetStructure()
    {
        $session = $this->buildSession();
        $model = $this->getSimpleFixtureModel($session);

        $this
            ->object($model->getStructure())
            ->isInstanceOf('\PommProject\ModelManager\Model\RowStructure')
            ->array($model->getStructure()->getDefinition())
            ->isIdenticalTo(['id' => 'int4', 'a_varchar' => 'varchar', 'a_boolean' => 'bool'])
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
        $model_without_pk = $this->getWithoutPKFixtureModel($this->buildSession());
        $this
            ->object($model->findByPK(['id' => 1]))
            ->isInstanceOf('\PommProject\ModelManager\Test\Fixture\SimpleFixture')
            ->integer($model->findByPK(['id' => 2])['id'])
            ->isEqualTo(2)
            ->variable($model->findByPK(['id' => 5]))
            ->isNull()
            ->integer($model->findByPK(['id' => 3])->status())
            ->isEqualTo(FlexibleEntityInterface::STATUS_EXIST)
            ->exception(function() use ($model_without_pk) { $model_without_pk->findByPK(['id' => 1]); })
            ->isInstanceOf('\PommProject\ModelManager\Exception\ModelException')
            ->message->contains("has no primary key.")
            ->exception(function() use ($model) { $model->findByPK(['a_varchar' => 'one']); })
            ->isInstanceOf('\PommProject\ModelManager\Exception\ModelException')
            ->message->contains("Key 'id' is missing to fully describes the primary key")
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
        $entity = new SimpleFixture(['a_varchar' => 'e', 'undefined_field' => null]);
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
        $model_without_pk = $this->getWithoutPKFixtureModel($this->buildSession());
        $entity = $model->createAndSave(['a_varchar' => 'qwerty', 'a_boolean' => false]);
        $entity_without_pk = $model_without_pk->createAndSave(['id' => 1, 'a_varchar' => 'qwerty', 'a_boolean' => false]);
        $entity->set('a_varchar', 'azerty')->set('a_boolean', true);
        $entity_without_pk->set('a_varchar', 'azerty')->set('a_boolean', true);
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
            ->exception(function() use ($model_without_pk, $entity_without_pk) { $model_without_pk->updateOne($entity_without_pk, ['a_varchar']); })
            ->isInstanceOf('\PommProject\ModelManager\Exception\ModelException')
            ->message->contains("has no primary key.")
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
        $model_without_pk = $this->getWithoutPKFixtureModel($this->buildSession());
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
            ->exception(function() use ($model_without_pk) { $model_without_pk->updateByPk(['id' => 1],  ['a_varchar' => 'whatever']); })
            ->isInstanceOf('\PommProject\ModelManager\Exception\ModelException')
            ->message->contains("has no primary key.")

        ;
    }

    public function testDeleteOne()
    {
        $model = $this->getWriteFixtureModel($this->buildSession());
        $model_without_pk = $this->getWithoutPKFixtureModel($this->buildSession());
        $entity_without_pk = $model_without_pk->createAndSave(['id' => 1, 'a_varchar' => 'qwerty', 'a_boolean' => false]);
        $entity = $model->createAndSave(['a_varchar' => 'mlkjhgf']);
        $this
            ->object($model->deleteOne($entity))
            ->isInstanceOf('\PommProject\ModelManager\Test\Fixture\WriteFixtureModel')
            ->variable($model->findByPK(['id' => $entity['id']]))
            ->isNull()
            ->integer($entity->status())
            ->isEqualTo(FlexibleEntityInterface::STATUS_NONE)
            ->exception(function() use ($model_without_pk, $entity_without_pk) { $model_without_pk->deleteOne($entity_without_pk); })
            ->isInstanceOf('\PommProject\ModelManager\Exception\ModelException')
            ->message->contains("has no primary key.")
            ;
    }

    public function testDeleteByPK()
    {
        $model = $this->getWriteFixtureModel($this->buildSession());
        $model_without_pk = $this->getWithoutPKFixtureModel($this->buildSession());
        $entity_without_pk = $model_without_pk->createAndSave(['id' => 1, 'a_varchar' => 'qwerty', 'a_boolean' => false]);
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
            ->exception(function() use ($model_without_pk, $entity_without_pk) { $model_without_pk->deleteOne($entity_without_pk); })
            ->isInstanceOf('\PommProject\ModelManager\Exception\ModelException')
            ->message->contains("has no primary key.")
            ;
    }

    public function testDeleteWhere()
    {
        $model = $this->getWriteFixtureModel($this->buildSession());
        $entity1 = $model->createAndSave(['a_varchar' => 'qwerty', 'a_boolean' => false]);
        $entity2 = $model->createAndSave(['a_varchar' => 'qwertz', 'a_boolean' => true]);
        $deleted_entities = $model->deleteWhere('a_varchar = $*::varchar', ['qwertz']);
        $this
            ->object($deleted_entities)
            ->isInstanceOf('\PommProject\ModelManager\Model\CollectionIterator')
            ->integer($deleted_entities->count())
            ->isEqualTo(1)
            ->object($deleted_entities->get(0))
            ->isInstanceOf('\PommProject\ModelManager\Test\Fixture\SimpleFixture')
            ->isEqualTo($entity2)
            ->integer($deleted_entities->get(0)->status())
            ->isEqualTo(FlexibleEntityInterface::STATUS_NONE)
        ;

        $deleted_entities2 = $model->deleteWhere('a_varchar = $*::varchar', ['qwertz']);
        $this
            ->object($deleted_entities2)
            ->isInstanceOf('\PommProject\ModelManager\Model\CollectionIterator')
            ->integer($deleted_entities2->count())
            ->isEqualTo(0)
           ;

        $deleted_entities3 = $model->deleteWhere(
            Where::create('a_boolean = $*::boolean', [false])
        );

        $this
            ->object($deleted_entities3)
            ->isInstanceOf('\PommProject\ModelManager\Model\CollectionIterator')
            ->integer($deleted_entities3->count())
            ->isEqualTo(1)
            ->object($deleted_entities3->get(0))
            ->isInstanceOf('\PommProject\ModelManager\Test\Fixture\SimpleFixture')
            ->isEqualTo($entity1)
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
