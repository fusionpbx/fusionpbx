<?php
/**
 * This file is part of the phpQr package
 *
 * See @see QRCode class for description of package and license.
 */

/**
 * Error correct level enumeration
 *
 * @author Maik Greubel <greubel@nkey.de>
 * @package phpQr
 */
abstract class QRErrorCorrectLevel
{
  const L = 1;
  const M = 0;
  const Q = 3;
  const H = 2;
}
