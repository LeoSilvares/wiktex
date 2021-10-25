<?php

function Licenca($n)
{
	if ($n == 0)
		return array("Atribuição", "CC BY", "https://creativecommons.org/licenses/by/4.0", "https://licensebuttons.net/l/by/3.0/88x31.png");
	if ($n == 1)
		return array("Atribuição-CompartilhaIgual", "CC BY-SA", "https://creativecommons.org/licenses/by-sa/4.0", "https://licensebuttons.net/l/by-sa/3.0/88x31.png");
	if ($n == 2)
		return array("Atribuição-SemDerivações", "CC BY-ND", "https://creativecommons.org/licenses/by-nd/4.0", "https://licensebuttons.net/l/by-nd/3.0/88x31.png");
	if ($n == 3)
		return array("Atribuição-NãoComercial", "CC BY-NC", "https://creativecommons.org/licenses/by-nc/4.0", "https://licensebuttons.net/l/by-nc/3.0/88x31.png");
	if ($n == 4)
		return array("Atribuição-NãoComercial-CompartilhaIgual", "CC BY-NC-SA", "https://creativecommons.org/licenses/by-nc-sa/4.0", "https://licensebuttons.net/l/by-nc-sa/3.0/88x31.png");
	if ($n == 5)
		return array("Atribuição-NãoComercial-SemDerivações", "CC BY-NC-ND", "https://creativecommons.org/licenses/by-nc-nd/4.0", "https://licensebuttons.net/l/by-nc-nd/3.0/88x31.png");
	
	return null;
}


function FazerLicencaCCPagina($n)
{
	$l = Licenca($n);
	
	if (!$l) return;
	
	return "<a class='licencaCC' href='" . $l[2] . "' target='_blank'><img src='" . $l[3] . "' title='Material disponível sob os termos da licença Creative Commons " . $l[1] . "'></img></a>";
}

?>