<?php

include("arquivo.php");
include("comum.php");


$pg = "1";
if (isset($_GET['pg']))
	$pg = $_GET['pg'];


echo LerArquivoTex($pg, $autor, $versao, $data);
?>
