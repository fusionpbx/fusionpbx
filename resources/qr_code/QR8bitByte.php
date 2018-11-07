<?php
/**
 * This file is part of the phpQr package
 * 
 * See @see QRCode class for description of package and license.
 */

/**
 * Import necessary dependencies
 */
require_once 'QRByte.php';
require_once 'QRMode.php';

/**
 * This class provides the 8bit Byte implementaton of a QRByte
 * 
 * @author Maik Greubel <greubel@nkey.de>
 * @package phpQr
 */
class QR8bitByte implements QRByte
{
  /**
   * The data
   * @var array
   */
  private $data;
  
  /**
   * The mode
   * @var unknown
   */
  private $mode;
  
  /**
   * Retrieve the mode
   * 
   * @return int The mode
   * @see QRByte::getMode()
   */
  public function getMode()
  {
    return $this->mode;
  }
  
  /**
   * Retrieve the length
   * 
   * @return int The length
   * @see QRByte::getLength()
   */
  public function getLength()
  {
    return strlen($this->data);    
  }
  
  /**
   * Write data to byte
   * 
   * @param QRBitBuffer $buffer The data to write into byte
   * 
   * @see QRByte::write()
   */
  public function write(QRBitBuffer $buffer)
  {
    for($i = 0; $i < strlen($this->data); $i++)
    {
      $buffer->put(ord($this->data[$i]), 8);
    }
  }
  
  /**
   * Create a new instance of a QR8bitByte
   * 
   * @param array $data The data for the Byte
   */
  public function __construct($data)
  {
    $this->data = $data;
    $this->mode = QRMode::MODE_8BIT_BYTE;
  }
}