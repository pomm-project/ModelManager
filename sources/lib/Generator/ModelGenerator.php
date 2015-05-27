<?php
/*
 * This file is part of Pomm's ModelManager package.
 *
 * (c) 2014 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\ModelManager\Generator;

use PommProject\Foundation\Where;
use PommProject\Foundation\Inflector;
use PommProject\Foundation\ParameterHolder;

use PommProject\ModelManager\Exception\GeneratorException;

/**
 * ModelGenerator
 *
 * Generate a new model file.
 * If the given file already exist, it needs the force option to be set at
 * 'yes'.
 *
 * @package ModelManager
 * @copyright 2014 Grégoire HUBERT
 * @author Grégoire HUBERT
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class ModelGenerator extends BaseGenerator
{
    /**
     * generate
     *
     * Generate structure file.
     *
     * @see BaseGenerator
     */
    public function generate(ParameterHolder $input, array $output = [])
    {
        $schema_oid = $this
            ->getSession()
            ->getInspector()
            ->getSchemaOid($this->schema);

        if ($schema_oid === null) {
            throw new GeneratorException(sprintf("Schema '%s' does not exist.", $this->schema));
        }

        $relations_info = $this
            ->getSession()
            ->getInspector()
            ->getSchemaRelations($schema_oid, new Where('cl.relname = $*', [$this->relation]))
            ;

        if ($relations_info->isEmpty()) {
            throw new GeneratorException(sprintf("Relation '%s.%s' does not exist.", $this->schema, $this->relation));
        }

        $this
            ->checkOverwrite($input)
            ->outputFileCreation($output)
            ->saveFile(
                $this->filename,
                $this->mergeTemplate(
                    [
                        'entity'        => Inflector::studlyCaps($this->relation),
                            'namespace'     => trim($this->namespace, '\\'),
                            'trait'         => $relations_info->current()['type'] === 'table' ? 'WriteQueries' : 'ReadQueries',
                            'relation_type' => $relations_info->current()['type'],
                            'relation'      => $this->relation
                        ]
                    )
                );

        return $output;
    }

    /**
     * getCodeTemplate
     *
     * @see BaseGenerator
     */
    protected function getCodeTemplate()
    {
        return <<<'_'
<?php

namespace {:namespace:};

use PommProject\ModelManager\Model\Model;
use PommProject\ModelManager\Model\Projection;
use PommProject\ModelManager\Model\ModelTrait\{:trait:};

use PommProject\Foundation\Where;

use {:namespace:}\AutoStructure\{:entity:} as {:entity:}Structure;
use {:namespace:}\{:entity:};

/**
 * {:entity:}Model
 *
 * Model class for {:relation_type:} {:relation:}.
 *
 * @see Model
 */
class {:entity:}Model extends Model
{
    use {:trait:};

    /**
     * __construct()
     *
     * Model constructor
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        $this->structure = new {:entity:}Structure;
        $this->flexible_entity_class = '\{:namespace:}\{:entity:}';
    }
}

_;
    }
}
