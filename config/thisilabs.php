<?php
// config/thisilabs.php

$THISILABS_API_KEY = getenv('THISILABS_API_KEY') ?: '';
$THISILABS_BASE_URL = getenv('THISILABS_BASE_URL') ?: 'https://api.thisilabs.com/v1';
$THISILABS_MODEL = getenv('THISILABS_MODEL') ?: 'gpt-5.4-mini';

$localConfig = __DIR__ . '/thisilabs.local.php';
if (is_file($localConfig)) {
    require $localConfig;
}
