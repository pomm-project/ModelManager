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
 * HydrationPlan
 *
 * Tell the FlexibleEntityConverter how to hydrate fields.
 *
 * @package ModelManager
 * @copyright 2014 Grégoire HUBERT
 * @author Grégoire HUBERT
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 *
 *
 * @see \IteratorAggregate
 */
class HydrationPlan implements \IteratorAggregate
{
    protected $values = [];
    protected $projection = [];

    /**
     * __construct
     *
     * Construct
     *
     * @access public
     * @param  Projection $projection
     * @return void
     */
    public function __construct(Projection $projection, array $values)
    {
        $this->projection = $projection;
        $this->values     = $values;
    }

    /**
     * see \IteratorAggregate
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->values);
    }

    /**
     * getFieldType
     *
     * Return the type of the given field. Proxy to Projection::getFieldType().
     *
     * @access public
     * @param  string $name
     * @return string
     */
    public function getFieldType($name)
    {
        return $this->projection->getFieldType($name);
    }

    /**
     * isArray
     *
     * Tell if the given field is an array or not.
     *
     * @access public
     * @param  string $name
     * @return bool
     */
    public function isArray($name)
    {
        return (bool) preg_match('/\[\]$/', $this->getFieldType($name));
    }
}
