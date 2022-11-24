<?php

use Maximal\Thumbnailers\AvifThumbnailer;

require_once __DIR__ . '/vendor/autoload.php';


// Поехали!
exec('rm -rf assets');

$timeStart = microtime(true);

?>

<style>
	img {
		border: 1px solid red;
	}
</style>

<p>PNG, horizontal → horizontal, inset:</p>
<?= AvifThumbnailer::picture('horizontal.png', 600, 300) ?>

<p>PNG, horizontal → horizontal, outbound:</p>
<?= AvifThumbnailer::picture('horizontal.png', 600, 300, false) ?>

<p>PNG, horizontal → vertical, inset:</p>
<?= AvifThumbnailer::picture('horizontal.png', 300, 600) ?>

<p>PNG, horizontal → vertical, outbound:</p>
<?= AvifThumbnailer::picture('horizontal.png', 300, 600, false) ?>



<p>PNG, vertical → horizontal, inset:</p>
<?= AvifThumbnailer::picture('vertical.png', 600, 300) ?>

<p>PNG, vertical → horizontal, outbound:</p>
<?= AvifThumbnailer::picture('vertical.png', 600, 300, false) ?>

<p>PNG, vertical → vertical, inset:</p>
<?= AvifThumbnailer::picture('vertical.png', 300, 600) ?>

<p>PNG, vertical → vertical, outbound:</p>
<?= AvifThumbnailer::picture('vertical.png', 300, 600, false) ?>



<p>GIF (skip):</p>
<?= AvifThumbnailer::picture('animated.gif', 600, 300) ?>


<p>Time: <?= round(microtime(true) - $timeStart, 3)?> sec.</p>
