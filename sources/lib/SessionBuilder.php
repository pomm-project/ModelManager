<?php
/*
 * This file is part of the PommProject/ModelManager package.
 *
 * (c) 2014 - 2015 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager;

use PommProject\Foundation\Client\ClientHolder;
use PommProject\Foundation\Session as FoundationSession;
use PommProject\Foundation\Session\Connection;
use PommProject\Foundation\Session\Session;
use PommProject\Foundation\SessionBuilder as FoundationSessionBuilder;
use PommProject\ModelManager\Model\ModelPooler;
use PommProject\ModelManager\ModelLayer\ModelLayerPooler;
use PommProject\ModelManager\Session as ModelManagerSession;

/**
 * SessionBuilder
 *
 * Session builder for the ModelManager package.
 *
 * @package   ModelManager
 * @copyright 2014 - 2015 Grégoire HUBERT
 * @author    Grégoire HUBERT
 * @license   X11 {@link http://opensource.org/licenses/mit-license.php}
 * @see       FoundationSessionBuilder
 */
class SessionBuilder extends FoundationSessionBuilder
{
    /**
     * postConfigure
     *
     * Register ModelManager's poolers.
     *
     * @access protected
     * @param  Session          $session
     * @return SessionBuilder
     */
    protected function postConfigure(Session $session)
    {
        parent::postConfigure($session);
        $session
            ->registerClientPooler(new ModelPooler)
            ->registerClientPooler(new ModelLayerPooler)
            ;

        return $this;
    }


    /**
     * createSession
     *
     * @param Connection   $connection
     * @param ClientHolder $client_holder
     * @param null|string  $stamp
     * @return  ModelManagerSession
     * @see     VanillaSessionBuilder
     */
    protected function createSession(Connection $connection, ClientHolder $client_holder, $stamp)
    {
        $this->configuration->setDefaultValue('class:session', '\PommProject\ModelManager\Session');

        return parent::createSession($connection, $client_holder, $stamp);
    }
}
