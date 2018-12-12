# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
