<?php

require("mcc_common.php");
require("mcc_scripts.php");

$html_body = "";

$dnd_scripts = mcc_JS_dragndrop();

$html_body .= <<<EOT
<script language="javascript" type="text/javascript">
<!--
$dnd_scripts

function handler ( data ) {
	window.document.formconsole.taconsole.value += "Handler " + data + " ";
}

dnd_setHandler(handler);

-->
</script>

<table><tr><td>
<div style="width: 100px; height: 100px;
		background: #FDD; border: 1px solid #E99;
		text-align: center; vertical-align: middle;"
		id="TargetA">
A
</div>

</td><td>

<div style="width: 100px; height: 100px;
		background: #DFD; border: 1px solid #9F9;
		text-align: center; vertical-align: middle;"
		id="TargetB">
B
</div>

<script language="javascript" type="text/javascript">
<!--
	dnd_registerTarget(document.getElementById('TargetA'), 'A');
	dnd_registerTarget(document.getElementById('TargetB'), 'B');
-->
</script>

</td></tr></table>

<div style="padding: 24px; height: 40px;">
<img src="images/wcg/wqueen.png" alt="" onload="dnd_registerSource(this, 'Queen');" />
<img src="images/wcg/bking.png" alt="" onload="dnd_registerSource(this, 'King');" />
</div>

<form name="formconsole">
<textarea name="taconsole" cols="80" rows="12">
</textarea>

</form>

EOT;


$html_body .= <<<EOT

EOT;

echo mcc_template_page($html_body, NULL, "Sample");

?>
