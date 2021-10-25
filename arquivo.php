<?php

$TAG_AUTOR = "%meta-autor ";
$TAG_VERSAO = "%meta-versao ";
$TAG_DATA = "%meta-data ";
$TAG_RESTAURADA = "%meta-restaurada ";
$TAG_CONTEUDO = "%conteudo\n";

function LerCampo($tx, $campo)
{
	$pos = strpos($tx, $campo);
	
	if ($pos === false)
		return false;

	$npos = strpos($tx, "\n", $pos);
	
	if ($pos === false)
		return null;
	
	$pos += strlen($campo);
	
	return substr($tx, $pos, $npos - $pos);
}
function LerConteudo($tx)
{
	global $TAG_CONTEUDO;
	$p = strpos($tx, $TAG_CONTEUDO);
	
	if (!$p)
		return $tx;
	
	return substr($tx, $p + strlen($TAG_CONTEUDO));
}
function LerArquivoTex($pg, & $autor, & $versao, & $data, $versao_a_ler = -1)
{
	global $TAG_AUTOR;
	global $TAG_VERSAO;
	global $TAG_DATA;
	
	$nome = "latex/" . $pg . ".tex";
	
	if ($versao_a_ler > -1)
		$nome = "latex/" . $pg . "/ver_" . $versao_a_ler . ".tex";
	
	$tx = file_get_contents($nome);
	
	if ($tx == null)
		return null;
	
	$autor = LerCampo($tx, $TAG_AUTOR);
	$versao = LerCampo($tx, $TAG_VERSAO);
	$data = LerCampo($tx, $TAG_DATA);
	
	//return ltrim(str_replace(">", "&gt;", str_replace("<", "&lt;", LerConteudo($tx))));
	return htmlentities(ltrim(LerConteudo($tx)));
}

function SalvarArquivoTex($pg, $conteudo, $autor, $versao, $restaurada = -1)
{
	global $TAG_AUTOR;
	global $TAG_VERSAO;
	global $TAG_DATA;
	global $TAG_CONTEUDO;
	global $TAG_RESTAURADA;
	
	$data = new DateTime('NOW');
	
	if (!$autor) $autor = "Desconhecido";
	if (!$versao) $versao = 0;
	//if (!$data) $data = "--/--/--";
	
	$dt = $data->format('d/M/Y H:i:s');
	
	$tx = "";
	
	if ($restaurada > 0)
		$tx = $TAG_RESTAURADA . $restaurada . "\n";
	
	$tx .= 	$TAG_AUTOR . $autor . "\n" .
			$TAG_VERSAO . $versao . "\n" .
			$TAG_DATA . $dt . "\n" .
			$TAG_CONTEUDO . "\n" . ltrim($conteudo);
			
	file_put_contents("latex/" . $pg . ".tex", $tx);
	
	RegistrarVersaoTex($pg, $autor, $versao, $dt, $restaurada);
}
function ArquivoTexExiste($pg)
{
	return file_exists("latex/" . $pg . ".tex");
}
function SalvarArquivoHtml($pg, $html)
{
	file_put_contents("pag/" . $pg . ".html", $html);
}
function CriarDir($d)
{
	if (!file_exists($d))
		mkdir($d, 0777, true);
}
function SalvarVersaoAnteriorTex($pg, $ver)
{
	CriarDir('latex/'. $pg);
	copy('latex/' . $pg . '.tex', 'latex/' . $pg . '/ver_' . $ver . '.tex');
}
function RegistrarVersaoTex($pg, $autor, $versao, $data, $restaurada)
{
	CriarDir('latex/'. $pg);
	file_put_contents('latex/'. $pg . '/ver.txt', $versao . "|" . $autor . "|" . $data . "|" . $restaurada . "\n", FILE_APPEND);
}
function LerVersoesTex($pg)
{
	$a = 'latex/' . $pg . "/ver.txt";
	
	if (!file_exists($a)) return false;
	
	$tx = file_get_contents($a);
	
	if (!$tx)
		return false;
	
	$ver = explode("\n", $tx);
	
	$ret = array();

	$pos = 0;
	
	foreach ($ver as $v)
	{
		$a = explode("|", $v);
		
		if (sizeof($a) >= 3)
		{
			//print_r($a);
			$ret[$pos] = $a;
			$pos ++;
		}
	}
	
	//print_r($ret);
	return $ret;
}

function CriarPagina($pg, $usuario)	// Corrigir
{
	SalvarArquivoTex($pg, "", $usuario, "1");
	SalvarArquivoHtml($pg, "");
}
function CriarPaginas($navegacao, $fora_de_uso, $primeira_pagina_nova, $usuario)
{
	foreach ($navegacao as $p)
		if ($p[1] >= $primeira_pagina_nova)
			if (!ArquivoTexExiste($p[1])) CriarPagina($p[1], $usuario);
	
	foreach ($fora_de_uso as $p)
		if ($p[1] >= $primeira_pagina_nova)
			if (!ArquivoTexExiste($p[1])) CriarPagina($p[1], $usuario);
}
function EhImagem($s)
{
	return  ($s == "png" ||
			 $s == "jpg" ||
			 $s == "jpeg");
}
function Excluido($s)
{
	if (strlen($s) < 6) return false;
	
	return (substr($s, 0, 5) == "__X__");
}
function ListarMedia($dir)
{
	$dir = "media/" . $dir;
	
	$arquivos = scandir($dir);
	$ret_dir = array();
	$ret_arq = array();
	$n_dir = 0;	
	$n_arq = 0;	
	
	foreach($arquivos as $a)
	{
		if (!Excluido($a))
		{
			if (is_dir($dir . "/" . $a))
			{
				if($a != "." && $a != "..")
				{
					$ret_dir[$n_dir] = array($a, "dir");//, "img/dir.png");
					$n_dir++;
				}
			}
			else
			{
				$tipo = "arq";
				$img = "img/arq.png";
				
				$ext = pathinfo($dir . "/" . $a, PATHINFO_EXTENSION);
				
				if (EhImagem($ext))
				{
					$tipo = "img";
					$img = $dir . "/" . $a;
				}

				$ret_arq[$n_arq] = array($a, $tipo, $img);
				$n_arq ++;
			}
		}
	}

	array_splice($ret_arq, 0, 0, $ret_dir); // inserindo
	
	return $ret_arq;
}
function RenomearArquivo($antigo, $novo)
{
	//echo "[Renomear [$antigo] ---> [$novo]]";
	rename($antigo, $novo);
}
function RenomearMedia($antigo, $novo)
{
	rename("media/" . $antigo, "media/" . $novo);
}
function ExcluirArquivo($nome)
{
	$p = strrpos($nome,"/");
	
	//echo "[$p]";
	
	if (!$p)
		RenomearArquivo($nome, "__X__" . $nome);
	else if ($p == 0) 
		RenomearArquivo($nome, "__X__" . substr($nome, 1));
	else 
		RenomearArquivo($nome, substr($nome, 0, $p+1) . "__X__" . substr($nome, $p+1));
}
function ExcluirMedia($nome)
{
	$nome = str_replace("//", "/", "media/" . $nome);
	
	ExcluirArquivo($nome);
}
function CriarPastaMedia($nome)
{
	$nome = str_replace("//", "/", "media/" . $nome);
	
	mkdir($nome);
}
function SalvarUsuarios($usuarios)
{
	$s = "<?php \$usuarios = array(\n";
	$primeiro = true;
		
	foreach($usuarios as $u=>$d)
	{
		if (!$primeiro)
			$s .= ",";
		
		$primeiro = false;
		
		$s .= "'$u'=>array('" . $d[0] . "','" . $d[1] . "','" . $d[2] . "')\n";  
	}
	$s .= "\n); ?>";
	
	file_put_contents("site/usuarios.php", $s);
}

?>