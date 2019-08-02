<?php declare(strict_types=1);

namespace BenRowan\DoctrineAssert\Tests\DoubleOneToOne;

use BenRowan\DoctrineAssert\DoctrineAssertTrait;
use BenRowan\DoctrineAssert\Tests\AbstractDoctrineAssertTest;
use Faker\Factory;
use Faker\ORM\Doctrine\Populator;
use PHPUnit\Framework\ExpectationFailedException;

class AssertDatabaseCountTest extends AbstractDoctrineAssertTest
{
    public const VFS_NAMESPACE = 'Vfs\\DoubleOneToOne\\';

    use DoctrineAssertTrait;

    protected function getVfsPath(): string
    {
        return __DIR__ . '/Vfs';
    }

    public function testSettingNoQueryConfigReturnsAllResults(): void
    {
        $generator = Factory::create();
        $populator = new Populator($generator, $this->getEntityManager());

        $populator->addEntity(self::VFS_NAMESPACE . 'Three', 100);
        $populator->addEntity(self::VFS_NAMESPACE . 'Two', 100);
        $populator->addEntity(self::VFS_NAMESPACE . 'One', 100);

        $populator->execute();

        $this->assertDatabaseCount(
            100,
            self::VFS_NAMESPACE . 'One',
            [
                self::VFS_NAMESPACE . 'Two' => [
                    self::VFS_NAMESPACE . 'Three' => []
                ]
            ]
        );
    }

    public function testSettingWrongCountFailsTest(): void
    {
        $this->expectException(ExpectationFailedException::class);

        $generator = Factory::create();
        $populator = new Populator($generator, $this->getEntityManager());

        $populator->addEntity(self::VFS_NAMESPACE . 'Three', 100);
        $populator->addEntity(self::VFS_NAMESPACE . 'Two', 100);
        $populator->addEntity(self::VFS_NAMESPACE . 'One', 100);

        $populator->execute();

        $this->assertDatabaseCount(
            50,
            self::VFS_NAMESPACE . 'One',
            [
                self::VFS_NAMESPACE . 'Two' => [
                    self::VFS_NAMESPACE . 'Three' => []
                ]
            ]
        );
    }

    public function testSettingSingleQueryConfigConstraintOnThirdEntityReturnsCorrectCount(): void
    {
        $generator = Factory::create();
        $populator = new Populator($generator, $this->getEntityManager());

        $populator->addEntity(self::VFS_NAMESPACE . 'Three', 50, [
            'active' => true
        ]);
        $populator->addEntity(self::VFS_NAMESPACE . 'Two', 50);
        $populator->addEntity(self::VFS_NAMESPACE . 'One', 50);
        $populator->execute();

        $populator->addEntity(self::VFS_NAMESPACE . 'Three', 50, [
            'active' => false
        ]);
        $populator->addEntity(self::VFS_NAMESPACE . 'Two', 50);
        $populator->addEntity(self::VFS_NAMESPACE . 'One', 50);
        $populator->execute();

        $this->assertDatabaseCount(
            50,
            self::VFS_NAMESPACE . 'One',
            [
                self::VFS_NAMESPACE . 'Two' => [
                    self::VFS_NAMESPACE . 'Three' => [
                        'active' => true
                    ]
                ]
            ]
        );

        $this->assertDatabaseCount(
            50,
            self::VFS_NAMESPACE . 'One',
            [
                self::VFS_NAMESPACE . 'Two' => [
                    self::VFS_NAMESPACE . 'Three' => [
                        'active' => false
                    ]
                ]
            ]
        );
    }

    public function testSettingDoubleQueryConfigConstraintOnThirdEntityReturnsCorrectCount(): void
    {
        $generator = Factory::create();
        $populator = new Populator($generator, $this->getEntityManager());

        $populator->addEntity(self::VFS_NAMESPACE . 'Three', 50, [
            'active' => true,
            'name'   => 'Aomame'
        ]);
        $populator->addEntity(self::VFS_NAMESPACE . 'Two', 50);
        $populator->addEntity(self::VFS_NAMESPACE . 'One', 50);
        $populator->execute();

        $populator->addEntity(self::VFS_NAMESPACE . 'Three', 50, [
            'active' => true,
            'name'   => 'Tamaru'
        ]);
        $populator->addEntity(self::VFS_NAMESPACE . 'Two', 50);
        $populator->addEntity(self::VFS_NAMESPACE . 'One', 50);
        $populator->execute();

        $populator->addEntity(self::VFS_NAMESPACE . 'Three', 50, [
            'active' => false,
            'name'   => 'Tengo'
        ]);
        $populator->addEntity(self::VFS_NAMESPACE . 'Two', 50);
        $populator->addEntity(self::VFS_NAMESPACE . 'One', 50);
        $populator->execute();

        $populator->addEntity(self::VFS_NAMESPACE . 'Three', 50, [
            'active' => false,
            'name'   => 'Eriko'
        ]);
        $populator->addEntity(self::VFS_NAMESPACE . 'Two', 50);
        $populator->addEntity(self::VFS_NAMESPACE . 'One', 50);
        $populator->execute();

        $this->assertDatabaseCount(
            50,
            self::VFS_NAMESPACE . 'One',
            [
                self::VFS_NAMESPACE . 'Two' => [
                    self::VFS_NAMESPACE . 'Three' => [
                        'active' => true,
                        'name'   => 'Aomame'
                    ]
                ]
            ]
        );

        $this->assertDatabaseCount(
            50,
            self::VFS_NAMESPACE . 'One',
            [
                self::VFS_NAMESPACE . 'Two' => [
                    self::VFS_NAMESPACE . 'Three' => [
                        'active' => true,
                        'name'   => 'Tamaru'
                    ]
                ]
            ]
        );

        $this->assertDatabaseCount(
            50,
            self::VFS_NAMESPACE . 'One',
            [
                self::VFS_NAMESPACE . 'Two' => [
                    self::VFS_NAMESPACE . 'Three' => [
                        'active' => false,
                        'name'   => 'Tengo'
                    ]
                ]
            ]
        );

        $this->assertDatabaseCount(
            50,
            self::VFS_NAMESPACE . 'One',
            [
                self::VFS_NAMESPACE . 'Two' => [
                    self::VFS_NAMESPACE . 'Three' => [
                        'active' => false,
                        'name'   => 'Eriko'
                    ]
                ]
            ]
        );
    }
}