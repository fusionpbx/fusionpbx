<?php
/**
 * This file is part of the phpQr package
 *
 * See @see QRCode class for description of package and license.
 */

/**
 * QRBitBuffer class
 * 
 * The purpose of this class is to act as data holder for QRCode.
 * 
 * @author Maik Greubel <greubel@nkey.de>
 * @package phpQr
 */
class QRBitBuffer
{
  /**
   * Array based buffer access
   * 
   * @var array
   */
  private $buffer;
  
  /**
   * Length of array
   * 
   * @var int
   */
  private $length;
  
  /**
   * Create a new instance of QRBitBuffer
   */
  public function __construct()
  {
    $this->buffer = array();
    $this->length = 0;
  }

  /**
   * Get particular bit given by index
   * 
   * @param int $index The index of bit
   * @return boolean
   */
  public function get($index)
  {
    $bufIndex = floor($index / 8);
    return ( ($this->buffer[$bufIndex] >> (7 - $index % 8) ) & 1) == 1;
  }
  
  /**
   * Get the byte at particular index
   * 
   * @param int $index The index of the byte
   * @return string
   */
  public function getAt($index)
  {
    return $this->buffer[$index];
  }
  
  /**
   * Put amount of bits
   * @param int $num The data to put
   * @param int $length The length of data
   */
  public function put($num, $length)
  {
    for($i = 0; $i < $length; $i++)
    {
      $this->putBit((($num >> ($length - $i - 1)) & 1) == 1);
    }
  }
  
  /**
   * Get current length in bits
   * 
   * @return int The amount of bits
   */
  public function getLengthInBits()
  {
    return $this->length;
  }
  
  /**
   * Put particular bit
   * 
   * @param int $bit The bit to put
   */
  public function putBit($bit)
  {
    $bufIndex = floor($this->length / 8);
    if(sizeof($this->buffer) <= $bufIndex)
    {
      array_push($this->buffer, 0);
    }
    
    if($bit)
    {
      $this->buffer[$bufIndex] |= (0x80 >> ($this->length % 8));
    }
    
    $this->length++;
  }
}