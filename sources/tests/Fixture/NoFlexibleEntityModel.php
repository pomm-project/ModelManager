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

use PommProject\ModelManager\Model\Model as PommModel;
use Mock\PommProject\ModelManager\Model\RowStructure as RowStructureMock;

class NoFlexibleEntityModel extends PommModel
{
    public function __construct()
    {
        $this->structure = new RowStructureMock();
    }
}
