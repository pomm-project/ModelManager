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

use Mock\PommProject\ModelManager\Model\Projection         as ProjectionMock;
use Mock\PommProject\ModelManager\Model\CollectionIterator as CollectionIteratorMock;

use PommProject\ModelManager\Model\ModelPooler;

use PommProject\ModelManager\Tester\ModelSessionAtoum;
use PommProject\Foundation\Converter\ConverterPooler;
use PommProject\Foundation\Session\Session;

use PommProject\ModelManager\Test\Fixture\SimpleFixtureModel;

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
            ->isEqualTo(['id' => 1, 'some_data' => 'one'])
            ->array($collection->get(3)->extract())
            ->isEqualTo(['id' => 4, 'some_data' => 'four'])
            ;
    }

    public function testGetWithFilters()
    {
        $collection = $this->getCollectionMock();
        $collection->registerFilter(
            function($values) { $values['id'] *= 2; return $values; }
        )
            ->registerFilter(
                function($values) {
                    $values['some_data'] =
                        strlen($values['some_data']) > 3
                        ? null
                        : $values['some_data'];
                    $values['id'] += 1;
                    return $values;
                }
        );
        $this
            ->array($collection->get(0)->extract())
            ->isEqualTo(['id' => 3, 'some_data' => 'one'])
            ->array($collection->get(3)->extract())
            ->isEqualTo(['id' => 9, 'some_data' => null])
            ;
    }

    public function testGetWithWrongFilter()
    {
        $collection = $this->getCollectionMock();
        $collection->registerFilter(function($values) { return $values['id']; });
        $this
            ->exception(function() use ($collection) { $collection->get(2); })
            ->isInstanceOf('\PommProject\ModelManager\Exception\ModelException')
            ->message->contains('Filters MUST return an array')
            ;
    }
    public function testRegisterBadFilters()
    {
        $collection = $this->getCollectionMock();
        $this
            ->exception(function() use ($collection) {
                $collection->registerFilter('whatever');
            })
            ->isInstanceOf('\PommProject\ModelManager\Exception\ModelException')
            ->message->contains('is not a callable')
            ;
    }
}
