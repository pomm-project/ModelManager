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

use PommProject\ModelManager\Model\FlexibleEntity;
use PommProject\Foundation\Inflector;

class SimpleFixture extends FlexibleEntity
{
    public function extract()
    {
        $fields = parent::extract();
        $new_fiels = [];

        foreach ($fields as $name => $value) {
            $new_fiels[Inflector::studlyCaps($name)] = $value;
        }

        return $new_fiels;
    }
}
