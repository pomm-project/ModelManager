<?php
/*
 * This file is part of the PommProject's ModelManager package.
 *
 * (c) 2014 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager\Model;

use PommProject\Foundation\Session\ResultHandler;
use PommProject\Foundation\Session\Session;
use PommProject\Foundation\ResultIterator;

use PommProject\ModelManager\Converter\PgEntity;
use PommProject\ModelManager\Model\FlexibleEntity\FlexibleEntityInterface;
use PommProject\ModelManager\Exception\ModelException;

/**
 * CollectionIterator
 *
 * Iterator for query results.
 *
 * @package ModelManager
 * @copyright 2014 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license MIT/X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class CollectionIterator extends ResultIterator
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Projection
     */
    protected $projection;

    /**
     * @var array
     */
    protected $filters = [];

    /**
     * @var HydrationPlan
     */
    protected $hydration_plan;

    /**
     * @var PgEntity
     */
    private $entity_converter;

    /**
     * __construct
     *
     * Constructor
     *
     * @access  public
     * @param   ResultHandler   $result
     * @param   Session         $session
     * @param   Projection      $projection
     */
    public function __construct(ResultHandler $result, Session $session, Projection $projection)
    {
        parent::__construct($result);
        $this->projection       = $projection;
        $this->session          = $session;
        $this->hydration_plan   = new HydrationPlan($projection, $session);
        $this->entity_converter = $this
          ->session
          ->getClientUsingPooler('converter', $this->projection->getFlexibleEntityClass())
          ->getConverter()
          ;
    }

    /**
     * get
     *
     * @see     ResultIterator
     * @return  FlexibleEntityInterface
     */
    public function get($index)
    {
        return $this->parseRow(parent::get($index));
    }

    /**
     * parseRow
     *
     * Convert values from Pg.
     *
     * @access  protected
     * @param   array          $values
     * @return  FlexibleEntityInterface
     * @see     ResultIterator
     */
    public function parseRow(array $values)
    {
        $values = $this->launchFilters($values);
        $entity = $this->hydration_plan->hydrate($values);

        return $this->entity_converter->cacheEntity($entity);
    }

    /**
     * launchFilters
     *
     * Launch filters on the given values.
     *
     * @access  protected
     * @param   array $values
     * @throws  ModelException   if return is not an array.
     * @return  array
     */
    protected function launchFilters(array $values)
    {
        foreach ($this->filters as $filter) {
            $values = call_user_func($filter, $values);

            if (!is_array($values)) {
                throw new ModelException(sprintf("Filter error. Filters MUST return an array of values."));
            }
        }

        return $values;
    }

    /**
     * registerFilter
     *
     * Register a new callable filter. All filters MUST return an associative
     * array with field name as key.
     *
     * @access public
     * @param  callable   $callable the filter.
     * @return CollectionIterator $this
     * @throws ModelException
     */
    public function registerFilter($callable)
    {
        if (!is_callable($callable)) {
            throw new ModelException(sprintf(
                "Given filter is not a callable (type '%s').",
                gettype($callable)
            ));
        }

        $this->filters[] = $callable;

        return $this;
    }

    /**
     * clearFilters
     *
     * Empty the filter stack.
     */
    public function clearFilters()
    {
        $this->filters = [];

        return $this;
    }

    /**
     * extract
     *
     * Return an array of entities extractd as arrays.
     *
     * @access public
     * @return array
     */
    public function extract()
    {
        $results = [];

        foreach ($this as $result) {
            $results[] = $result->extract();
        }

        return $results;
    }

    /**
     * slice
     *
     * see @ResultIterator
     *
     * @access public
     * @param  string   $name
     * @return array
     */
    public function slice($name)
    {
      return $this->convertSlice(parent::slice($name), $name);
    }


    /**
     * convertSlice
     *
     * Convert a slice.
     *
     * @access protected
     * @param  array  $values
     * @param  string $name
     * @return array
     */
    protected function convertSlice(array $values, $name)
    {
        $type = $this->projection->getFieldType($name);
        $converter = $this->hydration_plan->getConverterForField($name);

        return array_map(
            function ($val) use ($converter, $type) {
                return $converter->fromPg($val, $type);
            },
            $values
        );
    }

}
