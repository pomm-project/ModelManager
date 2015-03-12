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

use PommProject\ModelManager\Model\FlexibleEntity\FlexibleEntityInterface;
use PommProject\ModelManager\Exception\ModelException;
use PommProject\ModelManager\Converter\PgEntity;
use PommProject\Foundation\Client\ClientInterface;
use PommProject\Foundation\Session\Session;

/**
 * Model
 *
 * Base class for custom Model classes.
 *
 * @abstract
 * @package Pomm
 * @copyright 2014 Grégoire HUBERT
 * @author Grégoire HUBERT
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 * @see ClientInterface
 * @abstract
 */
abstract class Model implements ClientInterface
{
    protected $session;
    protected $flexible_entity_class;
    protected $structure;

    /**
     * getSession
     *
     * Return the current session. If session is not set, a ModelException is
     * thrown.
     *
     * @access public
     * @return Session
     */
    public function getSession()
    {
        if ($this->session === null) {
            throw new ModelException(sprintf("Model class '%s' is not registered against the session.", get_class($this)));
        }

        return $this->session;
    }

    /**
     * getClientType
     *
     * @see ClientInterface
     */
    public function getClientType()
    {
        return 'model';
    }

    /**
     * getClientIdentifier
     *
     * @see ClientInterface
     */
    public function getClientIdentifier()
    {
        return trim(get_class($this), "\\");
    }

    /**
     * initialize
     *
     * @see ClientInterface
     */
    public function initialize(Session $session)
    {
        $this->session = $session;

        if ($this->structure === null) {
            throw new ModelException(sprintf("Structure not set while initializing Model class '%s'.", get_class($this)));
        }

        if ($this->flexible_entity_class == null) {
            throw new ModelException(sprintf("Flexible entity not set while initializing Model class '%s'.", get_class($this)));
        } elseif (!(new \ReflectionClass($this->flexible_entity_class))
            ->implementsInterface('\PommProject\ModelManager\Model\FlexibleEntity\FlexibleEntityInterface')
        ) {
            throw new ModelException(sprintf("Flexible entity must implement FlexibleEntityInterface."));
        }

        $session->getPoolerForType('converter')
            ->getConverterHolder()
            ->registerConverter(
                $this->flexible_entity_class,
                new PgEntity(
                    $this->flexible_entity_class,
                    $this->getStructure()
                ),
                [
                    $this->getStructure()->getRelation(),
                    $this->flexible_entity_class,
                ]
        );
    }

    /**
     * shutdown
     *
     * @see ClientInterface
     */
    public function shutdown()
    {
    }

    /**
     * createEntity
     *
     * Create a new entity.
     *
     * @access public
     * @param array $values
     * @return FlexibleEntityInterface
     */
    public function createEntity(array $values = [])
    {
        $class_name = $this->getFlexibleEntityClass();

        return (new $class_name)
            ->hydrate($values)
            ;
    }

    /**
     * getStructure
     *
     * Return the structure.
     *
     * @access public
     * @return RowStructure
     */
    public function getStructure()
    {
        return $this->structure;
    }

    /**
     * query
     *
     * Execute the given query and return a Collection iterator on results. If
     * no projections are passed, it will use the default projection using
     * createProjection() method.
     *
     * @access protected
     * @param  sql                $sql
     * @param  array              $values
     * @param  Projection         $projection
     * @return CollectionIterator
     */
    protected function query($sql, array $values = [], Projection $projection = null)
    {
        if ($projection === null) {
            $projection = $this->createProjection();
        }

        $result = $this
            ->GetSession()
            ->getClientUsingPooler('prepared_query', $sql)
            ->execute($values)
            ;

        $collection = new CollectionIterator(
            $result,
            $this->getSession(),
            $projection
        );

        return $collection;
    }

    /**
     * createDefaultProjection
     *
     * This method creates a projection based on the structure definition of
     * the underlying relation. It may be used to shunt parent createProjection
     * call in inherited classes.
     * This method SHOULD NOT be used in query methods as it is not possible to
     * overload it, use createProjection instead.
     *
     * @access protected
     * @return Projection
     */
    final protected function createDefaultProjection()
    {
        return new Projection($this->flexible_entity_class, $this->structure->getDefinition());
    }

    /**
     * createProjection
     *
     * This is a helper to create a new projection according to the current
     * structure.Overriding this method will change projection for all models.
     *
     * @access  protected
     * @return  Projection
     */
    protected function createProjection()
    {
        return $this->createDefaultProjection();
    }

    /**
     * checkFlexibleEntity
     *
     * Check if the given entity is an instance of this model's flexible class.
     * If not an exception is thrown.
     *
     * @access protected
     * @param  FlexibleEntityInterface $entity
     * @throw  InvalidArgumentException
     * @return Model          $this
     */
    protected function checkFlexibleEntity(FlexibleEntityInterface $entity)
    {
        if (!($entity instanceof $this->flexible_entity_class)) {
            throw new \InvalidArgumentException(sprintf(
                "Entity class '%s' is not a '%s'.",
                get_class($entity),
                $this->flexible_entity_class
            ));
        }

        return $this;
    }

    /**
     * getFlexibleEntityClass
     *
     * Return the according flexible entity class associate with this Model
     * instance.
     *
     * @access protected
     * @return string
     */
    protected function getFlexibleEntityClass()
    {
        return $this->flexible_entity_class;
    }

    /**
     * escapeLiteral
     *
     * Handy method to escape strings.
     *
     * @access protected
     * @param  string $string
     * @return string
     */
    protected function escapeLiteral($string)
    {
        return $this
            ->getSession()
            ->getConnection()
            ->escapeLiteral($string);
    }

    /**
     * escapeLiteral
     *
     * Handy method to escape strings.
     *
     * @access protected
     * @param  string $string
     * @return string
     */
    protected function escapeIdentifier($string)
    {
        return $this
            ->getSession()
            ->getConnection()
            ->escapeIdentifier($string);
    }

    /**
     * executeAnonymousQuery
     *
     * Handy method for DDL statments.
     *
     * @access protected
     * @param  string $sql
     * @return Model  $this
     */
    protected function executeAnonymousQuery($sql)
    {
        $this
            ->getSession()
            ->getConnection()
            ->executeAnonymousQuery($sql);

        return $this;
    }
}
