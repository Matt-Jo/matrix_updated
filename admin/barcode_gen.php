<?php
if (empty($_GET['value'])) {
	die('The "value" parameter must be set.');
}
// Including all required classes
require('includes/classes/barcode/BCGFont.php');
require('includes/classes/barcode/BCGColor.php');
require('includes/classes/barcode/BCGDrawing.php');

// Including the barcode technology
include('includes/classes/barcode/BCGcode128.barcode.php');

// Loading Font
$font = new BCGFont('./includes/classes/barcode/font/Arial.ttf', 18);

// The arguments are R, G, B for color.
$color_black = new BCGColor(0, 0, 0);
$color_white = new BCGColor(255, 255, 255);

$code = new BCGcode128();
$code->setScale(2); // Resolution
$code->setThickness(30); // Thickness
$code->setForegroundColor($color_black); // Color of bars
$code->setBackgroundColor($color_white); // Color of spaces
$code->setFont($font); // Font (or 0)
$code->parse($_GET['value']); // Text

/* Here is the list of the arguments
1 - Filename (empty : display on screen)
2 - Background color */
$drawing = new BCGDrawing('', $color_white);
$drawing->setBarcode($code);
$drawing->draw();

// Header that says it is an image (remove it if you save the barcode to a file)
header('Content-Type: image/png');

// Draw (or save) the image into PNG format.
$drawing->finish(BCGDrawing::IMG_FORMAT_PNG);
?>
