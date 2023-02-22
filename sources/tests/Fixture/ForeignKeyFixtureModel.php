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

use PommProject\Foundation\Session\Session;
use PommProject\ModelManager\Model\ModelTrait\WriteQueries;

class ForeignKeyFixtureModel extends WriteFixtureModel
{
    use WriteQueries;

    public function __construct()
    {
        $this->structure = new ForeignKeyFixtureStructure();
        $this->flexible_entity_class = '\PommProject\ModelManager\Test\Fixture\ForeignKeyFixture';
        $this->getStructure()->setRelation('foreign_key_fixture');
    }

    protected function createTable()
    {
        $this->executeAnonymousQuery(
            sprintf(
                "create temporary table %s (id serial primary key, truncate_id int, CONSTRAINT fk_truncate FOREIGN KEY(truncate_id) REFERENCES truncate_fixture(id))",
                $this->getStructure()->getRelation()
            )
        );

        return $this;
    }
}
