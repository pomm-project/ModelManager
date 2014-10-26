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

use PommProject\ModelManager\Model\RowStructure;

class ComplexNumberStructure extends RowStructure
{
    public function __construct()
    {
        $this
            ->setRelation('pomm_test.complex_number')
            ->addField('real', 'float8')
            ->addField('imaginary', 'float8')
            ;
    }
}

