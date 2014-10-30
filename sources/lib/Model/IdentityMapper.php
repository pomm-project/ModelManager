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

/**
 * IdentityMapper
 *
 * Cache for FlexibleEntity instances to ensure there are no different
 * instances for the same data.
 *
 * @package ModelManager
 * @copyright 2014 Grégoire HUBERT
 * @author Grégoire HUBERT
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class IdentityMapper
{
    protected $instances = [];

    /**
     * getSignature
     *
     * Compute a unique signature upon entity's values in its primary key. If
     * an empty primary key is provided, null is returned.
     *
     * @static
     * @access public
     * @param  FlexibleEntity $entity
     * @param  array          $primary_key
     * @return string
     */
    public static function getSignature(FlexibleEntity $entity, array $primary_key)
    {
        if (count($primary_key) === 0) {
            return null;
        }

        return sha1(sprintf("%s|%s", serialize($entity->get($primary_key)), get_class($entity)));
    }

    /**
     * fetch
     *
     * Pool FlexibleEntity instances and update them if necessary.
     *
     * @access public
     * @param  FlexibleEntity $entity
     * @param  array          $primary_key
     * @return FlexibleEntity
     */
    public function fetch(FlexibleEntity $entity, array $primary_key)
    {
        $signature = self::getSignature($entity, $primary_key);

        if ($signature === null) {
            return $entity;
        }

        if (!array_key_exists($signature, $this->instances)) {
            $this->instances[$signature] = $entity;
            $entity->status(FlexibleEntity::EXIST);
        } else {
            $this->instances[$signature]->hydrate($entity->extract());
        }

        return $this->instances[$signature];
    }

    /**
     * clear
     *
     * Flush instances from the identity mapper.
     *
     * @access public
     * @return IdentityMapper $this
     */
    public function clear()
    {
        $this->instances = [];

        return $this;
    }
}
