<!DOCTYPE html>
<html 
  <?php language_attributes(); ?>>
  <?php get_template_part('functions/style-globals'); ?>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins&family=Playfair+Display&display=swap" rel="stylesheet">
<!-- Font Awesome CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<!-- Material Icons Outline -->
<link rel="stylesheet"
  href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:
opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
  
<?php wp_body_open(); ?>
