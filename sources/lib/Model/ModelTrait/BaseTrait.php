<?php
/*
 * This file is part of the PommProject/ModelManager package.
 *
 * (c) 2014 - 2015 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager\Model\ModelTrait;

use PommProject\ModelManager\Model\CollectionIterator;
use PommProject\ModelManager\Model\Projection;
use PommProject\ModelManager\Model\RowStructure;
use PommProject\ModelManager\Session;

/**
 * BaseModelTrait
 *
 * Abstract methods for Model traits.
 *
 * @package   ModelManager
 * @copyright 2014 - 2015 Grégoire HUBERT
 * @author    Grégoire HUBERT
 * @license   X11 {@link http://opensource.org/licenses/mit-license.php}
 */
trait BaseTrait
{
    /**
     * @see Model
     * @return Projection
     */
    abstract public function createProjection();

    /**
     * @see Model
     * @return CollectionIterator
     */
    abstract protected function query($sql, array $values = [], Projection $projection = null);

    /**
     * @see Model
     * @return Session
     */
    abstract protected function getSession();

    /**
     * @see Model
     * @return RowStructure

     */
    abstract public function getStructure();

    /**
     * @see Model
     */
    abstract public function getFlexibleEntityClass();

    /**
     * @see Model
     */
    abstract public function escapeLiteral($string);

    /**
     * @see Model
     */
    abstract public function escapeIdentifier($string);

    /**
     * @see Model
     */
    abstract public function executeAnonymousQuery($sql);

    /**
     * @see Model
     */
    abstract public function createEntity(array $values = []);
}
