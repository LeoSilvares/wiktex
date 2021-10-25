<?php
include("site/config.php");

function sha256($s)
{
	return hash("sha256", mb_convert_encoding($s, 'US-ASCII'));
}

if (!isset($_GET['p']))
	header('Location: index.php');

echo sha256($pass_salt . $_GET['p']);

?>