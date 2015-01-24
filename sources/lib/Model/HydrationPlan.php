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
class HydrationPlan
{
    protected $session;
    protected $projection;
    protected $converters = [];

    /**
     * __construct
     *
     * Construct
     *
     * @access public
     * @param  Projection $projection
     * @return void
     */
    public function __construct(Projection $projection, Session $session)
    {
        $this->projection = $projection;
        $this->session    = $session;

        $this->loadConverters();
    }

    /**
     * loadConverters
     *
     * Cache converters needed for this result set.
     *
     * @access protected
     * @return HydrationPlan    $this
     */
    protected function loadConverters()
    {
        foreach ($this->projection as $name => $type) {
            if ($this->projection->isArray($name)) {
                $this->converters[$name] = $this
                    ->session
                    ->getClientUsingPooler('converter', 'array')
                    ;
            } else {
                $this->converters[$name] = $this
                    ->session
                    ->getClientUsingPooler('converter', $type)
                    ;
            }
        }

        return $this;
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
     * @return FlexibleEntityInterface
     */
    public function hydrate(array $values)
    {
        $values = $this->convert('fromPg', $values);

        return $this->createEntity($values);
    }

    /**
     * dry
     *
     * Return values converted to Pg.
     *
     * @access public
     * @param  array    values
     * @return array
     */
    public function dry(array $values)
    {
        return $this->convert('toPg', $values);
    }

    /**
     * convert
     *
     * Convert values from / to postgres.
     *
     * @access protected
     * @param  string   $from_to
     * @param  array    $values
     * @return array
     */
    protected function convert($from_to, array $values)
    {
        $out_values = [];

        foreach ($values as $field_name => $value) {
            $out_values[$field_name] = $this->converters[$field_name]
                ->$from_to($value, $this->getFieldType($field_name))
                ;
        }

        return $out_values;
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

    /**
     * getConverterForField
     *
     * Return the converter client associated with a field.
     *
     * @access public
     * @param  string $name
     * @return ConverterClient
     */
    public function getConverterForField($field_name)
    {
        if (!isset($this->converters[$field_name])) {
            throw new \RuntimeException(
                sprintf(
                    "Error, '%s' field has no converters registered. Fields are {%s}.",
                    $field_name,
                    join(', ', array_keys($this->converters))
                )
            );
        }

        return $this->converters[$field_name];
    }
}
