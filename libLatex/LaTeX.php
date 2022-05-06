<?php

include('Comandos.php');
include('Ambientes.php');

define("DEBUG", false);

$local_padrao_media = "media/";

class Ambiente
{
	public static $TEM_QUEBRA_DE_LINHAS = 1;
	public static $ANCORA_EM_MODO_DE_EQUACAO = 2;
	public static $NAO_ENCERRA_PARAGRAFO_ABERTO = 4;
	public static $NAO_TEM_PARAGRAFOS = 8;
	public static $MODO_MATEMATICO = 16;
	public $nome;
	public $args;
	public $inicio;
	public $fim;
	public $tem_quebra_de_linhas = false; 	 	// Se os // devem ser considerados
	public $ancora_em_modo_de_equacao = false; 	// Se as ancoras html (#ancora) serão antecipadas para antes do objeto
	public $encerra_paragrafo_aberto = true;
	public $tem_paragrafos = true;
	public $modo_matematico = false;
	public $comandos = array();
	public $processor = NULL;
	
	public function __construct($n, $args, $flags = 0, $_inicio = null, $_fim = null)
	{
		$this->nome = $n;
		$this->args = $args;
		
		$this->inicio = ($_inicio!==null)? $_inicio : "\\begin{". $n . "}";
		$this->fim = ($_fim!==null)? $_fim : "\\end{". $n . "}";
		
		if (($flags & Ambiente::$TEM_QUEBRA_DE_LINHAS) > 0)
		{
			$this->tem_quebra_de_linhas = true;
		}
		if (($flags & Ambiente::$ANCORA_EM_MODO_DE_EQUACAO) > 0)
		{
			$this->ancora_em_modo_de_equacao = true;
		}
		if (($flags & Ambiente::$NAO_ENCERRA_PARAGRAFO_ABERTO) > 0)
		{
			$this->encerra_paragrafo_aberto = false;
		}
		if (($flags & Ambiente::$NAO_TEM_PARAGRAFOS) > 0)
		{
			$this->tem_paragrafos = false;
		}
		if (($flags & Ambiente::$MODO_MATEMATICO) > 0)
		{
			$this->modo_matematico = true;
		}
	}
	public function NovoComando($c)
	{
		array_push($this->comandos, $c);
	}
	public function Fazer($conteudo, $args, $opt_args)
	{
		return $this->inicio . $conteudo . $this->fim;
	}
	public static function FazerPadrao($nome, $conteudo)
	{
		$ret = "\\begin{".$nome.
		"}\n".$conteudo.
		"\n\\end{".$nome.
		"}\n";
		return $ret;
	}
	public function Iniciar($p, $args, $opt_args)
	{
		$this->processor = $p;
	}
	public function TratarToken($t, & $saida, & $tem_paragrafo_aberto)
	{
		return false;
	}
}
class Comando
{
	public $nome;
	public $args;
	public $sintaxe;
	public $processor;
	public $encerra_paragrafo_aberto = false;
	public $abre_novo_paragrafo = true;
	
	public static $ENCERRA_PARAGRAFO_ABERTO = 4;
	public static $NAO_ABRE_NOVO_PARAGRAFO = 8;
	
	
	public	function __construct($n, $a, $s, $flags = 0)
	{
		$this->nome = $n;
		$this->args = $a;
		$this->sintaxe = $s;
		
		if (($flags & Comando::$ENCERRA_PARAGRAFO_ABERTO) > 0)
		{
			$this->encerra_paragrafo_aberto = true;
		}
		if (($flags & Comando::$NAO_ABRE_NOVO_PARAGRAFO) > 0)
		{
			$this->abre_novo_paragrafo = false;
		}
	}
	public	function Iniciar($p)
	{
		$this->processor = $p;
	}
	public function Fazer(	$a, // argumentos
							$oa)
	{
		// argumentos opcionais
		if (DEBUG) 
		{
			printf("%s<br>", "Fazendo comando ".$this->nome);
			for ($i = 0; $i < $this->args; $i++)
			{
				printf("%s<br>", "  arg ".strval(($i + 1)).
					": ".$a[$i]);
			}
		}
		
		$s = $this->sintaxe;
		for ($i = 0; $i < $this->args; $i++)
		{
			$s = str_replace("#".strval(($i + 1)), $a[$i], $s);
		}
		return $s;
	}
	public	function FazerPadrao($nome)
	{
		// argumentos opcionais
		return $nome;
	}
}

class Referencia
{
	public $nome;
	public $numero;
	public	function __construct($n, $num)
	{
		$this->nome = $n;
		$this->numero = $num;
	}
}
class Processor
{
	public $pos = -1;
	public $tokenizer;
	public $comandos;
	public $ambientes;
	public $encerrar = false;
	//public $documento_iniciado = true;//false;
	public $ancora_em_modo_de_equacao = false;
	public $ancoras_atuais = "";
	public $fim_documento = array("\\end", "{", "document", "}");
	public $ignorar_documento_ate = NULL;
	public $identacao_itens = 0;
	public $equation_number = 0;
	public $referencias = array();
	public $modo_matematico = false;
	public $label_atual = false;
	
	public $token_diferenciado =  // tokens que são diferentes em modo matemático e em texto
									array(
											"\\{"=>array("\\{","{"),
											"\\}"=>array("\\}","}"),
											"\\$"=>array("\\$","\($\)"),
											"\\%"=>array("\\%","%"),
											"\\backslash"=>array("\\backslash","\\")
										);
	
	public	function __construct($_tokenizer)
	{
		$this->tokenizer = $_tokenizer;
	}

	function LerDef()
	{
		$this->tokenizer->Avancar();
		$nome = $this->tokenizer->LerProximo();
		//$this->Saida("Def: \'".$nome. "\'");
		// Pulando os parametros (TODO: no futuro, considerar)
		while ((strcmp($this->tokenizer->Proximo(), "#") == 0))
		{
			$this->tokenizer->Avancar(3);
		}
		$sintaxe = $this->LerArgumento();
		//$this->Saida("  sintaxe: \'".$sintaxe. "\'");
		array_push($this->comandos, new Def($nome, $sintaxe));
	}

	function LerNewCommand()
	{
		try{
			
			$this->tokenizer->Avancar();
			$nome = $this->LerNome();
			$args = 0;
			//$this->Saida("NewCommand: \'".$nome. "\'");
			// Pulando os parametros (TODO: no futuro, considerar)
			if ((strcmp($this->tokenizer->Proximo(), "[") == 0))
			{
				$args = $this->LerArgumentoNumero();
			}
			//$this->Saida("  args: ".strval($args));
			$sintaxe = $this->LerArgumento();
			//$this->Saida("  sintaxe: \'".$sintaxe. "\'");
			array_push($this->comandos, new Comando($nome, $args, $sintaxe));
		} 
		catch (Exception $e)
		{
			throw new Exception("Erro lendo \\newcommand: " . $e->getMessage());
		}
	}

	function LerNewTheo()
	{
		$this->tokenizer->Avancar();
		$nome = $this->LerNome();
		$texto = $this->LerArgumento();
		$contagem = "";
		if ((strcmp($this->tokenizer->Proximo(), "[") == 0))
		{
			$contagem = $this->LerArgumento();
		}
		array_push($this->ambientes, new Teorema($nome, $texto, $contagem));
	}

	function LerNewEnv()
	{
		throw new Exception('\\newenvironment ainda não implementado');
	}
	function LerArgumentosOpcionais()
	{
		$opt_args = array();
		
		while (strcmp($this->tokenizer->Proximo(), "[") == 0)
		{
			$a = $this->LerArgumento();
			array_push($opt_args, $a);
		}
		
		return $opt_args;
	}
	function FazerEnv(& $encerra_paragrafo_aberto)
	{
		$saida = "";
		
		$this->tokenizer->Avancar();
		$tipo = $this->LerArgumento();
		$env = $this->BuscarAmbiente($tipo);
		$tem_quebra_de_linhas = false;
		$encerra_paragrafo_aberto = true;
		$ult_ancora_em_modo_de_equacao = $this->ancora_em_modo_de_equacao;
		$tem_paragrafos = true;
		$modo_matematico = null;
		
		$num_args = 0;
		
		if ($env != NULL)
		{
			//$env->Iniciar($this);
			$tem_quebra_de_linhas = $env->tem_quebra_de_linhas;
			$this->ancora_em_modo_de_equacao |= $env->ancora_em_modo_de_equacao;
			
			$num_args = $env->args;
			$encerra_paragrafo_aberto = $env->encerra_paragrafo_aberto;
			$tem_paragrafos = $env->tem_paragrafos;
			$modo_matematico = $env->modo_matematico;
		}
		else
		{
			$this->Saida("  '$tipo' desconhecido");
			$modo_matematico = $this->modo_matematico;
		}
		
		$args = NULL;
		if ($num_args > 0)
		{
			//$this->Saida("Comando ".$nome. " tem ".strval($c->args)." argumentos");
			$args = array();
			for ($i = 0; $i < $num_args; $i++)
			{
				$a = $this->LerArgumento();
				array_push($args, $a);
			}
		}
		
		$opt_args = $this->LerArgumentosOpcionais();
		
		if ($env != NULL)
		{
			$env->Iniciar($this, $args, $opt_args);
		}
		/*
		if ((strcmp($tipo, "document") == 0))
		{
			$this->documento_iniciado = true;
			if ($this->ignorar_documento_ate != NULL)
			{
				$this->tokenizer->AvancarAte($this->ignorar_documento_ate);
			}
		}
		*/
		
		$conteudo = $this->LerEscopo($env, array("\\end", "{", $tipo, "}"), $tem_paragrafos, $tem_quebra_de_linhas, $modo_matematico);
		if ((strcmp($tipo, "document") == 0))
		{
			$this->encerrar = true;
		}
		if ($env == NULL)
		{
			return Ambiente::FazerPadrao($tipo, $conteudo);
		}
		
		if ($this->ancora_em_modo_de_equacao && !$ult_ancora_em_modo_de_equacao)
		{
			//$this->Saida("Ancoras: ".$ancoras);
			$saida .= $this->ancoras_atuais;
			$this->ancoras_atuais = "";
		}
		$this->ancora_em_modo_de_equacao = $ult_ancora_em_modo_de_equacao;
		
		return $saida . $env->Fazer($conteudo, $args, $opt_args);
	}

	function FazerComando($env, & $encerra_paragrafo_aberto, & $abre_novo_paragrafo)
	{
		$nome = $this->tokenizer->Proximo();
		//$this->Saida("Comando: \'".$nome. "\'");
		$this->tokenizer->Avancar();
		$c = $this->BuscarComando($nome, $env);
		
		if (strcmp($this->tokenizer->Proximo(), "*") == 0)
			$this->tokenizer->Avancar();
				
		$encerra_paragrafo_aberto = false;
		$abre_novo_paragrafo = true;
		
		if ($c == NULL)
		{
			$this->Saida("  desconhecido");
			return $nome;
		}
		else
		{
			$encerra_paragrafo_aberto = $c->encerra_paragrafo_aberto;
			$abre_novo_paragrafo = $c->abre_novo_paragrafo;
		
			$c->Iniciar($this);
		}
		$opt_args = $this->LerArgumentosOpcionais();
		
		$args = NULL;
		if ($c->args > 0)
		{
			//$this->Saida("Comando ".$nome. " tem ".strval($c->args)." argumentos");
			$args = array();
			for ($i = 0; $i < $c->args; $i++)
			{
				$a = $this->LerArgumento();
				//$this->Saida("  arg: \'".$a."\'");
				array_push($args, $a);
			}
		}
		return $c->Fazer($args, (count($opt_args) > 0) ? $opt_args : NULL);
	}
	
	private $token = "";
	
	public function AbreModoMatematico($tx, & $fechamento, & $inline)
	{
		if ($this->token == "\\[")
		{
			$fechamento = "\\]";
			$inline = false;
			return true;
		}
		else if ($this->token == "\\(")
		{
			$fechamento = "\\)";
			$inline = true;
			return true;
		}
		else if ($this->token == "$$")
		{
			$fechamento = "$$";
			$inline = false;
			return true;
		}
		else if ($this->token == "$")
		{
			$fechamento = "$";
			$inline = true;
			return true;
		}
		
		return false;
	}
	public function LerEscopo($env, $fim, $paragrafos = false, $tem_quebra_de_linhas = false, $modo_matematico = null)
	{
		$modo_matematico_previo = $this->modo_matematico;
		
		if ($modo_matematico !== null)
			$this->modo_matematico = $modo_matematico;
				
		$saida = "";
		$this->EntrouEscopo();
		$tem_paragrafo_aberto = false;
		
		if ($this->modo_matematico == true)
			$paragrafos = false;
		
		while (true)
		{
			//if (DEBUG) echo "[[$saida]]";
			//ult_token = token;
			$this->token = $this->tokenizer->Proximo();
			$this->Saida("[".$this->token.";" . ord($this->token[0]). ";" . strlen($this->token) . "]" );
			
			//echo "[" . $this->token . "]";
			
			$fim_matematica = "";
			$inline = false;
			
			$env_tratou = false;
			if ($env)
				$env_tratou = $env->TratarToken($this->token, $saida, $tem_paragrafo_aberto);
			
			if ($this->token == NULL)
			{
				if ($tem_paragrafo_aberto) 
					$saida .= "</p>";
				
				break;
			}
			else if ($env_tratou)
			{
				
			}
			else if ($this->tokenizer->Proximos($fim))
			{
				$this->tokenizer->Avancar(count($fim));
				//Saida("Saiu escopo. saida = '". saida ."'");
				$this->SaiuEscopo();
				
				if ($tem_paragrafo_aberto) 
					$saida .= "</p>";
				
				break;
			}
			else if ($this->tokenizer->Proximos($this->fim_documento))
			{
				$this->tokenizer->Avancar(count($this->fim_documento));
				$this->SaiuEscopo();
				$this->encerrar = true;
				
				break;
			}
			else if ((strcmp($this->token, "\\def") == 0))
			{
				$this->LerDef();
			}
			else if ((strcmp($this->token, "\\newcommand") == 0) || (strcmp($this->token, "\\renewcommand") == 0))
			{
				$this->LerNewCommand();
			}
			else if ((strcmp($this->token, "\\newenvironment") == 0))
			{
				$this->LerNewEnv();
			}
			else if ((strcmp($this->token, "\\newtheorem") == 0))
			{
				$this->LerNewTheo();
			}
			else if ((strcmp($this->token, "\\begin") == 0))
			{
				$fechar_paragrafo = false;
				
				$texto = $this->FazerEnv($fechar_paragrafo);
				
				if (!$this->modo_matematico)
				{
					if ($fechar_paragrafo && $tem_paragrafo_aberto)
					{
						$saida .= "</p>";	
						$tem_paragrafo_aberto = false;
					}
					else if (!$fechar_paragrafo && !$tem_paragrafo_aberto)
					{
						$saida .= "<p>";	
						$tem_paragrafo_aberto = true;
					}
				}
				
				$saida .= $texto; 
			}
			else if ((strcmp($this->token, "{") == 0))
			{
				$this->tokenizer->Avancar();
				$s = $this->LerEscopo(NULL, array("}"), $paragrafos, $this->modo_matematico);
				
				//if ($this->documento_iniciado || !$primeiro)
				//{
					$saida .= "{".$s."}";
				//}
			}
			else if (strcmp($this->token, "\\\\") == 0 || strcmp($this->token, "\\linebreak") == 0)
			{
				$this->tokenizer->Avancar();
				
				//if ($this->documento_iniciado || !$primeiro)
				//{	
					if ($tem_quebra_de_linhas || $this->modo_matematico)
					{
						$saida .= "\\\\";
					}
					//else if ($paragrafos && $tem_paragrafo_aberto)
					//{
					//	$saida .= "</p>";
					//	$tem_paragrafo_aberto = false;
					//}
					else if ($paragrafos)
					{
						$saida .= "<br>";
					}
				//}
			}
			else if (ord($this->token[0]) == 10)
			{
				$this->tokenizer->Avancar();
				
				//if ($this->documento_iniciado || $paragrafos)
				if ($paragrafos)
				{
					if(strlen($this->token) > 1)
					{
						if ($paragrafos && $tem_paragrafo_aberto)
						{
							$saida .= "</p>";
							$tem_paragrafo_aberto = false;
						}
					}
					else
					{
						$saida .= " ";
					}
				}
			}
			else if ($this->AbreModoMatematico($this->token, $fim_matematica, $inline))
			{
				if ($paragrafos)
				{
					if ($inline && !$tem_paragrafo_aberto)
					{
						$saida .= "<p>";
						$tem_paragrafo_aberto = true;
					}
					else if (!$inline && $tem_paragrafo_aberto)
					{
						$saida .= "</p>";
						$tem_paragrafo_aberto = false;
					}
				}
				
				$saida .= $this->token;
				$this->tokenizer->Avancar();
				$saida .= $this->LerEscopo(NULL, array($fim_matematica), false, true, true) . $fim_matematica;
			}
			else if ($this->token[0] == '\\')
			{
				$fechar_paragrafo = false;
				$abrir_paragrafo = true;
				
				if (isset($this->token_diferenciado[$this->token]))
				{
					$v = $this->token_diferenciado[$this->token];
					
					if ($this->modo_matematico) 
					{
						$texto = $v[0];
					}
					else 
					{
						$texto = $v[1];
					}
					
					$this->tokenizer->Avancar();
				}
				else
				{
					$texto = $this->FazerComando($env, $fechar_paragrafo, $abrir_paragrafo);
					
					//if ($this->documento_iniciado || $paragrafos)
					if ($paragrafos)
					{
						if ($fechar_paragrafo && $tem_paragrafo_aberto)
						{
							$saida .= "</p>";	
							$tem_paragrafo_aberto = false;
						}
						else if (!$tem_paragrafo_aberto && $abrir_paragrafo)
						{
							$saida .= "<p>";
							$tem_paragrafo_aberto = true;
						}
					}
				}
				
				$saida .= $texto; 
			}
			else
			{
				//if ($this->documento_iniciado || !$primeiro)
				//{
					//echo "[" . $this->token . ". paragrafos " . $paragrafos . "]";
						
					if (!$tem_paragrafo_aberto && $paragrafos)
					{
						$saida .= "<p>";
						$tem_paragrafo_aberto = true;
					}
					
					$saida .= htmlentities($this->token);
				//}
				
				$this->tokenizer->Avancar();
			}
			if ($this->encerrar)
			{
				break;
			}
		}
		
		$this->modo_matematico = $modo_matematico_previo;
		
		return $saida;
	}
	public	function QuebraDeLinhaRecente($s)
	{
		$t = strlen($s);
		if ($t == 0)
		{
			return true;
		}
		return ($s[$t - 1] == '\n');
	}
	public	function NovaLinha($s)
	{
		$saida = "";
		
		/*
		if ($this->tem_paragrafo_aberto)
		{
			$saida .= "<br>";
		}
		else
		{
			$saida .= "<p>";
			$this->tem_paragrafo_aberto = true;
		}
		*/
		
		$t = -1;
		for ($i = 0; $i < strlen($s); $i++)
		{
			$c = $s[$i];
			if ($c != ' ' && $c != '\t')
			{
				$t = $i;
				break;
			}
		}
		
		if ($t != -1)
			$saida .= substr($s, $t);
		
		return $saida;
	}
	public	function Processar($t, $_comandos, $_ambientes)
	{
		$this->comandos = $_comandos;
		$this->ambientes = $_ambientes;
		
		try{
			return $this->LerEscopo(NULL, NULL, true, false, false);
		} 
		catch (Exception $e)
		{
			echo "<div style='color: red'>" . $e->getMessage() . "</div>";
		}
	}
	public	function LerArgumento()
	{
		//Saida("LerArgumento()");
		$s = $this->tokenizer->LerProximo();
		$fim;
		if ((strcmp($s, "[") == 0))
		{
			return $this->LerEscopo(NULL, array("]"), false, $this->modo_matematico);
		}
		else if ((strcmp($s, "{") == 0))
		{
			return $this->LerEscopo(NULL, array("}"), false, $this->modo_matematico);
		}
		else
		{
			return $s;
		}
	}
	public	function LerNome()
	{
		if (!(strcmp($this->tokenizer->LerProximo(), "{") == 0))
		{
			throw new Exception('Erro lendo nome');
		}
		$s = $this->tokenizer->LerProximo();
		if (!(strcmp($this->tokenizer->LerProximo(), "}") == 0))
		{
			throw new Exception('Erro lendo nome');
		}
		return $s;
	}
	public	function LerArgumentoNumero()
	{
		$t = $this->tokenizer->LerProximo();
		if (!(strcmp($t, "{") == 0) && !(strcmp($t, "[") == 0))
		{
			throw new Exception('Erro lendo número');
		}
		$s = $this->tokenizer->LerProximo();
		$t = $this->tokenizer->LerProximo();
		if (!(strcmp($t, "}") == 0) && !(strcmp($t, "]") == 0))
		{
			throw new Exception('Erro lendo número');
		}
		return intval($s);
	}
	private function BuscarComando($c, $a)
	{
		if ($a != NULL)
		{
			for ($i = 0; $i < count($a->comandos); $i++)
			{
				if ((strcmp($a->comandos[$i]->nome, $c) == 0))
				{
					return $a->comandos[$i];
				}
			}
		}
		for ($i = 0; $i < count($this->comandos); $i++)
		{
			if ((strcmp($this->comandos[$i]->nome, $c) == 0))
			{
				return $this->comandos[$i];
			}
		}
		return NULL;
	}
	private function BuscarAmbiente($a)
	{
		for ($i = 0; $i < count($this->ambientes); $i++)
		{
			if ((strcmp($a, $this->ambientes[$i]->nome) == 0))
			{
				return $this->ambientes[$i];
			}
		}
		return NULL;
	}
	public $nivel = 0;
	private function Saida($s)
	{
		if (DEBUG) 
		{
			for ($i = 0; $i < $this->nivel; $i++)
			{
				printf("&nbsp;&nbsp;");
			}
			printf("%s<br/>\n", $s);
		}
	}
	private function EntrouEscopo()
	{
		$this->nivel++;
	}
	private function SaiuEscopo()
	{
		$this->nivel--;
	}
	public	function BuscarReferencia($nome)
	{
		for ($i = 0; $i < count($this->referencias); $i++)
		{
			if ((strcmp($nome, $this->referencias[$i]->nome) == 0))
			{
				return $this->referencias[$i];
			}
		}
		return NULL;
	}
	public function AdicionarReferencia($nome, $num)
	{
		$r = $this->BuscarReferencia($nome);
		if ($r != NULL)
		{
			$r->numero = $num;
			return;
		}
		array_push($this->referencias, new Referencia($nome, $num));
	}
	public function ReferenciaAtual($ref = false)
	{
		if ($ref)
		{
			$this->label_atual = $ref;
		}
		
		return $this->label_atual;
	}
}

class Tokenizer
{
	public $s;
	public $pos = 0;
	public $tk = array();
	public $tam = 0;
	public $ultimo_foi_quebra = false;
	public $ultimo_foi_espaco= false;
	
	public $verbatim = false;
	public $fim_verbatim = false;
	
	public	function __construct($s)
	{
		str_replace("\r\n", "\n", $s);
		$this->s = $s;
		$this->tam = strlen($s);
		
		$this->AtualizarVerbatim();
	}
	public function AtualizarVerbatim()
	{
		$this->verbatim = strpos($this->s, "\\begin{verbatim}", $this->pos);
		
		if ($this->verbatim === false)
		{
			$this->verbatim = -1;
			return;
		}

		$this->verbatim += 16;
		
		$this->fim_verbatim = strpos($this->s, "\\end{verbatim}", $this->verbatim);
		
		if ($this->fim_verbatim === false)
			$this->verbatim = false;
	}
	public function EhComandoSimbolo($c)
	{
		return (strpos("\\,.;:%$~!{}()[]", $c) !== false);
	}
	public function EhLimitador($c)
	{
		return (strpos("{}()[]", $c) !== false);
	}
	public	function LerToken()
	{
		$lendo = "";
		$comando_interno = "";
		$tipo_leitura = 0;	//0: texto, 1: \comando, 2: $, 3: {}, 4: %, 5: quebra

		$ultimo_foi_quebra = $this->ultimo_foi_quebra;
		$this->ultimo_foi_quebra = false;
		$ultimo_foi_espaco = false;
		
		for (; $this->pos < $this->tam; $this->pos++)
		{	
			if ($this->pos == $this->verbatim)
			{
				$i = $this->verbatim;
				$t = $this->fim_verbatim - $this->verbatim;
				$this->pos = $this->fim_verbatim;
				
				$this->AtualizarVerbatim();
				
				return substr($this->s, $i, $t);		
			}
			
			$espaco = false;
			$c = $this->s[$this->pos];
			
			if (ord($c) == 13)
			{
				
			}
			else if ($tipo_leitura == 0)
			{
				// lendo inicio ou texto normal
				if ($c == '\\')
				{
					if ($lendo != "")
					{
						return $lendo;
					}
					$lendo = "\\";
					$tipo_leitura = 1;
				}
				else if ($c == '$')
				{
					if ($lendo != "")
					{
						return $lendo;
					}
					$lendo = "$";
					$tipo_leitura = 2;
				}
				else if ($this->EhLimitador($c))
				{
					if ($lendo != "")
					{
						return $lendo;
					}
					$this->pos++;
					return "".strval($c);
				}
				else if (ord($c) == 10)
				{
					//echo "!lido quebra!";
					if ($lendo != "")
					{
						return $lendo;
					}
					
					$lendo = "\n";
					$tipo_leitura = 5;
				}
				else if ($c == '%')
				{
					if ($lendo != "")
					{
						return $lendo;
					}
					$tipo_leitura = 4;
				}
				else if ($c == ' ' || $c == '\t')
				{
					if (($lendo == "" && !$ultimo_foi_quebra)
						||
						($lendo != "" && !$ultimo_foi_espaco))
					{
						$lendo .= $c;
					}
					
					$espaco = true;
				}
				else if ($c == '&')
				{
					if ($lendo != "")
					{
						return $lendo;
					}
					$this->pos++;
					return "&";
				}
				else
				{
					$lendo .= $c;
				}
			}
			else if ($tipo_leitura == 1)
			{
				// \comando
				if (($c >= 'a' && $c <= 'z') || ($c >= 'A' && $c <= 'Z'))
				{
					$lendo .= $c;
				}
				else if (strlen($lendo) == 1 && $this->EhComandoSimbolo($c))
				{
					$this->pos++;
					return $lendo . $c;
				}
				else
				{
					return $lendo;
				}
			}
			else if ($tipo_leitura == 2)
			{
				// $
				if ($c == '$')
				{
					$this->pos++;
					return "$$";
				}
				else
				{
					return $lendo;
				}
			}
			else if ($tipo_leitura == 4) // %
			{
				if (ord($c) == 10)
				{
					$tipo_leitura = 0;
				}
			}
			else if ($tipo_leitura == 5) // \n
			{
				if (ord($c) == 10)
				{
					$lendo .= $c;
				}
				else if ($c != ' ' && $c != '\t')
				{
					$this->ultimo_foi_quebra = true;
					return $lendo;
				}
			}
		
			$ultimo_foi_espaco = $espaco;
		}
		
		if ($lendo != "")
		{
			return $lendo;
		}
		return NULL;
	}
	public	function AvancarAte($a)
	{
		$t = strpos($this->s, $a, $this->pos);
		if ($t === false)
		{
			return;
		}
		$this->pos = $t + strlen($a);
	}

	function AumentarVetorDeTokens()
	{
		$s = $this->LerToken();
		if ($s == NULL)
		{
			return false;
		}
		array_push($this->tk, $s);
		return true;
	}
	public	function Proximo()
	{
		while (count($this->tk) == 0)
		{
			if (!$this->AumentarVetorDeTokens())
			{
				return NULL;
			}
		}
		return $this->tk[0];
	}
	public	function LerProximo()
	{
		$s = $this->Proximo();
		array_splice($this->tk, 0, 1);
		return $s;
	}
	public	function Proximos($s)
	{
		
		if ($s == NULL)
		{
			return false;
		}
		
		$t = count($s);
		while (count($this->tk) < $t)
		{
			if (!$this->AumentarVetorDeTokens())
			{
				return false;
			}
		}
		for ($i = 0; $i < $t; $i++)
		{
			if (!(strcmp($s[$i], $this->tk[$i]) == 0))
			{
				return false;
			}
		}
		return true;
	}
	public	function Avancar($n = 1)
	{
		for ($i = 0; $i < $n; $i++)
		{
			array_splice($this->tk, 0, 1);
		}
	}
}

class LaTeX2Html
{
	public $ignorar_documento_ate = NULL;
	private $comandos = array();
	private $ambientes = array();
	
	public	function __construct($media = "")
	{
		global $local_padrao_media;
		
		if ($media)
			$local_padrao_media = $media;
		
		$this->IniciarComandosPadrao();
	}
	
	public function Processar($texto)
	{
		try
		{
			$t = new Tokenizer($texto);
			$p = new Processor($t);
			
			if ($this->ignorar_documento_ate != NULL)
			{
				$p->ignorar_documento_ate = $this->ignorar_documento_ate;
			}
			
			return $p->Processar($t, $this->comandos, $this->ambientes);
			//return str_replace("\n", "<br/>", $s);
		}
		catch (Exception $e)
		{
			return "<div class='erro'>Erro: " . $e->getMessage() . "<br><br>" . $e->getTraceAsString() . "</div>";
		}
		
	}
	public function IniciarComandosPadrao()
	{
		IniciarComandosLatexPadrao($this->comandos);
		IniciarAmbientesLatexPadrao($this->ambientes);
	}
	public function NewCommandIterator($command, $iteration_function, $sintaxe, $tipo = 0) //0: LaTeX, 1: HTML
	{
		_NewCommandIterator($this->comandos, $command, $iteration_function, $sintaxe, $tipo);
	}		
}

class Contador
{
	public $nome;
	public $valor = 0;
	
	public static $contadores = array();
	
	public function __construct($_nome, $_valor = 0)
	{
		$this->nome = $_nome;
		$this->valor = $_valor;
	}
	public function Usar()
	{
		$this->valor ++;
		return $this->valor;
	}
	public function Definir($v)
	{
		$this->valor = $v;
	}
	public function Zerar()
	{
		$this->valor = 0;
	}
	
	public static function Obter($nome)
	{
		if (!isset(Contador::$contadores[$nome]))
		{
			//echo "[contador $nome criado]";
			Contador::$contadores[$nome] = new Contador($nome);
		}
		
		return Contador::$contadores[$nome];
	}
	
	
}

?>