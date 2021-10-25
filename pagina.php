<?php

include("arquivo.php");
include("comum.php");
include("sessao.php");
include("site/config.php");
include("licencas.php");

$pg = "intro.html";

if (isset($_GET['pg']))
	$pg = $_GET['pg'];

$editor = Editor();

$autor = "";
$versao = "";
$data = "";
$tx_arq = LerArquivoTex($pg, $autor, $versao, $data);

?>

<html>
<head>
<link href="https://fonts.googleapis.com/css?family=Quicksand" rel="stylesheet">
<link rel="stylesheet" href="css/conteudo.css">
<link rel="stylesheet" href="css/custom.css">
<script>
MathJax = {
  tex: {
    inlineMath: [['$', '$'], ['\\(', '\\)']]
  },
  svg: {
    fontCache: 'global'
  }
};
</script>
<script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script> 
<?php if ($editor) { ?>
<script>
function Editar()
{
	<?php 
		echo "window.location.href = \"edit.php?pg=" . urlencode($pg) . "\";";
	?>
}
function Historico()
{
	<?php 
		echo "window.location.href = \"historico.php?pg=" . urlencode($pg) . "\";";
	?>
}
</script>
<?php } ?>
</head>
<body>

<?php  
	if ($editor)
	{
		echo 
			"<div id='edit'>" .
				DadosVersao($autor, $versao, $data) .
				"<div id='hist_button' class='float_round_button' onClick=\"Historico();\"></div>" .
				"<div id='edit_button' class='float_round_button' onClick=\"Editar();\"></div>" .
			"</div>";
	}
	
	echo "<div id='licencaCC'>". FazerLicencaCCPagina($licencaCC) . "</div>"; 

	
	include("pag/" . $pg . ".html");
?>

</body>
</html>
