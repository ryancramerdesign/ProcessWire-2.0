<?php if(!defined("PROCESSWIRE_INSTALL")) die(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title><?php echo $title; ?></title>

	<meta name="robots" content="noindex, nofollow" />

	<link rel="stylesheet" type="text/css" href="wire/templates-admin/styles/main.css" />
	<link rel="stylesheet" type="text/css" href="wire/modules/Jquery/JqueryUI/JqueryUI.css" />
	<link rel="stylesheet" type="text/css" href="wire/templates-admin/styles/install.css" />

	<script type="text/javascript" src="wire/modules/Jquery/JqueryCore/JqueryCore.js"></script>
	<script type="text/javascript" src="wire/templates-admin/scripts/install.js"></script>
</head>
<body>
<div id="masthead" class="masthead">
	<div class="container">
		<p id='logo'>ProcessWire</p>
		<h1 id='title'><?php echo $title; ?></h1>
	</div>
</div>

<div id="content" class="content">
	<div class="container">
		<form action='./install.php' method='post'>

