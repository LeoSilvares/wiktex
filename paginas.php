<?php

include("sessao.php");
include("configuracao.php");
include("arquivo.php");

include("site/indice.php");
include("site/fora_de_uso.php");
include("site/config.php");
include("site/ids_paginas.php");

if (!Editor())
{
	header('Location: index.php');
	exit();
}

$movendo = false;
$criando = false;
$erro = "";
$ignorado = false;

$aviso = "";						// aviso a ser escrito

$pagina_criada = false;				// número da página foi criada, se alguma
$alteracao_paginas = false;			// teve alteração que implique salvar o índice

$s = ChaveSessao("navegacao");
if ($s)
	$navegacao = $s;

$s = ChaveSessao("proxima_pagina_livre");
if ($s)
	$proxima_pagina_livre = $s;

$s = ChaveSessao("pagina_criada");
if ($s)
{
	$pagina_criada = $s;
	EscreverSessao("pagina_criada", false);
}

if(isset($_GET["a"]))
{
	$acao = $_GET["a"];
	
	$o = false;
	$d = false;
	if (isset($_GET['o'])) $o = $_GET['o'];
	if (isset($_GET['d'])) $d = $_GET['d'];
	
	if ($acao == 'm')	// mover
	{
		if ($o !== false && $d !== false)
		{
			MoverPagina($o, $d);
		}
		else
		{
			$aviso = "Mova a página para a posição desejada";
			$movendo = $o;
		}
	}
	else if ($acao == 'r')	// renomear
	{
		$v = false;
		if (isset($_GET['v'])) $v = $_GET['v'];
	
		if ($o !== false && $v)
			RenomearPagina($o, $v);
	}
	else if ($acao == 's') // mudança de status da página (visivel, escondida, excluida)
	{
		if ($o !== false && d !== false)
			StatusPagina($o, $d);
	}	
	else if ($acao == 'n') // nova página
	{
		if ($d !== false)
			NovaPagina($d);
		else 
			$criando = true;
	}
	else if ($acao == 'v')
	{
		CancelarAlteracoes();		
	}	
}

if ($erro || $ignorado)
	$movendo = false;

if ($alteracao_paginas)
	SalvarAlteracoes();
	
function SalvarAlteracoes()
{
	global $navegacao, $proxima_pagina_livre, $pagina_criada;
	
	SalvarNavegacao($navegacao);
	SalvarProximaPaginaLivre($proxima_pagina_livre);
	
	EscreverSessao("navegacao", $navegacao);
	EscreverSessao("proxima_pagina_livre", $proxima_pagina_livre);

	EscreverSessao("pagina_criada", $pagina_criada);
		
	header("Location: paginas.php");
	exit();
}
function CancelarAlteracoes()
{
	EscreverSessao("navegacao", false);
	EscreverSessao("proxima_pagina_livre", false);
	EscreverSessao("pagina_criada", false);
		
	header("Location: index.php");
	exit();
}

function LerPagina($pos, & $cap, & $pagina)
{
	$p = explode("_", $pos);
	
	$cap = -1;
	$pagina = -1;
		
	if(sizeof($p) == 1)
	{
		$cap = $p[0];
		$pagina = -1;
		return true;
	}
	
	if(sizeof($p) == 2)
	{
		$cap = $p[0];
		$pagina = $p[1];
		return true;
	}
	
	return false;
}
function LerCapitulo($n)
{
	global $navegacao;
	
	if (isset($navegacao[$n]))
		return $navegacao[$n];
	
	return null;
}
function MoverPagina($o, $d)
{
	global $alteracao_paginas, $navegacao, $erro, $ignorado;
	
	$cap_o = -1;
	$pagina_o = -1;
	$cap_d = -1;
	$pagina_d = -1;
	
	LerPagina($o, $cap_o, $pagina_o);
	LerPagina($d, $cap_d, $pagina_d);
	
	if ($cap_o == -1 || $cap_d == -1)
		return;
	
	if ($pagina_o == -1 && $pagina_d != -1) // movendo capítulo para dentro de capítulo
	{
		$cap = LerCapitulo($cap_o);
				
		if ($cap && isset($navegacao[$cap_d]))
		{
			if (sizeof($cap[3]) == 0) // não tem sessoes, pode ser movido
			{
				array_splice($navegacao[$cap_d][3], $pagina_d, 0, array($cap)); // inserindo
				array_splice($navegacao, $cap_o, 1); // removendo
				
				$alteracao_paginas = true;
			}
			else
			{
				$erro = "Não é possível mover um capítulo com páginas para dentro de um capítulo";
			}
		}		
	}
	else if ($pagina_o == -1 && $pagina_d == -1) // movendo capítulo
	{
		if ($cap_d == $cap_o || $cap_d == $cap_o + 1) // mudança pro mesmo lugar
		{
			$ignorado = true;
			return;
		}
		
		if (isset($navegacao[$cap_o]))
		{
			array_splice($navegacao, $cap_d, 0, array($navegacao[$cap_o])); // reinserindo
			
			if ($cap_o > $cap_d) $cap_o ++;
			array_splice($navegacao, $cap_o, 1); // removendo
			
			$alteracao_paginas = true;
		}		
	}
	else if ($pagina_o != -1 && $pagina_d != -1 && $cap_o == $cap_d) // movendo página dentro de um mesmo capítulo
	{
		if ($pagina_d == $pagina_o || $pagina_d == $pagina_o + 1) // mudança pro mesmo lugar
		{
			$ignorado = true;
			return;
		}
		
		if (isset($navegacao[$cap_o]))
		{
			$pagina = $navegacao[$cap_o][3][$pagina_o];
			
			array_splice($navegacao[$cap_o][3], $pagina_d, 0, array($pagina)); // reinserindo
			
			if ($pagina_o > $pagina_d) $pagina_o ++;
			array_splice($navegacao[$cap_o][3], $pagina_o, 1); // removendo
						
			$alteracao_paginas = true;
		}
	}
	else if ($pagina_o != -1 && $pagina_d != -1 && $cap_o != $cap_d) // movendo página de um capítulo para outro
	{
		if (isset($navegacao[$cap_o]) && isset($navegacao[$cap_o]))
		{
			$pagina = $navegacao[$cap_o][$pagina_o];
			
			array_splice($navegacao[$cap_d][3], $pagina_d, 0, array($pagina)); // reinserindo
			array_splice($navegacao[$cap_o][3], $pagina_o, 1); // removendo
			
			$alteracao_paginas = true;
		}		
	}
	else if ($pagina_o != -1 && $pagina_d == -1) // promovendo página a capítulo
	{
		echo "Fazendo";
		if (isset($navegacao[$cap_o]))
		{
			$pagina = $navegacao[$cap_o][3][$pagina_o];
			
			array_splice($navegacao[$cap_o][3], $pagina_o, 1); // removendo
			array_splice($navegacao, $cap_d, 0, array(array($pagina[0], $pagina[1], $pagina[2], array()))); // inserindo
			
			$alteracao_paginas = true;
		}		
	}
}
function StatusPagina($o, $s)
{
	if ($s != 'V' && $s != 'X' && $s != 'E')
		return;	
		
	global $alteracao_paginas, $navegacao, $erro;
	
	$cap_o = -1;
	$pagina_o = -1;
	
	LerPagina($o, $cap_o, $pagina_o);
	
	if ($cap_o === false)
		return;
	
	if ($pagina_o == -1) // capitulo
	{
		if (isset($navegacao[$cap_o]))
		{
			if ($s != "X" || sizeof($navegacao[$cap_o][3]) == 0) // Só pode ser excluído se não tiver páginas
			{
				$navegacao[$cap_o][2] = $s; 
				$alteracao_paginas = true;
			}
			else if ($s == "X")
			{
				$erro = "Não é possível excluir um capítulo com páginas";
			}
		}		
	}
	else if ($pagina_o != -1) // página
	{
		if (isset($navegacao[$cap_o]))
		{
			$navegacao[$cap_o][3][$pagina_o][2] = $s;			
			$alteracao_paginas = true;
		}
	}
}
function RenomearPagina($o, $v)
{
	global $alteracao_paginas, $navegacao, $fora_de_uso, $erro;
	
	$cap_o = -1;
	$pagina_o = -1;
	
	LerPagina($o, $cap_o, $pagina_o);
	
	if ($cap_o === false)
		return;
	
	if ($cap_o == 'F')
	{
		if ($pagina_o > -1)
		{
			$fora_de_uso[$pagina_o][0] = $v;
			$alteracao_paginas = true;
		}
	}
	else if ($pagina_o == -1) // renomeando capitulo
	{
		$navegacao[$cap_o][0] = $v;
		$alteracao_paginas = true;
	}
	else if ($pagina_o != -1) // renomeando página
	{
		$navegacao[$cap_o][3][$pagina_o][0] = $v;
		$alteracao_paginas = true;
	}
}
function NovaPagina($d)
{
	global $navegacao,$proxima_pagina_livre, $pagina_criada, $alteracao_paginas;
	
	while (ArquivoTexExiste($proxima_pagina_livre))
		$proxima_pagina_livre ++;
	
	$criou = false;
	
	$cap_d = -1;
	$pagina_d = -1;
	
	LerPagina($d, $cap_d, $pagina_d);
	
	//echo "[$proxima_pagina_livre,$d,$cap_d,$pagina_d]";
	
	if ($cap_d == -1)
		return;
	
	if ($pagina_d == -1) // novo capítulo
	{
		$pagina_criada = $d;
		$pagina = array("Página $proxima_pagina_livre", $proxima_pagina_livre, "E", array());
		array_splice($navegacao, $cap_d, 0, array($pagina)); // inserindo
		
		$criou = true;
	}
	else // nova página
	{
		if (isset($navegacao[$cap_d]))
		{
			$pagina_criada = $d;
			$pagina = array("Página $proxima_pagina_livre", $proxima_pagina_livre, "E");
		
			array_splice($navegacao[$cap_d][3], $pagina_d, 0, array( $pagina )); // inserindo
			
			$criou = true;
		}
	}
	
	if ($criou)
	{
		//echo "[Criou página $proxima_pagina_livre]";
		CriarPagina($proxima_pagina_livre, Nome());
		
		$alteracao_paginas = true;
		$proxima_pagina_livre++;
	}
}

function Marcador($tipo, $pos)
{
	global $movendo, $criando;
	
	if ($movendo === false && $criando === false)
		echo "<div class='place $tipo'></div>";
	else if ($movendo)
		echo "<div class='movendo $tipo' onclick='MoverPara(\"$movendo\", \"$pos\")'></div>";
	else if ($criando)
		echo "<div class='movendo $tipo' onclick='CriarEm(\"$pos\")'></div>";
	
}
function FazerTopico($tipo, $titulo, $id, $v, $pos, $fora=false)
{
	global $movendo, $criando, $pagina_criada;
	
	if ($movendo !== false)
	
	if ($pos == $movendo)
		$tipo .= " movendo_atual";	
	if ($pagina_criada  && $pos == $pagina_criada)
		$tipo .= " adicionada_agora";
	
	$botao_mostrar = false;
	
	if ($v == 'E')
	{
		$tipo .= " escondido";
		$botao_mostrar = "mostrar";
		$valor_mostrar = "V";
	}
	else if ($v == 'CE') // capítulo escondido
	{
		$tipo .= " escondido";
	}
	else if ($v == 'V')
	{
		$botao_mostrar = "esconder";
		$valor_mostrar = "E";
	}
	
	echo "<div class='topico $tipo'>" .
				"<div class='titulo' id='nome_$pos'>$titulo</div>".
				"<div class='div_renomear' id='renomear_$pos' style='visibility: hidden; display: none;'>" .
					"<input id='edit_renomear_$pos' value='$titulo'></input>".
					"<input type='button' onclick='FazerRenomear(\"$pos\");' value='Renomear'></input>".
					"<input type='button' onclick='CancelarRenomear(\"$pos\");' value='Cancelar'></input>".
				"</div>".
				"<div class='opcoes'>" .
					($botao_mostrar? "<div class='opcao $botao_mostrar' onClick='MostrarOuEsconder(\"$pos\",\"$valor_mostrar\")'></div>":"") .
					"<div class='opcao renomear' onClick='Renomear(\"$pos\")'></div>" .
					"<div class='opcao ver' onClick='Ver(\"$id\")'></div>" .
					"<div class='opcao mover' onClick='Mover(\"$pos\", \"$id\")'></div>" .
					((!$fora)?"<div class='opcao excluir' onClick='Excluir(\"$pos\")'></div>":"") .
				"</div>" .
			"</div>";
}
function FazerCapitulo($titulo, $id, $v, $paginas, $cap_pos)
{
	FazerTopico('capitulo', $titulo, $id, $v, $cap_pos);
	
	$pos = 0;
		
	foreach ($paginas as $s)
	{
		if ($s[2] != "X")
		{
			$pv = $s[2];
			if ($v == "E")
				$pv = "CE";
			
			Marcador('pagina', $cap_pos . "_" . $pos);
			FazerTopico('pagina', $s[0], $s[1], $pv, $cap_pos . "_" . $pos);
			$pos ++;
		}
	}
	Marcador("pagina", $cap_pos . "_" . $pos);
}

function Indice($aviso)
{
	global $navegacao;
	$pos = 0;
	
	echo "<h2>Índice</h2>";

	if ($aviso)
		echo "<div class='aviso'>$aviso</div>";

	//print_r($navegacao);

	foreach ($navegacao as $capitulo)
	{
		if ($capitulo[2] != "X")
		{
			Marcador('capitulo', $pos);
			FazerCapitulo($capitulo[0], $capitulo[1], $capitulo[2], $capitulo[3], $pos);
			$pos ++;
		}
	}
	Marcador('capitulo', $pos);
	
}
/*
function Lixeira($aviso)
{
	global $navegacao;
	
	if ($aviso)
		echo "<div class='aviso'>$aviso</div>";

	if (sizeof($fora_de_uso) > 0)
	{		
		echo "<h2>Páginas fora de uso</h2>";
		$pos = 0;
		foreach ($fora_de_uso as $pagina)
		{
			FazerTopico('fora', $pagina[0], $pagina[1], "F_$pos", true);
			$pos ++;
		}
	}
}
*/
?>

<html>

<head>
<link href="https://fonts.googleapis.com/css?family=Quicksand" rel="stylesheet">
<link rel="stylesheet" href="<?php echo $caminho_css; ?>/paginas.css">

<script>
function Mostrar(id)
{
	var e = document.getElementById(id);
	e.style.visibility = 'visible';
	e.style.display = 'inline';
}
function Esconder(id)
{
	var e = document.getElementById(id);
	e.style.visibility = 'hidden';
	e.style.display = 'none';
}
function Renomear(pos)
{
	Mostrar('renomear_'+pos);
	Esconder('nome_'+pos);
}
function CancelarRenomear(pos)
{
	Esconder('renomear_'+pos);
	Mostrar('nome_'+pos);
	
	document.getElementById("edit_renomear_" + pos).value = document.getElementById("nome_" + pos).innerHTML;
}
function FazerRenomear(pos)
{
	window.location.replace('paginas.php?a=r&o=' + pos + '&v=' + encodeURI(document.getElementById("edit_renomear_" + pos).value));
}
function Mover(pos, id)
{
	window.location.replace('paginas.php?a=m&o='+pos);
}
function MoverPara(origem, destino)
{
	window.location.replace('paginas.php?a=m&o='+origem + '&d=' + destino);
}
function CriarEm(destino)
{
	window.location.replace('paginas.php?a=n&d='+destino);
}
function Excluir(pos)
{
	var e = document.getElementById("nome_" + pos);
	if (e==null)
		return;
	
	var s = e.innerHTML;
	if (!confirm("Confirma a exclusão da página '" + s + "'?"))
		return;
	
	window.location.replace('paginas.php?a=s&o='+pos+'&d=X');
}
function MostrarOuEsconder(pos, v)
{
	window.location.replace('paginas.php?a=s&o='+pos+'&d='+v);
}
function Voltar()
{
	window.location.replace('paginas.php?a=v');
}
function Ver(id)
{
	window.location.replace('index.php?pg=' + id);
}
function Nova()
{
	window.location.replace('paginas.php?a=n');
}

</script>
	
</head>

<body>

<div id='main' class="main">
<div id="barra">
	
<div class="barra_btn" onclick='Nova();'>Nova página</div>
<div class="barra_btn" onclick='Voltar();'>Voltar</div>
			
</div>
<?php


if ($erro)
	echo "<div id='erro'>$erro</div>";

	
Indice($aviso);
//ForaDeUso($aviso_fora_de_uso);

?>

</div>

<?php

if ($pagina_criada)
{
	echo "\n<script> location.href = '#$pagina_criada'; Renomear('$pagina_criada'); </script>";
}
?>

</body>
</html>