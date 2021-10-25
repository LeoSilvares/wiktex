<?php

include("Uteis.php");

class subsection extends Comando
{
	public	function __construct()
	{
		parent::__construct("\\subsection", 1, "", Comando::$ENCERRA_PARAGRAFO_ABERTO | Comando::$NAO_ABRE_NOVO_PARAGRAFO);
	}
	public	function Fazer($a,
		// argumentos
		$oa)
	{
		// argumentos opcionais
		return "<h2>".$a[0]."</h2>\n\n";
	}
}
class section extends Comando
{
	public	function __construct()
	{
		parent::__construct("\\section", 1, "", Comando::$ENCERRA_PARAGRAFO_ABERTO | Comando::$NAO_ABRE_NOVO_PARAGRAFO);
	}
	public	function Fazer($a,
		// argumentos
		$oa)
	{
		// argumentos opcionais
		return "<h1>".$a[0]."</h1>\n\n";
	}
}
class ref extends Comando
{
	public	function __construct()
	{
		parent::__construct("\\ref", 1, "");
	}
	public	function Fazer($a, // argumentos
						   $oa)
	{
		// argumentos opcionais
		$n = $a[0];
		$r = $this->processor->BuscarReferencia($n);
		if ($r != NULL)
		{
			return "<a href='#".$r->nome."'>" . strval($r->numero). "</a>";
		}
		return "???";
	}
}
class label extends Comando
{
	public	function __construct()
	{
		parent::__construct("\\label", 1, "");
	}
	public	function Fazer( $a, // argumentos
							$oa)
	{
		// argumentos opcionais
		$n = $a[0];
		
		$tx = "<span id='".$n."'></span>";
		
		if ($this->processor->ancora_em_modo_de_equacao)
		{
			//System.out.println("Antecipando: ". tx);
			$this->processor->ancoras_atuais .= $tx;
			$this->processor->equation_number++;
			$this->processor->AdicionarReferencia($n, $this->processor->equation_number);
			return "\\tag{" . strval($this->processor->equation_number) . "}";
		}
		else
		{
			$this->processor->AdicionarReferencia($n, $this->processor->ReferenciaAtual());
		}
		
		return $tx;
	}
}
class href extends Comando
{
	public	function __construct()
	{
		parent::__construct("\\href", 2, "");
	}
	public	function Fazer( $a, // argumentos
							$oa)
	{
		return "<a href='" . $a[0] . "'>" . $a[1] . "</a>";
	}
}
class includegraphics extends Comando
{
	public	function __construct()
	{
		parent::__construct("\\includegraphics", 1, "");
	}
	public	function Fazer(	$arg,// argumentos
							$oa)
	{
		global $local_padrao_media;
		
		if (isset($oa[0]))
			$args = LerArgumentosOpcionais($oa[0]);
		else
			$args = array();
		
		$style = "";
		
		foreach($args as $a)
		{
			if (isset($a[1]))
				$style = AdicionarALista($style, ";", $a[0] . ": " . $a[1]);
		}
		
		if ($style)
			$style = "style='" . $style . "'";
				
		return "<img src='$local_padrao_media" . $arg[0] . "' $style></img>";
	}
}
class geogebra extends Comando
{
	public	function __construct()
	{
		parent::__construct("\\geogebra", 1, "");
	}
	public	function Fazer(	$arg,// argumentos
							$oa)
	{
		$id = $arg[0];
		
		if (isset($oa[0]))
			$args = LerArgumentosOpcionais($oa[0]);
		else
			$args = array();
		
		$style = "";
		$opt = "";
		
		foreach($args as $a)
		{
			//echo "[" . $a[0];
			if (isset($a[1]))
			{
				//echo "='" . $a[1] . "'";
				
				if (substr($a[0], 0, 1) == "g")
					$opt = AdicionarALista($opt, "", substr($a[0],1) . "/" . $a[1] . "/");
				else 
					$style .= $a[0] . ":" . $a[1] . ";";
			}
			//echo "]";
		}
		
		return "<iframe scrolling=\"no\" src=\"https://www.geogebra.org/material/iframe/id/" . $id . "/" . $opt . "border/888888/rc/false/ai/false/sdz/false/smb/false/stb/false/stbh/true/ld/false/sri/false\" style=\"border: 1px solid #e4e4e4; border-radius: 4px;" . $style . "\" frameborder=\"0\"></iframe>";
	}
}
class Formatacao extends Comando
{
	public $tag_i = "";
	public $tag_f = "";
	public	function __construct($n, $_tag_i, $_tag_f)
	{
		parent::__construct($n, 1, "");
		$this->tag_i = $_tag_i;
		$this->tag_f = $_tag_f;
	}
	public	function Fazer($a,
		// argumentos
		$oa)
	{
		// argumentos opcionais
		return $this->tag_i.$a[0].$this->tag_f;
	}
}
class IgnorarComando extends Comando
{
	public	function __construct($n, $args = 0)
	{
		parent::__construct($n, $args, "");
	}
	public	function Fazer(	$a, // argumentos
							$oa)
	{
		// argumentos opcionais
		if (DEBUG) printf("%s<br>", "Ignorando comando ".$this->nome);
		return "";
	}
}
class Def extends Comando
{
	public	function __construct($n, $s)
	{
		parent::__construct($n, 0, $s);
	}
}
class RepetirComando extends Comando
{
	public	function __construct($n, $f)
	{
		parent::__construct($n, 0, $n, $f);
	}
}
class youtube extends Comando
{
	public	function __construct()
	{
		parent::__construct("\\youtube", 2, "", Comando::$ENCERRA_PARAGRAFO_ABERTO | Comando::$NAO_ABRE_NOVO_PARAGRAFO);
	}
	public	function Fazer(	$a, // argumentos
							$oa)
	{
		return "<div class='div_youtube' onclick=\"parent.YouTube('" . $a[0] . "');\">" . $a[1] . "</div>";
	}
}
class hspace extends Comando
{
	public	function __construct()
	{
		parent::__construct("\\hspace", 1, "", Comando::$NAO_ABRE_NOVO_PARAGRAFO);
	}
	public	function Fazer(	$a, // argumentos
							$oa)
	{
		//echo "[" . ($this->encerra_paragrafo_aberto?1:0) . "|" . ($this->abre_novo_paragrafo?1:0) . "]";
		$w = $a[0];
		//return "<div class='div_space' style='width: $w;'></div>";
		return "<span class='span_space' style='margin-left: $w;'></span>";
	}
}
class vspace extends Comando
{
	public	function __construct()
	{
		parent::__construct("\\vspace", 1, "", Comando::$NAO_ABRE_NOVO_PARAGRAFO);
	}
	public	function Fazer(	$a, // argumentos
							$oa)
	{
		$h = $a[0];
		return "<div class='div_space' style='height: $h;'></div>";
	}
}
class HTMLIterator extends Comando
{
	private $iteration_function = "";
	private $sintaxe_item = "";
	
	public	function __construct($nome, $iteration_function, $sintaxe_item)
	{
		parent::__construct($nome, 0, "");
		$this->iteration_function = $iteration_function;
		$this->sintaxe_item = $sintaxe_item;
	}
	public	function Fazer(	$a, // argumentos
							$oa)
	{
		$saida = "";
		
		for ($i = 0;;$i ++)
		{
			$ret = call_user_func($this->iteration_function, $i);
			
			if (!$ret) break;
			
			$saida .= str_replace("#2", $ret[1], str_replace("#1", $ret[0], $this->sintaxe_item));
		}
		
		return $saida;
	}	
}
class setcounter extends Comando
{
	public function __construct()
	{
		parent::__construct("\\setcounter", 2, "");
	}
	public	function Fazer(	$a, // argumentos
							$oa)
	{
		//Contador::Obter($a[0])->Definir($a[1]);
		return "<span style='counter-set: li " . $a[1] . "'></span>";		
	}
	
}

function IniciarComandosLatexPadrao(& $comandos)
{
	array_push($comandos, new geogebra());
	array_push($comandos, new youtube());
	array_push($comandos, new includegraphics());
	array_push($comandos, new section());
	array_push($comandos, new subsection());
	array_push($comandos, new label());
	array_push($comandos, new ref());
	array_push($comandos, new href());
	array_push($comandos, new vspace());
	array_push($comandos, new hspace());
	
	array_push($comandos, new Def("\\bigskip", "<p>&nbsp;</p>"));
	//array_push($comandos, new Def("\\backslash", "\\"));
	
	array_push($comandos, new setcounter());
	
	array_push($comandos, new IgnorarComando("\\noindent"));
	array_push($comandos, new IgnorarComando("\\newpage"));
	
	array_push($comandos, new RepetirComando("\\item", Comando::$NAO_ABRE_NOVO_PARAGRAFO));
	
	array_push($comandos, new Formatacao("\\textbf", "<b>", "</b>"));
	array_push($comandos, new Formatacao("\\textit", "<i>", "</i>"));
	array_push($comandos, new Formatacao("\\emph", "<em>", "</em>"));
	array_push($comandos, new Formatacao("\\underline", "<u>", "</u>"));
	array_push($comandos, new Formatacao("\\texttt", "<span style='font-family: monospace'>", "</span>"));
}
function _NewCommandIterator(& $comandos, $command, $iteration_function, $sintaxe, $tipo = 0) //0: LaTeX, 1: HTML
{
	if ($tipo == 1)
		array_push($comandos, new HTMLIterator($command, $iteration_function, $sintaxe));
}
	
?>