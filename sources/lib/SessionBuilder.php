<?php
/*
 * This file is part of the PommProject/ModelManager package.
 *
 * (c) 2014 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager;

use PommProject\Foundation\Session\Session;
use PommProject\Foundation\SessionBuilder as FoundationSessionBuilder;

use PommProject\ModelManager\Model\ModelPooler;
use PommProject\ModelManager\ModelLayer\ModelLayerPooler;

/**
 * SessionBuilder
 *
 * Session builder for the ModelManager package.
 *
 * @package ModelManager
 * @copyright 2014 Grégoire HUBERT
 * @author Grégoire HUBERT
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 * @see FoundationSessionBuilder
 */
class SessionBuilder extends FoundationSessionBuilder
{
    protected function postConfigure(Session $session)
    {
        parent::postConfigure($session);
        $session
            ->registerClientPooler(new ModelPooler)
            ->registerClientPooler(new ModelLayerPooler)
            ;

        return $this;
    }
}
