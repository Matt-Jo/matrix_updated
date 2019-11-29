<?php
use Zend\Cache\StorageFactory;
use Zend\Cache\Psr\CacheItemPool\CacheItemPoolDecorator;

/**
 * PSR-6 compliant Cache Manager
 * 
 * Currently works as an adapter for zendframework/cache library
 * But the vendor should be relatively easy to replace by a another
 * PSR-6 implementation.
 * 
 * @todo: remove exposure of vendor constructs. Instead expose only standard (PSR) or our own interfaces. 
 */
class cache_manager implements cache_service_interface {
    use ck_singleton_trait;

    const DEFAULT_HOST = '127.0.0.1';
    const DEFAULT_PORT =  11211;
    const DEFAULT_PERSISTENT = true;
    const DEFAULT_WEIGHT  = 1;
    const DEFAULT_TIMEOUT = 1;
    const DEFAULT_RETRY_INTERVAL = 15;
    const DEFAULT_STATUS = true;
    const DEFAULT_FAILURE_CALLBACK = null;

    protected $pool;

    protected function __constructor($params) {
        $plugins = [];

        if(service_locator::get_config_service()->is_development) {
            $cacheLogger = function (\Exception $e) use ($logger) {
                $message = sprintf(
                    '[CACHE] %s:%s %s "%s"',
                    $exception->getFile(),
                    $exception->getLine(),
                    $exception->getCode(),
                    $exception->getMessage()
                );
                $logger->error($message);
            };

            $plugins = [
                'exceptionhandler' => [
                    'exception_callback' => $cacheLogger,
                    'throw_exceptions' => true,
                ],
            ];
        }

        $storage = StorageFactory::factory([
            'adapter' => [
                'name'              => 'memcached',
                'options'           => [
                    'servers'       => [
                        [
                            'host'                  => self::DEFAULT_HOST,
                            'port'                  => self::DEFAULT_PORT,
                            'persistent'            => self::DEFAULT_PERSISTENT,
                            'weight'                => self::DEFAULT_WEIGHT,
                            'timeout'               => self::DEFAULT_TIMEOUT,
                            'retry_interval'        => self::DEFAULT_RETRY_INTERVAL,
                            'status'                => self::DEFAULT_STATUS,
                            'failure_callback'      => self::DEFAULT_FAILURE_CALLBACK
                        ]
                    ],
                    'lib_options'   =>  [
                        'lifetime'					=> 86400,
                        'automatic_serialization'	=> true
                    ],
                    'plugins'       =>  $plugins
                ],
            ],
        ]);
        $this->pool = new CacheItemPoolDecorator($storage);
    }
    


    /**
     * Returns a Cache Item representing the specified key.
     *
     * This method must always return a CacheItemInterface object, even in case of
     * a cache miss. It MUST NOT return null.
     *
     * @param string $key
     *   The key for which to return the corresponding Cache Item.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return CacheItemInterface
     *   The corresponding Cache Item.
     */
    public function get_item(string $key) {
        return $this->pool->getItem($key);
    }

    /**
     * Returns a traversable set of cache items.
     *
     * @param string[] $keys
     *   An indexed array of keys of items to retrieve.
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return array|\Traversable
     *   A traversable collection of Cache Items keyed by the cache keys of
     *   each item. A Cache item will be returned for each key, even if that
     *   key is not found. However, if no keys are specified then an empty
     *   traversable MUST be returned instead.
     */
    public function get_items(array $keys = []) {
        return $this->pool->getItems($keys);
    }

    /**
     * Confirms if the cache contains specified cache item.
     *
     * Note: This method MAY avoid retrieving the cached value for performance reasons.
     * This could result in a race condition with CacheItemInterface::get(). To avoid
     * such situation use CacheItemInterface::isHit() instead.
     *
     * @param string $key
     *   The key for which to check existence.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if item exists in the cache, false otherwise.
     */
    public function has_item($key) {
        return $this->pool->hasItem($key);
    }

    /**
     * Deletes all items in the pool.
     *
     * @return bool
     *   True if the pool was successfully cleared. False if there was an error.
     */
    public function clear() {
        return $this->pool->clear();
    }

    /**
     * Removes the item from the pool.
     *
     * @param string $key
     *   The key to delete.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully removed. False if there was an error.
     */
    public function delete_item($key) {
        return $this->pool->deleteItem($key);
    }

    /**
     * Removes multiple items from the pool.
     *
     * @param string[] $keys
     *   An array of keys that should be removed from the pool.

     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the items were successfully removed. False if there was an error.
     */
    public function delete_items(array $keys) {
        return $this->pool->deleteItems($keys);
    }

    /**
     * Persists a cache item immediately.
     *
     * @param CacheItemInterface $item
     *   The cache item to save.
     *
     * @return bool
     *   True if the item was successfully persisted. False if there was an error.
     */
    public function save(CacheItemInterface $item) {
        return $this->pool->save($item);
    }

    /**
     * Sets a cache item to be persisted later.
     *
     * @param CacheItemInterface $item
     *   The cache item to save.
     *
     * @return bool
     *   False if the item could not be queued or if a commit was attempted and failed. True otherwise.
     */
    public function save_deferred(CacheItemInterface $item) {
        return $this->pool->saveDeferred($item);
    }

    /**
     * Persists any deferred cache items.
     *
     * @return bool
     *   True if all not-yet-saved items were successfully saved or there were none. False otherwise.
     */
    public function commit() {
        return $this->pool->commit();
    }


}

