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

use Atoum;
use PommProject\ModelManager\Test\Fixture\ComplexFixture;

class IdentityMapper extends Atoum
{
    public function testFetch()
    {
        $fixture = new ComplexFixture(['created_at' => new \DateTime("2014-10-30 10:13:56.420342+00"), 'some_id' => 1, 'yes' => true ]);
        $mapper = $this->newTestedInstance();

        $this
            ->object($mapper->fetch($fixture, ['some_id']))
            ->isInstanceOf('PommProject\ModelManager\Test\Fixture\ComplexFixture')
            ->isIdenticalTo($fixture)
            ->object($mapper->fetch(new ComplexFixture(['created_at' => new \DateTime("2013-10-30 10:13:56.420342+00"), 'some_id' => 1, 'yes' => false ]), ['some_id']))
            ->isIdenticalTo($fixture)
            ->dateTime($fixture->get('created_at'))
            ->hasYear(2013)
            ->boolean($fixture->get('yes'))
            ->isFalse()
            ->object($mapper->clear())
            ->object($mapper->fetch($fixture, ['some_id', 'created_at']))
            ->isInstanceOf('PommProject\ModelManager\Test\Fixture\ComplexFixture')
            ->isIdenticalTo($fixture)
            ->object($mapper->fetch(new ComplexFixture(['created_at' => new \DateTime("2013-10-30 10:13:56.420342+00"), 'some_id' => 1, 'yes' => true ]), ['some_id', 'created_at']))
            ->isIdenticalTo($fixture)
            ->boolean($fixture->get('yes'))
            ->isTrue()
            ;
    }
}
