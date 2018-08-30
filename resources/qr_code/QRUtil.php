<?php
/**
 * This file is part of the phpQr package
 *
 * See @see QRCode class for description of package and license.
 */

/**
 * Import necessary dependencies
 */
require_once 'QRCodeException.php';
require_once 'QRPolynominal.php';

/**
 * Derived exception
 *
 * @author Maik Greubel <greubel@nkey.de>
 * @package phpQr
 */
class QRUtilException extends QRCodeException {};

/**
 * Mask pattern enumeration
 * 
 * @author Maik Greubel <greubel@nkey.de>
 * @package phpQr
 */
abstract class QRMaskPattern
{
  const PATTERN000 = 0;
  const PATTERN001 = 1;
  const PATTERN010 = 2;
  const PATTERN011 = 3;
  const PATTERN100 = 4;
  const PATTERN101 = 5;
  const PATTERN110 = 6;
  const PATTERN111 = 7;
}

/**
 * The purpose of this class is to provide some common utility
 * functionality for the QRCode class and its parts.
 *  
 * @author Maik Greubel <greubel@nkey.de>
 * @package phpQr
 */
class QRUtil
{
  /**
   * Pattern position table
   * 
   * @var array
   */
  private $PATTERN_POSITION_TABLE = null;
  
  /**
   * 
   * @var int G15 pattern
   */
  private $G15;
  
  /**
   * 
   * @var int G18 pattern
   */
  private $G18;
  
  /**
   * 
   * @var int G15 mask pattern
   */
  private $G15_MASK;
  
  /**
   * 
   * @var QRUtil Singleton
   */
  private static $instance;
  
  /**
   * Singleton pattern
   * 
   * @return QRUtil
   */
  public static function getInstance()
  {
    if(!self::$instance)
    {
      self::$instance = new self;
    }
    
    return self::$instance;
  }
  
  /**
   * Create a new instance of QRUtil
   */
  private function __construct()
  {
    $this->init();
    $this->G15 = ((1 << 10) | (1 << 8) | (1 << 5) | (1 << 4) | (1 << 2) | (1 << 1) | (1 << 0));
    $this->G18 = ((1 << 12) | (1 << 11) | (1 << 10) | (1 << 9) | (1 << 8) | (1 << 5) | (1 << 2) | (1 << 0));
    $this->G15_MASK = ((1 << 14) | (1 << 12) | (1 << 10) | (1 << 4) | (1 << 1));
  }
  
  /**
   * Retrieve the Bose-Chaudhuri-Hocquenghem code type info
   * 
   * @param array $data
   * 
   * @return int
   */
  public function getBCHTypeInfo($data)
  {
    $d = $data << 10;
    while($this->getBCHDigit($d) - $this->getBCHDigit($this->G15) >= 0)
    {
      $d ^= ($this->G15 << ($this->getBCHDigit($d) - $this->getBCHDigit($this->G15)));
    }
    
    return (($data << 10) | $d) ^ $this->G15_MASK;
  }
  
  /**
   * Retrieve the Bose-Chaudhuri-Hocquenghem code type number
   * 
   * @param array $data
   * 
   * @return int
   */
  public function getBCHTypeNumber($data)
  {
    $d = $data << 12;
    while($this->getBCHDigit($d) - $this->getBCHDigit($this->G18) >= 0)
    {
      $d ^= ($this->G18 << ($this->getBCHDigit($d) - $this->getBCHDigit($this->G18)));
    }
    return ($data << 12) | $d;
  }
  
  /**
   * Retrieve the Bose-Chaudhuri-Hocquenghem digit
   * 
   * @param array $data
   * 
   * @return int
   */
  public function getBCHDigit($data)
  {
    $digit = 0;
    while($data != 0)
    {
      $digit++;
      $data = $data >> 1;
    }
    
    return $digit;
  }
  
  /**
   * Return the pattern position
   * 
   * @param int $typeNumber
   * @return array
   */
  public function getPatternPosition($typeNumber)
  {
    return $this->PATTERN_POSITION_TABLE[$typeNumber - 1];
  }
  
  /**
   * Return whether to mask a bit
   * 
   * @param int $maskPattern
   * @param int $i
   * @param int $j
   * @throws QRUtilException
   * @return boolean
   */
  public function getMask($maskPattern, $i, $j)
  {
    switch($maskPattern)
    {
      case QRMaskPattern::PATTERN000: return ($i + $j) % 2 == 0;
      case QRMaskPattern::PATTERN001: return ($i % 2) == 0;
      case QRMaskPattern::PATTERN010: return ($j % 3) == 0;
      case QRMaskPattern::PATTERN011: return ($i + $j) % 3 == 0;
      case QRMaskPattern::PATTERN100: return (floor($i / 2) + floor($j / 3)) % 2 == 0;
      case QRMaskPattern::PATTERN101: return ($i * $j) % 2 + ($i * $j) % 3 == 0;
      case QRMaskPattern::PATTERN110: return (($i * $j) % 2 + ($i * $j) % 3) % 2 == 0;
      case QRMaskPattern::PATTERN111: return (($i * $j) % 3 + ($i + $j) % 2) % 2 == 0;
      
      default: throw new QRUtilException("Bad mask pattern " . $maskPattern); 
    }
  }
  
  /**
   * Return error correction polynom
   * 
   * @param int $errorCorrectLength
   * @return QRPolynominal
   */
  public function getErrorCorrectPolynominal($errorCorrectLength)
  {
    $a = new QRPolynominal(array(1), 0);
    for($i = 0; $i < $errorCorrectLength; $i++)
    {
      $a = $a->multiply(new QRPolynominal(array(1, QRMath::getInstance()->gexp($i)), 0));
    }
    
    return $a;
  }
  
  /**
   * Get the bitmap length in bits
   * 
   * @param int $mode
   * @param int $type
   * @throws QRUtilException
   * @return int
   */
  public function getLengthInBits($mode, $type)
  {
    // 1 - 9
    if(1 <= $type && $type < 10)
    {
      switch($mode)
      {
        case QRMode::MODE_NUMBER:     return 10;
        case QRMode::MODE_ALPHA_NUM:  return 9;
        case QRMode::MODE_8BIT_BYTE:  return 8;
        case QRMode::MODE_KANJI:      return 8;
        default: throw new QRUtilException("Bad mode " . $mode);
      }
    }
    // 10 - 26
    else if($type < 27)
    {
      switch($mode)
      {
        case QRMode::MODE_NUMBER:     return 12;
        case QRMode::MODE_ALPHA_NUM:  return 11;
        case QRMode::MODE_8BIT_BYTE:  return 16;
        case QRMode::MODE_KANJI:      return 10;
        default: throw new QRUtilException("Bad mode " . $mode);
      }
    }
    // 27 - 40
    else if($type < 41)
    {
      switch($mode)
      {
        case QRMode::MODE_NUMBER:     return 14;
        case QRMode::MODE_ALPHA_NUM:  return 13;
        case QRMode::MODE_8BIT_BYTE:  return 16;
        case QRMode::MODE_KANJI:      return 12;
        default: throw new QRUtilException("Bad mode " . $mode);
        
      }      
    }
    else
    {
      throw new QRUtilException("Bad type " . $type);
    }
  }
  
  /**
   * Calculate the lost point
   * 
   * @param QRCode $qrCode
   * 
   * @return number
   */
  public function getLostPoint(QRCode $qrCode)
  {
    $moduleCount = $qrCode->getModuleCount();
    
    $lostPoint = 0;
    
    // Level 1
    for($row = 0; $row < $moduleCount; $row++)
    {
      for($col = 0; $col < $moduleCount; $col++)
      {
        $sameCount = 0;
        $dark = $qrCode->isDark($row, $col);
        
        for($r = -1; $r <= 1; $r++)
        {
          if($row + $r < 0 || $moduleCount <= $row + $r)
          {
            continue;
          }
          
          for($c = -1; $c <= 1; $c++)
          {
            if($col + $c < 0 || $moduleCount <= $col + $c)
            {
              continue;
            }
            
            if($r == 0 && $c == 0)
            {
              continue;
            }
            
            if($dark == $qrCode->isDark($row + $r, $col + $c))
            {
              $sameCount++;
            }
          }
        }
        
        if($sameCount > 5)
        {
          $lostPoint += (3 + $sameCount - 5);
        }
      }
    }
    
    // Level 2
    for($row = 0; $row < $moduleCount - 1; $row++)
    {
      for($col = 0; $col < $moduleCount - 1; $col++)
      {
        $count = 0;
        if($qrCode->isDark($row,      $col    )) $count++;
        if($qrCode->isDark($row + 1,  $col    )) $count++;
        if($qrCode->isDark($row,      $col + 1)) $count++;
        if($qrCode->isDark($row + 1,  $col + 1)) $count++;
        if($count == 0 || $count == 4)
        {
          $lostPoint += 3;
        }
      }
    }
    
    // Level 3
    for($row = 0; $row < $moduleCount; $row++)
    {
      for($col = 0; $col < $moduleCount - 6; $col++)
      {
        if($qrCode->isDark($row, $col)
          && !$qrCode->isDark($row, $col + 1)
          &&  $qrCode->isDark($row, $col + 2)
          &&  $qrCode->isDark($row, $col + 3)
          &&  $qrCode->isDark($row, $col + 4)
          && !$qrCode->isDark($row, $col + 5)
          &&  $qrCode->isDark($row, $col + 6))
        {
          $lostPoint += 40;
        }
      }
    }
    
    for($col = 0; $col < $moduleCount; $col++)
    {
      for($row = 0; $row < $moduleCount - 6; $row++)
      {
        if($qrCode->isDark($row, $col)
          && !$qrCode->isDark($row + 1, $col)
          &&  $qrCode->isDark($row + 2, $col)
          &&  $qrCode->isDark($row + 3, $col)
          &&  $qrCode->isDark($row + 4, $col)
          && !$qrCode->isDark($row + 5, $col)
          &&  $qrCode->isDark($row + 6, $col))
        {
          $lostPoint += 40;
        }
      }
    }
    
    // Level 4
    $darkCount = 0;
    
    for($col = 0; $col < $moduleCount; $col++)
    {
      for($row = 0; $row < $moduleCount; $row++)
      {
        if($qrCode->isDark($row, $col))
        {
          $darkCount++;
        }
      }
    }
    
    $ratio = abs(100 * $darkCount / $moduleCount / $moduleCount - 50) / 5;
    $lostPoint += $ratio * 10;
    
    return $lostPoint;
  }
  
  /**
   * Initialize the pattern position table
   */
  private function init()
  {
    $this->PATTERN_POSITION_TABLE = array();
    
    $this->addPattern(array());
    $this->addPattern(array(6, 18));
    $this->addPattern(array(6, 22));
    $this->addPattern(array(6, 26));
    $this->addPattern(array(6, 30));
    $this->addPattern(array(6, 34));
    $this->addPattern(array(6, 22, 38));
    $this->addPattern(array(6, 24, 42));
    $this->addPattern(array(6, 26, 46));
    $this->addPattern(array(6, 28, 50));
    $this->addPattern(array(6, 30, 54));
    $this->addPattern(array(6, 32, 58));
    $this->addPattern(array(6, 34, 62));
    $this->addPattern(array(6, 26, 46, 66));
    $this->addPattern(array(6, 26, 48, 70));
    $this->addPattern(array(6, 26, 50, 74));
    $this->addPattern(array(6, 30, 54, 78));
    $this->addPattern(array(6, 30, 56, 82));
    $this->addPattern(array(6, 30, 58, 86));
    $this->addPattern(array(6, 34, 62, 90));
    $this->addPattern(array(6, 28, 50, 72, 94));
    $this->addPattern(array(6, 26, 50, 74, 98));
    $this->addPattern(array(6, 30, 54, 78, 102));
    $this->addPattern(array(6, 28, 54, 80, 106));
    $this->addPattern(array(6, 32, 58, 84, 110));
    $this->addPattern(array(6, 30, 58, 86, 114));
    $this->addPattern(array(6, 34, 62, 90, 118));
    $this->addPattern(array(6, 26, 50, 74, 98, 122));
    $this->addPattern(array(6, 30, 54, 78, 102, 126));
    $this->addPattern(array(6, 26, 52, 78, 104, 130));
    $this->addPattern(array(6, 30, 56, 82, 108, 134));
    $this->addPattern(array(6, 34, 60, 86, 112, 138));
    $this->addPattern(array(6, 30, 58, 86, 114, 142));
    $this->addPattern(array(6, 34, 62, 90, 118, 146));
    $this->addPattern(array(6, 30, 54, 78, 102, 126, 150));
    $this->addPattern(array(6, 24, 50, 76, 102, 128, 154));
    $this->addPattern(array(6, 28, 54, 80, 106, 132, 158));
    $this->addPattern(array(6, 32, 58, 84, 110, 136, 162));
    $this->addPattern(array(6, 26, 54, 82, 110, 138, 166));
    $this->addPattern(array(6, 30, 58, 86, 114, 142, 170));
  }
  
  /**
   * Add a pattern to the pattern position table
   * 
   * @param array $d
   */
  private function addPattern($d)
  {
    array_push($this->PATTERN_POSITION_TABLE, $d);
  }
  
  /**
   * Create an empty array of n elements
   * 
   * All elements are uninitialed.
   * 
   * @param int $numElements
   * @return array
   */
  public function createEmptyArray($numElements)
  {
    return array_fill(0, $numElements, null);
  }
}