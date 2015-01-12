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
use PommProject\Foundation\Session\Session;

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
    protected $projection;

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
        return $this->projection->isArray($name);
    }


    /**
     * hydrate
     *
     * Take values fetched from the database, launch conversion system and
     * hydrate the FlexibleEntityInterface through the mapper.
     *
     * @access public
     * @param  Session          $session
     * @return FlexibleEntityInterface
     */
    public function hydrate(Session $session)
    {
        $values = $this->convert('fromPg', $session);

        return $this->createEntity($values);
    }

    /**
     * dry
     *
     * Return values converted to Pg.
     *
     * @access public
     * @param  Session $session
     * @return array
     */
    public function dry(Session $session)
    {
        return $this->convert('toPg', $session);
    }

    /**
     * convert
     *
     * Convert values from / to postgres.
     *
     * @access protected
     * @param  string   $from_to
     * @param  Session  $session
     * @return array
     */
    protected function convert($from_to, Session $session)
    {
        $values = [];
        foreach ($this->getIterator() as $field_name => $value) {
            if ($this->isArray($field_name)) {
                $values[$field_name] = $session
                    ->getClientUsingPooler('converter', 'array')
                    ->$from_to($value, $this->getFieldType($field_name))
                    ;
            } else {
                $values[$field_name] = $session
                    ->getClientUsingPooler('converter', $this->getFieldType($field_name))
                    ->$from_to($value)
                    ;
            }
        }

        return $values;
    }

    /**
     * createEntity
     *
     * Instanciate FlexibleEntityInterface from converted values.
     *
     * @access protected
     * @param  array $values
     * @return FlexibleEntityInterface
     */
    protected function createEntity(array $values)
    {
        $class = $this->projection->getFlexibleEntityClass();

        return (new $class())
            ->hydrate($values)
            ;
    }
}
