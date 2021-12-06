#!/usr/bin/env php

<?php
    $loader = require_once __DIR__ . '/vendor/autoload.php';
    $parser = new \TogglDaily\TogglDaily();
    $parser->run();
