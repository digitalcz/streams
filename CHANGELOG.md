# Changelog

All notable changes will be documented in this file.

Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

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
