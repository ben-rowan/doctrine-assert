<?php declare(strict_types=1);

namespace BenRowan\DoctrineAssert\Tests\SingleEntity;

use BenRowan\DoctrineAssert\DoctrineAssertTrait;
use BenRowan\DoctrineAssert\Tests\AbstractDoctrineTest;
use Faker\Factory;
use Faker\ORM\Doctrine\Populator;

class AssertDatabaseCountTest extends AbstractDoctrineTest
{
    public const VFS_NAMESPACE = 'Vfs\\SingleEntity\\';

    use DoctrineAssertTrait;

    public function setUp(): void
    {
        parent::setUp();
    }

    protected function getVfsPath(): string
    {
        return __DIR__ . '/Vfs';
    }

    protected function loadFixtures()
    {
        $generator = Factory::create();
        $populator = new Populator($generator, $this->getEntityManager());
        $populator->addEntity('Vfs\\SingleEntity\\Thing', 100);
        $populator->execute();
    }

    public function testAssertsCorrectCount(): void
    {
        $this->assertDatabaseCount(
            100,
            self::VFS_NAMESPACE . 'Thing',
            []
        );
    }
}