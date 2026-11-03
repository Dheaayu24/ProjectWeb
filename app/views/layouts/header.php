<?php
/**
 * Layout Header
 * File: app/views/layouts/header.php
 */
$pageTitle = $title ?? 'Mochi Mood – A Little Mochi, A Lot of Happiness ♡';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  
  <!-- Font Wajib: Poppins Only -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
  
  <!-- QueueBites Mochi Mood Stylesheet -->
  <link rel="stylesheet" href="public/css/style.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🍩</text></svg>">
</head>
<body>

<?php
if (!isset($noNavbar) || !$noNavbar) {
    include __DIR__ . '/navbar.php';
}
?>
