# doctrine-assert

A set of PHPUnit database assertions for your Doctrine entities.

## Usage

### Trait

To add `doctrine-assert` to your tests you simply `use` the provided trait in your test class.

```php
use DoctrineAssertTrait;
```

You now have access to the following assertions.

### Assertions

All the database assertions require a root entity and a query config. The query config defines
a number of joins taken from the root entity (zero or more) before then defining values for 
us to assert against.

For example:

```php
$this->assertDatabaseHas(
    SomeEntity::class, // <-- root entity
    [                  // <-- query config
        AnotherEntity::class => [
            YetAnotherEntity::class => [
                'active' => true
            ]
        ]
    ]
);
```

Here we can see that we're joining `AnotherEntity::class` to `SomeEntity:class`, then
`YetAnotherEntity::class` to `AnotherEntity::class` before asserting
that the value of `active` on `YetAnotherEntity::class` is `true` in one or more cases.

We can continue this nesting and joining as much as we need too including adding more than
one join per entity.

```php
$this->assertDatabaseHas(
    SomeEntity::class,
    [
        'active' => true,
        AnotherEntity::class => [
            'active' => true,
            YetAnotherEntity::class => [
                'active' => true
                // And we could just keep going if we needed to
            ]
        ],
        FinallyAnotherEntity::class => [
            'active' => true
        ]
    ]
);
```

Using this we can quickly and easily build up complex assertions against the current
database state.

#### Database Has

Assert that the database has one or more entities that match the provided query config.

```php
$this->assertDatabaseHas(
    SomeEntity::class,
    [
        'active' => true
    ]
);
```

#### Database Missing

Assert that the database has zero entities that match the provided query config.

```php
$this->assertDatabaseMissing(
    SomeEntity::class,
    [
        'active' => true
    ]
);
```

#### Database Count

Assert that the database has exactly `$count` entities that match the provided query config.

```php
$this->assertDatabaseCount(
    100,  // <-- count
    SomeEntity::class,
    [
        'active' => true
    ]
);
```

## Testing

If you'd like to extend `doctrine-assert` or create a test case for a bug you've found
then you'll need to be able to run the tests and create new ones.

### Running Tests

Running the tests should be as simple as:

```bash
bash qa.bash
```

This will run:
* [PHPStan](https://github.com/phpstan/phpstan)
* [PHPMD](https://phpmd.org/)
* [PHPUnit](https://phpunit.de/)

If you'd also like to run the tests with [Infection](https://infection.github.io/)
then use:

```bash
RUN_INFECTION='yes' bash qa.bash
```

You'll need to have [XDebug](https://xdebug.org/) installed for this to work though
as it requires coverage to be generated.

### Testing Framework

#### The Problem

In order to test the library we need to generate a large number of entities as well
as create the associated database schema. To keep the code self contained
and save committing large numbers of test entities into the code base I've decided
to make use of the virtual file system 
[vfs://stream](http://vfs.bovigo.org/) and [sqlite](https://sqlite.org/index.html). This
has the side benefit of running everything in RAM and making the test suite run
really fast.

#### How The Test Suite Works

_Feel free to skip this section and move straight on to 'Creating A New Test'_

The following process takes place before each test is run using `setUp()`.

##### Virtual File System

We first setup the [virtual file system](http://vfs.bovigo.org/). We copy over the Doctrine
[YAML mapping](https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/yaml-mapping.html)
files that we'll later use to generate our test entities.

##### Setup The Entity Manager

##### Generate The Entities

##### Require The Generated Entities

##### Update The Database Schema

### Creating New Tests

#### Test Structure

```text
DoubleOneToOne
├── AssertDatabaseCountTest.php
├── AssertDatabaseHasTest.php
├── AssertDatabaseMissingTest.php
└── Vfs
    └── config
        ├── Vfs.DoubleOneToOne.One.dcm.yml
        ├── Vfs.DoubleOneToOne.Three.dcm.yml
        └── Vfs.DoubleOneToOne.Two.dcm.yml
```

Tests are structured based on the types of entities they'd like to test against, all tests using
the same set of entities are stored in a directory, in this case `DoubleOneToOne`. This way we
can minimise the number of entity sets that we need to define.

Next we have the virtual file system `Vfs/` that will be used by any tests within this folder. Within
`Vfs/config` we define our test entities using Doctrine's
[YAML Mapping](https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/yaml-mapping.html) format. 

#### Define The Entities

#### Create Test File

#### Create Test

##### Setup Fixture Data

##### Make Assertion
