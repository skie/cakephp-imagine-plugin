<?php
declare(strict_types = 1);

/**
 * Copyright 2011-2017, Florian Krämer
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * Copyright 2011-2017, Florian Krämer
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace Burzum\Imagine\Lib;

use BadFunctionCallException;
use RuntimeException;

/**
 * Imagine Utility class
 *
 * A collection of utility functions
 */
class ImagineUtility {

	/**
	 * Turns the operations and their params into a string that can be used in a file name to cache an image.
	 *
	 * Suffix your image with the string generated by this method to be able to batch delete a file that has versions of it cached.
	 * The intended usage of this is to store the files as my_horse.thumbnail+width-100-height+100.jpg for example.
	 *
	 * So after upload store your image meta data in a db, give the filename the id of the record and suffix it
	 * with this string and store the string also in the db. In the views, if no further control over the image access is needed,
	 * you can simply direct-link the image like $this->Html->image('/images/05/04/61/my_horse.thumbnail+width-100-height+100.jpg');
	 *
	 * @param array $operations Operations
	 * @param array $separators Separators
	 * @param bool $hash Hash the string, default is false
	 * @throws \BadFunctionCallException
	 * @return string Filename compatible String representation of the operations
	 * @link http://support.microsoft.com/kb/177506
	 */
	public static function operationsToString($operations, $separators = [], $hash = false) {
		ksort($operations);

		$defaultSeparators = [
			'operations' => '.',
			'params' => '+',
			'value' => '-'
		];
		$separators = array_merge($defaultSeparators, $separators);

		$result = '';
		foreach ($operations as $operation => $data) {
			$tmp = [];
			foreach ($data as $key => $value) {
				if (is_string($value) || is_numeric($value)) {
					$tmp[] = $key . $separators['value'] . $value;
				}
			}
			$result = $separators['operations'] . $operation . $separators['params'] . implode($separators['params'], $tmp);
		}

		if ($hash && $result !== '') {
			if (function_exists($hash) || is_callable($hash)) {
				return $hash($result);
			}
			throw new BadFunctionCallException();
		}

		return $result;
	}

	/**
	 * This method expects an array of Model.configName => operationsArray
	 *
	 * @param array $imageSizes Image sizes
	 * @param int $hashLength Hash length, default is 8
	 * @return array Model.configName => hashValue
	 */
	public static function hashImageOperations($imageSizes, $hashLength = 8) {
		foreach ($imageSizes as $model => $operations) {
			foreach ($operations as $name => $operation) {
				$imageSizes[$model][$name] = substr(self::operationsToString($operation, [], 'md5'), 0, $hashLength);
			}
		}

		return $imageSizes;
	}

	/**
	 * Gets the orientation of an image file.
	 *
	 * @param string $imageFile Image file to get the orientation from.
	 * @return int The degree of orientation.
	 */
	public static function getImageOrientation($imageFile) {
		if (!file_exists($imageFile)) {
			throw new RuntimeException(sprintf('File %s not found!', $imageFile));
		}

		// God damn php bug...
		// * https://stackoverflow.com/questions/37352371/php-exif-read-data-illegal-ifd-size
		// * https://bugs.php.net/bug.php?id=74956
		$exif = @exif_read_data($imageFile);
		if ($exif === false) {
			return false;
		}

		$angle = 0;
		if (!empty($exif['Orientation'])) {
			switch ($exif['Orientation']) {
				case 0:
					$angle = 0;
					break;
				case 3:
					$angle = 180;
					break;
				case 6:
					$angle = -90;
					break;
				case 8:
					$angle = 90;
					break;
				default:
					$angle = 0;
					break;
			}

			return $angle;
		}

		return $angle;
	}
}
