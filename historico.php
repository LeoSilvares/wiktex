<?php

include("sessao.php");
include("configuracao.php");
include("arquivo.php");
include("site/config.php");


if (!Editor())
{
	header('Location: index.php');
	exit();
}

$pg = '';

if (isset($_GET['pg']))
{
	$pg = $_GET['pg'];
}

if (!$pg)
{
	header('Location: index.php');
	exit();
}

function ListarVersao($v)
{
	$tx = $v[0] . " (" . $v[1] . ", " . $v[2] . ")";
	
	if (isset($v[3]))
		if ($v[3] != -1)
		$tx .= " - restaurada da versao " .  $v[3]; 
	
	echo 	"<div class='versao'>" .
				"<div class='titulo'>Versao " .$tx ."</div>".
				"<div class='opcoes'><div class='opcao ver' onClick='Ver(\"" . $v[0] . "\")'></div></div>" .
			"</div>";
}

function ListarVersoes($pg)
{
	$versoes = LerVersoesTex($pg);
	
	if (!$versoes)
	{	
		echo "Nenhuma versão encontrada.";
		return;
	}	
	
	foreach ($versoes as $v)
		ListarVersao($v);
}


?>

<html>

<head>
<link href="https://fonts.googleapis.com/css?family=Quicksand" rel="stylesheet">
<link rel="stylesheet" href="<?php echo $caminho_css; ?>/historico.css">

<script>
function Ver(v)
{
	<?php 
		echo "window.location.replace('edit.php?pg=" . $pg . "&ver=' + v);";
	?>	
}
function Voltar()
{
	<?php echo "window.location.replace('edit.php?pg=" . $pg . "');"; ?>
}

</script>
	
</head>

<body>

<div id='main' class="main">
<div id="barra">
	<div id="voltar" class="barra_btn" onclick='Voltar();'>Voltar</div>
</div>
<div id="texto">
	Para restaurar uma versão anterior, visualize a versão e a salve
</div>

<?php ListarVersoes($pg) ?>

</div>

</body>
</html>