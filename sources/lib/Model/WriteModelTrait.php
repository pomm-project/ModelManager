<?php
/*
 * This file is part of the PommProject/ModelManager package.
 *
 * (c) 2014 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager\Model;

use PommProject\ModelManager\Model\BaseModelTrait;
use PommProject\ModelManager\Model\Model;
use PommProject\Foundation\Where;

/**
 * ReadModelTrait
 *
 * Basic read queries for model instances.
 *
 * @package ModelManager
 * @copyright 2014 Grégoire HUBERT
 * @author Grégoire HUBERT
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
trait WriteModelTrait
{
    use BaseModelTrait;

    public function insertOne(FlexibleEntity &$entity)
    {
        $values = [];

        foreach($this->getStructure()->getDefinition() as $name => $type) {
            if ($entity->has($name)) {
                $values[$name] = $this
                    ->getSession()
                    ->getClientUsingPooler('converter', $type)
                    ->toPg($entity->get($name), $type, $this->getSession())
                    ;
            }
        }

        $sql = strtr(
            "insert into :relation (:fields) values (%s) returning :projection",
            [
                ':relation'   => $this->getRelation(),
                ':fields'     => join(', ', array_keys($values)),
                ':projection' => $this->createProjection()->formatFieldsWithFieldAlias(),
            ]);

        $sql = sprintf($sql, join(', ', $values));
        $entity = $this->query($sql)->current();
        $entity->status(FlexibleEntity::EXIST);

        return $this;
    }
}
