<?php
/**
 * This file is part of the phpQr package
 *
 * See @see QRCode class for description of package and license.
 */

/**
 * Import necessary dependencies
 */
require_once 'QRUtil.php';
require_once 'QRMath.php';
require_once 'QRCodeException.php';

/**
 * Derived exception
 *
 * @author Maik Greubel <greubel@nkey.de>
 * @package phpQr
 */
class QRPolynominalException extends QRCodeException{};

/**
 * The purpose of this class is to provide a polynominal implementation for the QRCode package
 * 
 * @author Maik Greubel <greubel@nkey.de>
 * @package phpQr
 */
class QRPolynominal
{
  /**
   * Bitmap
   * 
   * @var array
   */
  private $num;
  
  /**
   * Create a new QRPolynominal instance
   * 
   * @param array $num
   * @param int $shift
   * 
   * @throws QRPolynominalException
   */
  public function __construct($num, $shift)
  {
    if(sizeof($num) == 0)
    {
      throw new QRPolynominalException("Invalid num size");
    }
    
    $offset = 0;
    while($offset < sizeof($num) && $num[$offset] == 0)
    {
      $offset++;
    }
    
    $this->num = QRUtil::getInstance()->createEmptyArray(sizeof($num) - $offset + $shift);
    for($i = 0; $i < sizeof($num) - $offset; $i++)
    {
      $this->num[$i] = $num[$i + $offset];
    }
  }
  
  /**
   * Get a particular bitmap index
   * 
   * @param int $index
   * @return multitype:
   */
  public function get($index)
  {
    return $this->num[$index];
  }
  
  /**
   * Get the length of bitmap
   */
  public function getLength()
  {
    return sizeof($this->num);
  }
  
  /**
   * Multiply another polynom against this
   * 
   * @param QRPolynominal $e The other
   * @return QRPolynominal The multiplied result
   */
  public function multiply(QRPolynominal $e)
  {
    $num = QRUtil::getInstance()->createEmptyArray($this->getLength() + $e->getLength() - 1);
    
    for($i = 0; $i < $this->getLength(); $i++)
    {
      for($j = 0; $j < $e->getLength(); $j++)
      {
        $a = QRMath::getInstance()->glog($this->get($i));
        $b = QRMath::getInstance()->glog($e->get($j));
        
        $base = 0;
        if(isset($num[$i + $j]))
          $base = $num[$i + $j];
        $num[$i + $j] = $base ^ QRMath::getInstance()->gexp( $a + $b );
      }
    }
    
    return new QRPolynominal($num, 0);
  }
  
  /**
   * Perform modulus against another polynom
   * 
   * @param QRPolynominal $e
   * 
   * @return QRPolynominal
   */
  public function mod(QRPolynominal $e)
  {
    if($this->getLength() - $e->getLength() < 0)
    {
      return $this;
    }
    
    $ratio = QRMath::getInstance()->glog($this->get(0)) - QRMath::getInstance()->glog($e->get(0));
    
    $num = QRUtil::getInstance()->createEmptyArray($this->getLength());
    
    for($i = 0; $i < $this->getLength(); $i++)
    {
      $num[$i] = $this->get($i);
    }
    
    for($i = 0; $i < $e->getLength(); $i++)
    {
      $num[$i] ^= QRMath::getInstance()->gexp(QRMath::getInstance()->glog($e->get($i)) + $ratio);
    }
    
    $result = new QRPolynominal($num, 0);
    $result = $result->mod($e);
    
    return $result;
  }
}