<?php declare(strict_types=1);

namespace BenRowan\DoctrineAssert\Tests;

use BenRowan\DoctrineAssert\DoctrineAssertTrait;
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

    public function testSettingNoQueryConfigFails(): void
    {
        $this->expectException(ExpectationFailedException::class);

        $generator = Factory::create();
        $populator = new Populator($generator, $this->getEntityManager());

        $populator->addEntity(self::VFS_NAMESPACE . 'One', 1);
        $populator->addEntity(self::VFS_NAMESPACE . 'Two', 1);
        $populator->execute();

        $this->assertDatabaseMissing(
            self::VFS_NAMESPACE . 'One',
            []
        );
    }

    public function testSettingMatchingQueryConfigFails(): void
    {
        $this->expectException(ExpectationFailedException::class);

        $generator = Factory::create();
        $populator = new Populator($generator, $this->getEntityManager());

        $populator->addEntity(self::VFS_NAMESPACE . 'Two', 1,
            [
                'active' => true
            ]
        );
        $populator->addEntity(self::VFS_NAMESPACE . 'One', 1);
        $populator->execute();

        $this->assertDatabaseMissing(
            self::VFS_NAMESPACE . 'One',
            [
                self::VFS_NAMESPACE . 'Two' => [
                    'active' => true
                ]
            ]
        );
    }

    public function testSettingNonMatchingQueryConfigPasses(): void
    {
        $generator = Factory::create();
        $populator = new Populator($generator, $this->getEntityManager());

        $populator->addEntity(self::VFS_NAMESPACE . 'Two', 1,
            [
                'active' => true
            ]
        );
        $populator->addEntity(self::VFS_NAMESPACE . 'One', 1);
        $populator->execute();

        $this->assertDatabaseMissing(
            self::VFS_NAMESPACE . 'One',
            [
                self::VFS_NAMESPACE . 'Two' => [
                    'active' => false
                ]
            ]
        );
    }
}