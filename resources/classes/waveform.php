<?php
/**
 *
 *
 * @author    MaximAL
 * @since     2019-02-13 Added `$onePhase` parameters to get only positive waveform data and image
 * @since     2018-10-22 Added `getWaveformData()` method and `$soxCommand` configuration
 * @since     2016-11-21
 * @date      2016-11-21
 * @time      19:08
 * @link      http://maximals.ru
 * @link      http://sijeko.ru
 * @link      https://github.com/maximal/audio-waveform-php
 * @copyright © MaximAL, Sijeko 2016-2019
 *
 * @modified  fusionate
 * @since     2024-02-07 Added option to return image in base64 format by setting $filename to 'base64'
 * @since     2024-02-08 Added `$singleAxis` parameter to combine channels (if stereo) into single axis
 * @since     2024-02-08 Added `$colorA` and `$colorB` parameters to allow different colors for each channel
 * @since     2024-02-08 Rename `$onePhase` parameter to `$singlePhase` and change to public static variable for class
 * @since     2024-02-08 Modified singleAxis so channel 2 would display as negative waveform data when singlePhase
 *            enabled
 *
 *
 */

namespace maximal\audio;

use Exception;

/**
 * Waveform class allows you to get waveform data and images from audio files
 *
 * @package maximal\audio
 */
class Waveform {
	public static $linesPerPixel = 8;
	public static $samplesPerLine = 512;
public static $singlePhase;
public static $singleAxis;
	public static $color = [95, 95, 95, 0.5];
public static $colorA;
public static $colorB;
	public static $backgroundColor = [245, 245, 245, 1];
		public static $axisColor = [0, 0, 0, 0.1]; // set `true` to get positive waveform phase only, `false` to get both positive and negative waveform phases
		public static $soxCommand = 'sox'; // combine double or single phases to use same axis

	// Colors in CSS `rgba(red, green, blue, opacity)` format
	protected $filename;
		protected $info; // color of left channel (1)
		protected $channels; // color of right channel (2)
	protected $samples;
	protected $sampleRate;

	// SoX command: 'sox', '/usr/local/bin/sox' etc
	protected $duration;

	/**
	 * Initializes a new instance of this class with the specified filename.
	 *
	 * @param string $filename The name of the file associated with this instance.
	 *
	 * @access public
	 */
	public function __construct($filename) {
		$this->filename = $filename;
	}

	/**
	 * Retrieves the duration of the current media file.
	 *
	 * If the duration has not been retrieved yet, it will be fetched from the media server.
	 *
	 * @return float The duration of the media file in seconds.
	 *
	 * @access public
	 */
	public function getDuration() {
		if (!$this->duration) {
			$this->getInfo();
		}
		return $this->duration;
	}

	/**
	 * Get waveform from the audio file.
	 *
	 * @param string $filename Image file name
	 * @param int    $width    Width of the image file in pixels
	 * @param int    $height   Height of the image file in pixels
	 *
	 * @return bool Returns `true` on success or `false` on failure, when generating an image file, or a base64 string.
	 * @throws \Exception
	 */
	public function getWaveform($filename, $width, $height) {
		// Calculating parameters
		$needChannels = $this->getChannels() > 1 ? 2 : 1;
		$data = $this->getWaveformData($width, self::$singlePhase ?? false);
		$lines1 = $data['lines1'];
		$lines2 = $data['lines2'];

		// Creating image
		$img = imagecreatetruecolor($width, $height);
		imagesavealpha($img, true);
		//if (function_exists('imageantialias')) {
		//	imageantialias($img, true);
		//}

		// Colors
		$back = self::rgbaToColor($img, self::$backgroundColor);
		$color = self::rgbaToColor($img, self::$color);
		$colorA = self::$colorA ? self::rgbaToColor($img, self::$colorA) : null;
		$colorB = self::$colorB ? self::rgbaToColor($img, self::$colorB) : null;
		$axis = self::rgbaToColor($img, self::$axisColor);
		$singleAxis = self::$singleAxis ?? false;
		imagefill($img, 0, 0, $back);

		// Center Ys
		if ($singleAxis) {
			$center1 = $center2 = $height / 2;
		} else {
			if (self::$singlePhase ?? false) {
				$center1 = $needChannels === 2 ? $height / 2 - 1 : $height - 1;
				$center2 = $needChannels === 2 ? $height - 1 : null;
			} else {
				$center1 = $needChannels === 2 ? ($height / 2 - 1) / 2 : $height / 2;
				$center2 = $needChannels === 2 ? $height - $center1 : null;
			}
		}

		// Drawing channel 1
		for ($i = 0; $i < count($lines1); $i += 2) {
			$x = $i / 2 / self::$linesPerPixel;
			if (self::$singlePhase ?? false) {
				$max = max($lines1[$i], $lines1[$i + 1]);
				@imageline($img, $x, $center1, $x, $center1 - $max * $center1, $colorA ?? $color);
			} else {
				$min = $lines1[$i];
				$max = $lines1[$i + 1];
				@imageline($img, $x, $center1 - $min * $center1, $x, $center1 - $max * $center1, $colorA ?? $color);
			}
		}
		// Drawing channel 2
		for ($i = 0; $i < count($lines2); $i += 2) {
			$x = $i / 2 / self::$linesPerPixel;
			if (self::$singlePhase ?? false) {
				$max = max($lines2[$i], $lines2[$i + 1]);
				if ($singleAxis) {
					@imageline($img, $x, $center1, $x, $center1 + $max * $center2, $colorB ?? $color);
				} else {
					@imageline($img, $x, $center2, $x, $center2 - $max * $center1, $colorB ?? $color);
				}
			} else {
				if ($singleAxis) {
					$min = $lines2[$i];
					$max = $lines2[$i + 1];
					@imageline($img, $x, $center1 - $min * $center1, $x, $center1 - $max * $center1, $colorB ?? $color);
				} else {
					$min = $lines2[$i];
					$max = $lines2[$i + 1];
					@imageline($img, $x, $center2 - $min * $center1, $x, $center2 - $max * $center1, $colorB ?? $color);
				}
			}
		}

		// Axis
		@imageline($img, 0, $center1, $width - 1, $center1, $axis);
		if ($center2 !== null) {
			@imageline($img, 0, $center2, $width - 1, $center2, $axis);
		}

		if ($filename == 'base64') {
			ob_start();
			imagepng($img);
			$image_data = ob_get_clean();
			return base64_encode($image_data);
		} else {
			return imagepng($img, $filename);
		}
	}

	/**
	 * Retrieves a collection of channels.
	 *
	 * If no channels have been loaded yet, the {@link getInfo()} method is called to load them first.
	 *
	 * @return array A collection of channel objects.
	 *
	 * @access public
	 */
	public function getChannels() {
		if (!$this->channels) {
			$this->getInfo();
		}
		return $this->channels;
	}

	/**
	 * Get waveform data from the audio file.
	 *
	 * @param int $width Desired width of the image file in pixels
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function getWaveformData($width) {
		// Calculating parameters
		$needChannels = $this->getChannels() > 1 ? 2 : 1;
		$samplesPerPixel = self::$samplesPerLine * self::$linesPerPixel;
		$needRate = 1.0 * $width * $samplesPerPixel * $this->getSampleRate() / $this->getSamples();

		//if ($needRate > 4000) {
		//	$needRate = 4000;
		//}

		// Command text
		$command = self::$soxCommand . ' ' . escapeshellarg($this->filename) .
			' -c ' . $needChannels .
			' -r ' . $needRate . ' -e floating-point -t raw -';

		//var_dump($command);

		$outputs = [
			1 => ['pipe', 'w'],  // stdout
			2 => ['pipe', 'w'],  // stderr
		];
		$pipes = null;
		$proc = proc_open($command, $outputs, $pipes);
		if (!$proc) {
			throw new Exception('Failed to run `sox` command');
		}

		$lines1 = [];
		$lines2 = [];
		while ($chunk = fread($pipes[1], 4 * $needChannels * self::$samplesPerLine)) {
			$data = unpack('f*', $chunk);
			$channel1 = [];
			$channel2 = [];
			foreach ($data as $index => $sample) {
				if ($needChannels === 2 && $index % 2 === 0) {
					$channel2 [] = $sample;
				} else {
					$channel1 [] = $sample;
				}
			}
			if (self::$singlePhase ?? false) {
				// Rectifying to get positive values only
				$lines1 [] = abs(min($channel1));
				$lines1 [] = abs(max($channel1));
				if ($needChannels === 2) {
					$lines2 [] = abs(min($channel2));
					$lines2 [] = abs(max($channel2));
				}
			} else {
				// Two phases
				$lines1 [] = min($channel1);
				$lines1 [] = max($channel1);
				if ($needChannels === 2) {
					$lines2 [] = min($channel2);
					$lines2 [] = max($channel2);
				}
			}
		}

		$err = stream_get_contents($pipes[2]);
		$ret = proc_close($proc);

		if ($ret !== 0) {
			throw new Exception('Failed to run `sox` command. Error:' . PHP_EOL . $err);
		}

		return ['lines1' => $lines1, 'lines2' => $lines2];
	}

	/**
	 * Retrieves the sample rate associated with this instance.
	 *
	 * If the sample rate has not been previously retrieved, it will be obtained
	 * by calling getInfo(). The sample rate is then cached for future retrieval.
	 *
	 * @return int|null The sample rate in Hz, or null if unable to retrieve the information.
	 *
	 * @access public
	 */
	public function getSampleRate() {
		if (!$this->sampleRate) {
			$this->getInfo();
		}
		return $this->sampleRate;
	}

	/**
	 * Retrieves information about the audio file associated with this instance.
	 *
	 * @access public
	 */
	public function getInfo() {
		$out = null;
		$ret = null;
		exec(self::$soxCommand . ' --i ' . escapeshellarg($this->filename) . ' 2>&1', $out, $ret);
		$str = implode('|', $out);

		$match = null;
		if (preg_match('/Channels?\s*\:\s*(\d+)/ui', $str, $match)) {
			$this->channels = intval($match[1]);
		}

		$match = null;
		if (preg_match('/Sample\s*Rate\s*\:\s*(\d+)/ui', $str, $match)) {
			$this->sampleRate = intval($match[1]);
		}

		$match = null;
		if (preg_match('/Duration.*[^\d](\d+)\s*samples?/ui', $str, $match)) {
			$this->samples = intval($match[1]);
		}

		if ($this->samples && $this->sampleRate) {
			$this->duration = 1.0 * $this->samples / $this->sampleRate;
		}

		if ($ret !== 0) {
			throw new Exception('Failed to get audio info.' . PHP_EOL . 'Error: ' . implode(PHP_EOL, $out) . PHP_EOL);
		}
	}

	/**
	 * Retrieves a collection of sample data.
	 *
	 * If no samples have been retrieved yet, this method will call getInfo() to populate the internal samples list.
	 *
	 * @return int The collection of sample data.
	 *
	 * @access public
	 */
	public function getSamples() {
		if (!$this->samples) {
			$this->getInfo();
		}
		return $this->samples;
	}

	/**
	 * Converts an RGBA color to a PHP image color with alpha channel.
	 *
	 * @param resource $img  The PHP image resource to convert the color for.
	 * @param array    $rgba An array containing the red, green, blue and alpha values of the color.
	 *
	 * @return int The allocated color index.
	 *
	 * @access public
	 * @static
	 */
	public static function rgbaToColor($img, $rgba) {
		return imagecolorallocatealpha($img, $rgba[0], $rgba[1], $rgba[2], round((1 - $rgba[3]) * 127));
	}
}
