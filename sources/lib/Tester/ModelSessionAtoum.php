<?php
/*
 * This file is part of the PommProject/ModelManager package.
 *
 * (c) 2014 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager\Tester;

use PommProject\Foundation\Tester\FoundationSessionAtoum;
use PommProject\ModelManager\SessionBuilder;

/**
 * ModelSessionAwareAtoum
 *
 * Session aware Atoum instance. This uses ModelManager's session builder to
 * ensiure all poolers are loaded.
 *
 * @package ModelManager
 * @copyright 2014 Grégoire HUBERT
 * @author Grégoire HUBERT
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 * @see FoundationSessionAtoum
 */
abstract class ModelSessionAtoum extends FoundationSessionAtoum
{
    protected function createSessionBuilder(array $configuration)
    {
        return new SessionBuilder($configuration);
    }
}

