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

use PommProject\ModelManager\Exception\ModelException;
use PommProject\Foundation\Client\ClientInterface;
use PommProject\Foundation\Session;

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
     * Return the current session.
     *
     * @access protected
     * @return Session
     */
    protected function getSession()
    {
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
        return preg_replace('/Model$/', '', get_class($this));
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
        }
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
     * query
     *
     * Execute the given query and return a Collection iterator on results. If
     * no projections are passed, it will use the default projection using
     * createProjection() method.
     *
     * @access protected
     * @param  sql        $sql
     * @param  array      $values
     * @param  Projection $projection
     * @return CollectionIterator
     */
    protected function query($sql, array $values = [], Projection $projection = null)
    {
        return new CollectionIterator(
            $this
                ->session
                ->getClientUsingPooler('prepared_statement', $sql)
                ->execute($values),
            $this->session,
            $projection === null ? $this->createProjection() : $projection,
            $this->flexible_entity_class
        );
    }

    /**
     * getPrimaryKey
     *
     * Proxy method to RowStructure::getPrimaryKey()
     *
     * @see RowStructure
     */
    public function getPrimaryKey()
    {
        return $this->structure->getPrimaryKey();
    }

    /**
     * getRelation
     *
     * Proxy method to RowStructure::getRelationName()
     *
     * @see RowStructure
     */
    public function getRelation()
    {
        return $this->structure->getRelation();
    }

    /**
     * createProjection
     *
     * This is a helper to create a new projection according to the current
     * structure.Overriding this method will change projection for all models.
     *
     * @access  public
     * @param   array      $tructure
     * @return  Projection
     */
    public function createProjection()
    {
        return new Projection($this->structure->getDefinition());
    }
}
