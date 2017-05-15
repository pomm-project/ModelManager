<?php
/*
 * This file is part of the PommProject/ModelManager package.
 *
 * (c) 2014 - 2015 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager\Test\Unit\Model;

use Mock\PommProject\ModelManager\Model\CollectionIterator as CollectionIteratorMock;
use Mock\PommProject\ModelManager\Model\Projection as ProjectionMock;
use PommProject\Foundation\Session\Session;
use PommProject\ModelManager\Test\Fixture\SimpleFixtureModel;
use PommProject\ModelManager\Tester\ModelSessionAtoum;

class CollectionIterator extends ModelSessionAtoum
{
    protected $session;

    protected function getSession()
    {
        if ($this->session === null) {
            $this->session = $this->buildSession();
        }

        return $this->session;
    }

    protected function initializeSession(Session $session)
    {
        $session
            ->registerClient(new SimpleFixtureModel)
            ;
    }

    protected function getSql()
    {
        return <<<SQL
select
    id, some_data
from
    (values (1, 'one'), (2, 'two'), (3, 'three'), (4, 'four'))
        pika (id, some_data)
SQL;
    }

    protected function getQueryResult($sql)
    {
        $sql = $sql === null ? $this->getSql() : $sql;

        return $this->getSession()->getConnection()->sendQueryWithParameters($sql);
    }

    protected function getCollectionMock($sql = null)
    {
        return new CollectionIteratorMock(
            $this->getQueryResult($sql),
            $this->getSession(),
            new ProjectionMock('\PommProject\ModelManager\Test\Fixture\SimpleFixture', ['id' => 'int4', 'some_data' => 'varchar'])
        );
    }

    public function testGetWithoutFilters()
    {
        $collection = $this->getCollectionMock();
        $this
            ->object($collection->get(0))
            ->isInstanceOf('\PommProject\ModelManager\Test\Fixture\SimpleFixture')
            ->mock($collection)
            ->call('parseRow')
            ->atLeastOnce()
            ->array($collection->get(0)->extract())
            ->isEqualTo(['Id' => 1, 'SomeData' => 'one'])
            ->array($collection->get(3)->extract())
            ->isEqualTo(['Id' => 4, 'SomeData' => 'four'])
            ;
    }

    public function testGetWithFilters()
    {
        $collection = $this->getCollectionMock();
        $collection->registerFilter(
            function ($values) { $values['id'] *= 2; return $values; }
        )
            ->registerFilter(
                function ($values) {
                    $values['some_data'] =
                        strlen($values['some_data']) > 3
                        ? null
                        : $values['some_data'];
                    ++$values['id'];
                    $values['new_value'] = 'love pomm';

                    return $values;
                }
        );
        $this
            ->array($collection->get(0)->extract())
            ->isEqualTo(['Id' => 3, 'SomeData' => 'one', 'NewValue' => 'love pomm'])
            ->array($collection->get(3)->extract())
            ->isEqualTo(['Id' => 9, 'SomeData' => null, 'NewValue' => 'love pomm'])
            ;
    }

    public function testGetWithWrongFilter()
    {
        $collection = $this->getCollectionMock();
        $collection->registerFilter(function ($values) { return $values['id']; });
        $this
            ->exception(function () use ($collection) { $collection->get(2); })
            ->isInstanceOf('\PommProject\ModelManager\Exception\ModelException')
            ->message->contains('Filters MUST return an array')
            ;
    }

    public function testRegisterBadFilters()
    {
        $collection = $this->getCollectionMock();
        $this
            ->exception(function () use ($collection) {
                $collection->registerFilter('whatever');
            })
            ->isInstanceOf('\PommProject\ModelManager\Exception\ModelException')
            ->message->contains('is not a callable')
            ;
    }

    public function testExtract()
    {
        $collection = $this->getCollectionMock();

        $this
            ->array($collection->extract())
            ->isIdenticalTo(
                [
                    ['Id' => 1, 'SomeData' => 'one'],
                    ['Id' => 2, 'SomeData' => 'two'],
                    ['Id' => 3, 'SomeData' => 'three'],
                    ['Id' => 4, 'SomeData' => 'four'],
                ]
            );
    }

    public function testSlice()
    {
        $collection = $this->getCollectionMock();

        $this
            ->array($collection->slice('some_data'))
            ->isIdenticalTo(
                [
                    'one',
                    'two',
                    'three',
                    'four',
                ]
            );
    }
}
