<?php

include('compilar.php');
include('arquivo.php');
include("sessao.php");

SoPermitirSeEditor();

$pg = "";
if (isset($_GET['pg']))
	$pg = $_GET['pg'];

//if ($pg == "")
//	exit();


?>
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
</head>
<body>

<?php
	$tx = "";
	if( isset($_POST['hidden']))
	{
		$tx = $_POST['hidden'];
	}
	else if ($pg)
	{
		$a = ""; $v = ""; $d = "";
		$tx = LerArquivoTex($pg, $a, $v, $d);
	}
	
	echo CompilarLaTeX($pg, $tx);
?>

<div id='hidden_div'>
	<form id='form' name='form' method='post' action="preview.php?pg=<?php echo $pg;?>">
		<input type='hidden' id='hidden' name='hidden'></input>
	</form>
</div>

</body>
</html>

