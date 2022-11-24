# PHP thumbnailer with AVIF support

`AvifThumbnailer` is a thumbnail helper which allows you to generate
and cache image thumbnails in your PHP application on the fly.


## Installation

Install this library with Composer:
```bash
composer require "maximal/php-avif-thumbnailer"
```
or add
```
"maximal/php-avif-thumbnailer": "^1.0"
```
to the `require` section of your app’s `composer.json` file.


## Checking the environment

You will need FFMPEG with AVIF (AV1) coder installed in your system. 

For instance in Ubuntu/Debian it is included in `ffmpeg` package:
```bash
sudo apt install ffmpeg
```

Check the command:
```bash
ffmpeg -version
```
You should get an output with the version number. Every version after `4.3` should be fine.

If you have installed `ffmpeg` to a different command or path, configure
the static property `AvifThumbnailer::$ffmpegCommand` before using the helper
(see the example below).

More info about AVIF: https://avif.io/blog/


## Generating thumbnails

Use this thumbnailer in your PHP application:
```php
use Maximal\Thumbnailers\AvifThumbnailer;

echo AvifThumbnailer::picture('/path/to/img/image.png', $width, $height);
```

More options (`outbound`  instead of default `inset`; `alt` and `class`
attribute added):
```php
use Maximal\Thumbnailers\AvifThumbnailer;

echo AvifThumbnailer::picture(
	'/path/to/img/image.png',
	$width,
	$height,
	false,
	['alt' => 'Alt attribute', 'class' => 'img-responsive']
);
```

Custom `ffmpeg` command:
```php
use Maximal\Thumbnailers\AvifThumbnailer;

AvifThumbnailer::$ffmpegCommand = '/usr/local/bin/ffmpeg';
echo AvifThumbnailer::picture('/path/to/img/image.jpg', $width, $height);
```

The helper’s `picture()` method uses modern `<picture>` HTML tag as follows:
```html
<picture data-cache="hit|new|fail">
	<source srcset="/assets/thumbnails/...image.png.avif" type="image/avif" />
	<img src="/assets/thumbnails/...image.png" other-attributes="" />
</picture>
```

Here you have `image/avif` source for
[browsers which support AVIF images](https://caniuse.com/#search=AVIF)
and traditional (PNG, JPEG, TIFF, GIF) image fallback.


# Author

* Websites: https://maximals.ru and https://sijeko.ru
* StackOverflow story: http://stackoverflow.com/users/story/1021887
* Twitter: https://twitter.com/almaximal
* Telegram: https://t.me/maximal
