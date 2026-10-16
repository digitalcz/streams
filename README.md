# Streams

[![Latest Stable Version](http://poser.pugx.org/digitalcz/streams/v)](https://packagist.org/packages/digitalcz/streams) 
[![Total Downloads](http://poser.pugx.org/digitalcz/streams/downloads)](https://packagist.org/packages/digitalcz/streams) 
[![Latest Unstable Version](http://poser.pugx.org/digitalcz/streams/v/unstable)](https://packagist.org/packages/digitalcz/streams) 
[![License](http://poser.pugx.org/digitalcz/streams/license)](https://packagist.org/packages/digitalcz/streams) 
[![PHP Version Require](http://poser.pugx.org/digitalcz/streams/require/php)](https://packagist.org/packages/digitalcz/streams)
[![CI](https://github.com/digitalcz/streams/workflows/CI/badge.svg)](https://github.com/digitalcz/streams/actions)
[![codecov](https://codecov.io/gh/digitalcz/streams/branch/1.x/graph/badge.svg?token=QzZ5iMNkg3)](https://codecov.io/gh/digitalcz/streams)

Opinionated abstraction around PHP streams implementing PSR-7 StreamInterface.
It aims to improve working with files or remote streams in unified way.

Heavily inspired by guzzle/psr7.

## Install

Via [Composer](https://getcomposer.org/)

```bash
$ composer require digitalcz/streams
```

## Usage

All streams implement `DigitalCz\Streams\StreamInterface`, which extends PSR-7
`Psr\Http\Message\StreamInterface` and adds a few convenience methods
(`copy()`, void-returning `close()`/`seek()`, …).

### Stream

A wrapper around any PHP stream resource. Use `Stream::from()` to build one from
a string, a resource or another PSR-7 stream — the data is held in `php://temp`.

```php
use DigitalCz\Streams\Stream;

// From a string
$stream = Stream::from('Hello world');
echo $stream->getContents();        // "Hello world"

// From an existing resource
$stream = Stream::from(fopen('php://memory', 'rb+'));

// From another PSR-7 stream
$stream = Stream::from($psrStream);

// Wrapping a resource directly (optionally with a known size)
$stream = new Stream(fopen('data.bin', 'rb'));

// Copy another stream into this one (returns bytes written)
$bytes = $stream->copy($otherStream);
```

### File

A stream backed by a real file on disk. Adds `getPath()` and `delete()`.

```php
use DigitalCz\Streams\File;

$file = new File('/path/to/file.txt');      // opened with mode "rb+" by default
echo $file->getPath();

// Build a file from a string / resource / PSR-7 stream.
// A string that is an existing path opens that file, otherwise it is the content.
$file = File::from('contents written to a temp file');

$temp = File::temp();                        // empty file in the system temp dir
$file->delete();                             // close and unlink
```

### TempFile

Like `File`, but backed by `tmpfile()` — the underlying file is removed
automatically once the stream handle is closed.

```php
use DigitalCz\Streams\TempFile;

$temp = TempFile::from('temporary content');
echo $temp->getPath();
$temp->close();                              // file is deleted on close
```

### BufferedStream

Wraps a non-seekable / one-shot source stream and buffers what it reads into
`php://temp`, so the source can be re-read and seeked. It is read-only.

```php
use DigitalCz\Streams\BufferedStream;

$buffered = new BufferedStream($nonSeekableSource);
$head = $buffered->read(1024);
$buffered->rewind();                         // works even if the source could not seek
$all = $buffered->getContents();
```

### StreamWrapper

Turns a `StreamInterface` back into a native PHP stream resource, usable with
any function that expects one (`fread`, `fgets`, `stream_copy_to_stream`, …).

```php
use DigitalCz\Streams\StreamWrapper;

$resource = StreamWrapper::from($stream);
$line = fgets($resource);
```

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
$ composer csfix    # fix codestyle
$ composer checks   # run all checks 

# or separately
$ composer tests    # run phpunit
$ composer phpstan  # run phpstan
$ composer cs       # run codesniffer
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email devs@digital.cz instead of using the issue tracker.

## Credits

- [Digital Solutions s.r.o.][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

[link-author]: https://github.com/digitalcz
[link-contributors]: ../../contributors
