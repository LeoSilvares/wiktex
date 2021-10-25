<?php

function LerArgumentosOpcionais($s)
{
	$ret = array();
	$s=str_replace(";", ",", $s);
	$args = explode(",", $s);
	$n = 0;
	
	foreach ($args as $a)
	{
		$ret[$n] = explode("=", $a);
		$n ++;
	}
	
	return $ret;
}
function AdicionarALista($s, $separador, $valor)
{
	if ($s)
		$s .= $separador;
	$s .= $valor;
	
	return $s;
}

?>