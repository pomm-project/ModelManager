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

/**
 * IdentityMapper
 *
 * Cache for FlexibleEntityInterface instances to ensure there are no different
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
     * @param  FlexibleEntityInterface  $entity
     * @param  array                    $primary_key
     * @return string
     */
    public static function getSignature(FlexibleEntityInterface $entity, array $primary_key)
    {
        if (count($primary_key) === 0) {
            return null;
        }

        return sha1(sprintf("%s|%s", serialize($entity->fields($primary_key)), get_class($entity)));
    }

    /**
     * fetch
     *
     * Pool FlexibleEntityInterface instances and update them if necessary.
     *
     * @access public
     * @param  FlexibleEntityInterface  $entity
     * @param  array                    $primary_key
     * @return FlexibleEntityInterface
     */
    public function fetch(FlexibleEntityInterface $entity, array $primary_key)
    {
        $signature = self::getSignature($entity, $primary_key);

        if ($signature === null) {
            return $entity;
        }

        if (!array_key_exists($signature, $this->instances)) {
            $this->instances[$signature] = $entity;
            $entity->status(FlexibleEntityInterface::STATUS_EXIST);
        } else {
            $this->instances[$signature]->hydrate($entity->fields());
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
