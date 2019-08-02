<?php declare(strict_types=1);

namespace BenRowan\DoctrineAssert\Config;

use BenRowan\DoctrineAssert\Exceptions\DoctrineAssertException;
use function count;
use Countable;
use function is_array;
use Iterator;
use function key;
use function reset;

/**
 * Class QueryConfigIterator.
 *
 *
 *
 * @note This iterator consumes the provided query config.
 *       This means it can only be iterated through once.
 *
 * @package BenRowan\DoctrineAssert\Config
 */
class QueryConfigIterator implements Iterator, Countable
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var mixed
     */
    private $current;

    /**
     * @var array
     */
    private $queryConfig;

    /**
     * QueryConfigIterator constructor.
     *
     * @param array $queryConfig The query config to be iterated.
     */
    public function __construct(array $queryConfig)
    {
        $this->queryConfig = $queryConfig;
    }

    /**
     * Returns true if the current config value represents a child of the current entity.
     *
     * @return bool
     */
    public function currentIsChildConfig(): bool
    {
        return is_array($this->current);
    }

    /**
     * Returns true if the current config value represents a value of the current entity.
     *
     * @return bool
     */
    public function currentIsValue(): bool
    {
        return ! is_array($this->current);
    }

    /**
     * Returns the current field.
     *
     * @return string
     */
    public function key(): string
    {
        return $this->key;
    }

    /**
     * Returns the current config value.
     *
     * @return mixed
     */
    public function current()
    {
        if ($this->currentIsChildConfig()) {
            return new QueryConfigIterator($this->current);
        }

        return $this->current;
    }

    private function shift()
    {
        $this->current = reset($this->queryConfig);
        $this->key     = (string)key($this->queryConfig);

        unset($this->queryConfig[$this->key]);
    }

    /**
     * Moves forward to the next config field.
     */
    public function next(): void
    {
        $this->shift();
    }

    /**
     * Sets the config to the first field.
     */
    public function rewind(): void
    {
        $this->shift();
    }

    /**
     * Checks their are more fields to consume.
     *
     * @return boolean
     */
    public function valid(): bool
    {
        return 0 !== $this->count();
    }

    /**
     * Counts the number of fields left in the config.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->queryConfig);
    }

    /**
     * Returns a JSON representation of the current query config state.
     *
     * @return string
     */
    public function toJson(): string
    {
        $json = json_encode($this->queryConfig, JSON_PRETTY_PRINT);

        if (false === $json) {
            throw new DoctrineAssertException('Unable to encode query config as JSON');
        }

        return $json;
    }
}