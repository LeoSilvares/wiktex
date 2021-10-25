<?php

include("arquivo.php");
include("comum.php");
include("sessao.php");

if (!Editor())
{
	header('Location: index.php');
	exit();
}

$dir_atual = "";
if (isset($_GET['d']))
	$dir_atual = $_GET['d'];

/*
$pg_sair = ChaveSessao('pagina_editando');

if (isset($_GET['pg']))
{
	$pg_sair = $_GET['pg'];
	EscreverSessao('pagina_editando', $pg_sair);
}
*/

if (isset($_GET['r']) && isset($_GET['n']))
{
	//echo "[renomear (" . $dir_atual . "/" . $_GET['r'], $dir_atual . "/" . $_GET['n'] . ")]";
	RenomearMedia($dir_atual . "/" . $_GET['r'], $dir_atual . "/" . $_GET['n']);
	
	if ($dir_atual)
		header('Location: media.php?d=' . urlencode($dir_atual));
	else 
		header('Location: media.php');
	
	exit();
}
if (isset($_GET['x']))
{
	ExcluirMedia($dir_atual . "/" . $_GET['x']);
	
	if ($dir_atual)
		header('Location: media.php?d=' . urlencode($dir_atual));
	else 
		header('Location: media.php');
	
	exit();
}
if (isset($_GET['c']))
{
	CriarPastaMedia($dir_atual . "/" . $_GET['c']);
	
	if ($dir_atual)
		header('Location: media.php?d=' . urlencode($dir_atual));
	else 
		header('Location: media.php');
	
	exit();
}

$tem_voltar = false;
$voltar = "";

$id_elemento = 0;

if ($dir_atual)
{
	$p = strrpos($dir_atual, "/");
	$tem_voltar = true;
	
	if (!$p)
		$voltar = "";
	else
		$voltar = substr($dir_atual, 0, $p);
}

function FazerDir($nome, $classe, $link, $img, $target)
{
	global $id_elemento;
	
	$id = $id_elemento;
	$id_elemento ++;
	
	/*
	echo "<a href='$link' $target>" . 
		 "<div class='elemento'>" . 
		 "<div class='imagem $classe' $img></div>" .
		 "<div class='nome'>$nome</div>" .
		 "</div><a>";
	*/
	
	$link = str_replace("//", "/", $link);
	echo "<div class='elemento' id='el$id' ondblclick='NavegarDir(\"$link\");' onclick = 'Selecionar(\"el$id\");'>" . 
		 "<div class='imagem $classe' $img></div>" .
		 "<div class='nome' id='nome_el$id'>$nome</div>" .
		 "</div>";
	
}

function FazerElemento($nome, $classe, $link, $img, $target)
{
	global $id_elemento;
	
	$id = $id_elemento;
	$id_elemento ++;
	
	/*
	echo "<a href='$link' $target>" . 
		 "<div class='elemento'>" . 
		 "<div class='imagem $classe' $img></div>" .
		 "<div class='nome'>$nome</div>" .
		 "</div><a>";
	*/
	
	$link = str_replace("//", "/", $link);
	echo "<div class='elemento' id='el$id' ondblclick='Navegar(\"$link\");' onclick = 'Selecionar(\"el$id\");'>" . 
		 "<div class='imagem $classe' $img></div>" .
		 "<div class='nome' id='nome_el$id'>$nome</div>" .
		 "</div>";
	
}

function MostrarMedia()
{
	global $dir_atual;
	
	$im = ListarMedia($dir_atual);
	
	//print_r($im);
	
	foreach ($im as $i)
		if ($i[1] == "dir")
			FazerDir($i[0], "dir", "media.php?d=" . urlencode($dir_atual . "/" . $i[0]), "", "");
		else
			FazerElemento($i[0], $i[1], "media/" . $dir_atual . "/" . $i[0], "style='background-image: url(\"$i[2]\");'", "target='_blank'");
}

?>

<html>
<head>
<link href="https://fonts.googleapis.com/css?family=Quicksand" rel="stylesheet">
<link rel="stylesheet" href="css/media.css">

<script>
<?php 
if ($tem_voltar) 
{
	echo "function Voltar(){";
	
	if ($voltar)
		echo "window.location.replace('media.php?d=$voltar');";
	else
		echo "window.location.replace('media.php');";
	echo "}\n";	
}
?>
function Sair()
{
	<?php 
	//if ($pg_sair)
	//	echo "window.location.replace('edit.php?pg=$pg_sair');";
	//else
		echo "window.close();";		
	?>
}
function Mostrar(id)
{
	var e = document.getElementById(id);
	
	e.style.visibility = "visible";
	e.style.display = "block";
}
function Esconder(id)
{
	var e = document.getElementById(id);
	
	e.style.visibility = "hidden";
	e.style.display = "none";
}

var timer = null;
function UploadTimer() 
{
	var e = document.getElementById("fupload");
	
	if (e.value != "")
	{
		//alert(e.value);
		clearInterval(timer);
		
		document.getElementById("upload_form").submit();
	}
}
function Upload()
{
	var e = document.getElementById("fupload");
	e.value = "";
	
	document.getElementById("fupload").click();	
	timer = setInterval(UploadTimer, 500);
}
function NavegarDir(l)
{
	window.location.replace(l);
}
function Navegar(l)
{
	window.open(l, '_blank').focus();
}
var el_selecionado = null;
function Selecionar(id)
{
	var e = document.getElementById(id);
	
	if (el_selecionado!=null)
		el_selecionado.classList.remove('selecionado');
	
	e.classList.add('selecionado');
	el_selecionado = e;
	
	Mostrar('renomear');
	Mostrar('excluir');
}
function Renomear()
{
	if (el_selecionado==null) return;
	
	var e = document.getElementById("nome_" + el_selecionado.id);
	
	var nome = e.innerHTML; 
	var novo_nome = window.prompt("Novo nome do arquivo:",nome);
	
	if (novo_nome != null)
	if (novo_nome != nome)
	{
		//alert("media.php?d=<?php echo urlencode($dir_atual); ?>&r=" + encodeURI(nome) + "&n=" + encodeURI(novo_nome));
		window.location.replace("media.php?d=<?php echo urlencode($dir_atual); ?>&r=" + encodeURI(nome) + "&n=" + encodeURI(novo_nome));
	}
}
function Excluir()
{
	if (el_selecionado==null) return;
	
	var e = document.getElementById("nome_" + el_selecionado.id);
	
	var nome = e.innerHTML; 
	
	if (window.confirm("Deseja excluir o arquivo '" + nome + "'?"))
	{
		window.location.replace("media.php?d=<?php echo urlencode($dir_atual); ?>&x=" + encodeURI(nome));
	}
}
function NovaPasta()
{
	var nome = window.prompt("Nome da pasta:", "");
	
	if (nome != null)
	{
		window.location.replace("media.php?d=<?php echo urlencode($dir_atual); ?>&c=" + encodeURI(nome));
	}
}

/*
function Upload()
{
	Mostrar("fundo");
	Mostrar("upload");
}
function EsconderUpload()
{
	Esconder("fundo");
	Esconder("upload");
}
*/
</script>

</head>
<body>

<div id="titulo">
<?php 
	echo "Arquivos de media"; 
	if ($dir_atual)
		echo " em " . str_replace("//", "/", $dir_atual);
?>	
</div>
<div id="barra">
<?php //echo DadosVersao($autor, $versao, $data);?>
<!-- <div id='barra_versao'>Para salvar, selecione "Visualizar" e depois "Salvar"</div> -->
				
<div id="visualizar" class="barra_btn" onclick='Sair();'>Sair</div>
<?php
if ($tem_voltar) 
	echo "<div id='visualizar' class='barra_btn' onclick='Voltar();'>Voltar</div>";
?>
<div class="barra_btn" onclick='NovaPasta();'>Nova pasta</div>
<div class="barra_btn" onclick='Upload();'>Upload</div>
<div class="barra_btn escondido" id="renomear" onclick='Renomear();'>Renomear</div>
<div class="barra_btn escondido" id="excluir" onclick='Excluir();'>Excluir</div>
</div>


<div id="imagens_div">
<?php MostrarMedia(); ?>
</div>

<div id='fundo' onclick='EsconderUpload();'></div>
<div id='upload'>
	<form id='upload_form' action="upload.php" method="post" enctype="multipart/form-data">
		<p>Selecione o arquivo:</p>
		<input type="file" name="fupload" id="fupload" value="Subir" class='botao'></input>
		<input type='hidden' name='dir_atual' id='dir_atual' value="<?php echo $dir_atual;?>"></input>
		<p style="text-align: right;"><input type='submit' class='botao'></input>&nbsp;&nbsp;<input type="button" class='botao' value="Cancelar" onclick='EsconderUpload();'></input></p>
	</form>
</div>

<?php
if (isset($_GET['m']))
{
	$m = $_GET['m'];
	echo "<script>alert('$m');</script>";
}
?>

</body>
</html>
