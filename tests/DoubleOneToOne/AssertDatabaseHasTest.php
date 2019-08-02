<?php declare(strict_types=1);

namespace BenRowan\DoctrineAssert\Tests\DoubleOneToOne;

use BenRowan\DoctrineAssert\DoctrineAssertTrait;
use BenRowan\DoctrineAssert\Tests\AbstractDoctrineAssertTest;
use Faker\Factory;
use Faker\ORM\Doctrine\Populator;
use PHPUnit\Framework\ExpectationFailedException;

class AssertDatabaseHasTest extends AbstractDoctrineAssertTest
{
    public const VFS_NAMESPACE = 'Vfs\\DoubleOneToOne\\';

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
                self::VFS_NAMESPACE . 'Two' => [
                    self::VFS_NAMESPACE . 'Three' => []
                ]
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
                    'name' => 'Two',
                    self::VFS_NAMESPACE . 'Three' => [
                        'name' => 'Three'
                    ]
                ]
            ]
        );
    }

    /**
     * @param string $nameOne
     * @param string $nameTwo
     * @param string $nameThree
     *
     * @dataProvider nonMatchingNamesDataProvider
     */
    public function testSettingNonMatchingQueryConfigFails(
        string $nameOne,
        string $nameTwo,
        string $nameThree
    ): void {

        $this->expectException(ExpectationFailedException::class);

        $this->assertDatabaseHas(
            self::VFS_NAMESPACE . 'One',
            [
                'name' => $nameOne,
                self::VFS_NAMESPACE . 'Two' => [
                    'name' => $nameTwo,
                    self::VFS_NAMESPACE . 'Three' => [
                        'name' => $nameThree
                    ]
                ]
            ]
        );
    }

    public function nonMatchingNamesDataProvider(): array
    {
        return [
            'Name 1 is wrong'            => ['Wrong', 'Two',   'Three'],
            'Name 2 is wrong'            => ['One',   'Wrong', 'Three'],
            'Name 3 is wrong'            => ['One',   'Two',   'Wrong'],
            'Names 1 and 2 are wrong'    => ['Wrong', 'Wrong', 'Three'],
            'Names 2 and 3 are wrong'    => ['One',   'Wrong', 'Wrong'],
            'Names 1 and 3 are wrong'    => ['Wrong', 'Two',   'Wrong'],
            'Names 1, 2 and 3 are wrong' => ['Wrong', 'Wrong', 'Wrong']
        ];
    }

    private function createEntities(): void
    {
        $generator = Factory::create();
        $populator = new Populator($generator, $this->getEntityManager());

        $populator->addEntity(self::VFS_NAMESPACE . 'Three', 1,
            [
                'name' => 'Three'
            ]
        );
        $populator->addEntity(self::VFS_NAMESPACE . 'Two', 1,
            [
                'name' => 'Two'
            ]
        );
        $populator->addEntity(self::VFS_NAMESPACE . 'One', 1,
            [
                'name' => 'One'
            ]
        );

        $populator->execute();
    }
}