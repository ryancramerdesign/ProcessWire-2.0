<?php

/**
 * ProcessWire 2.x Admin Markup Template
 *
 * Copyright 2010 by Ryan Cramer
 *
 *
 */

$searchForm = $user->hasPermission('ProcessPageSearch') ? $modules->get('ProcessPageSearch')->renderSearchForm() : '';
$bodyClass = $input->get->modal ? 'modal' : '';
if(!isset($content)) $content = '';

$config->styles->prepend($config->urls->adminTemplates . "styles/main.css"); 
$config->scripts->append($config->urls->adminTemplates . "scripts/main.js"); 

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta name="robots" content="noindex, nofollow" />

	<title><?php echo strip_tags($page->get("browser_title|headline|title|name")); ?> &bull; ProcessWire</title>

	<script type="text/javascript">
		<?php

		$jsConfig = $config->js();
		$jsConfig['debug'] = $config->debug;
		$jsConfig['urls'] = array(
			'root' => $config->urls->root, 
			'admin' => $config->urls->admin, 
			'modules' => $config->urls->modules, 
			'core' => $config->urls->core, 
			'files' => $config->urls->files, 
			'templates' => $config->urls->templates,
			'adminTemplates' => $config->urls->adminTemplates,
			); 
		?>

		var config = <?php echo json_encode($jsConfig); ?>;
	</script>

	<?php foreach($config->styles->unique() as $file) echo "\n\t<link type='text/css' href='$file' rel='stylesheet' />"; ?>

	<?php foreach($config->scripts->unique() as $file) echo "\n\t<script type='text/javascript' src='$file'></script>"; ?>

</head>
<body<?php if($bodyClass) echo " class='$bodyClass'"; ?>>
	<div id="masthead" class="masthead">
		<div class="container">
			<p id="logo">ProcessWire</p>

			<ul id='topnav'>
				<?php 
				foreach($page->rootParent->navChildren() as $p) {
					if(!$p->viewable()) continue; 
					if($p->process && !$user->hasPermission($p->process)) continue; 
					$class = strpos($page->path, $p->path) === 0 ? " class='on'" : '';
					echo "\n\t\t\t<li><a href='{$p->url}'$class>" . strip_tags($p->get('title|name')) . "</a></li>"; 
				}
				?>

			</ul>

			<ul id='breadcrumb'>
				<?php
				foreach($this->fuel('breadcrumbs') as $breadcrumb) {
					$title = htmlspecialchars(strip_tags($breadcrumb->title)); 
					echo "\n\t\t\t<li><a href='{$breadcrumb->url}'>{$title}</a> &gt;</li>";
				}
				?>

			</ul>
			

			<h1 id='title'><?php echo strip_tags($this->fuel->processHeadline ? $this->fuel->processHeadline : $page->get("title|name")); ?></h1>

			<?php echo $searchForm; ?>

		</div>
	</div>

	<?php if(count($notices)) include($config->paths->adminTemplates . "notices.inc"); ?>

	<div id="content" class="content">
		<div class="container">

			<?php if($page->summary) echo "<h2>{$page->summary}</h2>"; ?>
			<?php if($page->body) echo $page->body; ?>


			<?php echo $content?>

		</div>
	</div>


	<div id="footer" class="footer">
		<div class="container">
			<p>

			<?php if(!$user->isGuest()): ?>
			<span id='userinfo'><?php echo $user->name?> &bull; <a class='action' href='<?php echo $config->urls->admin?>logout/'>logout</a></span>
			<?php endif; ?>

			ProcessWire <?php echo $config->version; ?> &copy; <?php echo date("Y"); ?> by Ryan Cramer 
			</p>

			<?php if($config->debug) include($config->paths->adminTemplates . "debug.inc"); ?>
		</div>
	</div>

</body>
</html>
