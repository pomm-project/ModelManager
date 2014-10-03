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

use PommProject\ModelManager\Exception\ModelException;
use PommProject\ModelManager\Model\Projection;
use PommProject\Foundation\ResultIterator;
use PommProject\Foundation\ResultHandler;
use PommProject\Foundation\Session;

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
    protected $projection;
    protected $flexible_class_name;
    protected $filters = [];

    /**
     * __construct
     *
     * Constructor
     *
     * @access public
     * @param  ConverterHolder $converter_holder
     * @param  resource        $result_resource
     * @param  Projection      $projection
     * @param  string          $flexible_class_name
     * @return void
     */
    public function __construct(ResultHandler $result, Session $session, Projection $projection, $flexible_class_name)
    {
        $this->projection           = $projection;
        $this->flexible_class_name  = $flexible_class_name;

        parent::__construct($result, $session);
    }

    /**
     * parseRow
     *
     * Convert values from Pg.
     *
     * @access protected
     * @param  array $values
     * @return FlexibleEntity
     * @see    ResultIterator
     */
    public function parseRow(array $values)
    {
        $values = $this->launchFilters($values);
        $values = parent::parseRow($values);
        $class_name = $this->flexible_class_name;

        return new $class_name($values);
    }

    /**
     * getFieldType
     *
     * see @ResultIterator
     */
    protected function getFieldType($name)
    {
        $type = $this->projection->getFieldType($name);

        if ($type !== null) {
            if (preg_match('/^(.+)\[\]$/', $type, $matchs)) {
                $type = sprintf("_%s", $matchs[1]);
            }

            return $type;
        }
    }

    /**
     * launchFilters
     *
     * Launch filters on the given values.
     *
     * @access protected
     * @param  array $values
     * @return array
     */
    protected function launchFilters(array $values)
    {
        foreach($this->filters as $filter) {
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
     * @return Collection $this
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
}
