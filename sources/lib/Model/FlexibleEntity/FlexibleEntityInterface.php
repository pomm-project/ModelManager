<?php
/*
 * This file is part of the PommProject/ModelManager package.
 *
 * (c) 2014 - 2015 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager\Model\FlexibleEntity;

/**
 * FlexibleEntityInterface
 *
 * @package   ModelManager
 * @copyright 2014 - 2015 Grégoire HUBERT
 * @author    Grégoire HUBERT
 * @license   X11 {@link http://opensource.org/licenses/mit-license.php}
 */
interface FlexibleEntityInterface
{
    /*
     * These constants reflect the status of the entity.
     *
     * When status is NONE, the entity neither exists in the database nor has
     * been modified since creation.
     *
     * When status is EXIST, the entity exists in the database.
     *
     * When status is MODIFIED, the entity has been modified since creation or
     * last persist operation.
     */
    const STATUS_NONE     = 0;
    const STATUS_EXIST    = 1;
    const STATUS_MODIFIED = 2;

    /**
     * hydrate
     *
     * Set raw values in an entity. If some values are already set, they are
     * overridden with new values.
     *
     * @access public
     * @param  array    $fields
     * @return FlexibleEntityInterface
     */
    public function hydrate(array $fields);

    /**
     * fields
     *
     * Return an array of entity raw values. An optional array can be passed
     * with the list of fields to retrieve. If the array is null, all fields
     * are returned. The case when a given field does not exist is left as
     * one's choice.
     *
     * @access public
     * @param  array    $fields
     * @return array
     */
    public function fields(array $fields = null);

    /**
     * extract
     *
     * Return an array with a representation of the object values. It is mostly
     * used prior to a serialization in REST API or other string responses.
     *
     * @access public
     * @return array
     */
    public function extract();

    /**
     * status
     *
     * Return or set the current status of the instance. The status is a
     * bitmask of the different possible states an entity can have.
     * Status can be
     * FlexibleEntityInterface::STATUS_NONE     = 0,
     * FlexibleEntityInterface::STATUS_EXIST    = 1
     * FlexibleEntityInterface::STATUS_MODIFIED = 2
     * STATUS_EXIST + STATUS_MODIFIED           = 3
     * @see https://github.com/pomm-project/ModelManager/issues/46#issuecomment-130650107
     *
     * If a status is specified, it sets the current entity's status and
     * returns itself. If no status are provided, it returns the current
     * status.
     *
     * @access public
     * @param  int (null)
     * @return int|FlexibleEntityInterface
     */
    public function status($status = null);
}
