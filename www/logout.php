<?php

require("mcc_common.php");

session_destroy();
mcc_http_redirect("login.php");
?>
