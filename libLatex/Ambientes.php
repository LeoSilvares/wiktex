<?php

class Teorema extends Ambiente
{
	public $contagem;
	public $classe;
	public $classe_titulo;
	public $nome;
	public $texto;
	public $numerar = true;
	
	private $referencia; // referência (valor do contador) deste teorema
	
	static public $valor_contador = array();
	public static $SEM_NUMERO = 1;
	
	public function __construct($nome, $texto, $contagem, $classe = false, $classe_titulo = false, $flags = 0)
	{
		parent::__construct($nome, 0, 0);
		
		$this->nome = $nome;
		$this->texto = $texto;
		$this->contagem = $contagem;
		$this->classe = $classe;
		$this->classe_titulo = $classe_titulo;
		
		if ($flags & Teorema::$SEM_NUMERO)
			$this->numerar = false;
		
		Teorema::$valor_contador[$nome] = 0;
	}
	public	function Fazer($conteudo, $args, $opt_args)
	{
		$classe = "";
		if ($this->classe) $classe = " " . $this->classe;
		
		$saida = "<div class='theorem$classe'>";
		
		if ($this->texto)
		{
			$contador = Teorema::$valor_contador[$this->nome];
			$classe = "";
			if ($this->classe_titulo) $classe = " " . $this->classe_titulo;
		
			$saida .= "<div class='theorem_title$classe'>" . $this->texto . " " . $contador . "</div>"; 
		}
		
		$saida .= $conteudo . "</div>";
		
		return $saida;
	}
	public function Iniciar($p, $args, $opt_args)
	{
		parent::Iniciar($p, $args, $opt_args);
		
		if ($this->numerar)
		{
			Teorema::$valor_contador[$this->nome] ++;
			
			$p->ReferenciaAtual(Teorema::$valor_contador[$this->nome]);
		}
	}
}

class itemize extends Ambiente
{
	public $p;
	
	public $tem_item_aberto = false; 
	public $abertura = "<li>";
	public $fechamento = "</li>";
	
	public	function __construct()//, $item_i, $item_f)
	{
		parent::__construct("itemize", 0);
	}
	public function Fazer($conteudo, $args, $opt_args)
	{
		if ($this->tem_item_aberto)
			$conteudo .= $this->fechamento;
		
		return "<ul>\n" . $conteudo . "\n</ul>";
	}
	public	function Iniciar($p, $args, $opt_args)
	{
		$this->p = $p;
		$this->p->identacao_itens++;
		
		$this->tem_item_aberto = false;
	}
	
	public function TratarToken($t, & $saida, & $tem_paragrafo_aberto)
	{
		if (strcmp($t,"\\item")==0)
		{
			if ($tem_paragrafo_aberto)
				$saida .= "</p>";
			
			$tem_paragrafo_aberto = false;
			
			if ($this->tem_item_aberto)
				$saida .= $this->fechamento;
			
			$saida .= $this->abertura;
			$this->tem_item_aberto = true;
			
			$this->p->tokenizer->Avancar();
			
			return true;
		}
		
		return false;
	}
}
class itemizeCols extends Itemize
{
	public $w = false;
	
	public	function __construct()//, $item_i, $item_f)
	{
		Ambiente::__construct("itemize-cols", 1);
		
		$this->abertura = "<div class='itemize-cols'><li>";
		$this->fechamento = "</li></div>";
	}
	public function Fazer($conteudo, $args, $opt_args)
	{
		if ($this->tem_item_aberto)
			$conteudo .= $this->fechamento;
		
		return "<style>div.itemize-cols {display:inline-block;width:" . $this->w . ";}</style><ul>\n" . $conteudo . "\n</ul>";
	}
	public	function Iniciar($p, $args, $opt_args)
	{
		$this->p = $p;
		$this->p->identacao_itens++;
		
		$this->w = $args[0];		
	}
}
class enumerate extends itemize
{
	public $p;
	private $contador = 0;
	
	private $classe = "";
	private $estilo = "";	
	private $tipo_numero = "1";
	
	private $var_contador;
	
	public static $customizados = 0; 
	
	public $abertura = "<li>";
	public $fechamento = "</li>";
	
	public static $enumeration_level = 0;
	
	public	function __construct()
	{
		Ambiente::__construct("enumerate", 0);
	}
	public function Fazer($conteudo, $args, $opt_args)
	{
		if ($this->tem_item_aberto)
			$conteudo .= "</li>";
		
		if ($this->classe)
			$conteudo = preg_replace('/<li>\s*<p>/', '<li>', $conteudo);
		
		/*
		if ($this->var_contador->valor != 0)
		{
			$this->classe = str_replace("custom ", "customn ", $this->classe);
			$this->classe .= " start=" . ($this->var_contador->valor+1);
		}
		*/
		
		enumerate::$enumeration_level --;
		
		return 	 $this->estilo . 
				 "<ol" . $this->classe . ">" .
				 $conteudo . "\n</ol>";
	}
	public function Iniciar($p, $args, $opt_args)
	{
		$this->p = $p;
		$this->p->identacao_itens++;
		
		enumerate::$enumeration_level ++;
		$nome_contador = "enum" . strtolower(Romano(enumerate::$enumeration_level));
		
		$this->var_contador = Contador::Obter($nome_contador);
		$this->var_contador->Zerar();
		
		$this->classe = "";
		$this->estilo = "";
		$this->contador = 0;
		$this->tipo_numero = "1";
	
		if (isset($opt_args[0])) // tipo da enumeração
		{
			enumerate::$customizados++;
			$cn = enumerate::$customizados;
			
			$c = $opt_args[0];
				
			$c = str_replace('(', ' "(" ', $c);
			$c = str_replace(')', ' ")" ', $c);
			$c = str_replace('.', ' "." ', $c);
			$c = str_replace(':', ' ":" ', $c);
			$c = str_replace('-', ' "-" ', $c);
			
			if (strstr($c, "i") !== false)
			{	
				$c = str_replace("i", "counter(li, lower-roman)", $c);
				$this->tipo_numero = "i";
			}
			else if (strstr($c, "I") !== false)
			{	
				$c = str_replace("I", "counter(li, upper-roman)", $c);
				$this->tipo_numero = "I";
			}
			else if (strstr($c, "a") !== false)
			{	
				$c = str_replace("a", "counter(li, lower-latin)", $c);
				$this->tipo_numero = "a";
			}
			else if (strstr($c, "A") !== false)
			{	
				$c = str_replace("A", "counter(li, upper-latin)", $c);
				$this->tipo_numero = "A";
			}
			else if (strstr($c, "1") !== false)
			{	
				$c = str_replace("1", "counter(li)", $c);
				$this->tipo_numero = "1";
			}
			
			$this->estilo = "<style>ol.custom$cn > li::before{content: $c;}</style>";			
			$this->classe = " class='custom custom$cn'";
		}
		
	}
	public function TratarToken($t, & $saida, & $tem_paragrafo_aberto)
	{
		if (parent::TratarToken($t, $saida, $tem_paragrafo_aberto))
		{
			//echo "Contador atual: " . $this->contador . "<br>"; 
			$this->contador ++;
			$this->p->ReferenciaAtual($this->FormatarContador());
			
			return true;
		}
		
		return false;
	}
	public function FormatarContador()
	{
		if ($this->tipo_numero == "1")
			return $this->contador;
		else if ($this->tipo_numero == "A")
			return Alfa($this->contador);
		else if ($this->tipo_numero == "a")
			return strtolower(Alfa($this->contador));
		else if ($this->tipo_numero == "I")
			return Romano($this->contador);
		else if ($this->tipo_numero == "i")
			return strtolower(Romano($this->contador));
		
		return $this->contador;
	}
}
class minipage extends Ambiente
{
	public $class;
	public	function __construct($n = 0, $class="")
	{
		$this->class = $class;
		parent::__construct("minipage", 1, Ambiente :: $NAO_ENCERRA_PARAGRAFO_ABERTO, "", "");
	}
	public	function Fazer($conteudo, $args, $opt_args)
	{
		$w = $args[0];
		return "<div class='minipage " . $this->class . "' style='width: $w'>$conteudo</div>";	
	}
}
class multicols extends Ambiente
{
	public	function __construct()
	{
		parent::__construct("multicols", 1);
	}
	public	function Fazer($conteudo, $args, $opt_args)
	{
		$c = $args[0];
		return "<div style='column-count: $c'>$conteudo</div>";	
	}
}
class Div extends Ambiente
{
	public	function __construct($n, $class)
	{
		parent::__construct($n, 0, 0, "<div class='$class'>", "</div>");
	}
}
class IgnorarAmbiente extends Ambiente
{
	public	function __construct($n, $a)
	{
		parent::__construct($n, $a, 0, "", "");
	}
}

class tabular extends Ambiente
{
	public $colunas = 0;
	public $style = array();
	
	public $borda_acima = 0;
	public $borda_acima_atual = 0;
	public $col_atual = -1;
	public $linha_aberta = false;
	
	public	function __construct()
	{
		parent::__construct("tabular", 1, Ambiente :: $NAO_TEM_PARAGRAFOS);
	}
	public function Fazer($conteudo, $args, $opt_args)
	{
		return "<table class='tabular'>" . $conteudo . "</table>";
	}
	public function Iniciar($p, $args, $opt_args)
	{
		//echo "[Iniciar]";
		
		$this->processor = $p;
		//$this->processor->identacao_itens++;
		
		$borda = array();
		$alinhamento = array();
	
		$cols = $args[0];
		$colunas = 0;
		$t = strlen($cols);
		
		for ($i=0; $i < $t; $i++)
		{
			$c = $cols[$i];
			
			if ($c == '|')
			{
				if (!isset($borda[$colunas]))
					$borda[$colunas] = 0;
				
				$borda[$colunas] ++;
			}
			else if ($c == 'l' || $c == 'c' || $c == 'r')
			{
				if ($c == 'l') $a = "left";
				else if ($c == 'r') $a = "right";
				else if ($c == 'c') $a = "center";
				
				$alinhamento[$colunas] = $a;
				$borda[$colunas] = 0;
				$colunas ++;
			}
		}
		
		$this->colunas = $colunas;
		
		for ($i=0; $i<$colunas; $i++)
		{
			$this->style[$i] = "text-align: " . $alinhamento[$i] . 
								"; border-left-width: " . (2*$borda[$i]);
		}
		if (isset($borda[$colunas]))
			$this->style[$colunas - 1] .= "; border-right-width: " . $borda[$colunas];
		
		//for ($i=0; $i<$colunas; $i++)
		//	echo "(" .$this->style[$i] . ")";
	}
	private function IniciarLinha()
	{
		$this->col_atual = -1;
		$this->borda_acima_atual = 2*$this->borda_acima;
		$this->borda_acima = 0;
		return "<tr style='border-top-width: " . $this->borda_acima_atual . "'>" . $this->IniciarColuna();
	}
	private function IniciarColuna()
	{
		$this->col_atual ++;
		return "<td style='" . $this->style[$this->col_atual] ."; border-top-width: " . $this->borda_acima_atual . "'>";
	}
	public function TratarToken($t, & $saida, & $tem_paragrafo_aberto)
	{
		$s = "";
		if (strcmp($t,"\\\\")==0)
		{
			if ($this->col_atual >= 0)
			{
				$this->col_atual = -1;
				$s = "</td></tr>";
			}
		}
		else if (strcmp(trim($t),"&")==0)
		{
			if ($this->col_atual < $this->colunas - 1)
			{
				if ($this->col_atual != -1)
					$s .= "</td>";
				else
					$s .= $this->IniciarLinha();
			
				$s .= $this->IniciarColuna();
			}
		}
		else if (strcmp($t,"\\hline")==0)
		{
			$this->borda_acima ++;
		}
		else
		{
			if (trim($t) && strcmp($t, "\\end") != 0)
			{
				if ($this->col_atual == -1)
					$saida .= $this->IniciarLinha();
			}
			
			return false;
		}
		
		$this->processor->tokenizer->Avancar();
		
		$saida .= $s;
		
		return true;
	}
	
}
class ExpTeorema extends Teorema
{
	public function __construct($nome, $texto, $contagem, $classe = false, $classe_titulo = false, $flags = 0)
	{
		parent::__construct($nome, $texto, $contagem, $classe, $classe_titulo, $flags);
	}
	public	function Fazer($conteudo, $args, $opt_args)
	{
		Teorema::$valor_contador[$this->nome] ++;
		$contador = "";
		
		if ($this->numerar)
			$contador = Teorema::$valor_contador[$this->nome];
		
		$classe = "";
		if ($this->classe) $classe = " " . $this->classe;
		
		$id = $this->nome . "_exp_" . Teorema::$valor_contador[$this->nome];
		$saida = "<div class='theorem theorem_exp_hidden$classe' id='$id'>";
		
		if ($this->texto)
		{
			$classe = "";
			if ($this->classe_titulo) $classe = " " . $this->classe_titulo;
		
			$saida .= "<div class='theorem_title$classe'  onclick='document.getElementById(\"$id\").classList.toggle(\"theorem_exp_hidden\");document.getElementById(\"button_$id\").classList.toggle(\"btn_exp_hidden\");'>" . $this->texto . " " . $contador . 
						"<div class='btn_exp btn_exp_hidden' id='button_$id'></div>".
						"</div>"; 
		}
		
		$saida .= $conteudo . "</div>";
		
		return $saida;
	}
}
class verbatim extends Ambiente
{
	public	function __construct()
	{
		parent::__construct("verbatim", 0, Ambiente :: $NAO_TEM_PARAGRAFOS);
	}
	public function Fazer($conteudo, $args, $opt_args)
	{
		while (ord($conteudo[0]) == 13 || ord($conteudo[0] == 10) || substr($conteudo, 0, 1) == "\n")
			$conteudo = substr($conteudo, 1);
		
		//$conteudo = str_replace("\n", "<br/>", $conteudo);
		//$conteudo = str_replace(" ", "&nbsp;", $conteudo);
		$conteudo = str_replace("\\", "&bsol;", $conteudo);
		//return "<div class='div_verbatim'>$conteudo</div>";
		
		return "<div class='div_verbatim'><pre>$conteudo</pre></div>";
	}	
}
class html extends Ambiente
{
	public	function __construct()
	{
		parent::__construct("html", 0);
	}
	public function Fazer($conteudo, $args, $opt_args)
	{
		return html_entity_decode($conteudo);
	}	
}
class comment extends Ambiente
{
	public	function __construct()
	{
		parent::__construct("comment", 0);
	}
	public function Fazer($conteudo, $args, $opt_args)
	{
		return "";
	}	
}


function IniciarAmbientesLatexPadrao(& $ambientes)
{
	array_push($ambientes, new itemize());
	array_push($ambientes, new enumerate());
	array_push($ambientes, new itemizeCols());
	
	array_push($ambientes, new tabular());
	
	array_push($ambientes, new verbatim());
	array_push($ambientes, new html());
	array_push($ambientes, new comment());
	
	array_push($ambientes, new Ambiente("equation", 0, Ambiente::$TEM_QUEBRA_DE_LINHAS | Ambiente::$ANCORA_EM_MODO_DE_EQUACAO | Ambiente::$NAO_TEM_PARAGRAFOS | Ambiente::$MODO_MATEMATICO));
	array_push($ambientes, new Ambiente("eqnarray", 0, Ambiente::$TEM_QUEBRA_DE_LINHAS | Ambiente::$ANCORA_EM_MODO_DE_EQUACAO | Ambiente::$NAO_TEM_PARAGRAFOS));
	array_push($ambientes, new Ambiente("eqnarray*", 0, Ambiente::$TEM_QUEBRA_DE_LINHAS | Ambiente::$ANCORA_EM_MODO_DE_EQUACAO | Ambiente::$NAO_TEM_PARAGRAFOS));
	array_push($ambientes, new Ambiente("array", 0, Ambiente::$TEM_QUEBRA_DE_LINHAS | Ambiente::$ANCORA_EM_MODO_DE_EQUACAO | Ambiente::$NAO_TEM_PARAGRAFOS));
	
	array_push($ambientes, new multicols());
	array_push($ambientes, new minipage());
	
	array_push($ambientes, new Div("figure", "div_center"));
	array_push($ambientes, new Div("center", "div_center"));
	array_push($ambientes, new Div("box", "div_round div_box"));
	array_push($ambientes, new Div("tip", "div_round div_box div_tip"));
	array_push($ambientes, new Div("help", "div_round div_box div_help"));
	array_push($ambientes, new Div("alert", "div_round div_box div_alert"));
	array_push($ambientes, new Div("important", "div_round div_box div_important"));
	array_push($ambientes, new Div("info", "div_round div_box div_info"));
	array_push($ambientes, new Div("scratch", "div_round div_box div_scratch"));
	
	array_push($ambientes, new Teorema("activity", "Atividade", "", "example", "example_title"));
	array_push($ambientes, new Teorema("example", "Exemplo", "", "example", "example_title"));
	array_push($ambientes, new Teorema("exercise", "Exercício", "", "exercise", "exercise_title"));
	array_push($ambientes, new Teorema("theorem", "Teorema", "", "theorem", "theorem_title"));
	array_push($ambientes, new Teorema("proposition", "Proposição", "", "proposition", "proposition_title"));
	array_push($ambientes, new Teorema("definition", "Definição", "", "definition", "definition_title"));
	array_push($ambientes, new ExpTeorema("solution", "Solução", "", "solution", "solution_title", Teorema::$SEM_NUMERO));
	array_push($ambientes, new ExpTeorema("proof", "Demonstração", "", "proof", "proof_title", Teorema::$SEM_NUMERO));
}


function Romano($n) 
{
    $m = array('M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1);
    $ret = "";
    while ($n > 0) {
        foreach ($m as $r => $i) {
            if($n >= $i) {
                $n -= $i;
                $ret .= $r;
                break;
            }
        }
    }
    return $ret;
}
function Alfa($n) 
{
	$ret = "";
    
	do
	{
		$n --;
		$r = $n % 26;
		$ret = $ret . chr(ord('A') + $r);
		$n = ($n - $r)/26;
		
	} while ($n > 0);
	
	return $ret;
}
?>