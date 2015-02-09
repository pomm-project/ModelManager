<?php
/*
 * This file is part of the PommProject/ModelManager package.
 *
 * (c) 2014 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager\Converter;

use PommProject\Foundation\Exception\ConverterException;
use PommProject\Foundation\Converter\ConverterInterface;
use PommProject\Foundation\Session\Session;

use PommProject\ModelManager\Model\Projection;
use PommProject\ModelManager\Model\RowStructure;
use PommProject\ModelManager\Model\IdentityMapper;
use PommProject\ModelManager\Model\HydrationPlan;
use PommProject\ModelManager\Model\FlexibleEntity\FlexibleEntityInterface;

/**
 * PgEntity
 *
 * Entity converter.
 * It handles row types and composite types.
 *
 * @package ModelManager
 * @copyright 2014 Grégoire HUBERT
 * @author Grégoire HUBERT
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 * @see ConverterInterface
 */
class PgEntity implements ConverterInterface
{
    protected $row_structure;
    protected $identity_mapper;
    protected $flexible_entity_class;

    /**
     * __construct
     *
     * Constructor.
     *
     * @access public
     * @param  RowStructure $structure
     * @return null
     */
    public function __construct(
        $flexible_entity_class,
        RowStructure $structure,
        IdentityMapper $identity_mapper = null
    ) {
        $this->flexible_entity_class    = $flexible_entity_class;
        $this->row_structure            = $structure;
        $this->identity_mapper          = $identity_mapper === null
            ? new IdentityMapper()
            : $identity_mapper
            ;
    }

    /**
     * fromPg
     *
     * Embedable entities are converted here.
     *
     * @see ConverterInterface
     */
    public function fromPg($data, $type, Session $session)
    {
        $data = trim($data, '()');

        if ($data === '') {
            return null;
        }


        if ($type instanceOf Projection) {
            $projection = $type;
        } else {
            $projection = new Projection(
                $this->flexible_entity_class,
                $this->row_structure->getDefinition()
            );
        }

        $entity = (new HydrationPlan(
            $projection,
            $session
        ))->hydrate($this->transformData($data, $projection));

        return $this->cacheEntity($entity);
    }

    /**
     * transformData
     *
     * A short description here.
     *
     * @access private
     * @param  string       $data
     * @param  Projection   $projection
     * @return array
     */
    private function transformData($data, Projection $projection)
    {
        $data = stripcslashes($data);
        $values = str_getcsv($data);
        $definition = $projection->getFieldNames();
        $out_values = [];

        for ($index = 0; $index < count($values); $index++) {
            $out_values[$definition[$index]] = $values[$index];
        }

        return $out_values;
    }

    /**
     * cacheEntity
     *
     * Check entity against the cache.
     *
     * @access public
     * @param  FlexibleEntityInterface   $entity
     * @return FlexibleEntityInterface
     */
    public function cacheEntity(FlexibleEntityInterface $entity)
    {
        return $this
            ->identity_mapper
            ->fetch($entity, $this->row_structure->getPrimaryKey())
            ;
    }

    /**
     * toPg
     *
     * @see ConverterInterface
     */
    public function toPg($data, $type, Session $session)
    {
        if ($data === null) {
            return sprintf("NULL::%s", $type);
        } else if (!$data instanceOf $this->flexible_entity_class) {
            throw new ConverterException(
                sprintf(
                    "Converter for type '%s' only knows how to convert entites of type '%s' ('%s' given).",
                    $type,
                    $this->flexible_entity_class,
                    get_class($data)
                )
            );
        }

        $hydration_plan = new HydrationPlan(
            new Projection($this->flexible_entity_class, $this->row_structure->getDefinition()),
            $session
        );

        return sprintf(
            "row(%s)::%s",
            join(',', $hydration_plan->dry($data->fields())),
            $type
        );
    }
}
