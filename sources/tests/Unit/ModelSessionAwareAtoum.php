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

use PommProject\Foundation\Test\Unit\Converter\BaseConverter;
use PommProject\ModelManager\SessionBuilder;

class ModelSessionAwareAtoum extends BaseConverter
{
    protected function createSessionBuilder($configuration)
    {
        return new SessionBuilder($configuration);
    }
}
