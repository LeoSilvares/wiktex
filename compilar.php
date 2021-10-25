<?php

include('libLatex/LaTeX.php');
include('site/indice.php');

$paginas = -1; // -1 = não iniciado
$ignoradas = 0;
$id_atual = -1;

function LerPaginasDoCapitulo()
{
	global $navegacao, $paginas, $id_atual;
	
	if ($paginas == null) return;
	
	$paginas = null;
	
	foreach($navegacao as $c)
	{
		if ($c[1] == $id_atual)
		{
			$paginas = $c[3];
			
			return;
		}
	}
}

function iteration_pages($i)
{
	global $paginas, $ignoradas;
	
	if ($i == 0) // iniciando
	{
		$ignoradas = 0;
	
		if($paginas == -1)
			LerPaginasDoCapitulo();
	}
	
	if ($paginas == null)
		return null;
	
	$n = $i + $ignoradas;
	
	while (isset($paginas[$n]))
	{
		if ($paginas[$n][2] != "V")
		{
			$ignoradas ++;
			$n = $i + $ignoradas;
		}
		else 
		{
			return array($paginas[$n][0], $paginas[$n][1]);	
		}
	}		
	
	return null;
}
function CompilarLaTeX($pg, $tx)
{
	global $id_atual;
	
	$id_atual = $pg;
	
	$parser = new LaTeX2Html("media/");
	$parser->NewCommandIterator("\\tableofpages", "iteration_pages", "<a href='?pg=#2'><div class='toc_page'><div>#1</div></div></a>", 1);
	
	return $parser->Processar($tx);
}


?>