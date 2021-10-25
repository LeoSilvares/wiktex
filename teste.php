<?php

include("arquivo.php");

$autor = "";
$versao = "";
$data = "";
$tx_arq = LerArquivoTex("intro", $autor, $versao, $data);

echo "[" . $autor . ", " . $versao . ", " . $data . "]";

?>
