<?php

$sessao_iniciada = false;

function ChaveSessao($c)
{
	global $sessao_iniciada;
	
	if (!$sessao_iniciada)
	{
		session_start();
		$sessao_iniciada = true;
	}
	
	if (isset($_SESSION[$c]))
		return $_SESSION[$c];
				
	return false;
}
function EscreverSessao($c, $v)
{
	global $sessao_iniciada;
	
	if (!$sessao_iniciada)
	{
		session_start();
		$sessao_iniciada = true;
	}
	
	$_SESSION[$c] = $v;
}
function Perfil()
{
	return ChaveSessao('perfil');
}
function Nome()
{
	return ChaveSessao('nome');
}
function Login()
{
	return ChaveSessao('login');
}
function Editor()
{
	$p = Perfil();
	return ($p == 'a' || $p == 'e');		
}
function RegistrarLogin($login, $nome, $perfil)
{
	session_start();
	$_SESSION['nome'] = $nome;
	$_SESSION['login'] = $login;
	$_SESSION['perfil'] = $perfil;
}

function Deslogar()
{
	session_start();
	session_destroy();
}


?>