<?php
/**
 * Plugin Name: Events Plugin
 * Description: Events CPT with frontend form (image + tickets supported).
 * Version: 1.0
 * Author: Rae
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

// Load dependencies
require_once __DIR__ . '/includes/cpt.php';
require_once __DIR__ . '/includes/admin.php';
require_once __DIR__ . '/includes/create-event-form.php';
require_once __DIR__ . '/includes/create-event-handler.php';
