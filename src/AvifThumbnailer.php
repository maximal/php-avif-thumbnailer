<?php

/**
 *
 * @author MaximAL
 * @since 2022-11-23
 * @date 2022-11-23
 * @time 20:32
 * @copyright © MaximAL, Sijeko 2022
 * @link https://github.com/maximal/php-avif-thumbnailer
 */

namespace Maximal\Thumbnailers;

use Imagine\Gd\Imagine as GdImagine;
use Imagine\Gmagick\Imagine as GmagickImagine;
use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use Imagine\Image\ManipulatorInterface;
use Imagine\Imagick\Imagine as ImagickImagine;
use RuntimeException;

/**
 * `AvifThumbnailer` class generates AVIF thumbnails as well as thumbnails in original image format (like PNG or JPEG)
 *
 * It can be useful for optimizing graphics in your web application.
 */
class AvifThumbnailer
{
	/** Path of thumbnail cache directory */
	public static string $cachePath = 'assets/thumbnails';

	/** URL of thumbnail cache directory */
	public static string $cacheUrl = '/assets/thumbnails';

	/** Mode for new cache directories */
	public static int $dirMode = 0755;

	/** @var string[] File extensions to process */
	public static array $extensions = ['jpg', 'png'];

	/**
	 * FFMPEG utility command
	 *
	 * Set to `/usr/local/bin/ffmpeg` or other, depending on your environment.
	 */
	public static string $ffmpegCommand = 'ffmpeg';

	/** Imagine instance */
	private static ?ImagineInterface $imagine = null;

	/** imagick driver definition */
	public const DRIVER_IMAGICK = 'imagick';
	/** GD2 driver definition for Imagine implementation using the GD library */
	public const DRIVER_GD2 = 'gd2';
	/** gmagick driver definition */
	public const DRIVER_GMAGICK = 'gmagick';

	/**
	 * @var string[] the driver to use. This can be either a single driver name or an array of driver names.
	 * If the latter, the first available driver will be used.
	 */
	public static array $drivers = [self::DRIVER_IMAGICK, self::DRIVER_GD2, self::DRIVER_GMAGICK];

	/**
	 * @param string $path Path of the original image file
	 * @param int $width Width of generated thumbnail
	 * @param int $height Height of generated thumbnail
	 * @param bool $inset `true` for `THUMBNAIL_INSET` and `false` for `THUMBNAIL_OUTBOUND` mode
	 * @param array $options Key-value pairs of HTML attributes for the `&lt;img&gt;` tag
	 * @return string `&lt;picture&gt;` HTML tag with AVIF source and `&lt;img&gt;` fallback (thumbnail of initial type)
	 */
	public static function picture(
		string $path,
		int $width,
		int $height,
		bool $inset = true,
		array $options = []
	): string {
		if (!is_file($path)) {
			return sprintf(
				'<img src="#" alt="No File: %s" />',
				htmlspecialchars($path)
			);
		}

		$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
		if ($extension === 'jpeg') {
			// Normalize jpeg to jpg
			$extension = 'jpg';
		}

		// Building HTML attributes
		$attributes = [];
		foreach ($options as $attribute => $value) {
			$attribute = preg_replace('/[^a-z0-9_-]/i', '', $attribute);
			if (strtolower($attribute) === 'src') {
				continue;
			}
			$attributes [] = $attribute . '="' . htmlspecialchars($value) . '"';
		}

		if (!in_array($extension, self::$extensions)) {
			return sprintf(
				'<img src="%s"%s />',
				$path,
				count($attributes) > 0 ? (' ' . implode(' ', $attributes)) : ''
			);
		}

		$mode = $inset ? ManipulatorInterface::THUMBNAIL_INSET : ManipulatorInterface::THUMBNAIL_OUTBOUND;

		// Paths
		$thumbnailFileName = md5($path . '|' . $width . '|' . $height . '|' . $mode) . '.' . $extension;
		$thumbnailSubDir = substr($thumbnailFileName, 0, 2);
		$thumbnailDir = static::$cachePath . DIRECTORY_SEPARATOR . $thumbnailSubDir;
		$thumbnailPath = $thumbnailDir . DIRECTORY_SEPARATOR . $thumbnailFileName;
		if (!is_file($thumbnailPath) || filemtime($thumbnailPath) < filemtime($path)) {
			// Making directory if needed
			if (
				!is_dir($thumbnailDir) &&
				!mkdir($thumbnailDir, static::$dirMode, true) &&
				!is_dir($thumbnailDir)
			) {
				throw new RuntimeException(sprintf('Directory "%s" was not created', $thumbnailDir));
			}

			// Imagine object
			$image = static::getImagine()->open($path);

			$initialSize = $image->getSize();
			$iWidth = $initialSize->getWidth();
			$iHeight = $initialSize->getHeight();

			$iRatio = 1.0 * $iWidth / $iHeight;
			$ratio = 1.0 * $width / $height;

			if ($mode === ManipulatorInterface::THUMBNAIL_OUTBOUND) {
				// THUMBNAIL_OUTBOUND
				// Calculating Crop
				if ($ratio > $iRatio) {
					$croppedWidth = $iWidth;
					if ($height > $iHeight) {
						$croppedHeight = $iHeight;
					} else {
						$croppedHeight = max(round($croppedWidth / $ratio), $height);
					}
					$crop = 'crop=' . $croppedWidth . ':' . $croppedHeight .
						':0:' . round(($iHeight - $croppedHeight) / 2) .
						',scale=' . $width . ':' . $height;
				} else {
					$croppedHeight = $iHeight;
					if ($width > $iWidth) {
						$croppedWidth = $iWidth;
					} else {
						$croppedWidth = max(round($croppedHeight * $ratio), $width);
					}
					$crop = 'crop=' . $croppedWidth . ':' . $croppedHeight .
						':' . round(($iWidth - $croppedWidth) / 2) .
						':0,scale=' . $width . ':' . $height;
				}
			} else {
				// THUMBNAIL_INSET
				// Calculating Resize
				if ($ratio > $iRatio) {
					// Auto width
					$crop = 'scale=-1:min(' . $height . '\,in_w)';
				} else {
					// Auto height
					$crop = 'scale=min(' . $width . '\,in_w):-1';
				}
			}

			$image->thumbnail(new Box($width, $height), $mode)
				->save($thumbnailPath);

			$output = $code = null;
			exec(
				self::$ffmpegCommand . ' -hide_banner -loglevel error' .
				' -i ' . escapeshellarg($path) .
				' -vf ' . escapeshellarg($crop) .
				' -c:v libaom-av1 -still-picture 1 ' .
				' -y ' . escapeshellarg($thumbnailPath . '.avif'),
				$output,
				$code
			);
			$cache = $code === 0 ? 'new' : 'fail';
		} else {
			$cache = 'hit';
		}

		$url = static::$cacheUrl . '/' . $thumbnailSubDir . '/' . $thumbnailFileName;
		return sprintf(
			'<picture data-cache="%s">%s<img src="%s"%s /></picture>',
			$cache,
			$cache !== 'fail' ?
				sprintf('<source srcset="%s" type="image/avif" />', $url . '.avif') : '',
			$url,
			count($attributes) > 0 ? (' ' . implode(' ', $attributes)) : ''
		);
	}

	private static function getImagine(): ImagineInterface
	{
		if (self::$imagine === null) {
			self::$imagine = static::createImagine();
		}

		return self::$imagine;
	}

	/**
	 * Creates an `Imagine` object based on the specified [[driver]].
	 * @return ImagineInterface the new `Imagine` object
	 * @throws RuntimeException if [[driver]] is unknown or the system does not support any [[driver]].
	 */
	protected static function createImagine(): ImagineInterface
	{
		foreach (static::$drivers as $driver) {
			switch ($driver) {
				case self::DRIVER_IMAGICK:
					if (class_exists('Imagick', false)) {
						return new ImagickImagine();
					}
					break;
				case self::DRIVER_GMAGICK:
					if (class_exists('Gmagick', false)) {
						return new GmagickImagine();
					}
					break;
				case self::DRIVER_GD2:
					if (function_exists('gd_info')) {
						return new GdImagine();
					}
					break;
				default:
					throw new RuntimeException("Unknown driver: $driver");
			}
		}
		throw new RuntimeException(
			'Your system does not support any of these drivers: ' .
			implode(',', static::$drivers)
		);
	}
}
