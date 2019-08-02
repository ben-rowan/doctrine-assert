<?php declare(strict_types=1);

namespace BenRowan\DoctrineAssert\Tests;

use Doctrine\ORM\ORMException;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use Doctrine\ORM\Tools\EntityGenerator;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\ToolsException;
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
//        vfsStream::inspect(new vfsStreamPrintVisitor());
        $this->setupEntityManager();
        $this->generateEntities();
//        vfsStream::inspect(new vfsStreamPrintVisitor());
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

    private function setupVfs(): void
    {
        $this->rootDir = vfsStream::setup();

        vfsStream::copyFromFileSystem($this->getVfsPath());
    }

    /**
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

    private function generateEntities(): void
    {
        $classMetadataFactory = new DisconnectedClassMetadataFactory();
        $classMetadataFactory->setEntityManager($this->getEntityManager());
        $allMetadata = $classMetadataFactory->getAllMetadata();

        if (empty($allMetadata)) {
            throw new \InvalidArgumentException(
                'You need to configure a set of entity Fixtures for this test'
            );
        }

        $destinationPath = $this->rootDir->url();

        if (! file_exists($destinationPath)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Entities destination directory %s does not exist.',
                    $destinationPath
                )
            );
        }

        if (! is_writable($destinationPath)) {
            throw new \InvalidArgumentException(
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

    private function removeYmlFileExtension($fileName): string
    {
        $extensionLen = strlen('.dcm.yml');
        return substr($fileName, 0, -$extensionLen);
    }

    private function ymlFileNameToPath($fileName): string
    {
        return str_replace('.', '/', $fileName) . '.php';
    }

    private function ymlFileNameToVfsPath($fileName): string
    {
        $vfsRoot = $this->getRootDir()->url();

        return $vfsRoot . '/' . $this->ymlFileNameToPath($this->removeYmlFileExtension($fileName));
    }

    private function requireEntities(): void
    {
        $finder = new Finder();

        $finder->files()->in($this->getVfsPath() . self::CONFIG_PATH);

        foreach ($finder as $file) {
            $name       = $file->getFilename();
            $entityPath = $this->ymlFileNameToVfsPath($name);

            require_once $entityPath;
        }
    }

    /**
     * @throws ToolsException
     */
    private function updateSchema(): void
    {
        $allMetadata = $this->getEntityManager()
            ->getMetadataFactory()
            ->getAllMetadata();

        if (empty($allMetadata)) {
            throw new \InvalidArgumentException(
                'You need to configure a set of entity Fixtures for this test'
            );
        }

        $schemaTool = new SchemaTool($this->getEntityManager());
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($allMetadata);
    }
}