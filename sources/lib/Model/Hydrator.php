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

use PommProject\Foundation\Client\Client;
use PommProject\Foundation\Client\ClientInterface;

/**
 * Hydrator
 *
 * How to hydrate and cache FlexibleEntity instances.
 *
 * @package ModelManager
 * @copyright 2014 Grégoire HUBERT
 * @author Grégoire HUBERT
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 * @see Client
 */
class Hydrator extends Client
{
    protected $model_class ;
    protected $entity_class;
    protected $primary_key;
    protected $identity_mapper;

    /**
     * __construct
     *
     * Constructor
     *
     * @access public
     * @param  string $model_class
     * @param  string $entity_class
     * @param  array  $primary_key
     * @return void
     */
    public function __construct($model_class, $entity_class, array $primary_key = [], IdentityMapper $identity_mapper = null)
    {
        $this->model_class     = $model_class;
        $this->entity_class    = $entity_class;
        $this->primary_key     = $primary_key;
        $this->identity_mapper = $identity_mapper !== null ? $identity_mapper : new IdentityMapper();
    }

    /**
     * @see ClientInterface
     */
    public function getClientType()
    {
        return 'hydrator';
    }

    /**
     * @see ClientInterface
     */
    public function getClientIdentifier()
    {
        return $this->model_class;
    }

    /**
     * shutdown
     *
     * @see ClientInterface
     */
    public function shutdown()
    {
        $this->identity_mapper = null;
    }

    /**
     * hydrate
     *
     * Take values fetched from the database, launch conversion system and
     * hydrate the FlexibleEntity through the mapper.
     *
     * @access public
     * @param  HydrationPlan  $hydration_plan
     * @return FlexibleEntity
     */
    public function hydrate(HydrationPlan $hydration_plan)
    {
        $values = [];
        foreach ($hydration_plan->getIterator() as $field_name => $value) {
            if ($hydration_plan->isArray($field_name)) {
                $values[$field_name] = $this
                    ->getSession()
                    ->getClientUsingPooler('converter', 'array')
                    ->fromPg($value, $hydration_plan->getFieldType($field_name))
                    ;
            } else {
                $values[$field_name] = $this
                    ->getSession()
                    ->getClientUsingPooler('converter', $hydration_plan->getFieldType($field_name))
                    ->fromPg($value)
                    ;
            }
        }

        return $this->createEntity($values);
    }

    /**
     * createEntity
     *
     * Deal with the mapper to create or not the entity.
     *
     * @access protected
     * @param  array          $values
     * @return FlexibleEntity
     */
    protected function createEntity(array $values)
    {
        $class_name = $this->entity_class;

        return $this
            ->identity_mapper
            ->fetch(new $class_name($values), $this->primary_key)
            ;
    }
}
