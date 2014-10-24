<?php
/*
 * This file is part of the PommProject/ModelManager package.
 *
 * (c) 2014 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager\Test\Unit\Converter;

use PommProject\Foundation\Session\Session;

use PommProject\ModelManager\Model\RowStructure;
use PommProject\ModelManager\Tester\ModelSessionAtoum;
use PommProject\ModelManager\Test\Fixture\ComplexNumber;
use PommProject\ModelManager\Test\Fixture\ComplexNumberStructure;
use PommProject\ModelManager\Test\Fixture\ComplexFixture;
use PommProject\ModelManager\Test\Fixture\ComplexFixtureStructure;

class PgEntity extends ModelSessionAtoum
{
    protected function initializeSession(Session $session)
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
                ['complex_number', 'pomm_test.complex_number']
            )
            ;
    }

    protected function getComplexNumberConverter()
    {
        return $this->newTestedInstance(
            'PommProject\ModelManager\Test\Fixture\ComplexNumber',
            new ComplexNumberStructure()
        );
    }

    protected function getComplexFixtureConverter()
    {
        return $this->newTestedInstance(
            'PommProject\ModelManager\Test\Fixture\ComplexFixture',
            new ComplexFixtureStructure()
        );
    }

    public function testFromPg()
    {
        $entity = $this->getComplexNumberConverter()->fromPg(
                '(1.233,2.344)',
                'complex_number',
                $this->buildSession()
            );

        $this
            ->object($entity)
            ->isInstanceOf('PommProject\ModelManager\Test\Fixture\ComplexNumber')
            ->float($entity['real'])
            ->isEqualTo(1.233)
            ->float($entity['imaginary'])
            ->isEqualTo(2.344)
            ;
    }

    public function testComplexFromPg()
    {
        $converter = $this->getComplexFixtureConverter();
        $session = $this->buildSession();
        $entity = $converter->fromPg(
                '(1,1,"(1.233,2.344)","{""(3.455,4.566)"",""(5.677,6.788)""}","2014-10-24 12:44:40.021324+00","{""1982-04-21 23:12:43+00""}")',
                'complex_fixture',
                $session
            );

        $this
            ->object($entity)
            ->isInstanceOf('PommProject\ModelManager\Test\Fixture\ComplexFixture')
            ->integer($entity['version_id'])
            ->isEqualTo(1)
            ->object($entity['complex_number'])
            ->isInstanceOf('PommProject\ModelManager\Test\Fixture\ComplexNumber')
            ->array($entity['complex_numbers'])
            ->hasSize(2)
            ->variable($converter->fromPg(null, 'complex_fixture', $session))
            ->isNull()
            ;
    }

    public function testToPg()
    {
        $complex_fixture = new ComplexFixture(
            [
                'id' => 1,
                'version_id' => 1,
                'complex_number' => new ComplexNumber(['real' => 1.233,'imaginary' => 2.344]),
                'complex_numbers' =>
                    [
                        new ComplexNumber(['real' => 3.455, 'imaginary' => 4.566]),
                        new ComplexNumber(['real' => 5.677, 'imaginary' => 6.788]),
                    ],
                'created_at' => new \DateTime('2014-10-24 12:44:40.021324+00'),
                'updated_at' => [new \DateTime('1982-04-21 23:12:43+00')]
            ]);

        $converter = $this->getComplexFixtureConverter();
        $session = $this->buildSession();
        $string = $converter->toPg($complex_fixture, 'complex_fixture', $session);

        $this
            ->string($string)
            ->isEqualTo("row(int4 '1',int4 '1',row(float8 '1.233',float8 '2.344')::pomm_test.complex_number,ARRAY[row(float8 '3.455',float8 '4.566')::pomm_test.complex_number,row(float8 '5.677',float8 '6.788')::pomm_test.complex_number]::pomm_test.complex_number[],timestamptz '2014-10-24 12:44:40.021324+00:00',ARRAY[timestamptz '1982-04-21 23:12:43.000000+00:00']::timestamptz[])::complex_fixture")
            ->string($converter->toPg(null, 'complex_fixture', $session))
            ->isEqualTo('NULL::complex_fixture')
            ;
    }
}
