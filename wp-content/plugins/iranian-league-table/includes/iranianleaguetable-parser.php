<?php

function get_data($league_url){
    $response = file_get_contents($league_url);
    return $response;
}

function replace_var($input, $title_color, $title_text_color, $text_color, $b_color, $logo_size, $logo_type, $font_h_size, $font_d_size){
    $output = $input;
    $output = str_replace('$title_color', $title_color, $output);
    $output = str_replace('$title_text_color', $title_text_color, $output);
    $output = str_replace('$text_color', $text_color, $output);
    $output = str_replace('$b_color', $b_color, $output);
    $output = str_replace('$logo_size', $logo_size, $output);
    $output = str_replace('$logo_type', $logo_type, $output);
    $output = str_replace('$font_h_size', $font_h_size, $output);
    $output = str_replace('$font_d_size', $font_d_size, $output);
    return $output;
}

function en2fa_num($input, $farsi_numbers="false"){
    $output = $input;
    if ($farsi_numbers=="true"){
        $output = str_replace('0', '۰', $output);
        $output = str_replace('1', '۱', $output);
        $output = str_replace('2', '۲', $output);
        $output = str_replace('3', '۳', $output);
        $output = str_replace('4', '۴', $output);
        $output = str_replace('5', '۵', $output);
        $output = str_replace('6', '۶', $output);
        $output = str_replace('7', '۷', $output);
        $output = str_replace('8', '۸', $output);
        $output = str_replace('9', '۹', $output);
    }
    return $output;
}

function table($league = "persiangulf", $table_type = "basic", $title_color = "#212121", $title_text_color = "#eeeeee", $text_color = "#212121", $odd_color = "#ffffff", $even_color = "#eeeeee", $logo_size = "15", $logo = "true", $font_h_size = "12", $font_d_size = "13", $farsi_numbers = "true"){
    $render = "";
    $league_url = 'https://web-api.varzesh3.com/v1.0/developer-tools/football/leagues/6/standing';
    $league_name = $league;

    if ($league_name == 'one' || $league_name == '1' || $league_name == 'yek' || $league_name == 'lige1' || $league_name == 'leagueone' || $league_name == 'azadegan' || $league_name == 'ligeyek' || $league_name == 'لیگ۱'  || $league_name == 'آزادگان'){
        $league_url = 'https://web-api.varzesh3.com/v1.0/developer-tools/football/leagues/24/standing';
    }

    $data = NULL;
    $json = NULL;
    $path = WP_PLUGIN_DIR . "/iranian-league-table/data/";
    $file = $path . $league_name;
    $last_update = get_option( "ilt_last_update");

    // Update table after 120 seconds
    if ($last_update + 120 < time() || !file_exists($file)){
        $data = get_data($league_url);
        $json = json_decode($data, false);
        file_put_contents($file, $data);
        update_option( "ilt_last_update", time() );
    }
    else {
        $data = file_get_contents($file);
        $json = json_decode($data, false);
    }
    
    $min_width = "100px";

    if ($table_type != 'basic'){
        $min_width = "400px";
    }

    $advanced = '';
    if ($table_type != 'basic') {
        $advanced = '<th class="in-detailed" scope="col" style="width: 15%; text-align: center; padding: 0 2px 0 2px;" >برد</th><th class="in-detailed" scope="col" style="width: 15%; text-align: center; padding: 0 2px 0 2px;" >مساوی</th><th class="in-detailed" scope="col" style="width: 15%; text-align: center; padding: 0 2px 0 2px;" >باخت</th><th class="in-detailed" scope="col" style="width: 18%; text-align: center; padding: 0 2px 0 2px;" >گل‌ها</th><th class="in-detailed" scope="col" style="width: 14%; text-align: center; padding: 0 2px 0 2px;" >تفاضل</th>';
    }

    $input = '<div class="il-table-holder" style="direction: rtl; overflow-x: auto;" ><table style="text-align: center; white-space: nowrap; min-width: ' . $min_width . ';" class="ilt-standing"><thead style="background-color: $title_color; font-size: $font_h_sizepx; color: $title_text_color;"><tr><th scope="col" style="width: 15%; text-align: center; padding: 0 2px 0 2px;" >رتبه</th><th scope="col" style="width: 60%; padding: 0 2px 0 2px;">تیم</th><th scope="col" style="width: 15%; text-align: center; padding: 0 2px 0 2px;" >بازی</th>' . $advanced . '<th scope="col" style="width: 15%; text-align: center; padding: 0 2px 0 2px;" >امتياز</th></tr></thead><tbody>';
    $render = $render . replace_var($input, $title_color, $title_text_color, $text_color, $odd_color, $logo_size, $logo, $font_h_size, $font_d_size);

    $teams = $json->teams;
    $counter = 1;

    foreach($teams as $team) {
        $b_color = $odd_color;
        if ($counter % 2 == 0) $b_color = $even_color;
        $logo_type = 'inline-block';
        if ($logo == 'false') $logo_type = 'none';

        $i = '<tr style="color: $text_color; background-color: $b_color; font-size: $font_d_sizepx;"><td style="text-align: center;" >';
        $render = $render . replace_var($i, $title_color, $title_text_color, $text_color, $b_color, $logo_size, $logo_type, $font_h_size, $font_d_size);
        $render = $render . en2fa_num($counter, $farsi_numbers);
        $counter = $counter + 1;
        $i = '</td><td style="text-align: right;"><figure style="margin-right: 2px; display: $logo_type; width: $logo_sizepx; height: $logo_sizepx;"><img style="max-width: 100%;" src="';
        $render = $render . replace_var($i, $title_color, $title_text_color, $text_color, $b_color, $logo_size, $logo_type, $font_h_size, $font_d_size);
        
        // Update logos after 12 hours
        if ($last_update + 43200 < time() || !file_exists($path . $team->name . '.png')){
            $ch = curl_init($team->logo);
            $fp = fopen($path . $team->name . '.png' , 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
        }

        if (file_exists($path . $team->name . '.png')){
            $render = $render . plugins_url(). '/iranian-league-table/data/' . $team->name . '.png';
        }
        else{
            $render = $render . $team->logo;
        }
        
        $render = $render . '"></figure><span style="margin-right: 3px;">';
        $render = $render . $team->name;
        $render = $render . '</span></td><td style="text-align: center;" >';
        $render = $render . en2fa_num($team->played, $farsi_numbers);
        $render = $render . '</td>';

        if ($table_type != 'basic'){
            $render = $render . '<td class="in-detailed" style="text-align: center;" >';
            $render = $render . en2fa_num($team->wins, $farsi_numbers);
            $render = $render . '</td><td class="in-detailed" style="text-align: center;" >';
            $render = $render . en2fa_num($team->draws, $farsi_numbers);
            $render = $render . '</td><td class="in-detailed" style="text-align: center;" >';
            $render = $render . en2fa_num($team->losses, $farsi_numbers);
            $render = $render . '</td><td class="in-detailed" style="text-align: center;" >';
            $render = $render . en2fa_num($team->goalFor, $farsi_numbers) . '-' . en2fa_num($team->goalAgainst, $farsi_numbers);
            $render = $render . '</td><td class="in-detailed" style="text-align: center;" >';
            $render = $render . en2fa_num($team->goalDifference, $farsi_numbers);
            $render = $render . '</td>';
        }

        $render = $render . '<td style="text-align: center;" >';
        $render = $render . en2fa_num($team->points, $farsi_numbers);
        $render = $render . '</td></tr>';

    }
    $render = $render . '</tbody></table></div>';

    return $render;
}
