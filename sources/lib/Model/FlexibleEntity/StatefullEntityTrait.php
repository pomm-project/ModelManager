<?php
/*
 * This file is part of the PommProject/ModelManager package.
 *
 * (c) 2014 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager\Model\FlexibleEntity;

use PommProject\ModelManager\Model\FlexibleEntity\FlexibleEntityInterface;

/**
 * StatefullEntityTrait
 *
 * Entities with the ability to keep record of their modification or
 * persistence status.
 *
 * @package ModelManager
 * @copyright 2014-2015 Grégoire HUBERT
 * @author Grégoire HUBERT
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 * @see FlexibleEntityInterface
 */
trait StatefullEntityTrait
{
    private $status = FlexibleEntityInterface::STATUS_NONE;

    /**
     * @see FlexibleEntityInterface
     */
    public function status($status = null)
    {
        if ($status !== null) {
            $this->status = $status;

            return $this;
        }

        return $this->status;
    }

    /**
     * touch
     *
     * Set the entity as modified.
     *
     * @access public
     * @return FlexibleEntityInterface
     */
    public function touch()
    {
        $this->status = $this->status | FlexibleEntityInterface::STATUS_MODIFIED;

        return $this;
    }
}
