<?php
/**
 * Persian (Jalali) calendar dates.
 *
 * @package Iranian_League_Table
 */

defined( 'ABSPATH' ) || exit;

/**
 * Minimal Gregorian → Jalali conversion for the "last updated" line.
 */
final class ILT_Jalali {

	const MONTHS = array( 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند' );

	/**
	 * Converts a Gregorian date.
	 *
	 * @param int $gy Year.
	 * @param int $gm Month (1–12).
	 * @param int $gd Day.
	 * @return int[] [ year, month, day ]
	 */
	public static function from_gregorian( $gy, $gm, $gd ) {
		$g_d_m = array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );
		$gy2   = ( $gm > 2 ) ? ( $gy + 1 ) : $gy;
		$days  = 355666 + ( 365 * $gy ) + intdiv( $gy2 + 3, 4 ) - intdiv( $gy2 + 99, 100 ) + intdiv( $gy2 + 399, 400 ) + $gd + $g_d_m[ $gm - 1 ];
		$jy    = -1595 + ( 33 * intdiv( $days, 12053 ) );
		$days %= 12053;
		$jy   += 4 * intdiv( $days, 1461 );
		$days %= 1461;
		if ( $days > 365 ) {
			--$days;
			$jy  += intdiv( $days, 365 );
			$days = $days % 365;
		}
		if ( $days < 186 ) {
			$jm = 1 + intdiv( $days, 31 );
			$jd = 1 + ( $days % 31 );
		} else {
			$jm = 7 + intdiv( $days - 186, 30 );
			$jd = 1 + ( ( $days - 186 ) % 30 );
		}
		return array( $jy, $jm, $jd );
	}

	/**
	 * Formats a timestamp in the site's time zone, e.g. "۱۷ مهر ۱۴۰۵، ساعت ۰۳:۴۰".
	 *
	 * @param int  $timestamp Unix time.
	 * @param bool $farsi     Persian digits.
	 * @return string
	 */
	public static function format( $timestamp, $farsi = true ) {
		$parts                = explode( ' ', wp_date( 'Y n j H:i', (int) $timestamp ) );
		list( $jy, $jm, $jd ) = self::from_gregorian( (int) $parts[0], (int) $parts[1], (int) $parts[2] );
		$text                 = $jd . ' ' . self::MONTHS[ $jm - 1 ] . ' ' . $jy . '، ساعت ' . $parts[3];
		return ILT_Renderer::number( $text, $farsi );
	}
}
