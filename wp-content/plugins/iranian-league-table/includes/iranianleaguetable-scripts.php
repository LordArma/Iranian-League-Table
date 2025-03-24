<?php

  function yts_add_scripts(){
    wp_enqueue_style('yts-main-style', plugins_url(). '/iranian-league-table/css/style.css');
    wp_enqueue_script('yts-main-script', plugins_url(). '/iranian-league-table/js/main.js', NULL, NULL, true);
  }

  add_action('wp_enqueue_scripts', 'yts_add_scripts');
  