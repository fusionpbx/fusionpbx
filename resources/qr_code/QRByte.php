<?php
/**
 * This file is part of the phpQr package
 *
 * See @see QRCode class for description of package and license.
 */

/**
 * Import necessary dependencies
 */
require_once 'QRBitBuffer.php';

/**
 * This interface describes a QRByte implementation
 * 
 * @author Maik Greubel <greubel@nkey.de>
 * @package phpQr
 */
interface QRByte
{
  /**
   * Retrieve the mode
   * 
   * @return  int The mode
   */
  public function getMode();
  
  /**
   * Retrieve the length
   * 
   * @return int The length
   */
  public function getLength();
  
  /**
   * Write data to byte
   * 
   * @param QRBitBuffer $buffer The data to write into byte
   */
  public function write(QRBitBuffer $buffer);
}