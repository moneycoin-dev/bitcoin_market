<?php

namespace Nette;

use Nette\Application\UI;
use Nette\Forms\Controls\TextBase;
use Nette\Forms\FormContainer;
use Nette\Application\UI\Form;
use Nette\Utils\Html;
use Nette\Utils\Callback;
use Nette\Utils\Image;
use App\Controls\Cuts;


class CaptchaControl extends TextBase
{
	/*	 * #@+ character groups */
	const CONSONANTS = 'bcdfghjkmnpqrstvwxz';
	const VOWELS = 'aeiuy'; // not 'o'
	const NUMBERS = '123456789'; // not '0'
	/*	 * #@- */

	/** @var string */
	public static $defaultFontFile;

	/** @var int */
	public static $defaultFontSize = 30;

	/** @var array from Image::rgb() */
	public static $defaultTextColor = array('red' => 0, 'green' => 0, 'blue' => 0);

	/** @var int */
	public static $defaultTextMargin = 25;

	/** @var array from Image::rgb() */
	public static $defaultBackgroundColor = array('red' => 255, 'green' => 255, 'blue' => 255);

	/** @var int */
	public static $defaultLength = 5;

	/** @var int */
	public static $defaultImageHeight = 0;

	/** @var int */
	public static $defaultImageWidth = 0;

	/** @var int|bool */
	public static $defaultFilterSmooth = 1;

	/** @var int|bool */
	public static $defaultFilterContrast = -60;

	/** @var int */
	public static $defaultExpire = 10800; // 3 hours
	
	/** @var bool */
	public static $defaultUseNumbers = true;

	/** @var bool */
	private static $registered = false;

	/** @var Session */
	private static $session;

	/** @var string */
	private static $fontFile;

	/** @var int */
	private static $fontSize;

	/** @var array from Image::rgb() */
	private static $textColor;

	/** @var int */
	private $textMargin;

	/** @var array from Image::rgb() */
	private static $backgroundColor;

	/** @var int */
	private static $length;

	/** @var int */
	private $imageHeight;

	/** @var int */
	private $imageWidth;

	/** @var int|bool */
	private $filterSmooth;

	/** @var int|bool */
	private $filterContrast;

	/** @var int uniq id */
	private $uid;

	/** @var string */
	public static $word;

	/** @var int */
	private $expire;
	
	/** @var bool */
	private static $useNumbers;

	/**
	 * @return void
	 * @throws \Exception
	 */
	public function __construct()
	{
            
            
		if (!extension_loaded('gd')) {
			throw new Exception('PHP extension GD is not loaded.');
		}
		
		if (!self::$defaultFontFile)
			self::$defaultFontFile = dirname(__FILE__) . "/fonts/Vera.ttf";
                
                
                

		parent::__construct();

		$this->addFilter('strtolower');
		$this->label = Html::el('img');

		self::setFontFile(self::$defaultFontFile);
		self::setFontSize(self::$defaultFontSize);
		$this->setTextColor(self::$defaultTextColor);
		$this->setTextMargin(self::$defaultTextMargin);
		$this->setBackgroundColor(self::$defaultBackgroundColor);
		$this->setLength(self::$defaultLength);
		$this->setImageHeight(self::$defaultImageHeight);
		$this->setImageWidth(self::$defaultImageWidth);
		$this->setFilterSmooth(self::$defaultFilterSmooth);
		$this->setFilterContrast(self::$defaultFilterContrast);
		$this->setExpire(self::$defaultExpire);
		$this->useNumbers(self::$defaultUseNumbers);

                dump(self::$defaultFontSize);
                
                
		$this->setUid(uniqid());
	}

	/**
	 * Register CaptchaControl to FormContainer, start session and set $defaultFontFile (if not set)
	 * @return void
	 * @throws \InvalidStateException
	 */
	public static function register()
	{
		if (self::$registered)
			throw new InvalidStateException(__CLASS__ . " is already registered");

		//$session = self::getSessin();
		if (!$session->isStarted())
			$session->start();

		$session = $session->getSection('PavelMaca.Captcha');

		if (!self::$defaultFontFile)
			self::$defaultFontFile = dirname(__FILE__) . "/fonts/Vera.ttf";
                
              //  function (MyClass $obj, $arg, ...) { ... });
                //Cuts::callback(__CLASS__, 'addCaptcha')
                
               // $form->extensionMethod('addCaptcha', function($abs, $name){ return $abs[$name] ;});
                Nette\Forms\FormContainer::extensionMethod('Nette\Forms\FormContainer::addCaptcha', function(Form $form, $name){ return $form[$name] = new self; });
              
              //  $abs = $form;
                
           //     $form->addCaptcha('captcha', $abs);
               // $form->xa();
		self::$registered = TRUE;
	}

	/**
	 * Form container extension method. Do not call directly.
	 * @param Form
	 * @param string name
	 * @return CaptchaControl
	 */
	//public static function addCaptcha(Form $form, $name)
	//{
	//	return $form[$name] = new self;
	//}

	/*	 * **************** Setters & Getters **************p*m* */

	/**
	 * @param string path to font file
	 * @return CaptchaControl provides a fluent interface
	 * @throws \InvalidArgumentException
	 */
	public function setFontFile($path)
	{
		if (!empty($path) && file_exists($path)) {
			$this->fontFile = $path;
		} else {
			throw new InvalidArgumentException("Font file '" . $path . "' not found");
		}
		return $this;
	}

	/**
	 * @return string path to font file
	 */
	public static function getFontFile()
	{
		return self::$fontFile;
	}

	/**
	 * @param int
	 * @return CaptchaControl provides a fluent interface 
	 */
	public function setLength($length)
	{
		$this->length = (int) $length;
		return $this;
	}

	/**
	 * @return int
	 */
	public static function getLength()
	{
		return self::$length;
	}

	/**
	 * @param int
	 * @return CaptchaControl provides a fluent interface 
	 */
	public static function setFontSize($size)
	{
		$this->fontSize = (int) $size;
		return $this;
	}

	/**
	 * @return int 
	 */
	public static function getFontSize()
	{
		return self::$fontSize;
	}

	/**
	 * @param array red => 0-255, green => 0-255, blue => 0-255
	 * @return CaptchaControl provides a fluent interface
	 * @throws  \InvalidArgumentException
	 */
	public function setTextColor($rgb)
	{
		if (!isset($rgb["red"]) || !isset($rgb["green"]) || !isset($rgb["blue"])) {
			throw new InvalidArgumentException("TextColor must be valid rgb array, see Nette\Image::rgb()");
		}
		$this->textColor = Image::rgb($rgb["red"], $rgb["green"], $rgb["blue"]);
		return $this;
	}

	/**
	 * @return array generated by Image::rgb()
	 */
	public static function getTextColor()
	{
		return self::$textColor;
	}

	/**
	 * @param int 
	 * @return CaptchaControl provides a fluent interface 
	 */
	public function setTextMargin($margin)
	{
		$this->textMargin = (int) $margin;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getTextMargin()
	{
		return $this->textMargin;
	}

	/**
	 * @param array red 0-255, green 0-255, blue 0-255
	 * @return CaptchaControl provides a fluent interface
	 * @throws \InvalidArgumentException
	 */
	public function setBackgroundColor($rgb)
	{
		if (!isset($rgb["red"]) || !isset($rgb["green"]) || !isset($rgb["blue"])) {
			throw new InvalidArgumentException("BackgroundColor must be valid rgb array, see Nette\Image::rgb()");
		}
		$this->backgroundColor = Image::rgb($rgb["red"], $rgb["green"], $rgb["blue"]);
		return $this;
	}

	/**
	 * @return array generated by Image::rgb()
	 */
	public static function getBackgroundColor()
	{
		return self::$backgroundColor;
	}

	/**
	 * @param int 
	 * @return CaptchaControl provides a fluent interface 
	 */
	public function setImageHeight($heightt)
	{
		$this->imageHeight = (int) $heightt;
		return $this;
	}

	/**
	 * @return int 
	 */
	public function getImageHeight()
	{
		return $this->imageHeight;
	}

	/**
	 * @param int 
	 * @return CaptchaControl provides a fluent interface 
	 */
	public function setImageWidth($width)
	{
		$this->imageWidth = (int) $width;
		return $this;
	}

	/**
	 * @return int 
	 */
	public function getImageWidth()
	{
		return $this->imageWidth;
	}

	/**
	 * @param int|bool
	 * @return CaptchaControl provides a fluent interface
	 */
	public function setFilterSmooth($smooth)
	{
		$this->filterSmooth = $smooth;
		return $this;
	}

	/**
	 * @return int|bool
	 */
	public function getFilterSmooth()
	{
		return $this->filterSmooth;
	}

	/**
	 * @param int|bool
	 * @return CaptchaControl provides a fluent interface
	 */
	public function setFilterContrast($contrast)
	{
		$this->filterContrast = $contrast;
		return $this;
	}

	/**
	 * @return int|bool
	 */
	public function getFilterContrast()
	{
		return $this->filterContrast;
	}

	/**
	 * Set session expiration time
	 * @param int
	 * @return CaptchaControl provides a fluent interface
	 */
	public function setExpire($expire)
	{
		$this->expire = (int) $expire;
		return $this;
	}

	/**
	 * @return int 
	 */
	public function getExpire()
	{
		return $this->expire;
	}
	
	/**
	 * Use numbers in captcha image? 
	 * @param bool
	 * @return CaptchaControl provides a fluent interface
	 */
	public static function useNumbers($useNumbers = true)
	{
		self::$useNumbers = (bool) $useNumbers;
		return self::$useNumbers;
	}

	/**
	 * @param int
	 * @return void
	 */
	private function setUid($uid)
	{
		$this->uid = $uid;
	}

	/**
	 * @return int
	 */
	private function getUid()
	{
		return $this->uid;
	}

	/**
	 * @param int
	 * @param string
	 * @return void
	 * @throws \InvalidStateException
	 */
	private function setSession($uid, $word)
	{
		if (!self::$session)
			throw new InvalidStateException(__CLASS__ . ' session not found');


		self::$session->$uid = $word;
		self::$session->setExpiration($this->getExpire(), $uid);
	}

	/**
	 * @return string|bool return false if key not found 
	 * @throws \InvalidStateException
	 */
	private function getSession($uid)
	{
		if (!self::$session)
			throw new InvalidStateException(__CLASS__ . ' session not found');


		return isset(self::$session[$uid]) ? self::$session[$uid] : false;
	}

	/**
	 * Unset session key
	 * @param int
	 * @return void
	 */
	private function unsetSession($uid)
	{
		if (self::$session && isset(self::$session[$uid])) {
			unset(self::$session[$uid]);
		}
	}

	/**
	 * @return string
	 */
	private function getUidName()
	{
		return "_uid_" . $this->getName();
	}

	/**
	 * Get or generate random word for image
	 * @return string
	 */
	public static function getWord()
	{
		if (!self::$word) {
			$s = '';
			for ($i = 0; $i < self::getLength(); $i++) {
				if(self::$useNumbers === true && mt_rand(0, 10) % 3 === 0){
					$group = self::NUMBERS;
					$s .= $group{mt_rand(0, strlen($group) - 1)};
					continue;
				}
				$group = $i % 2 === 0 ? self::CONSONANTS : self::VOWELS;
				$s .= $group{mt_rand(0, strlen($group) - 1)};
			}
			self::$word = $s;
		}

		return self::$word;
	}

	/*	 * **************** TextBase **************p*m* */

	/**
	 * @param string deprecated
	 * @return Html
	 */
	public function getLabel($caption = NULL)
	{
		$this->setSession($this->getUid(), $this->getWord());

		$image = clone $this->label;
		$image->src = 'data:image/png;base64,' . $this->drawImage();
		//$image->width = $this->getImageWidth();
		//$image->height = $this->getImageHeight();

		if (!isset($image->alt))
			$image->alt = "Captcha";

		return $image;
	}

	/**
	 * This method will be called when the component (or component's parent)
	 * becomes attached to a monitored object. Do not call this method yourself.
	 * @param Nette\IComponent
	 * @return void
	 */
	protected function attached($form)
	{
		parent::attached($form);
		if ($form instanceof Form) {
			$name = $this->getUidName();
			$form[$name] = new Forms\Controls\HiddenField($this->getUid());
		}
	}

	/*	 * **************** Drawing image **************p*m* */

	/**
	 * Draw captcha image and encode to base64 string
	 * @return string
	 */
	private static function drawImage()
	{
		$word = self::getWord();
		$font = self::getFontFile();
		$size = self::getFontSize();
		$textColor = self::getTextColor();
		$bgColor = self::getBackgroundColor();

		$box = self::getDimensions();
		$width = $this->getImageWidth();
		$height = $this->getImageHeight();

		$first = Image::fromBlank($width, $height, $bgColor);
		$second = Image::fromBlank($width, $height, $bgColor);

		$x = ($width - $box['width']) / 2;
		$y = ($height + $box['height']) / 2;

		$first->fttext($size, 0, $x, $y, $textColor, $font, $word);

		$frequency = self::getRandom(0.05, 0.1);
		$amplitude = self::getRandom(2, 4);
		$phase = self::getRandom(0, 6);

		for ($x = 0; $x < $width; $x++) {
			for ($y = 0; $y < $height; $y++) {
				$sy = round($y + sin($x * $frequency + $phase) * $amplitude);
				$sx = round($x + sin($y * $frequency + $phase) * $amplitude);

				$color = $first->colorat($x, $y);
				$second->setpixel($sx, $sy, $color);
			}
		}

		$first->destroy();

		if (defined('IMG_FILTER_SMOOTH')) {
			$second->filter(IMG_FILTER_SMOOTH, $this->getFilterSmooth());
		}

		if (defined('IMG_FILTER_CONTRAST')) {
			$second->filter(IMG_FILTER_CONTRAST, $this->getFilterContrast());
		}

		// start buffering
		ob_start();
		imagepng($second->getImageResource());
		$contents = ob_get_contents();
		ob_end_clean();

		return base64_encode($contents);
	}

	/**
	 * Detects image dimensions and returns image text bounding box.
	 * @return array
	 */
	private static function getDimensions()
	{
            dump(self::getFontFile());
		$box = imagettfbbox(self::getFontSize(), 0, self::getFontFile(), self::getWord());
		$box['width'] = $box[2] - $box[0];
		$box['height'] = $box[3] - $box[5];

		if (self::getImageWidth() === 0) {
			self::setImageWidth($box['width'] + self::getTextMargin());
		}
		if (self::getImageHeight() === 0) {
			self::setImageHeight($box['height'] + self::getTextMargin());
		}

		return $box;
	}

	/**
	 * Returns a random number within the specified range.
	 * @param float lowest value
	 * @param float highest value
	 * @return float
	 */
	private function getRandom($min, $max)
	{
		return mt_rand() / mt_getrandmax() * ($max - $min) + $min;
	}

	/*	 * **************** Validation **************p*m* */

	/**
	 * Validate control. Do not call directly!
	 * @param CaptchaControl
	 * @return bool
	 * @throws \InvalidStateException
	 */
	public function validateCaptcha(CaptchaControl $control)
	{
		$parent = $control->getParent();
		$hiddenName = $control->getUidName();
		if (!isset($parent[$hiddenName])) {
			throw new InvalidStateException('Can\'t find ' . __CLASS__ . ' hidden field ' . $hiddenName . ' in parent');
		}

		$uid = $parent[$hiddenName]->getValue();

		$sessionValue = $control->getSession($uid);
		$control->unsetSession($uid);

		return ($sessionValue === $control->getValue());
	}

	/**
	 * @return Nette\Callback
	 */
	public function getValidator()
	{
		return \App\Controls\Cuts::callback($this, 'validateCaptcha');
	}
        
        public function render(){
          // $this->drawImage();
        }

}
