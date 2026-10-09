<?php
/**
 * Dev tool (no wp-cli/gettext needed):
 *   php bin/i18n.php
 * - extracts translatable strings into languages/iranian-league-table.pot
 * - compiles every languages/*.po into .mo and .l10n.php
 * - lists strings missing from each .po (exit code 1 if any)
 *
 * The .po files are the source of truth for translations; edit them, then run this.
 */

$plugin = dirname( __DIR__ ) . '/wp-content/plugins/iranian-league-table';
$domain = 'iranian-league-table';

// ---- Extraction ----------------------------------------------------------
function ilt_php_unquote( $q, $s ) {
	return $q === "'" ? strtr( $s, array( "\\'" => "'", '\\\\' => '\\' ) ) : stripcslashes( $s );
}

$strings = array(); // msgid => [refs]
$files   = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $plugin, FilesystemIterator::SKIP_DOTS ) );
foreach ( $files as $file ) {
	$path = $file->getPathname();
	$rel  = substr( $path, strlen( $plugin ) + 1 );
	if ( ! preg_match( '/\.php$/', $path ) || preg_match( '#^(languages|data|vendor|node_modules)/#', $rel ) ) {
		continue;
	}
	$src = file_get_contents( $path );
	$re  = '/\b(?:__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e)\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1\s*,\s*([\'"])' . preg_quote( $domain, '/' ) . '\3\s*\)/s';
	if ( preg_match_all( $re, $src, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
		foreach ( $m as $hit ) {
			$id   = ilt_php_unquote( $hit[1][0], $hit[2][0] );
			$line = substr_count( substr( $src, 0, $hit[0][1] ), "\n" ) + 1;
			$strings[ $id ][] = "$rel:$line";
		}
	}
	$re_n = '/\b_n\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1\s*,\s*([\'"])((?:\\\\.|(?!\3).)*)\3\s*,[^,]+,\s*([\'"])' . preg_quote( $domain, '/' ) . '\5\s*\)/s';
	if ( preg_match_all( $re_n, $src, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
		foreach ( $m as $hit ) {
			$id   = ilt_php_unquote( $hit[1][0], $hit[2][0] ) . "\0" . ilt_php_unquote( $hit[3][0], $hit[4][0] );
			$line = substr_count( substr( $src, 0, $hit[0][1] ), "\n" ) + 1;
			$strings[ $id ][] = "$rel:$line";
		}
	}
}

// block.json strings are translated with contexts (msgctxt + "\4" + msgid).
foreach ( glob( "$plugin/build/*/block.json" ) as $block_json ) {
	$block = json_decode( file_get_contents( $block_json ), true );
	$rel   = substr( $block_json, strlen( $plugin ) + 1 );
	foreach ( array( 'title' => 'block title', 'description' => 'block description' ) as $field => $ctx ) {
		if ( ! empty( $block[ $field ] ) ) {
			$strings[ $ctx . "\4" . $block[ $field ] ][] = $rel;
		}
	}
	foreach ( $block['keywords'] ?? array() as $keyword ) {
		$strings[ 'block keyword' . "\4" . $keyword ][] = $rel;
	}
}

$main = file_get_contents( $plugin . '/iranianleaguetable.php' );
foreach ( array( 'Plugin Name', 'Description', 'Plugin URI', 'Author', 'Author URI' ) as $header ) {
	if ( preg_match( '/^' . $header . ':\s*(.+)$/mi', $main, $hm ) ) {
		$strings[ trim( $hm[1] ) ][] = '#' . $header . ' of the plugin';
	}
}
ksort( $strings );

function ilt_po_quote( $s ) {
	return '"' . addcslashes( $s, "\\\"\n\t" ) . '"';
}

$pot  = "msgid \"\"\nmsgstr \"\"\n\"Project-Id-Version: Iranian League Table\\n\"\n\"MIME-Version: 1.0\\n\"\n";
$pot .= "\"Content-Type: text/plain; charset=UTF-8\\n\"\n\"Content-Transfer-Encoding: 8bit\\n\"\n\"X-Domain: $domain\\n\"\n";
foreach ( $strings as $id => $refs ) {
	$pot .= "\n";
	foreach ( $refs as $r ) {
		$pot .= ( $r[0] === '#' ? '#. ' . substr( $r, 1 ) : "#: $r" ) . "\n";
	}
	if ( false !== strpos( $id, "\4" ) ) {
		list( $ctx, $id ) = explode( "\4", $id, 2 );
		$pot             .= 'msgctxt ' . ilt_po_quote( $ctx ) . "\n";
	}
	if ( false !== strpos( $id, "\0" ) ) {
		list( $one, $many ) = explode( "\0", $id, 2 );
		$pot .= 'msgid ' . ilt_po_quote( $one ) . "\nmsgid_plural " . ilt_po_quote( $many ) . "\nmsgstr[0] \"\"\nmsgstr[1] \"\"\n";
	} else {
		$pot .= 'msgid ' . ilt_po_quote( $id ) . "\nmsgstr \"\"\n";
	}
}
file_put_contents( "$plugin/languages/$domain.pot", $pot );
echo 'POT: ' . count( $strings ) . " strings\n";

// ---- PO parsing / compiling ---------------------------------------------
function ilt_parse_po( $file ) {
	$entries = array();
	$cur     = array();
	$field   = null;
	$flush   = function () use ( &$entries, &$cur ) {
		if ( isset( $cur['msgid'] ) && empty( $cur['obsolete'] ) ) {
			if ( isset( $cur['msgctxt'] ) ) {
				$cur['msgid'] = $cur['msgctxt'] . "\4" . $cur['msgid'];
			}
			if ( isset( $cur['msgid_plural'] ) ) {
				$forms = array();
				for ( $i = 0; isset( $cur[ "msgstr[$i]" ] ); $i++ ) {
					$forms[] = $cur[ "msgstr[$i]" ];
				}
				$entries[ $cur['msgid'] . "\0" . $cur['msgid_plural'] ] = implode( '', $forms ) === '' ? '' : implode( "\0", $forms );
			} else {
				$entries[ $cur['msgid'] ] = $cur['msgstr'] ?? '';
			}
		}
		$cur = array();
	};
	foreach ( file( $file, FILE_IGNORE_NEW_LINES ) as $line ) {
		$line = rtrim( $line, "\r" );
		if ( preg_match( '/^#~/', $line ) ) {
			$cur['obsolete'] = true;
			continue;
		}
		if ( $line === '' || $line[0] === '#' ) {
			if ( $line === '' && ( isset( $cur['msgstr'] ) || isset( $cur['msgstr[0]'] ) ) ) {
				$flush();
			}
			continue;
		}
		if ( preg_match( '/^(msgid_plural|msgid|msgstr\[\d+\]|msgstr|msgctxt)\s+"(.*)"$/', $line, $m ) ) {
			if ( in_array( $m[1], array( 'msgid', 'msgctxt' ), true ) && ( isset( $cur['msgstr'] ) || isset( $cur['msgstr[0]'] ) ) ) {
				$flush();
			}
			$field         = $m[1];
			$cur[ $field ] = stripcslashes( $m[2] );
		} elseif ( preg_match( '/^"(.*)"$/', $line, $m ) && $field ) {
			$cur[ $field ] .= stripcslashes( $m[1] );
		}
	}
	$flush();
	return $entries;
}

function ilt_write_mo( $file, $entries ) {
	ksort( $entries, SORT_STRING );
	$n      = count( $entries );
	$ids    = '';
	$strs   = '';
	$id_tab = array();
	$st_tab = array();
	foreach ( $entries as $id => $str ) {
		$id_tab[] = array( strlen( $id ), strlen( $ids ) );
		$ids     .= $id . "\0";
		$st_tab[] = array( strlen( $str ), strlen( $strs ) );
		$strs    .= $str . "\0";
	}
	$ids_off  = 28 + 16 * $n;
	$strs_off = $ids_off + strlen( $ids );
	$out      = pack( 'V7', 0x950412de, 0, $n, 28, 28 + 8 * $n, 0, $ids_off );
	foreach ( $id_tab as $t ) {
		$out .= pack( 'V2', $t[0], $ids_off + $t[1] );
	}
	foreach ( $st_tab as $t ) {
		$out .= pack( 'V2', $t[0], $strs_off + $t[1] );
	}
	file_put_contents( $file, $out . $ids . $strs );
}

function ilt_php_str( $s ) {
	return "'" . strtr( $s, array( '\\' => '\\\\', "'" => "\\'" ) ) . "'";
}

function ilt_write_l10n_php( $file, $entries ) {
	$headers = array();
	foreach ( explode( "\n", $entries[''] ?? '' ) as $h ) {
		if ( strpos( $h, ':' ) !== false ) {
			list( $k, $v )                         = explode( ':', $h, 2 );
			$headers[ strtolower( trim( $k ) ) ] = trim( $v );
		}
	}
	unset( $entries[''] );
	ksort( $entries, SORT_STRING );
	$parts = array();
	foreach ( $headers as $k => $v ) {
		$parts[] = ilt_php_str( $k ) . '=>' . ilt_php_str( $v );
	}
	$msgs = array();
	foreach ( $entries as $id => $str ) {
		if ( $str !== '' ) {
			$msgs[] = ilt_php_str( $id ) . '=>' . ilt_php_str( $str );
		}
	}
	$parts[] = "'messages'=>[" . implode( ',', $msgs ) . ']';
	file_put_contents( $file, "<?php\nreturn [" . implode( ',', $parts ) . '];' );
}

$missing_total = 0;
foreach ( glob( "$plugin/languages/*.po" ) as $po ) {
	$entries = ilt_parse_po( $po );
	$compile = array_filter( $entries, function ( $v, $k ) { return $k === '' || $v !== ''; }, ARRAY_FILTER_USE_BOTH );
	ilt_write_mo( preg_replace( '/\.po$/', '.mo', $po ), $compile );
	ilt_write_l10n_php( preg_replace( '/\.po$/', '.l10n.php', $po ), $compile );
	$missing = array_diff( array_keys( $strings ), array_keys( $compile ) );
	echo basename( $po ) . ': ' . ( count( $compile ) - 1 ) . ' translated, ' . count( $missing ) . " missing\n";
	foreach ( $missing as $id ) {
		echo "  - $id\n";
	}
	$missing_total += count( $missing );
}
exit( $missing_total ? 1 : 0 );
