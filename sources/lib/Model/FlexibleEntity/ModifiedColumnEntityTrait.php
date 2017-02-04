<?php
/*
 * This file is part of the PommProject/ModelManager package.
 *
 * (c) 2014 - 2016 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager\Model\FlexibleEntity;

/**
 * ModifiedColumnEntityTrait
 *
 * @package     ModelManager
 * @copyright   2014 - 2015 Grégoire HUBERT
 * @author      Mikael Paris
 * @license     X11 {@link http://opensource.org/licenses/mit-license.php}
 * @see         FlexibleEntityInterface
 */
trait ModifiedColumnEntityTrait
{
    private $modified_columns = [];

    /**
     * @return array
     */
    public function getModifiedColumns()
    {
        return $this->modified_columns;
    }

    /**
     * @param $column
     * @return FlexibleEntityInterface
     */
    public function addModifiedColumn($column)
    {
        if (!in_array($column, $this->modified_columns)) {
            $this->modified_columns[] = $column;
        }

        return $this;
    }

    /**
     * @param $column
     * @return FlexibleEntityInterface
     */
    public function removeModifiedColumn($column)
    {
        $key = array_search($column, $this->modified_columns, true);

        if ($key !== FALSE) {
            unset($this->modified_columns[$key]);
        }

        return $this;
    }
}
