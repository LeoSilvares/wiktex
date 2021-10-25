<?php

/*
	A criptografia RSA é feita com a lib jsbn (http://www-cs-students.stanford.edu/~tjw/jsbn/)
	A criptografia RSA é feita com a lib jsencrypt (https://github.com/travist/jsencrypt)
*/

include("sessao.php");
include("arquivo.php");
include("site/usuarios.php");
include("site/config.php");
include("site/rsakeys.php");

$RSApub = str_replace("\n", "\\n", $RSApublic);

function sha256($s)
{
	return hash("sha256", mb_convert_encoding($s, 'US-ASCII'));
}
function Pepper($minutos_atras)
{
	global $pass_pepper;
	
	return sha256((round(time()/60) - $minutos_atras) . $pass_pepper);
}
function ValidPepper($pepper)
{
	for ($i=0; $i<5; $i++)
	{
		//echo "Pepper " . $i . ": " . Pepper($i) . "<br/>";
		if ($pepper == Pepper($i))
			return true;
	}
	
	return false;
}
function Autenticar($login, $pass, $pepper, & $erro)
{
	global $usuarios;
	
	if (!ValidPepper($pepper))
	{
		$erro = "Sua tentativa de login demorou muito. Por segurança, tente novamente";
		return false;
	}		
	
	if (isset($usuarios[$login]))
	{
		$upass = $usuarios[$login][1];
		
		//echo "pass: " . $pass . "<br/>hash: ". sha256($pepper . $pass);
		
		if ($pass == sha256($pepper . $upass))
		{
			//RegistrarLogin($login, $usuarios[$login][0], $usuarios[$login][2]);
			return true;
		}
	}
		
	$erro = "Login ou senha incorreto";
	return false;
}
function AlterarSenha($login, $pass, & $erro)
{
	global $usuarios, $RSAprivate, $RSApublic;
	
	$decrypted = null;
	openssl_private_decrypt(hex2bin($pass), $decrypted, $RSAprivate);
	
	if ($decrypted)
	{
		$usuarios[$login][1] = $decrypted;
		
		SalvarUsuarios($usuarios);
		
		return true;
	}
	
	return false;
}

if (!Editor())
{
	header('Location: index.php');
	exit();
}

$login = Login();

$erro = "";

$pepper = Pepper(0);
$salt = $pass_salt;

//echo "Pepper: " . $pepper . "<br/>";

$alterado = false;

if (isset($_POST['login']))
	$login = $_POST['login'];

if ($login && isset($_POST['hidden_npass']) && isset($_POST['hiddenpass']) && isset($_POST['pepper']))
	if (Autenticar($login, $_POST['hiddenpass'], $_POST['pepper'], $erro))
	{
		if(AlterarSenha($login, $_POST['hidden_npass'], $erro))
		{
			$alterado = true;
		}
	}

?>
<html>

<head>
<link href="https://fonts.googleapis.com/css?family=Quicksand" rel="stylesheet">
<link rel="stylesheet" href="<?php echo $caminho_css; ?>/login.css">
<script language="JavaScript" type="text/javascript" src="js/jsbn/jsbn.js"></script>
<script language="JavaScript" type="text/javascript" src="js/jsbn/prng4.js"></script>
<script language="JavaScript" type="text/javascript" src="js/jsbn/rng.js"></script>
<script language="JavaScript" type="text/javascript" src="js/jsbn/rsa.js"></script>
<script language="JavaScript" type="text/javascript" src="js/jsbn/base64.js"></script>
<!--
<script language="JavaScript" type="text/javascript" src="js/jsencrypt.js"></script>
-->
<script src='js/crypto.js'></script>

<script>
function Enviar()
{
	var npass = document.getElementById('npass1');
	var npass2 = document.getElementById('npass2');
	
	if (npass.value != npass2.value)
	{
		document.getElementById('erro').innerHTML = "As senhas novas informadas não coincidem.";
		return false;
	}		
		
	var login = <?php echo "'$login'";?>;
	var pass = document.getElementById('pass');
	var pepper = document.getElementById('pepper');
	
	if (login.value == "" || pass.value == "")
	{
		document.getElementById('erro').innerHTML = "Senha atual inválida";
		return;
	}
	
	var hiddenpass = document.getElementById('hiddenpass');
	hiddenpass.value = saltpepperhash(pass.value, "<?php echo $salt;?>", pepper.value);
	
	var hidden_npass = document.getElementById('hidden_npass');
	//hidden_npass.value = RSA_Plus_salthash(npass.value, "<?php echo $salt;?>", "<?php echo $RSApub;?>");
	hidden_npass.value = RSA_Plus_salthash(npass.value, "<?php echo $salt;?>", "<?php echo $RSAm;?>", "<?php echo $RSAe;?>");
	
	npass.value = "do not even try";
	npass2.value = "do not even try";
	pass.value = "do not even try";
	
	document.getElementById('login_form').submit();
}
</script>

</head>

<body>

<?php if (!$alterado) { ?>
<form method='post' action='conta.php' id='login_form'>
<div id='div_login'>
	
	<div class='titulo'>
		<?php echo $nome_site; ?>
	</div>
	<div class='login_linha erro'>
		<div id='erro'><?php echo $erro;?></div>
	</div>
	<p>Alterar senha:</p>
	<div class='login_linha'>
		<div class='label'>Senha anterior:</div>
		<div class='field'><input class='input' type='password' id='pass' name='pass'></input></div>  
	</div>
	<div class='login_linha'>
		<div class='label'>Nova senha:</div>
		<div class='field'><input class='input' type='password' id='npass1' name='pass'></input></div>  
	</div>
	<div class='login_linha'>
		<div class='label'>Repita a nova senha:</div>
		<div class='field'><input class='input' type='password' id='npass2' name='pass'></input></div>  
	</div>
	<div class='login_linha'>
		<div class='label'></div>
		<div class='field'><input type='button' class='botao' onClick='Enviar();' value="Enviar"></input></div>
	</div>
	<input type='hidden' name='hiddenpass' id='hiddenpass'></input>
	<input type='hidden' name='hidden_npass' id='hidden_npass'></input>
	<input type='hidden' name='pepper' id='pepper' value="<?php echo $pepper;?>"></input>
</div>
</form>
<?php } else { ?>
<div id='div_login'>
	
	<div class='titulo'>
		<?php echo $nome_site; ?>
	</div>
	<p>Senha alterada!</p>
	<div class='login_linha'>
		<div class='label'></div>
		<div class='field'><input type='button' class='botao' onClick="window.location.replace('index.php');" value="Ok"></input></div>
	</div>
</div>
<?php } ?>

</body>
</html>