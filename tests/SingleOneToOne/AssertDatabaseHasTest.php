<?php declare(strict_types=1);

namespace BenRowan\DoctrineAssert\Tests\SingleOneToOne;

use BenRowan\DoctrineAssert\DoctrineAssertTrait;
use BenRowan\DoctrineAssert\Tests\AbstractDoctrineAssertTest;
use Faker\Factory;
use Faker\ORM\Doctrine\Populator;
use PHPUnit\Framework\ExpectationFailedException;

class AssertDatabaseHasTest extends AbstractDoctrineAssertTest
{
    public const VFS_NAMESPACE = 'Vfs\\SingleOneToOne\\';

    use DoctrineAssertTrait;

    protected function getVfsPath(): string
    {
        return __DIR__ . '/Vfs';
    }

    public function testSettingNoQueryConfigPasses(): void
    {
        $generator = Factory::create();
        $populator = new Populator($generator, $this->getEntityManager());

        $populator->addEntity(self::VFS_NAMESPACE . 'One', 1);
        $populator->addEntity(self::VFS_NAMESPACE . 'Two', 1);
        $populator->execute();

        $this->assertDatabaseHas(
            self::VFS_NAMESPACE . 'One',
            []
        );
    }

    public function testSettingMatchingQueryConfigPasses(): void
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

        $populator->addEntity(self::VFS_NAMESPACE . 'Two', 1,
            [
                'active' => false
            ]
        );
        $populator->addEntity(self::VFS_NAMESPACE . 'One', 1);
        $populator->execute();

        $this->assertDatabaseHas(
            self::VFS_NAMESPACE . 'One',
            [
                self::VFS_NAMESPACE . 'Two' => [
                    'active' => true
                ]
            ]
        );

        $this->assertDatabaseHas(
            self::VFS_NAMESPACE . 'One',
            [
                self::VFS_NAMESPACE . 'Two' => [
                    'active' => false
                ]
            ]
        );
    }

    public function testSettingNonMatchingQueryConfigFails(): void
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

        $this->assertDatabaseHas(
            self::VFS_NAMESPACE . 'One',
            [
                self::VFS_NAMESPACE . 'Two' => [
                    'active' => false
                ]
            ]
        );
    }
}