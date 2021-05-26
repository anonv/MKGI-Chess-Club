<?php

function _StartRobotProcess ( $p_binary, $p_script ) {

	$descriptorspec = array(
		0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
		1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
		2 => array("file", "/tmp/chessrobot-error-output.txt", "a") // stderr is a file to write to
	);

	$cwd = '/tmp';
	$env = array();

	$process = proc_open($p_binary, $descriptorspec, $pipes, $cwd, $env);

	if (!is_resource($process)) {
		throw new Exception("Could not start Gnuchess process");
	}

	// $pipes now looks like this:
	// 0 => writeable handle connected to child stdin
	// 1 => readable handle connected to child stdout
	// Any error output will be appended to /tmp/error-output.txt

	fwrite($pipes[0], $p_script);
	fclose($pipes[0]);

	$robot_output = stream_get_contents($pipes[1]);
	fclose($pipes[1]);

	// It is important that you close any pipes before calling
	// proc_close in order to avoid a deadlock
	$return_value = proc_close($process);

	return $robot_output;
}

function ComputeNextMove_Gnuchess ( $p_moves, $p_depth = 3 ) {
	global $engine_gnuchess;

	if ( ! $engine_gnuchess ) {
		return NULL;
	}

	/*
	 * Create the input script
	 */

	$script = "";

	$script .= "xboard\n";
	$script .= "depth $p_depth\n";
	$script .= "force\n";

	foreach ( $p_moves as $move ) {
		$script .= $move->getShort() . "\n";
	}

	$script .= "go\n";
	$script .= "exit\n";

	/*
	 * Start the engine
	 */

	$reply = _StartRobotProcess($engine_gnuchess, $script);

	/*
	 * Now interpret the robot's response
	 */

	$re_mymove = "/my move is *: ([a-zA-Z0-9_-]+)/i";

	preg_match($re_mymove, $reply, $matches);

	if ( count($matches) != 2 ) {
		throw new Exception("No move in robot response");
	}

	LogDebug("Gnuchess answered '{$matches[0]}'");

	return $matches[1];
}

function ComputeNextMove_Phalanx ( $p_moves, $p_depth = 3 ) {
	global $engine_phalanx;

	if ( ! $engine_phalanx ) {
		return NULL;
	}

	$cmdline_parameters = '';

	if ( $p_depth == 1 ) {
		$cmdline_parameters .= ' -e 100';
	}

	/*
	 * Create the input script
	 */

	$script = "";

	$script .= "post\n";
	$script .= "depth $p_depth\n";
	$script .= "force\n";

	foreach ( $p_moves as $move ) {
		$script .= $move->getShort() . "\n";
	}

	$script .= "go\n";
	$script .= "score\n";
	$script .= "exit\n";

	/*
	 * Start the engine
	 */

	$reply = _StartRobotProcess($engine_phalanx . $cmdline_parameters, $script);

	/*
	 * Now interpret the robot's response
	 */

	$re_mymove = "/my move is ([a-zA-Z0-9_-]+)/i";

	preg_match($re_mymove, $reply, $matches);

	if ( count($matches) != 2 ) {
		throw new Exception("No move in robot response");
	}

	LogDebug("Gnuchess answered '{$matches[0]}'");

	return $matches[1];
}

?>
