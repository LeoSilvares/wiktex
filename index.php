<?php
include("navegacao.php");
include("sessao.php");
include("site/config.php");
include("licencas.php");

?>
<html>

<head>
<link href="https://fonts.googleapis.com/css?family=Quicksand" rel="stylesheet">
<link rel="stylesheet" href="<?php echo $caminho_css; ?>/index.css">

<script>
var atual=null;
var src_atual="";
function toc()
{
	document.getElementById("main").classList.toggle("main_g");
	document.getElementById("head").classList.toggle("head_g");
	document.getElementById("indice").classList.toggle("indice_g");
}
function Navegar(s, id)
{
	document.getElementById("corpo_pag").src="pagina.php?pg="+s + "&edit";
	DefinirAtual(id);
}
function DefinirAtual(a)
{
	src_atual = a;
	if (atual!=null) atual.classList.remove("atual");
	atual=document.getElementById(a);
	atual.classList.add("atual");
}
function Expandir(event, s, forcar)
{
	var e = document.getElementById("sessoes_" + s);
	
	if (e==null) return;
	
	if (e.classList.contains("capitulo_sessoes_esconder"))
	{	
		e.classList.remove("capitulo_sessoes_esconder");
		document.getElementById("tb_" + s).innerHTML = "&#9651;"
	}
	else if (!forcar)
	{	
		e.classList.add("capitulo_sessoes_esconder");
		document.getElementById("tb_" + s).innerHTML = "&#9661;"
	}
	
	if (event != null)
		event.stopPropagation();
}
function YouTube(id)
{
	document.getElementById('youtube').src = "https://www.youtube.com/embed/" + id;
	
	document.getElementById("full_back").classList.remove("full_hidden");
	document.getElementById("full_media").classList.remove("full_hidden");
}
function EsconderMedia()
{
	document.getElementById('youtube').src = "";
	
	document.getElementById("full_back").classList.add("full_hidden");
	document.getElementById("full_media").classList.add("full_hidden");
}

<?php

$perfil = Perfil();


if (Editor()) {
?>
function EditarIndice()
{
	window.location.replace('paginas.php');
}

<?php
}
?>


</script>
	
</head>

<body>

<?php
$pg = 1;

if(isset($_GET['pg']))
	$pg = $_GET['pg'];
?>
<div id='main' class="main_p">
<div id="head" class="head_p">
	<?php 
		echo $nome_site;
		//echo "<div id='licencaCC'>". FazerLicencaCC($licencaCC) . "</div>"; 
		//echo FazerLicencaCC($licencaCC); 
	?>
</div>
<div id="corpo">
	<iframe id="corpo_pag" src="pagina.php?pg=<?php echo $pg;?>"></iframe>
</div>
</div>
<div id="indice" class="indice_p">
<?php 

FazerIndice($pg);

//echo "<div id='licencaCC'>". FazerLicencaCC($licencaCC) . "</div>"; 

//if ($perfil)
//	echo "<div id='arquivos_de_media'><a href='media.php'>Arquivos de mídia</a></div>";
?>&nbsp;

</div>
<div id="toc" class="float_round_button" onClick="toc();"></div>

<?php

echo "<div id='div_login'>";

if(!$perfil)
	echo "<a href='login.php' class='naologado'>Login</a>";
else 
{
	$nome = Nome();
	$login = Login();
	
	if ($perfil == 'a')
		echo "<a class='logado' href='conta.php'>$nome ($login)</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href='adm.php' class='logado'>Administrar</a><a href='sair.php' class='logado'>Sair</a>";
	else if ($perfil == 'e')
		echo $nome . "<a href='sair.php' class='logado'>Sair</a>";
}	

echo "&nbsp;&nbsp;<a href=\"https://github.com/LeoSilvares/wiktex\" class='poweredby'>Powered by Wikitex</a>";

echo "</div>";

?>

<div id='full_back' class='full_hidden' onClick="EsconderMedia();"></div>
<div id='full_media' class='full_hidden'>
	<iframe id="youtube" width="560" height="315" src="" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
</div>

</body>
</html>