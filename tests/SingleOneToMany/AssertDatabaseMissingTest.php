<?php declare(strict_types=1);

namespace BenRowan\DoctrineAssert\Tests\SingleOneToMany;

use BenRowan\DoctrineAssert\DoctrineAssertTrait;
use BenRowan\DoctrineAssert\Tests\AbstractDoctrineAssertTest;
use Faker\Factory;
use Faker\ORM\Doctrine\Populator;
use PHPUnit\Framework\ExpectationFailedException;

class AssertDatabaseMissingTest extends AbstractDoctrineAssertTest
{
    public const VFS_NAMESPACE = 'Vfs\\SingleOneToMany\\';

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
        $populator->addEntity(self::VFS_NAMESPACE . 'Two', 2);
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

        $populator->addEntity(self::VFS_NAMESPACE . 'One', 1,
            [
                'name' => 'One'
            ]
        );
        $populator->addEntity(self::VFS_NAMESPACE . 'Two', 2,
            [
                'name'   => 'Two',
                'active' => true
            ]
        );
        $populator->execute();

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

    public function testSettingNonMatchingQueryConfigPasses(): void
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
                'name'   => 'Two',
                'active' => true
            ]
        );
        $populator->execute();

        $this->assertDatabaseMissing(
            self::VFS_NAMESPACE . 'One',
            [
                'name' => 'One',
                self::VFS_NAMESPACE . 'Two' => [
                    'name'   => 'Two',
                    'active' => false
                ]
            ]
        );
    }
}