# Changelog

All notable changes will be documented in this file.

Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [0.6.0]
### Changed
- Change the prefix in File::temp()

## [0.5.0]
### Changed
- Dependencies updates

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
