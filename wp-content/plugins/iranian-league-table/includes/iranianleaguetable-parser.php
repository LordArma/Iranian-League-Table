<?php

function get_data($league){
    $response = file_get_contents($league);
    return $response;
}

function replace_var($input, $title_color, $title_text_color, $text_color, $b_color, $logo_size, $logo_type, $font_h_size, $font_d_size){
    $output = $input;
    $output = str_replace('$title_color', $title_color, $input);
    $output = str_replace('$title_text_color', $title_text_color, $output);
    $output = str_replace('$text_color', $text_color, $output);
    $output = str_replace('$b_color', $b_color, $output);
    $output = str_replace('$logo_size', $logo_size, $output);
    $output = str_replace('$logo_type', $logo_type, $output);
    $output = str_replace('$font_h_size', $font_h_size, $output);
    $output = str_replace('$font_d_size', $font_d_size, $output);
    return $output;
}

function table($league, $table_type, $title_color, $title_text_color, $text_color, $odd_color, $even_color, $logo_size, $logo, $font_h_size, $font_d_size){
    $render = "";

    $json = json_decode(get_data($league), false);
    $advanced = '';

    if ($table_type != 'basic') {
        $advanced = '<th class="in-detailed" scope="col" style="width: 15%; text-align: center; padding: 0 2px 0 2px;" >برد</th><th class="in-detailed" scope="col" style="width: 15%; text-align: center; padding: 0 2px 0 2px;" >مساوی</th><th class="in-detailed" scope="col" style="width: 15%; text-align: center; padding: 0 2px 0 2px;" >باخت</th><th class="in-detailed" scope="col" style="width: 20%; text-align: center; padding: 0 2px 0 2px;" >گل‌ها</th><th class="in-detailed" scope="col" style="width: 14%; text-align: center; padding: 0 2px 0 2px;" >تفاضل</th>';
    }

    $input = '<div class="vaz3-table-holder" style="direction: rtl;" ><table style="text-align: center; white-space: nowrap;" class="vaz3-standing"><thead style="background-color: $title_color; font-size: $font_h_sizepx; color: $title_text_color;"><tr><th scope="col" style="width: 15%; text-align: center; padding: 0 2px 0 2px;" >رتبه</th><th scope="col" style="width: 55%; padding: 0 2px 0 2px;">تیم</th><th scope="col" style="width: 15%; text-align: center; padding: 0 2px 0 2px;" >بازی</th>' . $advanced . '<th scope="col" style="width: 15%; text-align: center; padding: 0 2px 0 2px;" >امتياز</th></tr></thead><tbody>';
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
        $render = $render . $counter;
        $counter = $counter + 1;
        $i = '</td><td style="text-align: right;"><figure style="margin-right: 2px; display: $logo_type; width: $logo_sizepx; height: $logo_sizepx;"><img style="max-width:100%;" src="';
        $render = $render . replace_var($i, $title_color, $title_text_color, $text_color, $b_color, $logo_size, $logo_type, $font_h_size, $font_d_size);
        $render = $render . $team->logo;
        $render = $render . '"></figure><span style="margin-right: 3px;">';
        $render = $render . $team->name;
        $render = $render . '</span></td><td style="text-align: center;" >';
        $render = $render . $team->played;
        $render = $render . '</td>';

        if ($table_type != 'basic'){
            $render = $render . '<td class="in-detailed" style="text-align: center;" >';
            $render = $render . $team->wins;
            $render = $render . '</td><td class="in-detailed" style="text-align: center;" >';
            $render = $render . $team->draws;
            $render = $render . '</td><td class="in-detailed" style="text-align: center;" >';
            $render = $render . $team->losses;
            $render = $render . '</td><td class="in-detailed" style="text-align: center;" >';
            $render = $render . $team->goalFor . '-' . $team->goalAgainst;
            $render = $render . '</td><td class="in-detailed" style="text-align: center;" >';
            $render = $render . $team->goalDifference;
            $render = $render . '</td>';
        }

        $render = $render . '<td style="text-align: center;" >';
        $render = $render . $team->points;
        $render = $render . '</td></tr>';

    }
    $render = $render . '</tbody></table></div>';

    return $render;
}