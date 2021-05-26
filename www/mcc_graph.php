<?php

class Graph {
	private $_series;

	private $_months = array("", "January", "February", "March", "April",
			"May", "June", "July", "August", "September",
			"October", "November", "December");

	function Graph() {
		$this->_series = array();
	}

	function add ( $p_name, $p_data ) {
		$this->_series[$p_name] = $p_data;
	}

	function renderAsPng ( $p_width, $p_height ) {
		header("Content-Type: image/png");
		$graphs = imagecreate($p_width, $p_height);
		$c_white = imagecolorallocate($graphs,0xFF,0xFF,0xFF);
		$this->_draw_graphs($graphs, 30, 0, $p_width - 30, $p_height);
		imagepng($graphs);
	}

	function _interpolate ( $p_min, $p_val, $p_max, $p_scale = 1.0 ) {
		return $p_scale * ( $p_val - $p_min ) / ( $p_max - $p_min );
	}

	function _setMinMax ( &$p_valmin, &$p_valmax, &$p_datemin, &$p_datemax,
				$p_series ) {
		foreach ( $p_series as $date => $val ) {
			if ( $val < $p_valmin ) {
				$p_valmin = $val;
			}

			if ( $val > $p_valmax ) {
				$p_valmax = $val;
			}

			if ( $date < $p_datemin ) {
				$p_datemin = $date;
			}

			if ( $date > $p_datemax ) {
				$p_datemax = $date;
			}
		}
	}

	function _draw_graphs ( &$p_im, $p_base_x, $p_base_y, $p_width, $p_height ) {
		// White is also the background
		$c_white = imagecolorallocate($p_im,0xFF,0xFF,0xFF);
		$c_graphs = array();
		$c_graphs[] = imagecolorallocate($p_im, 0xD0, 0x30, 0x20);
		$c_graphs[] = imagecolorallocate($p_im, 0x30, 0xD0, 0x20);

		$val_min = 1150;
		$val_max = 1250;

		$date_min = time();
		$date_max = $date_min;

		foreach ( $this->_series as $serie ) {
			$this->_setMinMax($val_min, $val_max, $date_min, $date_max, $serie);
		}

		$val_min = $val_min - 10;
		$val_max = $val_max + 10;

		$c_border     = imagecolorallocate($p_im, 0x80, 0x80, 0x80);
		$c_grad       = imagecolorallocate($p_im, 0xD0, 0xD0, 0xD0);
		$c_text       = imagecolorallocate($p_im, 0xB0, 0xB0, 0xB0);

		$c_shadow     = imagecolorallocate($p_im, 0xA0, 0xA0, 0xA0);

		$end_x = $p_base_x + $p_width;
		$end_y = $p_base_y;

		$origin_x = $p_base_x;
		$origin_y = $p_base_y + $p_height;

		// ----- Background ----------

		// $c_background = imagecolorallocate($p_im, 0xF0, 0xF0, 0xF0);
		// imagefilledrectangle($p_im, $origin_x, $end_y, $end_x, $origin_y, $c_background);

		$middle_y = $origin_y - $this->_interpolate($val_min, 1200, $val_max, $p_height);

		$colsteps = 16;
		$colorval_begin = 0xFF;
		$colorval_end   = 0xF0;

		for ( $c = 0; $c < $colsteps; $c++ ) {
			$colorval = ($colorval_begin*($colsteps-$c)+$colorval_end*$c)/$colsteps;
			$red   = min(255, $colorval);
			$green = min(255, $colorval);
			$blue  = min(255, $colorval * 1.10);
			$c_back    = imagecolorallocate($p_im, $red, $green, $blue);

			$y_down = ( $origin_y * ($colsteps - $c) + $middle_y * $c ) / $colsteps;
			$y_up   = ( $end_y    * ($colsteps - $c) + $middle_y * $c ) / $colsteps;

			// echo "$colorval, $y_down, $y_up\n";

			imagefilledrectangle($p_im, $origin_x, $y_up, $end_x, $y_down, $c_back);
		}

		// ----- Axis ----------------

		$font = 1;


		$year  = 2004;
		$month = 1;
		$stamp = mktime(0,0,0,$month, 1, $year);

		$val_stp = 25;

		while ( ( $val_max - $val_min ) / $val_stp > 7 ) {
			$val_stp *= 2;
		}

		$ft_x = -imagefontwidth($font) * 5;
		$ft_y = -imagefontheight($font) / 2;

		for ( $v = floor($val_min - ($val_min % $val_stp) + $val_stp);
		      $v < $val_max;
		      $v += $val_stp ) {

			$y = $origin_y - $this->_interpolate($val_min, $v, $val_max, $p_height);
			imagestring($p_im, $font, $origin_x + $ft_x, $y + $ft_y, $v, $c_text);
			imageline($p_im, $origin_x, $y, $end_x, $y, $c_grad);
		}

		$showmonths = ($date_max - $date_min) < ( 365 * 24 * 60 * 60 );

		while ( $stamp < $date_max ) {
			if ( $stamp > $date_min ) {
				$x = $origin_x + $this->_interpolate($date_min, $stamp, $date_max, $p_width);

				if ( $month == 1 ) {
					imageline($p_im, $x, $end_y, $x, $origin_y, $c_border);
				}
				else {
					imageline($p_im, $x, $end_y, $x, $origin_y, $c_grad);
				}

				if ( $showmonths ) {
					imagestring($p_im, $font,
						    $x + 2, $origin_y - imagefontheight($font),
						    $this->_months[$month], $c_text);
				}
				else if ( $month == 1 ) {
					imagestring($p_im, $font,
						    $x + 2, $origin_y - imagefontheight($font),
						    $year, $c_text);
				}
			}
			if ( $month == 12 ) {
				$month = 1;
				$year += 1;
			}
			else {
				$month += 1;
			}

			$stamp = mktime(0,0,0,$month, 1, $year);
		}

		$centery = $origin_y - $this->_interpolate($val_min, 1200, $val_max, $p_height);
		imageline($p_im, $origin_x, $centery, $end_x, $centery, $c_border);
		imageline($p_im, $origin_x, $origin_y, $origin_x, $end_y, $c_border);

		// -----

		$names = array_keys($this->_series);

		for ( $iserie = 0; $iserie < count($names); $iserie++ ) {
			$data = $this->_series[$names[$iserie]];
			$dates = array_keys($data);
			for ( $idx = 1; $idx < count($dates); $idx++ ) {
				$last_x = $origin_x + $this->_interpolate($date_min, $dates[$idx-1], $date_max, $p_width);
				$last_y = $origin_y - $this->_interpolate($val_min, $data[$dates[$idx-1]], $val_max, $p_height);
				$next_x = $origin_x + $this->_interpolate($date_min, $dates[$idx], $date_max, $p_width);
				$next_y = $origin_y - $this->_interpolate($val_min, $data[$dates[$idx]], $val_max, $p_height);
				imageline($p_im, $last_x, $last_y, $next_x, $next_y, $c_graphs[$iserie]);
			}
		}

		// ----- Names (if 2 graphs) ----------------

		for ( $iserie = 0; $iserie < count($names); $iserie++ ) {
			imagestring($p_im, 2, 40, 11 + 15 * $iserie, $names[$iserie], $c_shadow);
			imagestring($p_im, 2, 40, 10 + 15 * $iserie, $names[$iserie], $c_graphs[$iserie]);
		}
	}
};


?>
