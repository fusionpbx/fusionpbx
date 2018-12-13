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

/**
 * Derived exception
 * 
 * @author Maik Greubel <greubel@nkey.de>
 * @package phpQr
 */
class QRMathException extends QRCodeException
{
}

/**
 * QR Code math helper class
 *
 * @author Maik Greubel <greubel@nkey.de>
 * @package phpQr
 */
final class QRMath
{
  /**
   * Exponent table
   *
   * @var array
   */
  private $EXP_TABLE = null;
  
  /**
   * Logarithm table
   * 
   * @var array
   */
  private $LOG_TABLE = null;
  
  /**
   * Singleton pattern
   * 
   * @var QRMath
   */
  private static $instance = null;
  
  /**
   * Singleton pattern
   *
   * @return QRMath Singleton
   */
  public static function getInstance()
  {
    if (! self::$instance)
    {
      self::$instance = new self ();
    }
    
    return self::$instance;
  }
  
  /**
   * Create a new instance of QRMath
   */
  private function __construct()
  {
    $this->init ();
  }
  
  /**
   * Initialize the tables
   */
  private function init()
  {
    $this->EXP_TABLE = array ();
    for($i = 0; $i < 8; $i ++)
    {
      $this->EXP_TABLE [$i] = 1 << $i;
    }
    
    for($i = 8; $i < 256; $i ++)
    {
      $this->EXP_TABLE [$i] = $this->EXP_TABLE [$i - 4] ^ $this->EXP_TABLE [$i - 5] ^ $this->EXP_TABLE [$i - 6] ^ $this->EXP_TABLE [$i - 8];
    }
    
    $this->LOG_TABLE = array ();
    for($i = 0; $i < 255; $i ++)
    {
      $this->LOG_TABLE [$this->EXP_TABLE [$i]] = $i;
    }
  }
  
  /**
   * Get logarithm of n
   *
   * @param int $n          
   * @throws QRMathException
   * @return int
   */
  public function glog($n)
  {
    if ($n < 1)
    {
      throw new QRMathException ( "glog(" . $n . ")" );
    }
    
    foreach ( $this->LOG_TABLE as $key => $value )
    {
      if ($key == $n)
        return $value;
    }
    
    throw new QRMathException ( "glog($n)" );
  }
  
  /**
   * Get the exponent of n
   *
   * @param int $n          
   * @return int
   */
  public function gexp($n)
  {
    while ( $n < 0 )
    {
      $n += 255;
    }
    while ( $n >= 256 )
    {
      $n -= 255;
    }
    foreach ( $this->EXP_TABLE as $key => $value )
    {
      if ($key == $n)
        return $value;
    }
    
    throw new QRMathException ( "gexp($n)" );
  }
} 