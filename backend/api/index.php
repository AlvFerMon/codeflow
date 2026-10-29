<?php

// Punto de entrada en Vercel (runtime vercel-php). Vercel ejecuta este archivo como /api/index.php:
// sin corregir SCRIPT_NAME, Laravel tomaría "/api" como carpeta base de la aplicación y las rutas de la
// API (/api/auth/login...) llegarían sin el prefijo "api" → 404. Se hace como si fuera public/index.php.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__.'/../public/index.php';

require __DIR__.'/../public/index.php';
