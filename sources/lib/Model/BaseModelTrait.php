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

use PommProject\ModelManager\Model\Model;

/**
 * BaseModelTrait
 *
 * Abstract methods for Model traits.
 *
 * @package ModelManager
 * @copyright 2014 Grégoire HUBERT
 * @author Grégoire HUBERT
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
trait BaseModelTrait
{
    /**
     * @see Model
     */
    abstract public function createProjection();

    /**
     * @see Model
     */
    abstract public function getRelation();

    /**
     * @see Model
     */
    abstract protected function query($sql, array $values = [], Projection $projection = null);

    /**
     * @see Model
     */
    abstract protected function getSession();

    /**
     * @see Model
     */
    abstract public function getStructure();
}
