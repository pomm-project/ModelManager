<?php
/*
 * This file is part of the PommProject/ModelManager package.
 *
 * (c) 2014 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager\Test\Unit;

use PommProject\Foundation\Session\Session;
use PommProject\ModelManager\Tester\ModelSessionAtoum;
use PommProject\ModelManager\Test\Fixture\ComplexNumber;
use PommProject\ModelManager\Test\Fixture\ComplexFixture;
use PommProject\ModelManager\Test\Fixture\ComplexNumberStructure;
use PommProject\ModelManager\Test\Fixture\ComplexFixtureStructure;

abstract class BaseTest extends ModelSessionAtoum
{
    protected function initializeSession(Session $session)
    {
    }

    /**
     * Because newTestedInstance cannot be called in setUp or tearDown methods,
     * we must set up session manually in test methods.
     */
    protected function setUpSession(Session $session)
    {
        $session
            ->getPoolerForType('converter')
            ->getConverterHolder()
            ->registerConverter(
                'ComplexNumber',
                $this->newTestedInstance(
                    'PommProject\ModelManager\Test\Fixture\ComplexNumber',
                    new ComplexNumberStructure()
                ),
                ['pomm_test.complex_number']
            )
            ->registerConverter(
                'ComplexFixture',
                $this->newTestedInstance(
                    '\PommProject\ModelManager\Test\Fixture\ComplexFixture',
                    new ComplexFixtureStructure()
                ),
                ['complex_fixture']
            )
            ;

        return $session;
    }

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
        $this
            ->buildSession()
            ->getConnection()
            ->executeAnonymousQuery('drop schema if exists pomm_test cascade')
            ;
    }
}
