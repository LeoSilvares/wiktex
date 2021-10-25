<?php

include('arquivo.php');
include('sessao.php');

if (!Editor())
{
	header('Location: media.php');
	exit();
}


if (!isset($_POST['dir_atual']))
	Sair("Diretório inválido");

$dir_atual = str_replace("..", ".", $_POST['dir_atual']);
$dir_destino = "media/". $dir_atual;

if (!is_dir("media/". $dir_atual))
	Sair("Diretório inválido");

$arq = $dir_destino . "/" . basename($_FILES["fupload"]["name"]);

$ok = false;
$ext = strtolower(pathinfo($arq, PATHINFO_EXTENSION));

//if(!isset($_POST["submit"])) 
//	Sair("Método inválido");

if (!EhImagem($ext))
{
	Sair("Tipo de arquivo não permitido");
}
	
if (!move_uploaded_file($_FILES["fupload"]["tmp_name"], $arq))
{
	Sair("Erro salvando");
}


Sair("");


function Sair($mensagem)
{	
	global $dir_atual;
	
	$d = "";
	if ($dir_atual)
		$d="?d=" . $dir_atual;
	
	$m = "";
	if ($mensagem)
	{
		if ($d) 
			$m="&"; 
		else 
			$m="?";
		
		$m .= "m=" . urlencode($mensagem);
	}
	
	//echo "Location: media.php$d$m";
	header("Location: media.php$d$m");
}

?>
