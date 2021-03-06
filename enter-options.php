<?php

/* 
 * FUPS: Forum user-post scraper. An extensible PHP framework for scraping and
 * outputting the posts of a specified user from a specified forum/board
 * running supported forum software. Can be run as either a web app or a
 * commandline script.
 *
 * Copyright (C) 2013-2014 Laird Shaw.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/* File       : enter-options.php.
 * Description: The web app page that allows the user to enter their FUPS
 *              options. Varies depending on forum type.
 */

require_once __DIR__.'/common.php';
require_once __DIR__.'/classes/CFUPSBase.php';

$forum_type = isset($_GET['forum_type']) ? $_GET['forum_type'] : '';
$errmsg = '';
if (!$forum_type) $errmsg = 'Forum type not provided';
else {
	if (!($canonical_forum_type = FUPSBase::get_canonical_forum_type_s($forum_type))) {
		$errmsg = 'Unsupported forum type: "'.$forum_type.'"';
	}
}

$head_extra = '';

if ($errmsg) {
	$head_extra = '<meta  http-equiv="refresh" content="5; url=." />';
} else {
	$head_extra = <<<EOF
<style type="text/css">
#table_fups_enter_options {
	border-collapse: collapse;
}
#table_fups_enter_options td {
	vertical-align: top;
	border-top: thin solid black;
	border-bottom: thin solid black;
}
#table_fups_enter_options tr:last-of-type td {
	border-bottom: none;
}
@media only screen and (max-width: 900px) {
	#table_fups_enter_options td {
		display: block;
		border-top: none;
		border-bottom: none;
	}
	.fups_opt_desc {
		margin-bottom: 15px;
	}
	.fups_opt_label {
		font-style: italic;
	}
}
</style>
EOF;
}

$page = substr(__FILE__, strlen(FUPS_INC_ROOT));
fups_output_page_start($page, $errmsg ? $errmsg : 'FUPS options entry for '.$canonical_forum_type.' forums', $errmsg ? $errmsg : 'Scrape posts made under a particular username from a '.$canonical_forum_type.' forum.', $head_extra);
?>
			<ul class="fups_listmin">
				<li><a href="<?php echo $fups_url_homepage; ?>">&lt;&lt; Back to the FUPS homepage</a></li>
			</ul>

			<h2>FUPS: Forum user-post scraper</h2>
<?php
$script = '';

if ($errmsg) {
	echo '<p style="border: thin solid black; background-color: red;">Error: '.$errmsg.'. Redirecting you to <a href=".">forum selection page</a> in 5 seconds.</p>';
} else {
	global $fups_url_run, $fups_url_ajax_test;

	require_once __DIR__.'/classes/C'.$canonical_forum_type.'.php';

	$forum_class = $canonical_forum_type.'FUPS';
	$forum_obj = new $forum_class(null, null, true);
	$settings_arr = $forum_obj->get_settings_array();
?>
			<h3>Enter settings for your <?php echo $canonical_forum_type; ?> forum</h3>

			<p>To retrieve your posts: fill in the settings below, optionally after reading the questions and answers below the settings form, then click "Retrieve posts!". A status page will appear, updating progress automatically in a status box. When scraping is complete, the results file(s) will be linked to.</p>

			<p>Unconditionally required fields are single-asterisked. Double-asterisked fields indicate that <em>at least one of</em> these fields is required.</p>

			<form id="mainform" method="post" action="<?php echo $fups_url_run; ?>">
			<table id="table_fups_enter_options">
<?php
	foreach ($settings_arr as $key => $settings) {
		if (isset($settings['hidden']) && $settings['hidden']) continue;

		$is_checkbox = isset($settings['type']) && $settings['type'] == 'checkbox';
?>
				<tr><td class="fups_opt_label"><label for="<?php echo $key; ?>"><?php echo ($settings['required'] ? '<strong>*</strong>' : ($settings['one_of_required'] ? '<strong>**</strong>' : '')).$settings['label']; ?></label></td><td class="fups_opt_input"><input type="<?php echo isset($settings['type']) ? $settings['type'] : 'text'; ?>" name="<?php echo $key; ?>" id="<?php echo $key; ?>" <?php echo $is_checkbox ? ($settings['default'] == true ? 'checked="checked"' : '') : "value=\"{$settings['default']}\""; if (isset($settings['style'])) echo ' style="'.$settings['style'].'" '; if (isset($settings['readonly']) && $settings['readonly']) echo ' readonly="readonly"'; ?>/></td><td class="fups_opt_desc"><?php echo $settings['description']; ?></td></tr>

<?php
	}
?>
				<tr><td><input type="submit" value="Retrieve posts!" /></td><td></td><td></td></tr>
			</table>
			</form>

			<h3>Answers to possible questions</h3>
<?php
	$qanda = $forum_class::get_qanda_s();
	if (isset($fups_extra_qanda)) $qanda = array_merge($qanda, $fups_extra_qanda);
	foreach ($qanda as $id => $qa) {
		echo '			<h4 id="'.$id.'">'.$qa['q'].'</h4>'."\n";
		echo '			';
		if ($qa['a'][0] != '<') echo '<p>';
		echo $qa['a'];
		if ($qa['a'][0] != '<') echo '</p>';
		echo "\n";
	}

	if (!$errmsg) {
?>
	<script type="text/javascript">
		//<![CDATA[
		var xhr;
		if (!xhr) try {
			if (window.XMLHttpRequest) {
				xhr = new XMLHttpRequest();
			} else if (window.ActiveXObject) {
				xhr = new ActiveXObject('Microsoft.XMLHTTP');
			}
		} catch (e) {
			xhr = null;
		}
		if (xhr) {
			try {
				var url = '<?php echo $fups_url_ajax_test; ?>';
				xhr.open('GET', url, true);
				xhr.onreadystatechange = function () {
					try {
						if (xhr.readyState == 4 && xhr.status == 200 && xhr.responseText == 'Tested OK.') {
							document.getElementById('mainform').action += '?ajax=yes';
						}
					} catch (e) {}
				}
				xhr.send(null);
			} catch (e) {}
		}
		//]]>
	</script>
<?php
	}
}

fups_output_page_end($page);

?>
