# Changelog

All notable changes will be documented in this file.

Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [Unreleased]
### Added
- `File::fromString()` that always treats the string as content, never as a path

### Deprecated
- Passing a string to `File::from()`: an existing path opens that file instead of using the string as content, which is unsafe with untrusted input. Use `File::fromString()` or `new File($path)`

### Fixed
- `Stream::copy()` returns the total number of copied bytes instead of the size of the last chunk
- `Stream::copy()` no longer throws when the source is non-seekable and already at EOF
- `BufferedStream::seek()` past the buffered data after a rewind lands on the requested position
- `BufferedStream::seek()` with `SEEK_END` reads the source to the end when its size is unknown
- `StreamWrapper` no longer detaches a wrapped decorator (`File`, `TempFile`, `BufferedStream`) when the resource is cast, e.g. by `stream_select()`; casting is only supported for a wrapped `Stream`
- `StreamWrapper` reports failed seek, tell, read and write to `fseek()`, `fread()` and `fwrite()` instead of throwing

## [1.2.0] - 2026-10-15
### Changed
- Require PHP 8.2 or newer (PHP 8.1 is end-of-life)
- Allow PHPUnit 12, drop PHPUnit 10
- Use PHPUnit attributes instead of doc-comment metadata in tests
- Allow `symfony/var-dumper` 8 and `digitalcz/coding-standard` 0.6 in development dependencies

## [1.1.1] - 2026-06-15
### Added
- Add usage examples to README
- Add `.editorconfig`

### Changed
- Update codecov badge to point to the `1.x` branch
- Remove dead `export-ignore` entries from `.gitattributes`

### Fixed
- `Stream::__toString()` no longer throws on non-readable/detached streams, conforming to PSR-7

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
