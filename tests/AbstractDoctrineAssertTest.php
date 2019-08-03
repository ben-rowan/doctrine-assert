<?php declare(strict_types=1);

namespace BenRowan\DoctrineAssert\Tests;

use Doctrine\ORM\ORMException;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use Doctrine\ORM\Tools\EntityGenerator;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\ToolsException;
use InvalidArgumentException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

abstract class AbstractDoctrineAssertTest extends TestCase
{
    public const CONFIG_PATH = '/config';

    public const ENTITY_GEN_GENERATE_ANNOTATIONS = true;
    public const ENTITY_GEN_GENERATE_METHODS     = true;
    public const ENTITY_GEN_REGENERATE_ENTITIES  = true;
    public const ENTITY_GEN_UPDATE_ENTITIES      = false;
    public const ENTITY_GEN_NUM_SPACES           = 4;
    public const ENTITY_GEN_BACKUP_EXISTING      = false;

    use GeneratorTrait;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var vfsStreamDirectory
     */
    private $rootDir;

    /**
     * @throws ORMException
     */
    public function setUp()
    {
        $this->setupVfs();
        $this->setupEntityManager();
        $this->generateEntities();
        $this->requireEntities();
        $this->updateSchema();
    }

    abstract protected function getVfsPath(): string;

    protected function getRootDir()
    {
        return $this->rootDir;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    /**
     * Setup the virtual file system for our entities to live in.
     */
    private function setupVfs(): void
    {
        $this->rootDir = vfsStream::setup();

        vfsStream::copyFromFileSystem($this->getVfsPath());
    }

    /**
     * Using the YAML mapping file config and an SQLite database setup
     * the entity manager for this test.
     *
     * @throws ORMException
     */
    private function setupEntityManager(): void
    {
        $isDevMode = true;

        $config = Setup::createYAMLMetadataConfiguration(
            [$this->rootDir->url() . self::CONFIG_PATH],
            $isDevMode
        );

        $connection = [
            'driver' => 'pdo_sqlite',
            'memory' => true
        ];

        $this->entityManager = EntityManager::create($connection, $config);
    }

    /**
     * Based on the YAML mapping files generate a set of entities in our virtual
     * file system.
     */
    private function generateEntities(): void
    {
        $classMetadataFactory = new DisconnectedClassMetadataFactory();
        $classMetadataFactory->setEntityManager($this->getEntityManager());
        $allMetadata = $classMetadataFactory->getAllMetadata();

        if (empty($allMetadata)) {
            throw new InvalidArgumentException(
                'You need to configure a set of entity Fixtures for this test'
            );
        }

        $destinationPath = $this->rootDir->url();

        if (! file_exists($destinationPath)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Entities destination directory %s does not exist.',
                    $destinationPath
                )
            );
        }

        if (! is_writable($destinationPath)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Entities destination directory %s does not have write permissions.',
                    $destinationPath
                )
            );
        }

        $entityGenerator = new EntityGenerator();

        $entityGenerator->setGenerateAnnotations(self::ENTITY_GEN_GENERATE_ANNOTATIONS);
        $entityGenerator->setGenerateStubMethods(self::ENTITY_GEN_GENERATE_METHODS);
        $entityGenerator->setRegenerateEntityIfExists(self::ENTITY_GEN_REGENERATE_ENTITIES);
        $entityGenerator->setUpdateEntityIfExists(self::ENTITY_GEN_UPDATE_ENTITIES);
        $entityGenerator->setNumSpaces(self::ENTITY_GEN_NUM_SPACES);
        $entityGenerator->setBackupExisting(self::ENTITY_GEN_BACKUP_EXISTING);

        $entityGenerator->generate($allMetadata, $destinationPath);
    }

    /**
     * Drops the yml file extension from the end of the filename
     *
     * @param string $ymlPath
     *
     * @return string
     */
    private function removeYmlFileExtension(string $ymlPath): string
    {
        // We don't use basename here because we want to keep the path

        return str_replace('.dcm.yml', '', $ymlPath);
    }

    /**
     * Converts the YAML mapping file path into the path of it's associated
     * generated entity.
     *
     * @param string $ymlPath
     *
     * @return string
     */
    private function ymlPathToEntityPath(string $ymlPath): string
    {
        $withoutExtension = $this->removeYmlFileExtension($ymlPath);

        return str_replace('.', '/', $withoutExtension) . '.php';
    }

    /**
     * Returns the path of the associated generated entity in the virtual file
     * system for this YAML mapping file.
     *
     * @param string $ymlPath
     *
     * @return string
     */
    private function ymlPathToVfsEntityPath(string $ymlPath): string
    {
        $vfsRoot = $this->getRootDir()->url();

        return $vfsRoot . '/' . $this->ymlPathToEntityPath($ymlPath);
    }

    /**
     * Requires all the generated entities so we can use them.
     */
    private function requireEntities(): void
    {
        $finder = new Finder();

        $finder->files()->in($this->getVfsPath() . self::CONFIG_PATH);

        foreach ($finder as $file) {
            $filename = $file->getFilename();
            $entity   = $this->ymlPathToVfsEntityPath($filename);

            require_once $entity;
        }
    }

    /**
     * Update the database schema to match our set of entities
     *
     * @throws ToolsException
     */
    private function updateSchema(): void
    {
        $allMetadata = $this->getEntityManager()
            ->getMetadataFactory()
            ->getAllMetadata();

        if (empty($allMetadata)) {
            throw new InvalidArgumentException(
                'You need to configure a set of entity Fixtures for this test'
            );
        }

        $schemaTool = new SchemaTool($this->getEntityManager());
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($allMetadata);
    }
}