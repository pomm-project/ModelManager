<?php
/*
 * This file is part of the PommProject/ModelManager package.
 *
 * (c) 2014 - 2015 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager\Test\Fixture;

use PommProject\ModelManager\Model\RowStructure as PommRowStructure;

class ChuStructure extends PommRowStructure
{
    public function __construct()
    {
        $this->relation                 = 'chu';
        $this->field_definitions['chu'] = 'bool';
        $this->primary_key              = ['chu'];
    }
}
