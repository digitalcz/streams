# Changelog

All notable changes will be documented in this file.

Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [Unreleased]

## [1.1.0] - 2025-09-22
### Added
- Add PHP 8.4 support
- Add memory protection with 100MB limit for string streams
- Add detailed system error messages in File class for better debugging
- Add comprehensive tests for large string handling and buffer size verification
- Add composer-normalize tool for consistent composer.json structure

### Changed
- Update development dependencies (PHPStan, coding standards)
- Normalize composer.json structure
- Improve type safety with updated PHPStan dependencies

### Fixed
- Fix incorrect buffer size calculation (1024 ^ 2 → 1024 * 1024) in BufferedStream and Stream classes
- Fix missing space in File exception message
- Fix missing space in clone operator in StreamWrapper

## [1.0.0]
First stable release 🚀

## [0.6.0]
### Changed
- Change the prefix in File::temp()

## [0.5.0]
### Added
- Improve test coverage for BufferedStream

### Changed
- Update dependencies

## [0.4.0]
### Added
- Add more tests
- Add StreamDecoratorTrait test
- Improve test coverage

## [0.3.0] 2022-02-27
### Added
- Add BufferedStream that buffers stream data, to make it seekable

### Removed
- Remove CachingStream in favor of BufferedStream

## [0.2.1] 2022-02-22
### Fixed
- Fixed StreamWrapper invalid schema

## [0.2.0] 2022-02-22
### Changed
- Refactor Stream to implement PSR-7 StreamInterface
- Use Decorator pattern for FileInterface implementations

### Added
- Add integration test for PSR-7 StreamInterface
- Add StreamWrapper
- Add CachingStream

### Removed
- Remove TempStream::fromStream() in favor of TempStream::from()
- Remove Stream::getHandle() in favor of StreamWrapper::from($stream)

## [0.1.0] 2022-02-17
First release 🚀
