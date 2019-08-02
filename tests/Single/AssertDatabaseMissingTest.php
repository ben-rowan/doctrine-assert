<?php declare(strict_types=1);

namespace BenRowan\DoctrineAssert\Tests;

use BenRowan\DoctrineAssert\DoctrineAssertTrait;
use Faker\Factory;
use Faker\ORM\Doctrine\Populator;
use PHPUnit\Framework\ExpectationFailedException;

class AssertDatabaseMissingTest extends AbstractDoctrineAssertTest
{
    public const VFS_NAMESPACE = 'Vfs\\Single\\';

    use DoctrineAssertTrait;

    protected function getVfsPath(): string
    {
        return __DIR__ . '/Vfs';
    }

    public function testSettingNoQueryConfigFails(): void
    {
        $this->expectException(ExpectationFailedException::class);

        $generator = Factory::create();
        $populator = new Populator($generator, $this->getEntityManager());

        $populator->addEntity(
            self::VFS_NAMESPACE . 'Thing',
            1
        );

        $populator->execute();

        $this->assertDatabaseMissing(
            self::VFS_NAMESPACE . 'Thing',
            []
        );
    }

    public function testSettingMatchingQueryConfigFails(): void
    {
        $this->expectException(ExpectationFailedException::class);

        $generator = Factory::create();
        $populator = new Populator($generator, $this->getEntityManager());

        $populator->addEntity(
            self::VFS_NAMESPACE . 'Thing',
            1,
            [
                'name' => 'Thing'
            ]
        );

        $populator->execute();

        $this->assertDatabaseMissing(
            self::VFS_NAMESPACE . 'Thing',
            [
                'name' => 'Thing'
            ]
        );
    }

    public function testSettingNonMatchingQueryConfigPasses(): void
    {
        $generator = Factory::create();
        $populator = new Populator($generator, $this->getEntityManager());

        $populator->addEntity(
            self::VFS_NAMESPACE . 'Thing',
            1,
            [
                'name' => 'Thing'
            ]
        );

        $populator->execute();

        $this->assertDatabaseMissing(
            self::VFS_NAMESPACE . 'Thing',
            [
                'name' => 'Something'
            ]
        );
    }
}