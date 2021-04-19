Twyne CLI client
================

This is a simple command-line client for [Twyne](https://github.com/samwilson/twyne).

[![CI](https://github.com/samwilson/twyne-client/actions/workflows/ci.yml/badge.svg)](https://github.com/samwilson/twyne-client/actions/workflows/ci.yml)

## Usage

### upload

Upload files.

    upload [-u|--url URL] [-k|--apikey APIKEY] [-t|--tags TAGS] [--timezone TIMEZONE] [-a|--author AUTHOR] [-g|--group GROUP] [--] <source>

* `--url` `-u` — The Twyne site base URL.
* `--apikey` `-k` — The Twyne API key.
* `--tags` `-t` — Semicolon-separated tags.
* `--timezone` — The timezone of the times in the EXIF data of any uploaded photos.
  Default: 'Z'
* `--author` `-a` — Author name.
* `--group` `-g` — The name of the user group.
  Default: 'Private'
* `<source>` A directory or file name.

### configure

Configure the Twyne client and store the configuration in a local file.

    configure [-c|--config CONFIG]

## Licence: MIT

Copyright 2021 Sam Wilson.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software
and associated documentation files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or
substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING
BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

