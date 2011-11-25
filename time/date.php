<?php
  /**
   * Various date functions.
   *
   * @access public
   * @module date
   * @package Date functions
   */

  # Copyright (c) 2001 dev/consulting GmbH
  # Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
  #
# Licensed under the MIT, BSD and GPL licenses.


  /**
   * Get doomday.
   *
   * Get day of week for the first day of a year.
   *
   * @access public
   * @param integer $year
   * @returns int Day of week. 1 is Monday, 2 is Tuesday etc.
   */
  function doomday ($year)
  {
    $CenturyDoomday = Array (5, 4, 2, 0);
    $CC             = floor ($year / 100);
    $YY             = $year % 100;
    $CCDoomday      = $CenturyDoomday[$CC % 4];
    $YYDoomday      = 0;

    if ($YY == 0)
      $YYDoomday = $CCDoomday;
    else
      if ($YY%12 == 0)
        $YYDoomday = ($CCDoomday + $YY / 12 - 1) % 7;
      else
        $YYDoomday = ($CCDoomday + (floor ($YY / 12) + ($YY % 12)
                     + floor ((($YY - 1) % 12) / 4))
                     ) % 7;
    if (($CC%4 == 0) && ($YY != 0))
      $YYDoomday = $YYDoomday + 1;

    return $YYDoomday;
  }

  /**
   * Check if year is a leap year.
   *
   * @access public
   * @param integer $year
   * @returns bool
   */
  function is_leap_year ($year)
  {
    if (($year % 4 == 0)
        && (($year < 1582) || (!($year % 100 == 0)) || ($year % 400 == 0)))
        return true;
    return false;
  } 

  /**
   * Get day of year.
   *
   * @access public
   * @param integer $year
   * @param integer $month
   * @param integer $day
   * @returns int Day of year starting at 1.
   */
  function day_of_year ($year, $month, $day)
  {
    $monthdays = array (31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    $days = 0;

    $monthdays[1]  = 28 + is_leap_year ($year);
    if ($day <= $monthdays[$month - 1]) {
      for ($i = 0; $i < $month - 1; $i++)
         $days = $days + $monthdays[$i];
      $days = $days + $day;
    }
    return $days;
  } 

  /**
   * Get day of week.
   *
   * @access public
   * @param integer $year
   * @param integer $month
   * @param integer $day
   * @returns int Day of week. 1 is Monday, 2 is Tuesday etc.
   */
  function day_of_week ($year, $month, $day)
  {
    $doomday = doomday ($year);
    $days = day_of_year ($year, $month, $day) - 1;
    return ($doomday + ($days % 7)) % 7;
  } 
?>
