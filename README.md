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

_Feel free to skip this section and move straight on to
'[Creating New Tests](https://github.com/ben-rowan/doctrine-assert#creating-new-tests)'_

#### The Problem

In order to test the library we need to generate a large number of entities as well
as create the associated database schema. To keep the code self contained
and save committing large numbers of test entities into the code base we make use of
the virtual file system 
[vfs://stream](http://vfs.bovigo.org/) and [sqlite](https://sqlite.org/index.html). This
has the added side benefit of running everything in RAM making the test suite run
as fast as possible.

#### How The Testing Framework Works

The following process takes place before each test is run and is managed by
[`AbstractDoctrineAssertTest`](./tests/AbstractDoctrineAssertTest.php).

##### Virtual File System

We first initialise the [virtual file system](http://vfs.bovigo.org/) before copying over
the Doctrine
[YAML mapping](https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/yaml-mapping.html)
files that we'll later use to generate our test entities.

##### Entity Manager

Now we setup the entity manager with our YAML mapping config and an in memory SQLite
database. The database is recreated before each test run making sure we have no leakage
between tests.

##### Generate Entities

After we've setup the entity manager we can use it's meta data and an
[`EntityGenerator`](https://github.com/doctrine/orm/blob/2.6/lib/Doctrine/ORM/Tools/EntityGenerator.php)
to create the tests entities. Before we can use these we also need to loop
through and require them.

##### Update Schema

Finally we update the database schema to match our entities and we're good to go.

### Test Structure

```text
DoubleOneToOne
├── AssertDatabaseCountTest.php
├── AssertDatabaseHasTest.php
├── AssertDatabaseMissingTest.php
└── Vfs
    └── config
        ├── Vfs.DoubleOneToOne.One.dcm.yml
        ├── Vfs.DoubleOneToOne.Two.dcm.yml
        └── Vfs.DoubleOneToOne.Three.dcm.yml
```

Tests are structured based on the types of entities they'd like to test against, all tests using
the same set of entities are stored in a directory, in this case `DoubleOneToOne`. This way we
can minimise the number of entity sets that we need to define.

Next we have the virtual file system `Vfs/` that will be used by any tests within this folder. Within
`Vfs/config` we define our test entities using Doctrine's
[YAML Mapping](https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/yaml-mapping.html) format.
The YAML filename should match the class name defined inside so `Vfs\DoubleOneToOne\One`
becomes `Vfs.DoubleOneToOne.One` with `.dcm.yml` on the end `Vfs.DoubleOneToOne.One.dcm.yml`.

### Creating A Test

Here's the basic outline of a test.

```php
class YourTest extends AbstractDoctrineAssertTest
{
    public const VFS_NAMESPACE = 'Vfs\\YourTest\\';

    use DoctrineAssertTrait;

    protected function getVfsPath(): string
    {
        return __DIR__ . '/Vfs';
    }

    public function setUp()
    {
        parent::setUp();

        $this->createEntities();
    }

    public function testSomethingPasses(): void
    {
        $this->assertDatabaseHas(
            self::VFS_NAMESPACE . 'One',
            [
                'test' => 'passes'
            ]
        );
    }
    
    public function testSomethingFails(): void
    {
        $this->expectException(ExpectationFailedException::class);
    
        $this->assertDatabaseHas(
            self::VFS_NAMESPACE . 'One',
            [
                'test' => 'fails'
            ]
        );
    }

    private function createEntities(): void
    {
        $generator = Factory::create();
        $populator = new Populator($generator, $this->getEntityManager());

        $populator->addEntity(self::VFS_NAMESPACE . 'One', 1,
            [
                'test' => 'passes'
            ]
        );

        // ...

        $populator->execute();
    }
}
```

#### Define Entities

If the entities that we need for our test don't already exist then we create a directory
for our tests and YAML files that define all the entities our test will require. If the
entities do exist then we can simply add our new test to that directory.

#### Create Test Class

Now we create a test class. The class _must_ extend
[`AbstractDoctrineAssertTest`](./tests/AbstractDoctrineAssertTest.php).

You'll also want to `use`
[`DoctrineAssertTrait`](https://github.com/ben-rowan/doctrine-assert/blob/master/src/DoctrineAssertTrait.php)
so that you can make test assertions with it.

#### Create Test Method

We now create one method for each item we'd like to test (as normal).

#### Setup Fixture Data

You can setup the fixture data you require for your test using
[Fakers generators and populators](https://github.com/fzaninotto/Faker#populating-entities-using-an-orm-or-an-odm).

```php
$generator = Factory::create();
$populator = new Populator($generator, $this->getEntityManager());

$populator->addEntity(self::VFS_NAMESPACE . 'Three', 1,
    [
        'name' => 'Three'
    ]
);

// ...

$populator->execute();
```

#### Make Assertion

Finally you'll use to the `doctrine-assert` assertions to make an assertion. If you'd
like to test that an assertion correctly fails then you can simply expect the
`ExpectationFailedException`.

```php
$this->expectException(ExpectationFailedException::class);
```