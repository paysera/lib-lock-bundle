# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 2.2.0
### Added
- Support for Symfony 7.4
### Changed
- The services are defined in `PayseraLockExtension` instead of `Resources/config/services.xml`, which is removed. Symfony 7.4 no longer reports that the XML configuration format is deprecated
- `PayseraLockExtension` extends `Symfony\Component\DependencyInjection\Extension\Extension` instead of `Symfony\Component\HttpKernel\DependencyInjection\Extension`, which is internal since Symfony 7.1. Breaking for subclasses that call the class-cache methods of the HttpKernel class (`addAnnotatedClassesToCompile()`, deprecated since Symfony 7.1, and its getter): register those classes from an extension that extends the HttpKernel class
### Removed
- Support for `symfony/lock` 3.4: the bundle uses `LockFactory`, which 3.4 does not have
- Support for `symfony/lock` 4.4.0 and 4.4.1, whose `LockFactory` does not accept a `PersistingStoreInterface` store
- Support for `symfony/config`, `symfony/dependency-injection` and `symfony/http-kernel` 3.0 to 3.4.46 and 4.0 to 4.3: 3.4.47 and 4.4 are the lowest releases, tested with the lowest dependencies on PHP 7.1, 8.0 and 8.4

## 2.1.0
### Added
- Support for symfony 6

## 2.0.2
### Added
- Support for symfony/config ^5.0

## 2.0.1
### Added
- Support for PHP >=7.1
- Support for Symfony 5.X
### Removed
- Support for PHP 7.0

## 2.0.0
### Removed
- Symfony 2.* support
### Changed
- Replaced symfony/symfony with symfony components

## 1.0.2
### Added
- Added support for Symfony 4.x

## 1.0.1
### Fixed
- Fixed faulty strict type declaration in `\Paysera\Bundle\LockBundle\Service\LockManager`

## 1.0.0
### Changed
- `\Paysera\Bundle\LockBundle\Service\LockManager::createLock` now creates lock without TTL
- Added strict type declaration in `\Paysera\Bundle\LockBundle\Service\LockManager`
- Updated README.md

## 0.2.1
### Changed
- `PayseraLockExtension` Definition method **setArgument** is replaced to **replaceArgument** so bundle will work properly on Symfony ^2.8

## 0.2.0
### Changed 
- `LockManager` now takes and return `LockInterface` instead of `Lock` which implements it 

## 0.1.1
### Changed
- Downgraded `phpunit` to `^6.0` to have PHP7.0 dev compatibility

## 0.1.0
### Added
- Initial release
