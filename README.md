# lib-lock-bundle

Provides quick integration with `symfony/lock`

### Installation
```bash
composer require paysera/lib-lock-bundle
```

### Configuration
```yaml
paysera_lock:
    ttl: 5 # integer, optional
    redis_client: # service id, required
```
`ttl` - time for locks TTL in seconds, default 5 seconds.

`redis_client` - service id for any `Redis` client service supported by [symfony/lock](https://symfony.com/doc/master/components/lock.html#lock-store-redis)  

### Usage

* `LockManager::createLock($identifier)` - creates lock **but not acquires it**
* `LockManager::acquire($lock)` - acquires lock or throws `LockAcquiringException` on failure
* `LockManager::createAccuired($identifier)` - creates **acquired** lock or throws `LockAcquiringException`
* `LockManager::release($lock)` - releases lock

#### Example

```php
$lock = $this->lockManager->createLock($identifier);
try {
    $this->lockManager->acquire($lock);
} catch (LockAcquiringException $exception) {
    throw new Exception('...');
}

// do something when got lock

$lock->release();
```
OR
```php
try {
    $lock = $this->lockManager->createAcquired($identifier);
} catch (LockAcquiringException $exception) {
    throw new Exception('...');
}

// do rest

```
