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
require_once 'QRErrorCorrectLevel.php';

/**
 * Derived exception class
 * 
 * @author Maik Greubel <greubel@nkey.de>
 * @package phpQr
 */
class QRRSBlockException extends QRCodeException
{ 
}

/**
 * This class is a Reed-Solomon implementation for the QRCode.
 * The purpose is to provide error correction and block information.
 *
 * Inspired by qrcode.js from https://github.com/jeromeetienne/jquery-qrcode
 * 
 * @author Maik Greubel <greubel@nkey.de>
 * @package phpQr
 * @link http://www.thonky.com/qr-code-tutorial/error-correction-table/
 */
class QRRSBlock
{
  /**
   * The total count of blocks
   * 
   * @var int The total count of blocks
   */
  private $totalCount;
  
  /**
   * The data count of blocks
   * 
   * @var int The data count of blocks
   */
  private $dataCount;
  
  /**
   * The block table
   * @var array The block table
   */
  private $RS_BLOCK_TABLE;
  
  /**
   * Singleton pattern
   * 
   * @var QRRSBlock Singleton
   */
  private static $instance;
  
  /**
   * The serialized block data for faster initialization
   * 
   * @var string
   */
  private $blockFileName = 'rsblock.dat';
  
  /**
   * Singleton pattern
   * 
   * @return QRRSBlock
   */
  public static function getInstance()
  {
    if(!self::$instance)
    {
      self::$instance = new self(0, 0);
    }
    
    return self::$instance;
  }
  
  /**
   * Retrieve the data count
   * 
   * @return int The data count
   */
  public function getDataCount()
  {
    return $this->dataCount;
  }
  
  /**
   * Retrieve the total count
   * 
   * @return int The total count
   */
  public function getTotalCount()
  {
    return $this->totalCount;
  }
  
  /**
   * Create a new QR Reed-Solomon block instance
   * 
   * @param int $totalCount The total count of blocks
   * @param int $dataCount The data count of blocks
   */
  private function __construct($totalCount, $dataCount)
  {
    $this->initRsBlock();
    
    $this->totalCount = $totalCount;
    $this->dataCount  = $dataCount;
  }
  
  /**
   * Get rs blocks of particular type and error correction level
   * 
   * @param int $typeNumber
   * @param int $errorCorrectLevel
   * @throws QRRSBlockException
   * @return QRRSBlock
   */
  public function getRSBlocks($typeNumber, $errorCorrectLevel)
  {
    $rsBlock = $this->getRsBlockTable($typeNumber, $errorCorrectLevel);
    
    if(!$rsBlock)
    {
      throw new QRRSBlockException("Bad RS Block at type number " . $typeNumber . " / error correct level " . $errorCorrectLevel);
    }
    
    $length = sizeof($rsBlock) / 3;
    
    $list = array();
    
    for($i = 0; $i < $length; $i++)
    {
      $count = $rsBlock[$i * 3 + 0];
      $totalCount = $rsBlock[$i * 3 + 1];
      $dataCount = $rsBlock[$i * 3 + 2];
      
      for($j = 0; $j < $count; $j++)
      {
        array_push($list, new QRRSBlock($totalCount, $dataCount));
      }
    }
    
    return $list;
  }
  
  /**
   * Get the reed-solomon block table
   * 
   * @param int $typeNumber
   * @param int $errorCorrectLevel
   * @return int|NULL
   */
  public function getRsBlockTable($typeNumber, $errorCorrectLevel)
  {
    switch ($errorCorrectLevel)
    {
      case QRErrorCorrectLevel::L:
        return $this->RS_BLOCK_TABLE[($typeNumber - 1) * 4 + 0];
      case QRErrorCorrectLevel::M:
        return $this->RS_BLOCK_TABLE[($typeNumber - 1) * 4 + 1];
      case QRErrorCorrectLevel::Q:
        return $this->RS_BLOCK_TABLE[($typeNumber - 1) * 4 + 2];
      case QRErrorCorrectLevel::H:
        return $this->RS_BLOCK_TABLE[($typeNumber - 1) * 4 + 3];
      default:
        return null;
    }
  }
  
  /**
   * This method initialize the RS block
   */
  private function initRsBlock()
  {
    if($this->loadBlockFile())
    {
      return;
    }
    
    $this->RS_BLOCK_TABLE = array();
    
    // L
    // M
    // Q
    // H
    
    // 1
    $this->addRsBlock(array(1, 26, 19));
    $this->addRsBlock(array(1, 26, 16));
    $this->addRsBlock(array(1, 26, 13));
    $this->addRsBlock(array(1, 26, 9));
    
    // 2
    $this->addRsBlock(array(1, 44, 34));
    $this->addRsBlock(array(1, 44, 28));
    $this->addRsBlock(array(1, 44, 22));
    $this->addRsBlock(array(1, 44, 16));
    
    // 3
    $this->addRsBlock(array(1, 70, 55));
    $this->addRsBlock(array(1, 70, 44));
    $this->addRsBlock(array(2, 35, 17));
    $this->addRsBlock(array(2, 35, 13));
    
    // 4
    $this->addRsBlock(array(1, 100, 80));
    $this->addRsBlock(array(2, 50, 32));
    $this->addRsBlock(array(2, 50, 24));
    $this->addRsBlock(array(4, 25, 9));
    
    // 5
    $this->addRsBlock(array(1, 134, 108));
    $this->addRsBlock(array(2, 67, 43));
    $this->addRsBlock(array(2, 33, 15, 2, 34, 16));
    $this->addRsBlock(array(2, 33, 11, 2, 34, 12));
    
    // 6
    $this->addRsBlock(array(2, 86, 68));
    $this->addRsBlock(array(4, 43, 27));
    $this->addRsBlock(array(4, 43, 19));
    $this->addRsBlock(array(4, 43, 15));
    
    // 7
    $this->addRsBlock(array(2, 98, 78));
    $this->addRsBlock(array(4, 49, 31));
    $this->addRsBlock(array(2, 32, 14, 4, 33, 15));
    $this->addRsBlock(array(4, 39, 13, 1, 40, 14));
    
    // 8
    $this->addRsBlock(array(2, 121, 97));
    $this->addRsBlock(array(2, 60, 38, 2, 61, 39));
    $this->addRsBlock(array(4, 40, 18, 2, 41, 19));
    $this->addRsBlock(array(4, 40, 14, 2, 41, 15));
    
    // 9
    $this->addRsBlock(array(2, 146, 116));
    $this->addRsBlock(array(3, 58, 36, 2, 59, 37));
    $this->addRsBlock(array(4, 36, 16, 4, 37, 17));
    $this->addRsBlock(array(4, 36, 12, 4, 37, 13));
    
    // 10
    $this->addRsBlock(array(2, 86, 68, 2, 87, 69));
    $this->addRsBlock(array(4, 69, 43, 1, 70, 44));
    $this->addRsBlock(array(6, 43, 19, 2, 44, 20));
    $this->addRsBlock(array(6, 43, 15, 2, 44, 16));
    
    // 11
    $this->addRsBlock(array(4, 101, 81));
    $this->addRsBlock(array(1, 80, 50, 4, 81, 51));
    $this->addRsBlock(array(4, 50, 22, 4, 51, 23));
    $this->addRsBlock(array(3, 36, 12, 8, 37, 13));
    
    // 12
    $this->addRsBlock(array(2, 116, 92, 2, 117, 93));
    $this->addRsBlock(array(6, 58, 36, 2, 59, 37));
    $this->addRsBlock(array(4, 46, 20, 6, 47, 21));
    $this->addRsBlock(array(7, 42, 14, 4, 43, 15));
    
    // 13
    $this->addRsBlock(array(4, 133, 107));
    $this->addRsBlock(array(8, 59, 37, 1, 60, 38));
    $this->addRsBlock(array(8, 44, 20, 4, 45, 21));
    $this->addRsBlock(array(12, 33, 11, 4, 34, 12));
    
    // 14
    $this->addRsBlock(array(3, 145, 115, 1, 146, 116));
    $this->addRsBlock(array(4, 64, 40, 5, 65, 41));
    $this->addRsBlock(array(11, 36, 16, 5, 37, 17));
    $this->addRsBlock(array(11, 36, 12, 5, 37, 13));
    
    // 15
    $this->addRsBlock(array(5, 109, 87, 1, 110, 88));
    $this->addRsBlock(array(5, 65, 41, 5, 66, 42));
    $this->addRsBlock(array(5, 54, 24, 7, 55, 25));
    $this->addRsBlock(array(11, 36, 12));
    
    // 16
    $this->addRsBlock(array(5, 122, 98, 1, 123, 99));
    $this->addRsBlock(array(7, 73, 45, 3, 74, 46));
    $this->addRsBlock(array(15, 43, 19, 2, 44, 20));
    $this->addRsBlock(array(3, 45, 15, 13, 46, 16));
    
    // 17
    $this->addRsBlock(array(1, 135, 107, 5, 136, 108));
    $this->addRsBlock(array(10, 74, 46, 1, 75, 47));
    $this->addRsBlock(array(1, 50, 22, 15, 51, 23));
    $this->addRsBlock(array(2, 42, 14, 17, 43, 15));
    
    // 18
    $this->addRsBlock(array(5, 150, 120, 1, 151, 121));
    $this->addRsBlock(array(9, 69, 43, 4, 70, 44));
    $this->addRsBlock(array(17, 50, 22, 1, 51, 23));
    $this->addRsBlock(array(2, 42, 14, 19, 43, 15));
    
    // 19
    $this->addRsBlock(array(3, 141, 113, 4, 142, 114));
    $this->addRsBlock(array(3, 70, 44, 11, 71, 45));
    $this->addRsBlock(array(17, 47, 21, 4, 48, 22));
    $this->addRsBlock(array(9, 39, 13, 16, 40, 14));
    
    // 20
    $this->addRsBlock(array(3, 135, 107, 5, 136, 108));
    $this->addRsBlock(array(3, 67, 41, 13, 68, 42));
    $this->addRsBlock(array(15, 54, 24, 5, 55, 25));
    $this->addRsBlock(array(15, 43, 15, 10, 44, 16));
    
    // 21
    $this->addRsBlock(array(4, 144, 116, 4, 145, 117));
    $this->addRsBlock(array(17, 68, 42));
    $this->addRsBlock(array(17, 50, 22, 6, 51, 23));
    $this->addRsBlock(array(19, 46, 16, 6, 47, 17));
    
    // 22
    $this->addRsBlock(array(2, 139, 111, 7, 140, 112));
    $this->addRsBlock(array(17, 74, 46));
    $this->addRsBlock(array(7, 54, 24, 16, 55, 25));
    $this->addRsBlock(array(34, 37, 13));
    
    // 23
    $this->addRsBlock(array(4, 151, 121, 5, 152, 122));
    $this->addRsBlock(array(4, 75, 47, 14, 76, 48));
    $this->addRsBlock(array(11, 54, 24, 14, 55, 25));
    $this->addRsBlock(array(16, 45, 15, 14, 46, 16));
    
    // 24
    $this->addRsBlock(array(6, 147, 117, 4, 148, 118));
    $this->addRsBlock(array(6, 73, 45, 14, 74, 46));
    $this->addRsBlock(array(11, 54, 24, 16, 55, 25));
    $this->addRsBlock(array(30, 46, 16, 2, 47, 17));
    
    // 25
    $this->addRsBlock(array(8, 132, 106, 4, 133, 107));
    $this->addRsBlock(array(8, 75, 47, 13, 76, 48));
    $this->addRsBlock(array(7, 54, 24, 22, 55, 25));
    $this->addRsBlock(array(22, 45, 15, 13, 46, 16));
    
    // 26
    $this->addRsBlock(array(10, 142, 114, 2, 143, 115));
    $this->addRsBlock(array(19, 74, 46, 4, 75, 47));
    $this->addRsBlock(array(28, 50, 22, 6, 51, 23));
    $this->addRsBlock(array(33, 46, 16, 4, 47, 17));
    
    // 27
    $this->addRsBlock(array(8, 152, 122, 4, 153, 123));
    $this->addRsBlock(array(22, 73, 45, 3, 74, 46));
    $this->addRsBlock(array(8, 53, 23, 26, 54, 24));
    $this->addRsBlock(array(12, 45, 15, 28, 46, 16));
    
    // 28
    $this->addRsBlock(array(3, 147, 117, 10, 148, 118));
    $this->addRsBlock(array(3, 73, 45, 23, 74, 46));
    $this->addRsBlock(array(4, 54, 24, 31, 55, 25));
    $this->addRsBlock(array(11, 45, 15, 31, 46, 16));
    
    // 29
    $this->addRsBlock(array(7, 146, 116, 7, 147, 117));
    $this->addRsBlock(array(21, 73, 45, 7, 74, 46));
    $this->addRsBlock(array(1, 53, 23, 37, 54, 24));
    $this->addRsBlock(array(19, 45, 15, 26, 46, 16));
    
    // 30
    $this->addRsBlock(array(5, 145, 115, 10, 146, 116));
    $this->addRsBlock(array(19, 75, 47, 10, 76, 48));
    $this->addRsBlock(array(15, 54, 24, 25, 55, 25));
    $this->addRsBlock(array(23, 45, 15, 25, 46, 16));
    
    // 31
    $this->addRsBlock(array(13, 145, 115, 3, 146, 116));
    $this->addRsBlock(array(2, 74, 46, 29, 75, 47));
    $this->addRsBlock(array(42, 54, 24, 1, 55, 25));
    $this->addRsBlock(array(23, 45, 15, 28, 46, 16));
    
    // 32
    $this->addRsBlock(array(17, 145, 115));
    $this->addRsBlock(array(10, 74, 46, 23, 75, 47));
    $this->addRsBlock(array(42, 54, 24, 1, 55, 25));
    $this->addRsBlock(array(23, 45, 15, 28, 46, 16));
    
    // 33
    $this->addRsBlock(array(17, 145, 115, 1, 146, 116));
    $this->addRsBlock(array(14, 74, 46, 21, 75, 47));
    $this->addRsBlock(array(29, 54, 24, 19, 55, 25));
    $this->addRsBlock(array(11, 45, 15, 46, 46, 16));
    
    // 34
    $this->addRsBlock(array(13, 145, 115, 6, 146, 116));
    $this->addRsBlock(array(14, 74, 46, 21, 75, 47));
    $this->addRsBlock(array(44, 54, 24, 7, 55, 25));
    $this->addRsBlock(array(59, 46, 16, 1, 47, 17));
    
    // 35
    $this->addRsBlock(array(12, 151, 121, 7, 152, 122));
    $this->addRsBlock(array(12, 75, 47, 26, 76, 48));
    $this->addRsBlock(array(39, 54, 24, 14, 55, 25));
    $this->addRsBlock(array(22, 45, 15, 41, 46, 16));
    
    // 36
    $this->addRsBlock(array(6, 151, 121, 14, 152, 122));
    $this->addRsBlock(array(6, 75, 47, 34, 76, 48));
    $this->addRsBlock(array(46, 54, 24, 10, 55, 25));
    $this->addRsBlock(array(2, 45, 15, 64, 46, 16));
    
    // 37
    $this->addRsBlock(array(17, 152, 122, 4, 153, 123));
    $this->addRsBlock(array(29, 74, 46, 14, 75, 47));
    $this->addRsBlock(array(49, 54, 24, 10, 55, 25));
    $this->addRsBlock(array(24, 45, 15, 46, 46, 16));
    
    // 38
    $this->addRsBlock(array(4, 152, 122, 18, 153, 123));
    $this->addRsBlock(array(13, 74, 46, 32, 75, 47));
    $this->addRsBlock(array(48, 54, 24, 14, 55, 25));
    $this->addRsBlock(array(42, 45, 15, 32, 46, 16));
    
    // 39
    $this->addRsBlock(array(20, 147, 117, 4, 148, 118));
    $this->addRsBlock(array(40, 75, 47, 7, 76, 48));
    $this->addRsBlock(array(43, 54, 24, 22, 55, 25));
    $this->addRsBlock(array(10, 45, 15, 67, 46, 16));
    
    // 40
    $this->addRsBlock(array(19, 148, 118, 6, 149, 119));
    $this->addRsBlock(array(18, 75, 47, 31, 76, 48));
    $this->addRsBlock(array(34, 54, 24, 34, 55, 25));
    $this->addRsBlock(array(20, 45, 15, 61, 46, 16));
    
    $this->saveBlockFile();
  }
  
  /**
   * Add a new block information to the block
   * 
   * @param array $block
   */
  private function addRsBlock($block)
  {
    array_push($this->RS_BLOCK_TABLE, $block);
  }
  
  /**
   * Return the absolute path to the block file
   * @return string
   */
  private function getBlockFileAbsolute()
  {
    return sprintf("%s%s%s", dirname(__FILE__), DIRECTORY_SEPARATOR, $this->blockFileName);
  }
  
  /**
   * Try to load the block file
   * 
   * @return boolean
   */
  private function loadBlockFile()
  {
    $file = $this->getBlockFileAbsolute();
    
    if(!file_exists($file))
    {
      return false;
    }
    
    $serialized = file_get_contents($file);
    
    if(!$serialized)
    {
      return false;
    }
    
    $this->RS_BLOCK_TABLE = unserialize($serialized);
    
    if(!$this->RS_BLOCK_TABLE)
    {
      return false;
    }
    
    return true;
  }
  
  /**
   * Try to save the block file
   */
  private function saveBlockFile()
  {
    $file = $this->getBlockFileAbsolute();
    
    if(file_exists($file))
    {
      unlink($file);
    }
    
    file_put_contents($file, serialize($this->RS_BLOCK_TABLE));
  }
}