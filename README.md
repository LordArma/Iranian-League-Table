# Iranian League Table
[![en](https://img.shields.io/badge/lang-en-red.svg)](https://github.com/LordArma/Iranian-League-Table)
[![fa](https://img.shields.io/badge/lang-fa-yellow.svg)](https://github.com/LordArma/Iranian-League-Table/blob/master/README.fa.md)

A WordPress plugin that helps WordPress sites display the Iranian Premier League (Persian Gulf League) or the Iranian League One (Azadegan League) table in Persian as a widget or shortcode.

The tables are displayed in Farsi and can be customized in color and display type to match their display location.

These tables data are provided by the [Varzesh3](https://www.varzesh3.com/developer-tools) website.

## Download
You can download the last version from: [here](https://github.com/LordArma/Iranian-League-Table/releases).

## How to use?
You can use this plugin in two ways:
- As a Widget
- As a Shortcode

### Use Iranian League Table As A Widget
If your WordPress theme supports legacy widgets, you can easily place your desired table in the appropriate place from the WordPress widgets section and then set its options.

### Use Iranian League Table As A Shortcode
This method works correctly in all versions of WordPress. All you need to do is place the shortcode of this plugin along with your desired parameters in the shortcode placement area, such as WordPress blocks, on your WordPress site pages and posts.

### Example of Using Shortcode
‌Basic display of the Iranian Premier League (Persian Gulf) table
```
[iran_league league="persiangulf" mode="basic"]
```

Advanced display of the Iranian League One (Azadegan) table
```
[iran_league league="azadegan" mode="advanced"]
```

‌Basic display of the Iranian Premier League table without team logos
```
[iran_league league="persiangulf" mode="basic" logo="false"]
```

Display the Iranian Premier League table with the desired color and size
```
[iran_league league="bartar" mode="advanced" title_backcolor="#212121" title_color="#ffffff" text_color="#212121" odd_color="#ffffff" even_color="#eeeeee" logo_size="15" logo="true" title_font="12" text_font="13"]
```
