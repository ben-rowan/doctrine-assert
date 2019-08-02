<?php declare(strict_types=1);

namespace BenRowan\DoctrineAssert\Tests;

use function count;
use RuntimeException;

/**
 * Trait GeneratorTrait
 *
 * @package BenRowan\DoctrineAssert\Tests
 *
 * @SuppressWarnings(PHPMD.UnusedLocalVariable) - Mess Detector is getting confused by $current
 */
trait GeneratorTrait
{
    protected function createGenerator(array $data): callable
    {
        $generator = new class($data) {
            /**
             * Current index into data
             *
             * @var int
             */
            private $current = 0;
            /**
             * Data to be provided to the consumer
             *
             * @var array
             */
            private $data;

            public function __construct(array $data)
            {
                $this->data = array_values($data);
            }

            public function generate()
            {
                if ($this->current >= count($this->data)) {
                    throw new RuntimeException('No more data left in generator');
                }

                $generated = $this->data[$this->current];

                $this->current++;

                return $generated;
            }
        };

        return function () use ($generator) { return $generator->generate(); };
    }
}