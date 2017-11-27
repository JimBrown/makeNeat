<?php

/**
 * Cleans up the final HTML output.
 *
 * Based on code from the plugin headConsolidator by Stephan Billiard.
 *
 * Add <code>zp_apply_filter('theme_file_top')</code> to the beginning of any PHP files that send output to the browser.
 *
 * Add <code>zp_apply_filter('theme_file_end')</code> to the end of any PHP files that send output to the browser.
 *
 * <b>NOTE:</b> Do not add the above to any PHP files that are "included".
 *
 * Enabling this plugin will result in the html output buffered and captured, the head section consolidated,
 * scripts with "src" moved to the head section, inline scripts moved to after the body, and the body section
 * neatened with appropriate line splitting, concatenating, and tabbing and then everything sent to the browser.
 * 
 * If a script section should not be moved, add <code>nomove</code> after the script open tag.
 * eg: <code>&lt;script nomove type=text/javascript&gt;</code>
 *
 * This option will add processing time to the page.
 * 
 * @author Jim Brown
 *
 * @package plugins
 * @subpackage example
 * @category package
 *
 */
$plugin_is_filter = 9 | THEME_PLUGIN;
$plugin_description = gettext('A plugin to collect and neaten the output to the browser.');
$plugin_author = "Jim Brown";
$plugin_version = "1.0";

// Note: these are not exact. If some other plugin decides to insert before or after, it's output
// will not get processed.
zp_register_filter('theme_file_top', 'makeNeatStart', 99999);
zp_register_filter('theme_file_end', 'makeNeatEnd', -99999);

function makeNeatStart() {
	ob_start();
}

function makeNeatEnd() {
	$data = ob_get_contents();
	ob_end_clean();

	define("tabType", "Space"); // Can be "Space" or "Tab".
	$notJS = array();
	$notCSS = array();

	$theBody = makeNeat_extract($data, '~<body>(.*)</body>~msU');
	$body = $theBody[1][0];

	$matches = makeNeat_extract($data, '~<script(?:|\s*type="text/javascript"|)\s*src="(.*)"(?:|\s*type="text/javascript"|)\s*></script>~msU');
	foreach ($matches[0] as $key => $str) {
  	if (strpos($str, 'text/javascript') === false) {
  		$notJS[] = $matches[0][$key];
  		unset($matches[1][$key]);
  	}
	}
	$headJS = array();
	while (!empty($matches[1])) { // flush out the duplicates. Earliest wins
  	$file = array_pop($matches[1]);
  	$headJS[basename($file)] = $file;
	}
	$headJS = array_reverse($headJS);
	foreach ($headJS as $key => $headJSLine) {
  	$headJS[$key] = '<script type="text/javascript" src="' . trim($headJSLine) . '"></script>';
	}

	$matches = makeNeat_extract($body, '~<script(?:|\s*type="text/javascript"|)\s*src="(.*)"(?:|\s*type="text/javascript"|)\s*></script>~msU');
	foreach ($matches[0] as $key => $str) {
  	if (strpos($str, 'text/javascript') === false) {
  		$notJS[] = $matches[0][$key];
  		unset($matches[1][$key]);
  	}
	}
	$bodyJS = array();
	while (!empty($matches[1])) { // flush out the duplicates. Earliest wins
  	$file = array_pop($matches[1]);
  	$bodyJS[basename($file)] = $file;
	}
	$bodyJS = array_reverse($bodyJS);
	foreach ($bodyJS as $key => $bodyJSLine) {
  	$bodyJS[$key] = '<script type="text/javascript" src="' . trim($bodyJSLine) . '"></script>';
	}

	$matches = makeNeat_extract($data, '~<link\s*(?:|type="text/css"|)\s*rel="stylesheet"\s*href="(.*)"\s*(?:|type="text/css"|)(?:\s*)/>~msU');
	foreach ($matches[0] as $key => $str) {
  	if (strpos($str, 'text/css') === false) {
  		$notCSS[] = $matches[0][$key];
  		unset($matches[1][$key]);
  	}
	}
	$cs = array();
	while (!empty($matches[1])) { // flush out the duplicates. Earliest wins
  	$file = array_pop($matches[1]);
  	$cs[basename($file)] = $file;
	}
	$cs = array_reverse($cs);
	foreach ($cs as $key => $csLine) {
  	$cs[$key] = '<link type="text/css" rel="stylesheet" href="' . trim($csLine) . '" />';
	}

	$jsi = array();
	$matches = makeNeat_extract($data, '~<script type="text/javascript"(?:\s*)>(.*)</script>~msU');
	$inlinejs = $matches[1];
	if (!empty($inlinejs)) {
  	if (empty($jsi)) array_push($jsi, '// <!-- <![CDATA[');
  	foreach ($inlinejs as $somejs) {
  		$somejs = str_replace('// <!-- <![CDATA[', '', $somejs);
  		$somejs = str_replace('// ]]> -->', '', $somejs);
  		$somejs = trim($somejs);
  		$somejsbits = explode("\n",$somejs);
  		$somejsbits = array_map('trim',$somejsbits);
  		array_push($jsi, "/**/");
  		foreach($somejsbits as $somejsbit) {
    		if (!empty($somejsbit)) array_push($jsi, $somejsbit);
  		}
  	}
	}
	$matches = makeNeat_extract($body, '~<script type="text/javascript"(?:\s*)>(.*)</script>~msU');
	$inlinejs = $matches[1];
	if (!empty($inlinejs)) {
  	if (empty($jsi)) array_push($jsi, '// <!-- <![CDATA[');
  	foreach ($inlinejs as $somejs) {
  		$somejs = str_replace('// <!-- <![CDATA[', '', $somejs);
  		$somejs = str_replace('// ]]> -->', '', $somejs);
  		$somejs = trim($somejs);
  		$somejsbits = explode("\n",$somejs);
  		$somejsbits = array_map('trim',$somejsbits);
  		array_push($jsi, "/**/");
  		foreach($somejsbits as $somejsbit) {
    		if (!empty($somejsbit)) array_push($jsi, $somejsbit);
  		}
  	}
	}
	if (!empty($jsi)) array_push($jsi, '// ]]> -->');

	$matches = makeNeat_extract($data, '~<title>(.*)</title>~msU');
	$title = $matches[0];
	foreach($title as $key => $line) {
  	$line = trim($line);
  	if (empty($line)) {
  		unset($title[$key]);
  	} else {
  		$title[$key] = $line;
  	}
	}

	$matches = makeNeat_extract($data, '~<meta (.*)>~msU');
	$meta = $matches[0];
	foreach($meta as $key => $line) {
  	$line = trim($line);
  	if (empty($line)) {
  		unset($meta[$key]);
  	} else {
  		$meta[$key] = $line;
  	}
	}
	asort($meta);

	$matches = makeNeat_extract($data, '~<head>(.*)</head>~msU');
	$unprocessed = explode("\n",$matches[1][0]);
	foreach ($unprocessed as $key => $line) {
  	$line = trim($line);
  	if (empty($line)) {
  		unset($unprocessed[$key]);
  	} else {
  		$unprocessed[$key] = $line;
  	}
	}

	$body = explode("\n", trim(preg_replace('/\s+/', ' ', $body)));
	foreach ($body as $key => $line) {
  	$line = trim($line);
  	if (empty($line)) {
  		unset($body[$key]);
  	} else {
  		$body[$key] = $line;
  	}
	}
	$body = array_values($body);

	$tabSize = 1;
	tabLine("<head>", $tabSize++);
	if (!empty($title) && !empty($meta)) {
  	tabLine('<!-- ' . gettext('Title and Meta references') . " -->", $tabSize++);
  	if (!empty($title)) {
  		foreach ($title as $line) {
    		tabLine($line, $tabSize);
  		}
  	}
  	if (!empty($meta)) {
  		foreach ($meta as $line) {
    		tabLine($line, $tabSize);
  		}
  	}
  	tabLine('<!-- ' . gettext('end of Title and Meta references') . " -->", --$tabSize);
	}
	if (!empty($cs)) {
  	tabLine('<!-- ' . gettext('CSS references') . " -->", $tabSize++);
  	foreach ($cs as $line) {
  		tabLine($line, $tabSize);
  	}
  	tabLine('<!-- ' . gettext('end of CSS references') . " -->", --$tabSize);
	}
	if (!empty($notCSS)) {
  	tabLine('<!-- ' . gettext('other Link references') . " -->", $tabSize++);
  	foreach ($notCSS as $line) {
  		tabLine($line, $tabSize);
  	}
  	tabLine('<!-- ' . gettext('end of other Link references') . " -->", --$tabSize);
	}
	if (!empty($headJS)) {
  	tabLine('<!-- ' . gettext('external Javascript') . " -->", $tabSize++);
  	foreach ($headJS as $line) {
  		tabLine($line, $tabSize);
  	}
  	tabLine('<!-- ' . gettext('end of external Javascript') . " -->", --$tabSize);
	}
	if (!empty($notJS)) {
  	tabLine('<!-- ' . gettext('other Script') . " -->", $tabSize++);
  	foreach ($notJS as $line) {
  		tabLine($line, $tabSize);
  	}
  	tabLine('<!-- ' . gettext('end of other Script') . " -->", --$tabSize);
	}
	if (!empty($unprocessed)) {
  	tabLine('<!-- ' . gettext('unprocessed head items') . " -->", $tabSize++);
  	foreach ($unprocessed as $line) {
  		tabLine($line, $tabSize);
  	}
  	tabLine('<!-- ' . gettext('end of unprocessed head items') . " -->", --$tabSize);
	}
	tabLine("</head>", --$tabSize);
	tabLine("<body>", $tabSize++);
	tabBody($body, $tabSize);
	tabLine("</body>", --$tabSize);
	if (!empty($jsi)) {
  	tabLine('<!-- ' . gettext('in-line Javascript') . " -->", $tabSize++);
  	tabLine('<script type="text/javascript">', $tabSize++);
  	tabScript($jsi, $tabSize);
  	tabLine('</script>',--$tabSize);
  	tabLine('<!-- ' . gettext('end of in-line Javascript') . " -->", --$tabSize);
	}
	if (!empty($bodyJS)) {
  	tabLine('<!-- ' . gettext('external Javascript') . " -->", $tabSize++);
  	foreach ($bodyJS as $line) {
  		tabLine($line, $tabSize);
  	}
  	tabLine('<!-- ' . gettext('end of external Javascript') . " -->", --$tabSize);
	}
}

function makeNeat_extract(&$data, $pattern) {
	preg_match_all($pattern, $data, $matches);
	foreach ($matches[0] as $found) {
  	$data = trim(str_replace($found, '', $data));
	}
	return $matches;
}

function tabLine($theLine, $tabSize) {
	switch (strtolower(tabType)) {
  	case "space":
  		echo str_pad($theLine, strlen($theLine) + $tabSize * 2, " ", STR_PAD_LEFT) . "\n";
  		break;
  	case "tab":
  		echo str_pad($theLine, strlen($theLine) + $tabSize, "\t", STR_PAD_LEFT) . "\n";
  		break;
	}
}

function tabScript($theLines,$tabSize) {
	// Pass 1: Split lines on ';' or '}'
	$cnt1 = 0;
	$quoting = false;
	while ($cnt1 < count($theLines)) {
		$theLine = $theLines[$cnt1];
		$cnt2 = 0;
		while ($cnt2 < strlen($theLine)) {
			$char = $theLine[$cnt2];
			if ($char == '"' || $char == "'" || $quoting) {
				if (!$quoting) {
					$quote = $char;
					$quoting = true;
					$cnt2++;
				}
				do {
					if (($theLine[$cnt2 - 1] != '\\') && ($theLine[$cnt2] == $quote)) {
						$quoting = false;
						break;
					}
				} while ($cnt2++ < strlen($theLine));
			} else {
				if ($char == '{' && strlen($theLine) == 1) {
					$theLines[$cnt1 - 1] = $theLines[$cnt1 - 1] . " " . $theLines[$cnt1];
					unset($theLines[$cnt1]);
					$theLines = array_values($theLines);
					$cnt1--;
					break;
				}
				if (($char == ';') && $cnt2 < strlen($theLine) - 1) {
					$theLines[$cnt1] = trim(substr($theLine, 0, $cnt2+1));
					array_splice($theLines, $cnt1+1, 0, trim(substr($theLine, $cnt2+1)));
					break;
				}
			}
			$cnt2++;
		}
		$cnt1++;
	}
	//Pass 2: Output lines with tabs.
	foreach ($theLines as $theLine) {
  	if (substr($theLine,0,1) == "}" || substr($theLine,-1) == "}") $tabSize--;
  	tabLine($theLine, $tabSize);
  	if (substr($theLine,0,1) == "{" || substr($theLine,-1) == "{") $tabSize++;
	}
}

function tabBody($theLines,$tabSize) {
	// Pass 1: put all tags on their own line.
	$cnt1 = 0;
	$quoting = false;
	while ($cnt1 < count($theLines)) {
  	$theLine = $theLines[$cnt1];
  	$cnt2 = 0;
  	while ($cnt2 < strlen($theLine)) {
  		$char = $theLine[$cnt2];
  		if ($char == '"' || $char == "'" || $quoting) {
    		if (!$quoting) {
    			$quote = $char;
    			$quoting = true;
    			$cnt2++;
    		}
    		do {
    			if ($theLine[$cnt2++] == $quote) {
      			$quoting = false;
      			break;
    			}
    		} while ($cnt2 < strlen($theLine));
  		} else {
    		if ($char == ">" && $cnt2 + 1 < strlen($theLine)) {
    			$theLines[$cnt1] = trim(substr($theLine, 0, $cnt2 + 1));
    			array_splice($theLines, $cnt1+1, 0, trim(substr($theLine, $cnt2 + 1)));
    			$theLines = array_values($theLines);
    			break;
    		}
    		if ($char == "<" && $cnt2 > 0) {
    			$theLines[$cnt1] = trim(substr($theLine, 0, $cnt2));
    			array_splice($theLines, $cnt1+1, 0, trim(substr($theLine, $cnt2)));
    			$theLines = array_values($theLines);
    			break;
    		}
    		$cnt2++;
  		}
  	}
  	$cnt1++;
	}

	//Pass 2: Concatenate selected tags back to single line.
	$openTags = array("<a ","<h1","<h2","<h3","<h4","<h5","<h6","<strong","<label","<textarea","<td","<tr>","<tr ","<p>","<p ","<li>","<li ","<option");
	$closeTags = array("</a>","</h1>","</h2>","</h3>","</h4>","</h5>","</h6>","</strong>","</label>","</textarea>","</td>","</tr>","</tr>","</p>","</p>","</li>","</li>","</option>");
	foreach ($openTags as $key => $openTag) {
  	$closeTag = $closeTags[$key];
  	$cnt1 = 0;
  	$newLines = array();
  	while ($cnt1 < count($theLines)) {
  		$theLine = $theLines[$cnt1];
  		if (substr($theLine,0,strlen($openTag)) == $openTag) {
    		do {
    			if (($openTag == "<li>" || $openTag == "<li ") && substr($theLines[$cnt1 + 1], 0, 3) == "<ul") break;
    			$theLine = $theLine . $theLines[++$cnt1];
    		} while ($theLines[$cnt1] != $closeTag);
  		}
  		$newLines[] = $theLine;
  		$cnt1++;
  	}
  	$theLines = $newLines;
	}

	//Pass 3: Output lines with tabs.
	$openTags = array("<div","<table","<form","<span","<ul","<ol","<select");
	$closeTags = array("</div","</table","</form","</span","</ul","</ol","</select");
	foreach ($theLines as $theLine) {
  	foreach ($closeTags as $closeTag) {
  		if (substr($theLine, 0, strlen($closeTag)) == $closeTag) {
    		$tabSize--;
    		break;
  		}
  	}
  	tabLine($theLine, $tabSize);
  	foreach ($openTags as $openTag) {
  		if (substr($theLine, 0, strlen($openTag)) == $openTag) {
    		$tabSize++;
    		break;
  		}
  	}
	}
}

?>