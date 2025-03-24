<?php
  // Add Scripts
  function yts_add_scripts(){
    // Add Main CSS
    wp_enqueue_style('yts-main-style', plugins_url(). '/iranian-league-table/css/style.css');
    // Add Main JS
    wp_enqueue_script('yts-main-script', plugins_url(). '/iranian-league-table/js/main.js', NULL, NULL, true);

    // Add Google Script
    // wp_register_script('varzesh3', 'https://static.varzesh3.com/js/developers-varzesh3.js');
    // wp_enqueue_script('varzesh3');
  }

  add_action('wp_enqueue_scripts', 'yts_add_scripts');