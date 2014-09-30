<?php
/*
 * This file is part of the PommProject/ModelManager package.
 *
 * (c) 2014 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager\Test\Unit\Model;

use Atoum;
use PommProject\Foundation\DatabaseConfiguration;
use Mock\PommProject\Foundation\Session;

class ModelPooler extends Atoum
{
    protected $session;

    protected function getSession()
    {
        if ($this->session === null) {
            $this->session = new Session(new DatabaseConfiguration($GLOBALS['pomm_db1']));
        }

        return $this->session;
    }

    protected function getClientPooler()
    {
        $pooler = $this->newTestedInstance();
        $this->getSession()->registerClientPooler($pooler);

        return $pooler;
    }

    public function testGetPoolerType()
    {
        $this
            ->string($this->getClientPooler()->getPoolerType())
            ->isEqualTo('model')
            ;
    }

    public function testGetClient()
    {
        $client_pooler = $this->getClientPooler();
        $session = $this->getSession();

        $this
            ->assert('Client is not in the ClientHolder.')
            ->object($client_pooler->getClient('\PommProject\ModelManager\Test\Fixture\SimpleFixture'))
            ->isInstanceOf('\PommProject\ModelManager\Test\Fixture\SimpleFixtureModel')
            ->mock($session)
            ->call('getClient')
            ->withArguments('model', 'PommProject\ModelManager\Test\Fixture\SimpleFixture')
            ->once()
            ->call('registerClient')
            ->once()
            ->assert('Client should be in the ClientHolder now.')
            ->object($client_pooler->getClient('\PommProject\ModelManager\Test\Fixture\SimpleFixture'))
            ->isInstanceOf('\PommProject\ModelManager\Test\Fixture\SimpleFixtureModel')
            ->mock($session)
            ->call('getClient')
            ->withArguments('model', 'PommProject\ModelManager\Test\Fixture\SimpleFixture')
            ->call('registerClient')
            ->never()
            ;
    }
}
