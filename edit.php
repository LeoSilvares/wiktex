<?php

include("arquivo.php");
include("comum.php");


$pg = "1";
if (isset($_GET['pg']))
	$pg = $_GET['pg'];

$view = isset($_GET['v']);

$ver = -1;
if (isset($_GET['ver']))
	$ver = $_GET['ver'];

$autor = "";
$versao = 0;
$data = "";

$tx_arq = LerArquivoTex($pg, $autor, $versao, $data);

$versao_exibida = $versao;
$restaurada = -1;


if ($ver > -1 && $ver != $versao)
{
	$restaurada = $ver;
	$tx_arq = LerArquivoTex($pg, $autor, $versao_exibida, $data, $ver);
}

?>

<html>
<head>
<link href="https://fonts.googleapis.com/css?family=Quicksand" rel="stylesheet">
<link rel="stylesheet" href="css/edit.css">

<script>
var alteracao = false;
function Esconder(s)
{
	var e = document.getElementById(s);
	
	e.style.visibility = 'hidden';
	e.style.display = 'none';
	
	
}
function Mostrar(s)
{
	e = document.getElementById(s);
	
	e.style.visibility = 'visible';
	e.style.display = 'inline-block';
	
}
function Enviar(doc)
{
	if (doc == null) return;
	
	var h = doc.getElementById('hidden');
	var tx = editor.getValue();
	h.value = tx;
	
	doc.getElementById("form").submit();
}
function Visualizar()
{
	Esconder("editor");
	Esconder("visualizar");
	Mostrar("preview_div");
	Mostrar("voltar");
	Mostrar("salvar");
	
	var iframe = document.getElementById('preview');
	var innerDocument = iframe.contentDocument || iframe.contentWindow.document;
	Enviar(innerDocument);
}
function Cancelar()
{
	<?php 
		echo "window.location.replace('pagina.php?pg=" . $pg . "');"; 
	?>
}
function Editar()
{
	Mostrar("editor");
	Mostrar("visualizar");
	Esconder("preview_div");
	Esconder("voltar");
	Esconder("salvar");
}
function Salvar()
{
	alteracao = false;
	Enviar(document);
}
function Midia()
{
	<?php 
		//echo "window.location.replace('media.php?pg=" . $pg . "');"; 
		//echo "window.open('media.php?pg=" . $pg . "', '_blank');"; 
		echo "window.open('media.php', '_blank');"; 
	?>
}

window.onbeforeunload = function () 
{
	if (!alteracao) return null;
		
	alert("Você tem alterações feitas e não salvas! Lembre-se de que apenas visualizar as mudanças não faz com que fiquem salvas.");
	
	return "Deseja mesmo sair?";
};

</script>

</head>
<body>

<div id="barra">
<?php //echo DadosVersao($autor, $versao, $data);?>
<!-- <div id='barra_versao'>Para salvar, selecione "Visualizar" e depois "Salvar"</div> -->
				
<div id="visualizar" class="barra_btn" onclick='Visualizar();'>Visualizar</div>
<div id="cancelar" class="barra_btn" onclick='Cancelar();'>Sair</div>
<div id="cancelar" class="barra_btn" onclick='Midia();'>Mídia</div>
<div id="voltar" class="barra_btn" style="display: none; visibility: hidden;" onclick='Editar();'>Editar</div>
<div id="salvar" class="barra_btn" style="display: none; visibility: hidden;" onclick='Salvar();'>Salvar</div>
</div>


<div id="editor_div">
	<div id="editor">
	<?php  
		echo $tx_arq;
	?>
	</div>
	<div id="preview_div" style="display: none; visibility: hidden;">
		<iframe id="preview" src="preview.php?pg=<?php echo $pg;?>"></iframe> 
	</div>
</div>

<script src="ace-builds/src-noconflict/ace.js" type="text/javascript" charset="utf-8"></script>
<script>
    var editor = ace.edit("editor");
    editor.setTheme("ace/theme/cloud");
    editor.session.setMode("ace/mode/latex");
	editor.setShowPrintMargin(false);
	
	editor.session.on('change', function(delta) {alteracao = true;});
</script>

<div id='hidden_div'>
	<form id='form' name='form' method='post' action="salvar.php?pg=<?php echo urlencode($pg);?>">
		<input type='hidden' id='hidden' name='hidden'></input>
		<input type='hidden' id='versao' name='versao' value=<?php echo $versao;?>></input>
		<input type='hidden' id='restaurada' name='restaurada' value=<?php echo $restaurada;?>></input>
	</form>
</div>

<?php 
if ($view)
{
	echo "<script> Visualizar(); </script>";
}
?>

</body>
</html>
