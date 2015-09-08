==========================
Pomm-project Model Manager
==========================

.. contents::

Overview
--------

Model manager is an extension of Pomm’s Foundation in order to add an object oriented model manager layer. It adds the following poolers:

- Model: map object oriented entities on database structures through projections.
- ModelManager: group model manipulations into transactions.

Pomm’s model manager is different from classical ORM:

Entities are not hard coupled to tables:
    In ORM, entities classes describe the structure of the underlaying tabled using attributes and/or annotations. This creates a hard coupling between entities and tables hence condemns the ORM to perform ``SELECT * FROM …`` queries. Pomm’s model manager defined a `projection <https://en.wikipedia.org/wiki/Projection_%28relational_algebra%29>`_ between tables (or views, or just sets) and flexible entities. In short, business entities are decoupled from SQL (technical) implementation.

No abstraction layer:
    Obviously, there is no abstraction layer so it leverages the features of Postgres through the Foundation package. Model’s methods can directly use the rich SQL of Postgres so programmers can adjust queries and indexes granting applications with database’s optimum performances.

Setup
-----

Configuration
~~~~~~~~~~~~~

Poolers for ``model`` and ``model_layer`` must be registered. A ``SessionBuilder`` class is provided by the package that automatically loads the poolers:

.. code:: php

    <?php
    //…

    $pomm = new Pomm(['my_database' => 
        [
            'dsn' => 'pgsql://user:pass@host:port/db_name',
            'class:session_builder' => '\PommProject\ModelManager\SessionBuilder',
        ]
    ]);

Projects using a custom session builder must either have it to extend this class or simply load poolers manually.

Setting up classes
~~~~~~~~~~~~~~~~~~

Each table in the database is associated to 3 different classes in PHP but there can be more (or less):

- a __structure__ class that reflects the underlying database structure and can be auto-generated (recommended).
- a __model__ class that defines the default projection and proposes methods to interact with the database (can be automatically created).
- an __entity__ class that represents a row of the associated using the model’s projection (can be automatically created).

By default, the `CLI tool <https://github.com/pomm-project/Cli>`_ creates the following structure when generating classes::

    SessionName/
    └── ObjectSchema
        ├── AutoStructure
        │   └── Entity.php
        ├── EntityModel.php
        └── Entity.php

Structure classe files in the ``AutoStructure`` directory are overwritten everytime the database is introspected. These files therefore may not be edited by hand since all changes will be lost. All the classes are set in a path that defines a namespace as described by the `PSR-4 <http://www.php-fig.org/psr/psr-4/fr/>`_ standard. This namespace contains the name of the session which should represent a logical name for the database (often set as the project’s name) and the Postgres schema’s name.

Model and entity classes are never overwritten by the CLI (unless explicitely forced to do so).

Structure classes
-----------------

Structure classes own relations’ structure informations:

Basic usage
~~~~~~~~~~~

.. code:: php

    <?php
    // …

    $structure = (new RowStructure)
        ->setDefinition(
            [
                'field_a'   => 'type',
                'field_b'   => 'type',
                …
                'field_n'   => 'type',
            ]
        )
        ->setPrimaryKey(['field_a', 'field_b', …])
        ->setRelation('schema_name.relation_name')
        ;

    $structure->addField('field_m', 'type');
    // same as
    $structure['field_m'] = 'type';

Custom row structure classes
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Although it is possible to use directly the ``RowStructure`` class, it can also be extended to create specific structure classes representing database relations’ structures.

.. code:: php

    <?php
    // …

    class MyRowStructure extends RowStructure
    {
        public function __construct()
        {
            $this
                ->setDefinition(
                    [
                        'field_a'   => 'type',
                        'field_b'   => 'type',
                        …
                        'field_n'   => 'type',
                    ]
                )
                ->setPrimaryKey(['field_a', 'field_b', …])
                ->setRelation('schema_name.relation_name')
            ;
        }
    }

This way, database structure definitions are described in a unique defined place in the code. 

Inheritance
~~~~~~~~~~~

Postgresql supports table multiple inheritance. This term is confusing because from a functional overview, it is not really inheritance since since children rows are seen in the parent table but there cannot be constraints on the parent table that verifies the children rows. Postgres inheritance works more like a structural trait mechanism. It is possible to add as many structural traits as wanted on the table, it adds the columns from the parent tables to the child table. If a parent table is modified, alterations are propagated to the children. ``RowStructure`` class makes easy inheritance declaration:

.. code:: php

    <?php
    // …

    $child_structure = (new ChildRowStructure)
        ->inherits(new ParentRowStructure)
        ;

Important:
    Table inheritance can makes several columns to have the same name. While Postgresql supports this, it is really tricky to write queries and get results from such rows. It is not advised to use Pomm when tables have several fields with the same name.

Model classes
-------------

Model classes are the keystone of the ModelManager package. These clients allow SQL manipulations on object oriented entities through a projection.

Basic definition
~~~~~~~~~~~~~~~~

Model classes need two things to be able to register to the session:

- a structure instance.
- an entity class name that implements ``FlexibleEntityInterface``.

The best place to set them up is in the constructor:

.. code:: php

    <?php
    //…
    class EmployeeModel extends Model
    {
        public function __construct()
        {    // ↓ underlying database structure
            $this->structure = new EmployeeStructure;
            $this->flexible_entity_class = '\Model\Company\PeopleSchema\Employee';
        }   // ↑ associated entity
    }

With PHP >= 5.5, it is possible to use the ``::class`` constant to name entity class:

.. code:: php

    <?php
    //…
    use \Model\Company\PeopleSchema\Employee;
    //…
            $this->flexible_entity_class = Employee::class;

Assuming the model manager session builder is used, calling this useless model class is made through the ``Client`` pooler::

.. code:: php

    <?php
    //…
    $model = $session->getModel('\My\Namespace\EmployeeModel')

Querying the database
---------------------

Queries and projection
~~~~~~~~~~~~~~~~~~~~~~

The Model package comes with its own ``QueryManager`` and result iterator. The goal is to let developers focus on what queries do instead of actually making queries. Tedious parts of writing SQL queries are solved using the model’s structure and projection:

.. code:: php

    <?php
    //…
    class EmployeeModel extends Model
    {
    //…
        public function findByName($name)
        {
            // select employee_id, name, … from my_schema.employee where name ~* $1
            $sql = strtr(
                "select {projection} from {relation} where name ~* $*",
                [
                    '{projection}'  => $this->createProjection(),
                    '{relation}'    => $this->structure->getRelation(),
                ]
            );

            // ↓ return an iterator on flexible entities
            return $this->query($sql, [$name]);
        }
    }

The example above shows how Pomm’s model manager decouples entities from database relations using the projection. Furthermore, it eases developer’s work by not having them to write the list of fields and maintain it over time.

It is also possible to expand projection in different ways:

- ``formatFields()`` (default) → ``"field_a", "field_b", …``
- ``formatFieldsWithFieldAlias()`` → ``"field_a" as field_a, "field_b" as field_b, …``

These formatting methods can also take a table alias as parameter. The field name is then expanded as ``"alias"."field_name"``. This is useful when using joins that present columns with the same name.

The way projection and relation are expanded is shown using PHP’s function ``strtr`` but it can be made any other way (``sprintf``, ``str_replace``, etc.)

Default queries
~~~~~~~~~~~~~~~

Because simples queries are almost always the same, Pomm comes with traits to automatically add queries in model classes:

- ReadQueries
    - ``findAll``
    - ``findWhere``
    - ``findByPK``
    - ``countWhere``
    - ``existWhere``
    - ``paginate``
- WriteQueries (uses ReadQueries)
    - ``insertOne``
    - ``updateOne``
    - ``deleteOne``
    - ``updateByPk``
    - ``deleteByPK``
    - ``deleteWhere``
    - ``createAndSave``

All the queries above (except ``countWhere`` and ``existWhere``) use the projection defined by the ``createProjection`` method (see `Projection`_ below). 

Projection
~~~~~~~~~~

The projection mechanism handles the content of the ``SELECT`` fields in the model queries. The model’s underlying database structure defines the default projection of the model class so, by default, the fields SELECTed will be the same as the underlying relation. This projection is changed by overloading the ``createProjection`` method. It is possible to add or delete fields from the projection:

.. code:: php

    <?php
    //…
    class EmployeeModel extends Model
    {
    //…
        public function createProjection()
        {
            return parent::createProjection() // default projection
                ->unsetField('password')
                ->unsetField('department_id')
                ;
        }
    }

It is possible to add new fields referencing other fields. In order to keep escaping and aliasing good, field references must be enclosed by ``%:`` and ``:%``.

.. code:: php

    <?php
    //…
    class EmployeeModel extends Model
    {
    //…
        public function createProjection()
        {
            return parent::createProjection()
                ->setField('age', 'age(%:birthdate:%, now())', 'interval')
                ;
        }
    }

The example above adds a field named ``age`` defined by the expression ``age("birthdate", now())`` which is an interval.

Complex queries
~~~~~~~~~~~~~~~

When performing joins, there must be informations regarding the foreign relations. They are available through their own model class:

.. code:: php

    <?php
    //…
    class EmployeeModel extends Model
    {
    //…
        public function findWithDeparment($name)
        {
            $department_model = $this
                ->getSession()
                ->getModel('\Company\People\DepartmentModel')
                ;

            $sql = <<<SQL
    select
        {projection}
    from
        {employee} emp
        inner join {department} dep using (department_id)
    where
        emp.name ~* $*
    SQL;

            $projection = $this->createProjection()
                ->setField("department_name", "dep.name", "varchar")
                ;

            $sql = strtr(
                $sql,
                [
                    '{employee}'    => $this->structure->getRelation(),
                    '{department}'  => $department_model->getStructure()->getRelation(),
                    '{projection}'  => $projection->formatFields('emp'),
                ]
            );

            return $this->query($sql, [$name], $projection);
        }
    }

