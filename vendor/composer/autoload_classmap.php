<?php

// autoload_classmap.php @generated by Composer

$vendorDir = dirname(__DIR__);
$baseDir = dirname($vendorDir);

return array(
    'App\\Controller' => $baseDir . '/app/Controller.php',
    'App\\Database' => $baseDir . '/app/Database.php',
    'App\\Router' => $baseDir . '/app/Router.php',
    'Composer\\InstalledVersions' => $vendorDir . '/composer/InstalledVersions.php',
    'Ollama\\Ollama' => $baseDir . '/app/middleware/Ollama.php',
    'Ollama\\OllamaDB' => $baseDir . '/app/middleware/OllamaDB.php',
);
