<?php declare(strict_types=1);

namespace BenRowan\DoctrineAssert\Tests\Single;

use BenRowan\DoctrineAssert\DoctrineAssertTrait;
use BenRowan\DoctrineAssert\Tests\AbstractDoctrineAssertTest;
use Faker\Factory;
use Faker\ORM\Doctrine\Populator;
use PHPUnit\Framework\ExpectationFailedException;

class AssertDatabaseCountTest extends AbstractDoctrineAssertTest
{
    public const VFS_NAMESPACE = 'Vfs\\Single\\';

    use DoctrineAssertTrait;

    protected function getVfsPath(): string
    {
        return __DIR__ . '/Vfs';
    }

    public function testSettingNoQueryConfigReturnsAllResults(): void
    {
        $generator = Factory::create();
        $populator = new Populator($generator, $this->getEntityManager());

        $populator->addEntity(self::VFS_NAMESPACE . 'Thing', 100);
        $populator->execute();

        $this->assertDatabaseCount(
            100,
            self::VFS_NAMESPACE . 'Thing',
            []
        );
    }

    public function testWrongCountFailsTest(): void
    {
        $this->expectException(ExpectationFailedException::class);

        $generator = Factory::create();
        $populator = new Populator($generator, $this->getEntityManager());

        $populator->addEntity(self::VFS_NAMESPACE . 'Thing', 100);
        $populator->execute();

        $this->assertDatabaseCount(
            50,
            self::VFS_NAMESPACE . 'Thing',
            []
        );
    }

    public function testSettingSingleQueryConfigConstraintReturnsCorrectCount(): void
    {
        $generator = Factory::create();
        $populator = new Populator($generator, $this->getEntityManager());

        $populator->addEntity(self::VFS_NAMESPACE . 'Thing', 50, [
            'active' => false
        ]);
        $populator->execute();

        $populator->addEntity(self::VFS_NAMESPACE . 'Thing', 50, [
            'active' => true
        ]);
        $populator->execute();

        $this->assertDatabaseCount(
            50,
            self::VFS_NAMESPACE . 'Thing',
            [
                'active' => true
            ]
        );

        $this->assertDatabaseCount(
            50,
            self::VFS_NAMESPACE . 'Thing',
            [
                'active' => false
            ]
        );
    }

    public function testSettingDoubleQueryConfigConstraintReturnsCorrectCount(): void
    {
        $generator = Factory::create();
        $populator = new Populator($generator, $this->getEntityManager());

        $populator->addEntity(self::VFS_NAMESPACE . 'Thing', 50, [
            'active' => true,
            'name'   => 'Aomame'
        ]);
        $populator->execute();

        $populator->addEntity(self::VFS_NAMESPACE . 'Thing', 50, [
            'active' => true,
            'name'   => 'Tamaru'
        ]);
        $populator->execute();

        $populator->addEntity(self::VFS_NAMESPACE . 'Thing', 50, [
            'active' => false,
            'name'   => 'Tengo'
        ]);
        $populator->execute();

        $populator->addEntity(self::VFS_NAMESPACE . 'Thing', 50, [
            'active' => false,
            'name'   => 'Eriko'
        ]);
        $populator->execute();

        $this->assertDatabaseCount(
            50,
            self::VFS_NAMESPACE . 'Thing',
            [
                'active' => true,
                'name'   => 'Aomame'
            ]
        );

        $this->assertDatabaseCount(
            50,
            self::VFS_NAMESPACE . 'Thing',
            [
                'active' => true,
                'name'   => 'Tamaru'
            ]
        );

        $this->assertDatabaseCount(
            50,
            self::VFS_NAMESPACE . 'Thing',
            [
                'active' => false,
                'name'   => 'Tengo'
            ]
        );

        $this->assertDatabaseCount(
            50,
            self::VFS_NAMESPACE . 'Thing',
            [
                'active' => false,
                'name'   => 'Eriko'
            ]
        );
    }
}