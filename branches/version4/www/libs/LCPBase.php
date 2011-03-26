<?
// The source code packaged with this file is Free Software, Copyright (C) 2011 by
// Ricardo Galli <gallir at gallir dot com>.
// It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
// You can get copies of the licenses here:
// 		http://www.affero.org/oagpl.html
// AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".


class LCPBase {
	function hola() {
		echo "hola";
	}

	function to_html(&$string, $do_links = true) {
		global $globals;
		static $regexp = false, $p_hashtype = false, $p_do_links = false, $p_class = false;

		$string = nl2br($string, true);

		$class = get_class($this);

		// Check if the regexp must change, otherwise use the previous one
		if (! $regexp || $p_class != $class || $p_do_links != $do_links) {
			$p_class = $class; $p_do_links = $do_links;
			$regexp = '';

			$regexp .= '#[^\s\.\,\:\;\¡\!\)\-<>]{1,42}';

			$regexp .= '|\{([a-z]{3,10})\}';

			if ($class == 'Post') {
				$regexp .= '|@[^\s<>;:,\?\)\]\"\']+(?:,\d+){0,1}';
			} elseif ($class == 'Comment') {
				$regexp .= '|@[^\s<>;:,\?\)\]\"\']\w+';
			}

			if ($do_links) {
				$regexp .= '|(https{0,1}:\/\/)([^\s<>]{5,500})';
			}

			$regexp = '/([\s\(\[{}¡;,:¿]|^)('.$regexp.')/Smu';
		}
		return preg_replace_callback($regexp, array( &$this, 'to_html_cb'), $string);
	}

	function to_html_cb(&$matches) {
		global $globals;

		switch ($matches[2][0]) {
			case '#':
				if (get_class($this) == 'Comment' and preg_match('/^#\d+$/', $matches[2])) {
					$id = substr($matches[2], 1);
					if ($id > 0) {
						return $matches[1].'<a class="tooltip c:'.$this->link.'-'.$id.'" href="'.$this->link_permalink.'/000'.$id.'" rel="nofollow">#'.$id.'</a>';
					} else {
						return $matches[1].'<a class="tooltip l:'.$this->link.'" href="'.$this->link_permalink.'" rel="nofollow">#'.$id.'</a>';
					}
				} else {
					switch (get_class($this)) {
						case 'Link':
							$w = 'links';
							break;
						case 'Comment':
							$w = 'comments';
							break;
						case 'Post':
							$w = 'posts';
							break;
					}
					return $matches[1].'<a href="'.$globals['base_url'].'search.php?w='.$w.'&amp;q=%23'.substr($matches[2], 1).'&amp;o=date">#'.substr($matches[2], 1).'</a>';
				}
				break;

			case '@':
				$ref = substr($matches[2], 1);
				if (get_class($this) == 'Post') {
					$a = explode(',', $ref);
					if (count($a) > 1) {
						$user = $a[0];
						$id = ','.$a[1];
					} else {
						$user = $ref;
						$id = '';
					}
					$user_url = urlencode($user);
					return $matches[1]."<a class='tooltip p:$user_url$id-$this->date' href='".$globals['base_url']."backend/get_post_url.php?id=$user_url$id-".$this->date."'>@$user</a>";
				} else {
					return $matches[1]."<a class='tooltip u:$ref' href='".get_user_uri($ref)."'>@$ref</a>";
				}
				break;

			case 'h':
				$suffix = $extra = '';
				if (substr($matches[4], -1) == ')' && strrchr($matches[4], '(') === false) {
					$matches[4] = substr($matches[4], 0, -1);
					$suffix = ')';
				}
				if (preg_match('/\.(jpg|gif|png)$/S', $matches[4])) $extra = 'class="fancybox"';
				return $matches[1].'<a '.$extra.' href="'.$matches[3].$matches[4].'" title="'.$matches[4].'" rel="nofollow">'.substr($matches[4], 0, 70).'</a>'.$suffix;

			case '{':
				$m = array($matches[2], $matches[3]);
				return $matches[1].put_smileys_callback($m);

			case '_':
				return $matches[1].'<i>'.substr($matches[2], 1, -1).'</i>';
			case '*':
				return $matches[1].'<b>'.substr($matches[2], 1, -1).'</b>';
			case '-':
				return $matches[1].'<strike>'.substr($matches[2], 1, -1).'</strike>';
		}
		return $matches[1].$matches[2];
	}

}

?>
