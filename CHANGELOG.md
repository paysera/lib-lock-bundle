# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 2.2.1
### Changed
- `symfony/yaml` moved from `require` to `require-dev`: only the test configuration is YAML
### Removed
- `symfony/console` from `require`: the bundle never used it

## 2.2.0
### Added
- Support for Symfony 7.4
### Changed
- The services are defined in `PayseraLockExtension` instead of `Resources/config/services.xml`, which is removed, so Symfony 7.4 no longer reports that the XML configuration format is deprecated
- **BREAKING**: `PayseraLockExtension` extends `Symfony\Component\DependencyInjection\Extension\Extension` instead of HttpKernel's `Extension`, which is internal since Symfony 7.1. A subclass that calls HttpKernel's class-cache method `addAnnotatedClassesToCompile()` or its getter must drop those calls; Symfony has deprecated both
### Removed
- Support for `symfony/lock` 3.4: the bundle uses `LockFactory`, which 3.4 does not have
- Support for `symfony/lock` 4.4.0 and 4.4.1: their `LockFactory` does not accept the `PersistingStoreInterface` that the tests mock
- Support for Symfony 3 and 4.0 to 4.3 of `symfony/config`, `symfony/dependency-injection` and `symfony/http-kernel`: the bundle already requires `symfony/lock` and `symfony/console` 4.4, so no application on those Symfony versions could install it
- Support for `symfony/yaml` 7.0 to 7.3

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
