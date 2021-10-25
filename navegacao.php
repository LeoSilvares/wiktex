<?php

include("site/indice.php");

$cap_num = 0;

function IdPagina($src)
{
	return str_replace("/", "___", $src);
}

function FazerMarcador($tipo, $titulo, $src, $atual, $bullet)
{
	$id = IdPagina($src);
	
	/*
	echo 	"<div class='$tipo" . ($atual?" atual":"") .  "' id='$id' onClick=\"Expandir(event, '$id', true); Navegar('$src', '$id');\">" .
			"<div class='toctitle'>$titulo</div>";
	*/
	
	echo 	"<div class='$tipo" . ($atual?" atual":"") .  "' id='$id'>" .
			"<div class='toctitle'><a href='index.php?pg=$src'>$titulo</a></div>";
	
	if ($bullet == "+")
	{
		echo "<div class='tocbullet' id='tb_$id' onClick=\"Expandir(event, '$id', false)\">&#9661;</div>";
	}
	else if ($bullet == "-")
	{
		echo "<div class='tocbullet' id='tb_$id' onClick=\"Expandir(event, '$id', false)\">&#9651;</div>";
	}
	
	echo "</div>";
}
function FazerCapitulo($titulo, $src, $sessoes, $atual)
{
	global $cap_num;
	$cap_num ++;
	
	$bullet = "";
	
	$capitulo_expandido = ($atual == $src);
	
	foreach($sessoes as $s)
		if ($s[2] == "V")
		{
			$bullet = "+";
			
			break;
		}
	
	foreach($sessoes as $s)
		$capitulo_expandido |= ($atual==$s[1]);
	
	FazerMarcador('capitulo', $titulo, $src, $atual==$src, $bullet);
	if (sizeof($sessoes) > 0)
	{
		$sec_num = 0;
		
		echo "<div class='capitulo_sessoes capitulo_sessoes_esconder' id='sessoes_" . IdPagina($src) . "'>";
		
		foreach($sessoes as $s)
			//if ($s[2] == "V")
				FazerMarcador('sessao', $s[0], $s[1], $atual==$s[1], "🢭");
		
		echo "</div>";
	}	
	
	if ($capitulo_expandido)
		echo "<script>Expandir(null, '$src', false)</script>";
}

function FazerIndice($atual)
{
	global $navegacao;
	
	$edit = Editor();
	
	if ($edit)
	{
		echo "<div id='editar_indice'>" .
				"<div class='float_round_button_nav' id='botao_editar_indice' onclick='EditarIndice();'></div>".
			 "</div>";
	}
	
	foreach ($navegacao as $capitulo)
		if ($capitulo[2] == "V")
			FazerCapitulo($capitulo[0], $capitulo[1], $capitulo[3], $atual);
	
	$id = IdPagina($atual);
	echo "<script>DefinirAtual('$id');</script>";
	
	if ($edit)
	{	
		$guia = 
			array(
				"Guia de edição","guia","V", 
				array(
					array("Comandos suportados","comandos","V"),
					array("Recursos multimídia","recursos","V"),
					array("Modo matemático","modo_matematico","V"),
					array("Ambientes de definições e teoremas","teoremas","V"),
					array("Ambientes de destaque","destaques","V"),
					array("Ambientes de enumeração","enumeracao","V"),
					array("Ambientes de layout","layout","V"),
					array("Ambientes específicos","ambientes","V"),
					
				)
			);
			
		echo "<p></p>";
		FazerCapitulo($guia[0], $guia[1], $guia[3], $atual);
		/*
		echo "<div class='capitulo' id='guia' onClick=\"Expandir(event, 'guia', true); Navegar('guia', 'guia');\">" .
			 "<div class='toctitle'>Guia de edição</div>" .
			 "</div>";
		*/
	}
	
}

?>