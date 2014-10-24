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

use PommProject\Foundation\Client\Client;
use PommProject\Foundation\Session\Session;
use PommProject\Foundation\Exception\SqlException;

class ModelSchemaClient extends Client
{
    public function getClientType()
    {
        return 'test';
    }

    public function getClientIdentifier()
    {
        return 'complex_fixture';
    }

    public function initialize(Session $session)
    {
        parent::initialize($session);

        $this->createSchema();
    }

    public function shutdown()
    {
        $this->dropSchema();
    }

    public function createSchema()
    {
        $sql =
            [
                "drop schema if exists pomm_test cascade",
                "begin",
                "create schema pomm_test",
                "create type pomm_test.complex_number as (real float8, imaginary float8)",
                "commit",
            ];

        try {
            foreach ($sql as $stmt) {
                $this->executeSql($stmt);
            }
        } catch (SqlException $e) {
            $this->executeSql('rollback');
            throw $e;
        }

        return $this;
    }

    public function dropSchema()
    {
        $sql = "drop schema if exists pomm_test cascade";
        $this->executeSql($sql);

        return $this;
    }

    protected function executeSql($sql)
    {
        return $this
            ->getSession()
            ->getConnection()
            ->executeAnonymousQuery($sql)
            ;
    }
}
