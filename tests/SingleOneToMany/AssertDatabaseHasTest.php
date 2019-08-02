<?php declare(strict_types=1);

namespace BenRowan\DoctrineAssert\Tests\SingleOneToMany;

use BenRowan\DoctrineAssert\DoctrineAssertTrait;
use BenRowan\DoctrineAssert\Tests\AbstractDoctrineAssertTest;
use Faker\Factory;
use Faker\ORM\Doctrine\Populator;
use PHPUnit\Framework\ExpectationFailedException;

class AssertDatabaseHasTest extends AbstractDoctrineAssertTest
{
    public const VFS_NAMESPACE = 'Vfs\\SingleOneToMany\\';

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

    public function testSettingNoQueryConfigPasses(): void
    {
        $this->assertDatabaseHas(
            self::VFS_NAMESPACE . 'One',
            [
                self::VFS_NAMESPACE . 'Two' => []
            ]
        );
    }

    public function testSettingMatchingQueryConfigPasses(): void
    {
        $this->assertDatabaseHas(
            self::VFS_NAMESPACE . 'One',
            [
                'name' => 'One',
                self::VFS_NAMESPACE . 'Two' => [
                    'name'   => 'Two-One',
                    'active' => true
                ]
            ]
        );

        $this->assertDatabaseHas(
            self::VFS_NAMESPACE . 'One',
            [
                'name' => 'One',
                self::VFS_NAMESPACE . 'Two' => [
                    'name'   => 'Two-Two',
                    'active' => false
                ]
            ]
        );
    }

    /**
     * @param string $nameOne
     * @param string $nameTwo
     * @param bool $active
     *
     * @dataProvider nonMatchingQueryConfig
     */
    public function testSettingNonMatchingQueryConfigFails(
        string $nameOne,
        string $nameTwo,
        bool $active
    ): void {

        $this->expectException(ExpectationFailedException::class);

        $this->assertDatabaseHas(
            self::VFS_NAMESPACE . 'One',
            [
                'name' => $nameOne,
                self::VFS_NAMESPACE . 'Two' => [
                    'name'   => $nameTwo,
                    'active' => $active
                ]
            ]
        );
    }

    public function nonMatchingQueryConfig(): array
    {
        return [
            'Name 1 is wrong'                 => ['Wrong', 'Two-One', true],
            'Name 2 is wrong'                 => ['One',   'Wrong',   true],
            'Name 2 is with wrong bool'       => ['One',   'Two-One', false],
            'Name 2 is with wrong bool again' => ['One',   'Two-Two', true],
        ];
    }

    private function createEntities(): void
    {
        $generator = Factory::create();
        $populator = new Populator($generator, $this->getEntityManager());

        $populator->addEntity(self::VFS_NAMESPACE . 'One', 1,
            [
                'name' => 'One'
            ]
        );
        $populator->addEntity(self::VFS_NAMESPACE . 'Two', 2,
            [
                'name' => $this->createGenerator(
                    [
                        'Two-One',
                        'Two-Two'
                    ]
                ),
                'active' => $this->createGenerator(
                    [
                        true,
                        false
                    ]
                )
            ]
        );
        $populator->execute();
    }
}