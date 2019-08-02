<?php declare(strict_types=1);

namespace BenRowan\DoctrineAssert\Tests\SingleOneToOne;

use BenRowan\DoctrineAssert\DoctrineAssertTrait;
use BenRowan\DoctrineAssert\Tests\AbstractDoctrineAssertTest;
use Faker\Factory;
use Faker\ORM\Doctrine\Populator;
use PHPUnit\Framework\ExpectationFailedException;

class AssertDatabaseMissingTest extends AbstractDoctrineAssertTest
{
    public const VFS_NAMESPACE = 'Vfs\\SingleOneToOne\\';

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

    public function testSettingNoQueryConfigFails(): void
    {
        $this->expectException(ExpectationFailedException::class);

        $this->assertDatabaseMissing(
            self::VFS_NAMESPACE . 'One',
            [
                self::VFS_NAMESPACE . 'Two' => []
            ]
        );
    }

    public function testSettingMatchingQueryConfigFails(): void
    {
        $this->expectException(ExpectationFailedException::class);

        $this->assertDatabaseMissing(
            self::VFS_NAMESPACE . 'One',
            [
                'name' => 'One',
                self::VFS_NAMESPACE . 'Two' => [
                    'name'   => 'Two',
                    'active' => true
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
    public function testSettingNonMatchingQueryConfigPasses(
        string $nameOne,
        string $nameTwo,
        bool $active
    ): void {
        $this->assertDatabaseMissing(
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
            'Name 1 is wrong'              => ['Wrong', 'Two-One', true],
            'Name 2 is wrong'              => ['One',   'Wrong',   true],
            'Name 2 with wrong bool'       => ['One',   'Two-One', false],
            'Name 2 with wrong bool again' => ['One',   'Two-Two', true],
        ];
    }

    private function createEntities(): void
    {
        $generator = Factory::create();
        $populator = new Populator($generator, $this->getEntityManager());

        $populator->addEntity(self::VFS_NAMESPACE . 'Two', 1,
            [
                'name'   => 'Two',
                'active' => true
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