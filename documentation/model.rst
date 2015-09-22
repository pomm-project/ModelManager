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

Postgresql supports table multiple inheritance. This term is confusing because from a functional overview, it is not really inheritance since children rows are seen in the parent table but there cannot be constraints on the parent table that verifies the children rows. Postgres inheritance works more like a structural trait mechanism. It is possible to add as many structural traits as wanted on the table, it adds the columns from the parent tables to the child table. If a parent table is modified, alterations are propagated to the children. ``RowStructure`` class makes easy inheritance declaration:

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

Assuming the model manager session builder is used, calling this useless model class is made through the ``Client`` pooler:

.. code:: php

    <?php
    //…
    $model = $session->getModel('\My\Namespace\EmployeeModel')

Querying the database
---------------------

Projection
~~~~~~~~~~

The projection mechanism handles the content of the ``SELECT`` fields in the model queries. The model’s underlying database structure defines the default projection of the model class so, by default, the SELECTed fields will be the same as the underlying relation. This projection is changed by overloading the ``createProjection`` method. It is possible to add or delete fields from the projection:

.. code:: php

    <?php
    //…
    class EmployeeModel extends Model
    {
    //…
        public function createProjection()
        {
            return parent::createProjection() // default projection
                ->unsetField('password')      // Removing unwanted fields
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

The example above adds a field named ``age`` defined by the expression ``age("birthdate", now())`` which is an interval. The fact that the field is enclosed by the delimiters makes possible to alias the field with the table alias (see `Basic queries`_ below).

Basic queries
~~~~~~~~~~~~~

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
                    '{projection}'  => $this->createProjection(), // expand projection
                    '{relation}'    => $this->structure->getRelation(),
                ]
            );

            // ↓ return an iterator on flexible entities
            // ↓ parameters are escaped and converted.
            return $this->query($sql, [$name]);
        }
    }

Of course, there is no need to write such simple query since it is already shipped by Pomm’s built-in queries (see `findWhere`_).

Expanding the projection
~~~~~~~~~~~~~~~~~~~~~~~~

The example above shows how Pomm’s model manager decouples entities from database relations using the projection. Furthermore, it eases developer’s work by not having them to write the list of fields and maintain it over time.

It is also possible to expand projection in different ways:

- ``formatFields()`` (default) → ``"field_a", "field_b", …``
- ``formatFieldsWithFieldAlias()`` → ``"field_a" as field_a, "field_b" as field_b, …``

These formatting methods can also take a table alias as parameter. The field name is then expanded as ``"alias"."field_name"``. This is useful when using joins that present columns with the same name.

The way projection and relation are expanded is shown using PHP’s function ``strtr`` but it can be made any other way (``sprintf``, ``str_replace``, etc.)

Default queries
~~~~~~~~~~~~~~~

Because simples queries are almost always the same, Pomm comes with traits to automatically add queries in model classes. All these queries (but ``countWhere`` and ``existWhere``) use the ``createProjection()`` method to get the fields to be returned (see `Projection`_).

**ReadQueries**

findAll
.......

This method performs a query with no conditions. Still, it can take a query suffix argument that is appended on the right of the query to sort or limit the number of results. This suffix is **NOT** escaped and is passed as-is the database. Ensure the string passed as suffix is SQL safe.

.. code:: php

    <?php
    // …
    // select {projection} from {relation} order by salary desc limit 5
    $employees = $employee_model->fetchAll('order by salary desc limit 5');

findWhere
.........

Generic method to fetch row instances upon a SQL criteria. For convenience, this method can take a ``Where`` instance as argument (see `Foundation documentation <https://github.com/pomm-project/Foundation/blob/master/documentation/foundation.rst#where-the-condition-builder>`_).

.. code:: php

    <?php
    // …
    // select {projection} from {relation} where name ~* 'markus'
    $employees = $employee_model->findWhere("name ~* $*", ['markus']);

    // select {projection} from {relation} where name ~* 'markus' order by salary inc
    $employees = $employee_model->findWhere("name ~* $*", ['markus'], 'order by salary inc');

    // select {projection} from {relation} where birthdate > '…' or parental_authorisation
    $where = Where::create("birthdate > $*::timestamp", [new \DateTime('18 years ago')])
        ->orWhere('parental_authorisation')
        ;
    $workable_employees = $employee_model->findWhere($where);

findByPK
........

Returns a single entity or null if no entities match this primary key.

.. code:: php

    <?php
    // …
    // select {projection} from {relation} where employee_id = $*
    $employee = $employee_model->findByPK(['employee_id' => 'e4 … c9']);

countWhere
..........

Returns the count of rows matching the given criteria. For convenience, the criteria can be a ``Where`` instance.

.. code:: php

    <?php
    // …
    // select count(*) as result from {relation} where gender = $*::gender_type
    $male_count = $employee_model->countWhere("gender = $*::gender_type", ['M']);

existWhere
..........

Returns a boolean whether rows matching the given criteria do exist or not. The criteria can be a ``Where`` instance. This implementation is more performant than a count since it stops on the first row matching the given criteria whereas a count implies scanning the whole table.

.. code:: php

    <?php
    // …
    // select exists (select true from from {relation} where email ~ $*) as result
    $email_exists = $employee_model->existWhere("email ~ $*", ['^markus']);

paginate
........

This method allows basic pagination for queries using ``LIMIT`` and ``OFFSET`` sql keywords. This is needed for the classical «results per page» approach. For performance reasons, the infinite scrolling approach must be preferred to this whereas it is applicable, see `this page for more information <http://use-the-index-luke.com/no-offset>`_.

This method adds a suffix to the given SQL query, the query passed as argument must not contain an ``OFFSET`` nor a ``LIMIT`` clause already.

.. code:: php

    <?php
    // …
    // Paginate a query with 25 results per page and get page 10’s results:
    $employees = $employee_model->paginate($sql, $parameters $total_result_count, 25, 10);

**WriteQueries** (uses ReadQueries)

createAndSave
.............

Create a new record from given data and return an according flexible entity. This entity is hydrated with data sent back by the database depending on the model’s configured projection so the entity has got the default values set by the database.

.. code:: php

    <?php
    // …
    // insert into {relation} (name, …) values ($*::varchar, …) returning {projection}
    $employee = $employee_model->createAndSave(['name' => 'Alice Ajouh', 'gender' => 'F', …]);

insertOne
.........

Insert a given entity and makes it to reflect values changed by the database.

.. code:: php

    <?php
    // …
    // insert into {relation} (name, …) values ($*::varchar, …) returning {projection}
    $employee = new Employee(['name' => 'Alice Ajouh', 'gender' => 'F', …]);
    $employee_model->insertOne($employee);

updateOne
.........

Update the given entity and makes it to reflect values changed by the database. The fields to be updated are passed as parameter hence changed values that are not updated will be override by values in the database. This way, the entity reflects what is in the database.

.. code:: php

    <?php
    // …
    $employee = $employee_model->findByPK(['employee_id' => '…']);
    $employee
        ->setSalary($new_salary)
        ->setName('whatever')
        ;
    // update {relation} set salary = $* where employee_id = $* returning {projection}
    $employee_model->updateOne($employee, ['salary']);
    $employee->get(['name', 'salary']);
    // ↑ ['name' => 'john doe', 'salary' => $new_salary]

deleteOne
.........

Drop an entity and makes it to reflect the last values according to the model’s projection.

.. code:: php

    <?php
    // …
    $employee = $employee_model->findByPK(['employee_id' => '…']);
    // delete from {relation} where employee_id = $* returning {projection}
    $employee_model->deleteOne($employee->setName('whatever'), ['salary']);
    $employee->getName(); // john doe


updateByPk
..........

Update a row identified by its primary key and return the entity corresponding to the model’s projection. Return ``null`` if no records match the given primary key.

.. code:: php

    <?php
    // …
    // update {relation} set salary = $* where employee_id = $* returning {projection}
    $employee = $employee_model->updateByPK(
        ['employee_id' => '…'],
        ['salary' => $new_salary]
    );

deleteByPK
..........

Delete a row identified by its primary key and return the entity corresponding to the model’s projection. Return ``null`` if no records match the given primary key.

.. code:: php

    <?php
    // …
    // delete from {relation} where employee_id = $* returning {projection}
    $employee = $employee_model->deleteByPK(['employee_id' => '…']);

deleteWhere
...........

Mass deletion, return an iterator on deleted results hydrated by the model’s projection. For convenience, it can take a ``Where`` instance as parameter.

.. code:: php

    <?php
    // …
    // delete from {relation} where salary > $* returning {projection}
    $employees = $employee_model->deleteWhere('salary > $*', [$max_salary]);

Projection
~~~~~~~~~~

The projection mechanism handles the content of the ``SELECT`` fields in the model queries. The model’s underlying database structure defines the default projection of the model class so, by default, the fields selected will be the same as the underlying relation. This projection is changed by overloading the ``createProjection`` method. It is possible to add or delete fields from the projection:

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

The example above shows how to create a custom projection that adds joined table’s field informations. This custom projection must be passed as parameter to the ``query`` function so the hydration mechanisme knows how to convert these fields. The foreign relations’ name are also replaced using their related model class.

Collection iterator
-------------------

Iterator overview
~~~~~~~~~~~~~~~~~

The model’s query method returns a ``CollectionIterator`` instance which contains a link to the database results. Since it extends the ``ConvertedResultIterator`` class it implements ``SeekableIterator``, ``Countable`` and ``JsonSerializable``. The specific task of this class is to return ``FlexibleEntityInterface`` instances in place of associative arrays.

Collection filters
~~~~~~~~~~~~~~~~~~

One interesting features of ``CollectionIterator`` is they can be attached filters. Filters are anonymous functions that take converted values in an array as parameter and must return an array. Several filters can be attached to a collection this way, they will be triggered in the same order they are added. This may be particularily useful when dealing with JSON fields that can be represented as PHP class instance:

.. code:: php

    <?php
    //…
    $collection = $model->findAll();
    $collection->registerFilter(function($values) {
        $values['json_field'] = new JsonObject($values['json_field']);

        return $values;
        });
    $my_entity = $collection->current();
    $my_entity['json_field']; // return a JsonObject instance.

Every time a row is fethed from the database, when all the filters have been triggered, the values are injected in an entity instance. It is possible to clear the filters attached to a collection by using the ``clearFilters`` method.
Important note:
    Filters do not actually discard results, this would make the iterator to return wrong count and / or rows. The filters are just a way to transform data before they hydrate entity classes. All filters must return an array.

Flexible entities
-----------------

Flexible entities are an object oriented representation of results returned by model classes’ queries. As the returned rows depend on projections, they are higly subject to change, this is why entities hydrated with results are called «flexible».

FlexibleEntityInterface
~~~~~~~~~~~~~~~~~~~~~~~

Although Pomm comes with a ``FlexibleEntity`` as default flexible entity class, it is possible to build custom data container classes as long as they implement ``FlexibleEntityInterface``. 

``hydrate``
    This method is responsible of how the instance is hydrated with the given data. It can set default values or override unwanted values.

``fields``
    Return the list of keys pointing on values stored by the entity.

``extract``
    Return the array representation of the hosted data.

``status``
    Since the entity is mutable, it is important to keep track of its status (see `Stateful entities`_ below).

For convenience, a ``StatefulEntityTrait`` is provided by the package, it implements two functions: ``status`` and ``touch`` which behaves like Unix’s ``touch`` utility.

Stateful entities
~~~~~~~~~~~~~~~~~

By default, entities can be either persisted or not, modified or not or a combination of both. These different states are represented using a bitmask:

- bit 1: 1 = persisted
- bit 2: 1 = modified

Combination of these two bits creates 4 different states:

- 0: not persisted nor modified (``FlexibleEntityInterface::STATUS_NONE``).
- 1: persisted and not modified since then (``FlexibleEntityInterface::STATUS_EXIST``).
- 2: modified and not persisted yet (``FlexibleEntityInterface::STATUS_MODIFIED``).
- 3: persisted and modified since then (Sum of the two last statuses above).

.. code:: php

    <?php
    //…
    $my_entity = new MyEntity(['field1' => 'a value', …]);
    $my_entity->status(); // 0 (none)
    $my_entity->setField1('whatever');
    $my_entity->status(); // 2 (modified)
    $model->insertOne($my_entity);
    $my_entity->status(); // 1 (persisted)
    $my_entity->touch()->status(); // 3 (modified + persisted)
    $my_entity->status() & FLexibleEntityInteface::STATUS_EXIST; // 1
    $my_entity->status() & FLexibleEntityInteface::STATUS_MODIFIED; // 2

It is possible to add more states (``STATUS_TAINTED`` by example to indicate an entity may contain untrusted values). This then will add a new bit 3 state hence four more different states (4, 5, 6 and 7).

``Status`` is a special method. To avoid collisions with custom accessors, it can take two forms:

- ``status()`` return the entity’s current state
- ``status($status)`` set the status and return ``$this``


Getters and setters
~~~~~~~~~~~~~~~~~~~

Generic getter
..............

Pomm’s default flexible entity class mimics POPO implementation by using PHP’s magic setters and getters.

.. code:: php

    <?php
    //…
    $my_entity = new MyEntity(['field1' => 1]);
    $my_entity->field1;         // 1
    $my_entity['field1'];       // 1
    $my_entity->get('field1');  // 1
    $my_entity->getField1();    // 1

What happen if a getter is implemented in ``MyEntity`` class?

.. code:: php

    <?php
    //…
    class MyEntity extends FlexibleEntity
    {
        public function getField1()
        {
            return $this->get('field1') * 2;
        }
    }
    //…
    $my_entity = new MyEntity(['field1' => 1]);
    $my_entity->field1;         // 2
    $my_entity['field1'];       // 2
    $my_entity->get('field1');  // 1
    $my_entity->getField1();    // 2

The getter is automatically used when the entity is accessed like an array or a standard object. The only way to get raw values stored in the entity is to use the generic getter ``get("field_name")``. This is mainly useful when the raw value is needed to create URLs in templates. This generic accessor can also take an array of field names, values are then returned in an associative array.

By default, a ``ModelException`` is thrown if a non existant key is accessed to prevent silent errors in templates:

.. code:: php

    <?php
    //…
    $my_entity = new MyEntity(['field1' => 1]);
    $my_entity->field2; // Throws an exception

It is still possible to silently ignore calls to unset attributes using the static ``FlexibleEntity::$strict`` attribute. By default, it is set to true. Turned to false, it will mute these errors.

.. code:: php

    <?php
    //…
    MyEntity::$strict = false;
    $my_entity = new MyEntity(['field1' => 1]);
    $my_entity->field2; // Returns null

has
...

By default, this accessor returns true if the entity has this key (even if the value is null). This is used by the ``ArrayAccess`` implementation and the extract (see `extract`_) method.

.. code:: php

    <?php
    //…
    $my_entity = new MyEntity(['field1' => null]);
    $my_entity->has('field1');  // true
    $my_entity->hasField1();    // true
    isset($my_entity['field1']; // true
    isset($my_entity->field1);  // true
    $my_entity->has('field2');  // false


set
...

This is the way values are updated in the entity.

.. code:: php

    <?php
    //…
    $my_entity = new MyEntity(['field1' => 1]);
    $my_entity->set('field2', 2);
    $my_entity->setField2(2);  // By default, same as above
    $my_entity['field2'] = 2;  // same as above
    $my_entity->field2 = 2;    // same as above

add
...

The ``add`` method is a shortcut to easily add a new value when the attribute is an array or to create an array with the given value.

.. code:: php

    <?php
    //…
    $computer = $model->findByPK(['computer_id' => …]);
    $computer->add('interfaces', '192.168.2.81/24');
    $computer->addInterfaces('192.168.2.81/24'); // By default, same as above

clear
.....

Unset a key, value pair from the container and set the entity as modified if the key exists.

.. code:: php

    <?php
    //…
    $my_entity = new MyEntity(['field1' => null]);
    $my_entity->clear('field1');
    $my_entity->clearField1();    // identical as above
    unset($my_entity->field1);    // identical as above
    unset($my_entity['field1']);  // identical as above
    $my_entity->status() & FlexibleEntityInterface::STATUS_MODIFIED; // 2

extract
.......

This method outputs the array representation of the entity. To do so it extracts recursively its attributes (that can be flexible entities). By default, only values present in the container are dumped but custom getters will be dumped too if their according ``has`` method exists and returns true.

.. code:: php

    <?php
    //…
    class Student extends FlexibleEntity
    {
        public function getAge()
        {
            return (new \DateTime())
                ->diff($this->getBirthdate())
                ;
        }

        public function hasAge()
        {
            return $this->hasBirthdate();
        }
    }
    //…
    $student = new Student(['birthdate' => new \DateTime('1991-06-29')]);
    $student->extract();
    /* array (2):
    [
        'birthdate' => \DateTime instance (…),
        'age' => \DateInterval instance (…)
    ]
    */
