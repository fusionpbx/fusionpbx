<?php
/**
 * This file provides the main QRCode class.
 * 
 * The project is a rewrite from jQuery extension available at
 * @link https://github.com/jeromeetienne/jquery-qrcode
 * 
 * For a detailed description of QRCode and its features please check
 * @link http://www.qrcode.com/
 * 
 * QR Code is registered trademark of
 *  DENSO WAVE INCORPORATED
 *  http://www.denso-wave.com/qrcode/faqpatent-e.html
 *  
 * All files in the package have the same license:
 * http://opensource.org/licenses/BSD-2-Clause
 * 
 * @copyright BSD2
 * @author Maik Greubel <greubel@nkey.de>
 */

/**
 * Import necessary dependencies
 */
require_once 'QR8bitByte.php';
require_once 'QRBitBuffer.php';
require_once 'QRRSBlock.php';
require_once 'QRUtil.php';
require_once 'QRCodeException.php';

/**
 * This is the main class
 *
 * It provides the functionality to generate a QRCode bitmap
 * out of appended data elements.
 *
 * @package phpQr
 * @author Maik Greubel <greubel@nkey.de>
 */
class QRCode
{
  /**
   * Needed for padding
   * 
   * @final
   *
   */
  const PAD0 = 0xec;
  
  /**
   * Needed for padding
   * 
   * @final
   *
   */
  const PAD1 = 0x11;
  
  /**
   * The type number of qrcode
   * 
   * @var int
   */
  private $typeNumber;
  
  /**
   * Level of error correction
   * 
   * @see QRErrorCorrectLevel
   *
   * @var int
   */
  private $errorCorrectLevel;
  
  /**
   * Bitmap
   * 
   * @var array
   */
  private $modules;
  
  /**
   * Amount of modules in bitmap
   *
   * @var int
   */
  private $moduleCount;
  
  /**
   * The data as array
   *
   * @var array
   */
  private $dataCache;
  
  /**
   * All append data elements
   *
   * @var array
   */
  private $dataList;
  
  /**
   * Create a new instance of QRCode
   * 
   * @param int $typeNumber
   *          The type of QRCode
   * @param int $errorCorrectLevel
   *          The error correction level
   */
  public function __construct($typeNumber, $errorCorrectLevel)
  {
    $this->typeNumber = $typeNumber;
    $this->errorCorrectLevel = $errorCorrectLevel;
    $this->modules = null;
    $this->moduleCount = 0;
    $this->dataCache = null;
    $this->dataList = array ();
  }
  
  /**
   * This function is only needed for debugging purposes and returns the bitmap
   * DONT USE THIS TO MANIPULATE THE BITMAP!
   *
   * @return array
   */
  public function getModules()
  {
    return $this->modules;
  }
  
  /**
   * Add a new data element to the QRCode
   *
   * @param string $data          
   */
  public function addData($data)
  {
    $newData = new QR8bitByte ( $data );
    array_push ( $this->dataList, $newData );
    $this->dataCache = null;
  }
  
  /**
   * Returns whether a given bitmap entry is dark or not
   *
   * @param int $row
   *          The row in bitmap
   * @param int $col
   *          The column in bitmap
   *          
   * @throws QRCodeException
   * @return true in case of its a dark bit, false otherwise
   */
  public function isDark($row, $col)
  {
    if ($row < 0 || $this->moduleCount <= $row || $col < 0 || $this->moduleCount <= $col)
    {
      throw new QRCodeException ( "$row,$col" );
    }
    
    return $this->modules [$row] [$col];
  }
  
  /**
   * Get the amount of modules in bitmap
   *
   * @return int
   */
  public function getModuleCount()
  {
    return $this->moduleCount;
  }
  
  /**
   * Generate the QRCode bitmap
   */
  public function make()
  {
    if ($this->typeNumber < 1)
    {
      $typeNumber = 1;
      for($typeNumber = 1; $typeNumber < 40; $typeNumber ++)
      {
        $rsBlocks = QRRSBlock::getInstance ()->getRSBlocks ( $typeNumber, $this->errorCorrectLevel );
        
        $buffer = new QRBitBuffer ();
        $totalDataCount = 0;
        for($i = 0; $i < sizeof ( $rsBlocks ); $i ++)
        {
          $totalDataCount += $rsBlocks [$i]->getDataCount ();
        }
        
        for($i = 0; $i < sizeof ( $this->dataList ); $i ++)
        {
          $data = $this->dataList [$i];
          
          assert ( $data instanceof QRByte );
          
          $buffer->put ( $data->getMode (), 4 );
          $buffer->put ( $data->getLength (), QRUtil::getInstance ()->getLengthInBits ( $data->getMode (), $typeNumber ) );
          $data->write ( $buffer );
        }
        if ($buffer->getLengthInBits () <= $totalDataCount * 8)
          break;
      }
      $this->typeNumber = $typeNumber;
    }
    $this->makeImpl ( false, $this->getBestMaskPattern () );
  }
  
  /**
   * Generates the bitmap (really)
   *
   * @param boolean $test          
   * @param int $maskPattern          
   */
  private function makeImpl($test, $maskPattern)
  {
    $this->moduleCount = $this->typeNumber * 4 + 17;
    $this->modules = QRUtil::getInstance ()->createEmptyArray ( $this->moduleCount );
    
    for($row = 0; $row < $this->moduleCount; $row ++)
    {
      $this->modules [$row] = QRUtil::getInstance ()->createEmptyArray ( $this->moduleCount );
      
      for($col = 0; $col < $this->moduleCount; $col ++)
      {
        $this->modules [$row] [$col] = null;
      }
    }
    
    $this->setupPositionProbePattern ( 0, 0 );
    $this->setupPositionProbePattern ( $this->moduleCount - 7, 0 );
    $this->setupPositionProbePattern ( 0, $this->moduleCount - 7 );
    $this->setupPositionAdjustPattern ();
    $this->setupTimingPattern ();
    $this->setupTypeInfo ( $test, $maskPattern );
    
    if ($this->typeNumber >= 7)
    {
      $this->setTypeNumber ( $test );
    }
    
    if ($this->dataCache == null)
    {
      $this->dataCache = self::createData ( $this->typeNumber, $this->errorCorrectLevel, $this->dataList );
    }
    
    $this->mapData ( $this->dataCache, $maskPattern );
  }
  
  /**
   * Add the position probes to the bitmap
   *
   * @param int $row          
   * @param int $col          
   */
  private function setupPositionProbePattern($row, $col)
  {
    for($r = - 1; $r <= 7; $r ++)
    {
      if ($row + $r <= - 1 || $this->moduleCount <= $row + $r)
        continue;
      
      for($c = - 1; $c <= 7; $c ++)
      {
        if ($col + $c <= - 1 || $this->moduleCount <= $col + $c)
          continue;
        
        if ((0 <= $r && $r <= 6 && ($c == 0 || $c == 6)) || (0 <= $c && $c <= 6 && ($r == 0 || $r == 6)) || (2 <= $r && $r <= 4 && 2 <= $c && $c <= 4))
        {
          $this->modules [$row + $r] [$col + $c] = true;
        }
        else
        {
          $this->modules [$row + $r] [$col + $c] = false;
        }
      }
    }
  }
  
  /**
   * Get the best mask pattern for this QRCode
   *
   * @return int
   */
  private function getBestMaskPattern()
  {
    $minLostPoint = 0;
    $pattern = 0;
    
    for($i = 0; $i < 8; $i ++)
    {
      $this->makeImpl ( true, $i );
      
      $lostPoint = QRUtil::getInstance ()->getLostPoint ( $this );
      
      if ($i == 0 || $minLostPoint > $lostPoint)
      {
        $minLostPoint = $lostPoint;
        $pattern = $i;
      }
    }
    
    return $pattern;
  }
  
  /**
   * Add the timing pattern to bitmap
   */
  private function setupTimingPattern()
  {
    for($r = 8; $r < $this->moduleCount - 8; $r ++)
    {
      if ($this->modules [$r] [6] != null)
      {
        continue;
      }
      $this->modules [$r] [6] = ($r % 2 == 0);
    }
    
    for($c = 8; $c < $this->moduleCount - 8; $c ++)
    {
      if ($this->modules [6] [$c] != null)
      {
        continue;
      }
      $this->modules [6] [$c] = ($c % 2 == 0);
    }
  }
  
  /**
   * Add the position adjust pattern to bitmap
   */
  private function setupPositionAdjustPattern()
  {
    $pos = QRUtil::getInstance ()->getPatternPosition ( $this->typeNumber );
    
    for($i = 0; $i < sizeof ( $pos ); $i ++)
    {
      for($j = 0; $j < sizeof ( $pos ); $j ++)
      {
        $row = $pos [$i];
        $col = $pos [$j];
        
        if ($this->modules [$row] [$col] != null)
        {
          continue;
        }
        
        for($r = - 2; $r <= 2; $r ++)
        {
          for($c = - 2; $c <= 2; $c ++)
          {
            if ($r == - 2 || $r == 2 || $c == - 2 || $c == 2 || ($r == 0 && $c == 0))
            {
              $this->modules [$row + $r] [$col + $c] = true;
            }
            else
            {
              $this->modules [$row + $r] [$col + $c] = false;
            }
          }
        }
      }
    }
  }
  
  /**
   * Add the type number to bitmap
   * 
   * @param boolean $test          
   */
  private function setTypeNumber($test)
  {
    $bits = QRUtil::getInstance ()->getBCHTypeNumber ( $this->typeNumber );
    
    for($i = 0; $i < 18; $i ++)
    {
      $mod = (! $test && (($bits >> $i) & 1) == 1);
      $this->modules [floor ( $i / 3 )] [$i % 3 + $this->moduleCount - 8 - 3] = $mod;
    }
    
    for($i = 0; $i < 18; $i ++)
    {
      $mod = (! $test && (($bits >> $i) & 1) == 1);
      $this->modules [$i % 3 + $this->moduleCount - 8 - 3] [floor ( $i / 3 )] = $mod;
    }
  }
  
  /**
   * Add the type info to bitmap
   *
   * @param boolean $test          
   * @param int $maskPattern          
   */
  private function setupTypeInfo($test, $maskPattern)
  {
    $data = ($this->errorCorrectLevel << 3) | $maskPattern;
    $bits = QRUtil::getInstance ()->getBCHTypeInfo ( $data );
    
    // vertical
    for($i = 0; $i < 15; $i ++)
    {
      $mod = (! $test && (($bits >> $i) & 1) == 1);
      if ($i < 6)
      {
        $this->modules [$i] [8] = $mod;
      }
      else if ($i < 8)
      {
        $this->modules [$i + 1] [8] = $mod;
      }
      else
      {
        $this->modules [$this->moduleCount - 15 + $i] [8] = $mod;
      }
    }
    
    // horizontal
    for($i = 0; $i < 15; $i ++)
    {
      $mod = (! $test && (($bits >> $i) & 1) == 1);
      
      if ($i < 8)
      {
        $this->modules [8] [$this->moduleCount - $i - 1] = $mod;
      }
      else if ($i < 9)
      {
        $this->modules [8] [15 - $i - 1 + 1] = $mod;
      }
      else
      {
        $this->modules [8] [15 - $i - 1] = $mod;
      }
    }
    
    // fixed module
    $this->modules [$this->moduleCount - 8] [8] = (! $test);
  }
  
  /**
   * Add the data to bitmap
   *
   * @param array $data          
   * @param int $maskPattern          
   */
  private function mapData($data, $maskPattern)
  {
    $inc = - 1;
    $row = $this->moduleCount - 1;
    $bitIndex = 7;
    $byteIndex = 0;
    
    for($col = $this->moduleCount - 1; $col > 0; $col -= 2)
    {
      if ($col == 6)
        $col --;
      
      while ( true )
      {
        for($c = 0; $c < 2; $c ++)
        {
          if ($this->modules [$row] [$col - $c] === null)
          {
            $dark = false;
            
            if ($byteIndex < sizeof ( $data ))
            {
              $dark = ((($data [$byteIndex] >> $bitIndex) & 1) == 1);
            }
            
            $mask = QRUtil::getInstance ()->getMask ( $maskPattern, $row, $col - $c );
            
            if ($mask)
              $dark = ! $dark;
            
            $this->modules [$row] [$col - $c] = $dark;
            $bitIndex --;
            
            if ($bitIndex == - 1)
            {
              $byteIndex ++;
              $bitIndex = 7;
            }
          }
        }
        
        $row += $inc;
        
        if ($row < 0 || $this->moduleCount <= $row)
        {
          $row -= $inc;
          $inc = - $inc;
          break;
        }
      }
    }
  }
  
  /**
   * Create a bitmap out of all append data elements
   *
   * @param int $typeNumber          
   * @param int $errorCorrectLevel          
   * @param array $dataList          
   *
   * @throws QRCodeException
   *
   * @return array
   */
  private function createData($typeNumber, $errorCorrectLevel, $dataList)
  {
    $rsBlocks = QRRSBlock::getInstance ()->getRSBlocks ( $typeNumber, $errorCorrectLevel );
    
    $buffer = new QRBitBuffer ();
    
    for($i = 0; $i < sizeof ( $dataList ); $i ++)
    {
      $data = $dataList [$i];
      assert ( $data instanceof QRByte );
      
      $buffer->put ( $data->getMode (), 4 );
      $buffer->put ( $data->getLength (), QRUtil::getInstance ()->getLengthInBits ( $data->getMode (), $typeNumber ) );
      $data->write ( $buffer );
    }
    
    // calc num max data
    $totalDataCount = 0;
    for($i = 0; $i < sizeof ( $rsBlocks ); $i ++)
    {
      $totalDataCount += $rsBlocks [$i]->getDataCount ();
    }
    
    if ($buffer->getLengthInBits () > $totalDataCount * 8)
    {
      throw new QRCodeException ( "code length overflow (" . $buffer->getLengthInBits () . " > " . ($totalDataCount * 8) . ")" );
    }
    
    // end code
    if ($buffer->getLengthInBits () + 4 <= $totalDataCount * 8)
    {
      $buffer->put ( 0, 4 );
    }
    
    // padding
    while ( $buffer->getLengthInBits () % 8 != 0 )
    {
      $buffer->putBit ( false );
    }
    
    // padding
    while ( true )
    {
      if ($buffer->getLengthInBits () >= $totalDataCount * 8)
      {
        break;
      }
      
      $buffer->put ( QRCode::PAD0, 8 );
      
      if ($buffer->getLengthInBits () >= $totalDataCount * 8)
      {
        break;
      }
      $buffer->put ( QRCode::PAD1, 8 );
    }
    
    return $this->createBytes ( $buffer, $rsBlocks );
  }
  
  /**
   * Create bitmap out of the bit buffer using reed solomon blocks
   *
   * @param QRBitBuffer $buffer          
   * @param array $rsBlocks          
   * @return array
   */
  public function createBytes(QRBitBuffer $buffer, $rsBlocks)
  {
    $offset = 0;
    $maxDcCount = 0;
    $maxEcCount = 0;
    
    $dcdata = QRUtil::getInstance ()->createEmptyArray ( sizeof ( $rsBlocks ) );
    $ecdata = QRUtil::getInstance ()->createEmptyArray ( sizeof ( $rsBlocks ) );
    
    for($r = 0; $r < sizeof ( $rsBlocks ); $r ++)
    {
      $dcCount = $rsBlocks [$r]->getDataCount ();
      $ecCount = $rsBlocks [$r]->getTotalCount () - $dcCount;
      
      $maxDcCount = max ( array (
          $maxDcCount,
          $dcCount 
      ) );
      $maxEcCount = max ( array (
          $maxEcCount,
          $ecCount 
      ) );
      
      $dcdata [$r] = QRUtil::getInstance ()->createEmptyArray ( $dcCount );
      
      for($i = 0; $i < sizeof ( $dcdata [$r] ); $i ++)
      {
        $dcdata [$r] [$i] = 0xff & $buffer->getAt ( $i + $offset );
      }
      $offset += $dcCount;
      
      $rsPoly = QRUtil::getInstance ()->getErrorCorrectPolynominal ( $ecCount );
      $rawPoly = new QRPolynominal ( $dcdata [$r], $rsPoly->getLength () - 1 );
      
      $modPoly = $rawPoly->mod ( $rsPoly );
      $ecdata [$r] = QRUtil::getInstance ()->createEmptyArray ( $rsPoly->getLength () - 1 );
      for($i = 0; $i < sizeof ( $ecdata [$r] ); $i ++)
      {
        $modIndex = $i + $modPoly->getLength () - sizeof ( $ecdata [$r] );
        $ecdata [$r] [$i] = ($modIndex >= 0) ? $modPoly->get ( $modIndex ) : 0;
      }
    }
    
    $totalCodeCount = 0;
    for($i = 0; $i < sizeof ( $rsBlocks ); $i ++)
    {
      $totalCodeCount += $rsBlocks [$i]->getTotalCount ();
    }
    
    $data = QRUtil::getInstance ()->createEmptyArray ( $totalCodeCount );
    $index = 0;
    
    for($i = 0; $i < $maxDcCount; $i ++)
    {
      for($r = 0; $r < sizeof ( $rsBlocks ); $r ++)
      {
        if ($i < sizeof ( $dcdata [$r] ))
        {
          $data [$index ++] = $dcdata [$r] [$i];
        }
      }
    }
    
    for($i = 0; $i < $maxEcCount; $i ++)
    {
      for($r = 0; $r < sizeof ( $rsBlocks ); $r ++)
      {
        if ($i < sizeof ( $ecdata [$r] ))
        {
          $data [$index ++] = $ecdata [$r] [$i];
        }
      }
    }
    
    return $data;
  }
}