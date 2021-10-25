<?php
include("sessao.php");
include("site/usuarios.php");
include("site/config.php");


/*
	A senha salva é sha256($pass_salt . SENHA_LIMPA), onde SENHA_LIMPA é a senha limpa 
	do usuário (a que ele digita)
	O usuário envia sha256($pepper . SENHA_S), onde SENHA_S = sha256(sal . SENHA_LIMPA) 
	e $pepper = sha256((round(time()/60) . $pass_pepper)
	

*/

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
			RegistrarLogin($login, $usuarios[$login][0], $usuarios[$login][2]);
			return true;
		}
	}
		
	$erro = "Login ou senha incorreto";
	return false;
}

$login = "";
$erro = "";

$pepper = Pepper(0);
$salt = $pass_salt;

//echo "Pepper: " . $pepper . "<br/>";

if (isset($_POST['login']))
	$login = $_POST['login'];

if ($login && isset($_POST['hiddenpass']) && isset($_POST['pepper']))
	if (Autenticar($login, $_POST['hiddenpass'], $_POST['pepper'], $erro))
	{
		header('Location: index.php');
		exit();
	}

?>
<html>

<head>
<link href="https://fonts.googleapis.com/css?family=Quicksand" rel="stylesheet">
<link rel="stylesheet" href="<?php echo $caminho_css; ?>/login.css">
<script src='js/crypto.js'></script>

<script>
function Enviar()
{
	var login = document.getElementById('login');
	var pass = document.getElementById('pass');
	var pepper = document.getElementById('pepper');
	
	if (login.value == "" || pass.value == "")
	{
		document.getElementById('erro').innerHTML = "Login ou senha inválidos";
		return;
	}
	
	var hiddenpass = document.getElementById('hiddenpass');
	hiddenpass.value = saltpepperhash(pass.value, "<?php echo $salt;?>", pepper.value);
	
	pass.value = "do not even try";
	
	document.getElementById('login_form').submit();
}
</script>

</head>

<body>

<form method='post' action='login.php' id='login_form'>
<div id='div_login'>
	
	<div class='titulo'>
		<?php echo $nome_site; ?>
	</div>
	<div class='login_linha erro'>
		<div id='erro'><?php echo $erro;?></div>
	</div>
	<div class='login_linha'>
		<div class='label'>Login:</div>
		<div class='field'><input class='input' type='text' value='<?php echo $login?>' id='login' name='login'></input></div>  
	</div>
	<div class='login_linha'>
		<div class='label'>Senha:</div>
		<div class='field'><input class='input' type='password' id='pass' name='pass'></input></div>  
	</div>
	<div class='login_linha'>
		<div class='label'></div>
		<div class='field'><input type='button' class='botao' onClick='Enviar();' value="Enviar"></input></div>
	</div>
	<input type='hidden' name='hiddenpass' id='hiddenpass'></input>
	<input type='hidden' name='pepper' id='pepper' value="<?php echo $pepper;?>"></input>
</div>
</form>

</body>
</html>