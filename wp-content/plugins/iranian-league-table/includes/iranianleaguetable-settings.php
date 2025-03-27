<?php

function submenu_help_callback(){
    echo '<div>';

    echo '<h2>';
    _e('Example of Using Shortcode', 'ilt_domain');
    echo '</h2>';

    echo '<p>';
    _e('Basic display of the Iranian Premier League (Persian Gulf) table', 'ilt_domain');
    echo '</p>';
    echo '<code>';
    echo '[iran_league league="persiangulf" mode="basic"]';
    echo '</code>';

    echo '<p>';
    _e('Advanced display of the Iranian League One (Azadegan) table', 'ilt_domain');
    echo '</p>';
    echo '<code>';
    echo '[iran_league league="azadegan" mode="advanced"]';
    echo '</code>';

    echo '<p>';
    _e('Basic display of the Iranian Premier League table without team logos', 'ilt_domain');
    echo '</p>';
    echo '<code>';
    echo '[iran_league league="persiangulf" mode="basic" logo="false"]';
    echo '</code>';

    echo '<p>';
    _e('Basic display of the Iranian Premier League table without team logos and Farsi numbers', 'ilt_domain');
    echo '</p>';
    echo '<code>';
    echo '[iran_league league="persiangulf" mode="basic" logo="false" farsi_numbers="true"]';
    echo '</code>';

    echo '<p>';
    _e('Display the Iranian Premier League table with the desired color and size', 'ilt_domain');
    echo '</p>';
    echo '<code>';
    echo '[iran_league league="bartar" mode="advanced" title_backcolor="#212121" title_color="#ffffff" text_color="#212121" odd_color="#ffffff" even_color="#eeeeee" logo_size="15" logo="true" title_font="12" text_font="13" farsi_numbers="false"]';
    echo '</code>';

    echo '</div>';
}