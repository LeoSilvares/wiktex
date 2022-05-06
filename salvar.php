<?php

include('compilar.php');
include('arquivo.php');
include('sessao.php');

SoPermitirSeEditor();

$pg = "";
if (isset($_GET['pg']))
	$pg = $_GET['pg'];

if ($pg == "")
	exit();


if( isset($_POST['hidden']))
{
	$tx = $_POST['hidden'];
	$v = $_POST['versao'];
	$restaurada = $_POST['restaurada'];
	//echo "[" . $v . "]";
	$versao = $v + 1;
	
	//echo "[" . $versao . "]";
	
	SalvarVersaoAnteriorTex($pg, $versao - 1);
	SalvarArquivoTex($pg, $tx, Nome(), $versao, $restaurada);
	
	$html = CompilarLaTeX($pg, $tx);

	SalvarArquivoHtml($pg, $html);
	
	header('Location: pagina.php?pg=' . $pg);
}

?>
