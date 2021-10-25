<?php


function SalvarNavegacao($navegacao)
{
	$s = '<?php $' . 'navegacao = array(';
	
	$p=0;
	foreach ($navegacao as $n)
	{
		if ($p) $s.=',';
		$s .= 'array("' . $n[0] . '","' . $n[1] . '","' . $n[2] . '",array(';

		$pp=0;
		foreach ($n[3] as $pagina)
		{
			if ($pp) $s.=',';
			$s .= 'array("' . $pagina[0] . '","' . $pagina[1] . '","' . $pagina[2] . '")';
			$pp++;
		}
		$s .= '))';
		$p++;
	}
	$s .= ');?>';

	file_put_contents("site/indice.php", $s);
}	
function SalvarProximaPaginaLivre($proxima_pagina_livre)
{
	$s = '<?php $' . 'proxima_pagina_livre = ' . $proxima_pagina_livre. '; ?>';
	
	file_put_contents("site/ids_paginas.php", $s);
}
?>