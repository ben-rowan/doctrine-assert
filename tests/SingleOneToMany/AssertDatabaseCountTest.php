<?php declare(strict_types=1);

namespace BenRowan\DoctrineAssert\Tests\SingleOneToMany;

use BenRowan\DoctrineAssert\DoctrineAssertTrait;
use BenRowan\DoctrineAssert\Tests\AbstractDoctrineAssertTest;
use Faker\Factory;
use Faker\ORM\Doctrine\Populator;

class AssertDatabaseCountTest extends AbstractDoctrineAssertTest
{
    public const VFS_NAMESPACE = 'Vfs\\SingleOneToMany\\';

    use DoctrineAssertTrait;

    protected function getVfsPath(): string
    {
        return __DIR__ . '/Vfs';
    }

    public function testSettingNoQueryConfigReturnsAllResults(): void
    {
        $generator = Factory::create();
        $populator = new Populator($generator, $this->getEntityManager());

        $populator->addEntity(self::VFS_NAMESPACE . 'One', 50, [
            'name' => 'One'
        ]);
        $populator->addEntity(self::VFS_NAMESPACE . 'Two', 50, [
            'active' => true
        ]);
        $populator->execute();

        $this->assertDatabaseCount(
            50,
            self::VFS_NAMESPACE . 'One',
            []
        );
    }

    public function testSettingValueBeforeJoinGivesCorrectResult(): void
    {
        $generator = Factory::create();
        $populator = new Populator($generator, $this->getEntityManager());

        $populator->addEntity(self::VFS_NAMESPACE . 'One', 50, [
            'name' => 'One'
        ]);
        $populator->addEntity(self::VFS_NAMESPACE . 'Two', 50, [
            'active' => true
        ]);
        $populator->execute();

        $this->assertDatabaseCount(
            50,
            self::VFS_NAMESPACE . 'One',
            [
                'name' => 'One',
                self::VFS_NAMESPACE . 'Two' => [
                    'active' => true
                ]
            ]
        );
    }
}