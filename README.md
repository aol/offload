# Offload

Simplify cached PHP tasks: background refresh, last-known-good, and single writer.

[![Build Status](https://travis-ci.org/aol/offload.svg?branch=master)](https://travis-ci.org/aol/offload)
[![Coverage Status](https://coveralls.io/repos/aol/offload/badge.svg?branch=master&service=github)](https://coveralls.io/github/aol/offload?branch=master)
[![Code Climate](https://codeclimate.com/github/aol/offload/badges/gpa.svg)](https://codeclimate.com/github/aol/offload)
[![Latest Stable Version](https://poser.pugx.org/aol/offload/v/stable)](https://packagist.org/packages/aol/offload)
[![Latest Unstable Version](https://poser.pugx.org/aol/offload/v/unstable)](https://packagist.org/packages/aol/offload)
[![License](https://poser.pugx.org/aol/offload/license)](https://packagist.org/packages/aol/offload)

Example:

```php
$offload = new OffloadManager(/* ... */);

// Fetch a result and repopulate it if necessary.
$data = $offload->fetch('task-key', function () {

  // Perform a time consuming task...
  return $data;

})->getData();
```

This will run a task in the background and cache the returned `$data` under the `task-key`.
If the data is requested again, it will be returned immediately if in cache and a repopulation will be offload if the cache is stale.

## How's This Work?

Offload caches data with two timeframes, **fresh** and **stale**. These TTLs can be controlled using options,
see **Offload Options**.

- **Fresh** (cache hit): Data is immediately returned with **no** repopulation.
- **Stale** (cache hit, stale): Data is immediately returned and a repopulation is queued.
- **No Data** (cache miss): Forces the repopulation to run immediately and that data is then cached and returned.

Offload uses a task queue to keep track of stale cache hits that need to be repopulated. When
`$offload->drain()` is called, all tasks are run and the cache is repopulated. This is best to do once the
request is completed so that the overhead of repopulating cache does not interfere with returning a response
to the client quickly. See **Draining the Offload Queue**.

The `OffloadManager` can take any cache store implementing `OffloadCacheInterface`.

### Exclusive Tasks

Tasks are run as `exclusive` by default. This behavior can be changed using options, see **Offload Options**.

Exclusive task repopulation means there will ever only be a single concurrent stale repopulation for a given key.
This avoids the **rush to repopulate cache** that happens when a cached item expires.

The `OffloadManager` uses a lock implementation to provide this capability and can take any lock
implementing `OffloadLockInterface`.

### Initialization

Here is an example of using a memcached instance for caching and a redis instance for locking:

```php
// Setup a cache.
$cache = new OffloadCacheMemcached($memcached_instance);

// Setup a lock.
$lock = new OffloadLockRedis($predis_instance);

// Default options for offload manager:
$default_options = [
  'ttl_fresh'          => 5,    // Cache time in seconds
  'ttl_stale'          => 5,    // Stale cache time in seconds
  'exclusive'          => true, // Whether to run tasks exclusively by key (no concurrent repopulates for the same key)
  'background'         => true, // Whether to run tasks in the background
  'background_timeout' => 5,    // Timeout for exclusive background repopulates in seconds
];

// Create the offload manager.
$offload = new OffloadManager($cache, $lock, $default_options);
```

### Draining the Offload Queue

To drain the offload queue properly, it is best to setup a PHP shutdown handler.
This ensures the offload tasks will always be run at the end of the request. Example:

```php
// ...

register_shutdown_function(function () use ($offload) {
  if ($offload->hasWork()) {

    // Flush all buffers.
    while (ob_get_level()) {
      ob_end_flush();
    }
    flush();

    // End the request if possible (under PHP FPM).
    if (function_exists('fastcgi_finish_request')) {
      fastcgi_finish_request();
    }

    // Run all tasks in the queue.
    $offload->drain();
  }
});
```

## Deferreds

Offload supports returning deferred tasks from the repopulate callable. This allows several tasks to run in parallel
when the offload queue is drained.

For example, using [Guzzle Async Requests](http://guzzle.readthedocs.org/en/latest/quickstart.html#async-requests):

```php
$data = $offload->fetch('task-key', function () {

  // ...

  $promise = $guzzle_client->getAsync('http://www.example.com');

  return new OffloadDeferred([$promise, 'wait']);

})->getData();
```

The `OffloadDeferred` class takes a single callable that will wait for the result.
In the above example, `$promise->wait()` waits for the HTTP request to complete and returns the result.

The repopulate callable can return any class that implements the `OffloadDeferredInterface`, so you can
make adapters for custom async handling.

## API

The `OffloadManager` implements `OffloadManagerInterface` and exposes the following methods:

|`OffloadManager`||
|:---|:---|
|`fetch(...)`|Fetch data from cache and repopulate if necessary.|
|`fetchCached(...)`|Same as `fetch(...)` with a specific fresh cache TTL.|
|`queue(...)`|Queue a task to run.|
|`queueCached(...)`|Same as `queue(...)` with a specific fresh cache TTL.|
|`hasWork()`|Whether the offload manager has work.|
|`drain()`|Drain the offload manager task queue.|
|`getCache()`|An object for interacting with the cache manually.|

See below for more details on the above methods.

See **Offload Options** for more information on the `$options` that can be provided.
See **Offload Result** for more information on what `OffloadResult` details are returned.

### Fetching Data

#### `fetch`

`fetch($key, callable $repopulate, array $options = []): OffloadResult`

Check cache for data for the given `$key`.
If the data is in cache, return it immediately.
If the data is stale, schedule a repopulate to run when the offload manager is drained.

```php
$result = $offload->fetch($key, function () {
  // Perform long running task...
  return $data;
});
```

#### `fetchCached`

`fetchCached($key, $ttl_fresh, callable $repopulate, array $options = []): OffloadResult`

Same as `fetch`. The following are equivalent:

```php
$result = $offload->fetch($key, $repopulate, ['ttl_fresh' => 5]);
// is the same as:
$result = $offload->fetchCached($key, 5, $repopulate);
```

### Queuing Tasks

#### `queue`

`queue($key, callable $repopulate, array $options = []): void`

Queue a repopuate task to be run. Do not check cache. Takes similar options as `fetch`.

#### `queueCached`

`queueCached($key, $ttl_fresh, callable $repopulate, array $options = []): void`

Same as `queue`. The following are equivalent:

```php
$result = $offload->queue($key, $repopulate, ['ttl_fresh' => 5]);
// is the same as:
$result = $offload->queueCached($key, 5, $repopulate);
```

### Fetch/Queue Arguments

|Option|Type||
|:---|:---|:---|
|`$key`|`string`|The key of the data to store.|
|`$ttl_fresh`|`float`|The fresh TTL in seconds for cached data. This is only provided to `fetchCached` and `queueCached`.|
|`$repopulate`|`callable`|A callable that returns data to repopulate cache.|
|`$options`|`array`|Options for the offload (see **Offload Options**).|

### Marking a Result as "Bad"

Sometimes results returned from a repopulate are not in a good state and _should not be cached_.

The offload manager provides a `OffloadRun` instance to the repopulate callable that can be used to mark the result as bad, for example:

```php
$offload->fetch($key, function (OffloadRun $run) {

  // Get some data from a service...
  $object = $this->service->get($arguments);
  if (!$object->isValid()) {

    // If the data returned is not valid, mark the result as bad.
    // This will tell the offload manager *not to cache* the data.
    $run->setBad();

  }

});
```

### Offload Options

Offload options are provided as an array, for example:

```php
$options = [
  'ttl_fresh'  => 5,
  'ttl_stale'  => 10,
  'background' => true,
  // ...
];
$result = $offload->fetch($key, function () { /* ... */ }, $options);
```

|Option||
|:---|:---|
|`ttl_fresh`|How long to cache data regarded as fresh in seconds. Fresh data is not repopulated. Defaults to `0`.|
|`ttl_stale`|How long to cache data regarded as stale in seconds. Stale data is repopulated when fetched. This value is _added to_ the `ttl_fresh` to get a total cache time. Defaults to `5`.|
|`exclusive`|Whether to run the task exclusively (no other tasks for the same key can run concurrently). Defaults to `true`.|
|`background`|Whether to run the task in the background. This means it will wait until the offload manager is drained instead of repopulating immediately. Defaults to `true`.|
|`background_timeout`|How long to timeout exclusive background tasks in seconds. Defaults to `5`.|
|`background_release_lock`|Whether to release the repopulate lock as soon as the offloaded task is complete. Defaults to `true`. If set to `false`, offload will wait until the background timeout completes before allowing new repopulates.|

### Offload Result

The `OffloadResult` class provides the following methods:

|`OffloadResult`||
|:---|:---|
|`getData()`|The data returned from the repopulate callable.|
|`isFromCache()`|Whether the data came from cache.|
|`getExpireTime()`|When the data from cache expires (unix time seconds).|
|`getStaleTime()`|How long the data has been stale in seconds. If the value is less than zero, that's how far it is from becoming stale.|
|`isStale()`|Whether the result is from cache, but is stale.|


## License

  [BSD-3-Clause](LICENSE)
