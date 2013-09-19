<?php
/**
 * Syntax highlighting class
 * @author Emdek <http://emdek.pl>
 * @version v0.9.05
 * @date 2010-08-31 12:20:46
 * @license LGPL
 */

class SyntaxHighlight
{

/**
 * Highlights string for given type
 * @param string $code
 * @param string $mode
 * @return string
 */

	static public function highlightString($code, $mode)
	{
		$method = 'highlightMode'.ucfirst(strtolower($mode));

		if (!method_exists('SyntaxHighlight', $method))
		{
			$method = 'highlightModeTxt';
		}

		$code = str_replace(array('&', '<', '>', "\r\n", "\r"), array('&amp;', '&lt;', '&gt;', "\n", "\n"), $code);

		return self::$method($code);
	}

/**
 * Highlights file for given type
 * @param string $code
 * @param string $mode
 * @return string
 * @throws Exception
 */

	static public function highlightFile($fileName, $mode)
	{
		if (!file_exists($fileName))
		{
			throw new Exception('File does not exists!');
		}

		return self::highlightString(file_get_contents($fileName), $mode);
	}

/**
 * Additional formatting for parsed code
 * @param string $code
 * @param boolean $highlight
 * @param string $class
 * @return string
 */

	static public function highlightFormat($code, $highlight = 0, $class = '')
	{
		if ($highlight)
		{
			$code = preg_replace(
		array(
			'#(\t)#',
			'#( |\t)( |\t)*$#m',
			),
		array(
			'<span class="whitespace">\\1</span>',
			'<span class="whitespace">\\1</span>\\2',
			),
		$code
			);

			$code = str_replace(
		array(
			'<span class="punctuation">{</span>',
			'<span class="punctuation">}</span>',
			'<span class="punctuation">(</span>',
			'<span class="punctuation">)</span>',
			'<span class="punctuation">[</span>',
			'<span class="punctuation">]</span>',
			),
		array(
			'<span><span class="punctuation switcher" onmouseover="this.parentNode.className = \'highlightrange\'" onmouseout="this.parentNode.className = \'\'" onclick="this.nextSibling.style.visibility = ((this.nextSibling.style.visibility == \'hidden\') ? \'visible\' : \'hidden\')">{</span><span>',
			'<span class="punctuation" onmouseover="this.parentNode.parentNode.className = \'highlightrange\'" onmouseout="this.parentNode.parentNode.className = \'\'">}</span></span></span>',
			'<span><span class="punctuation" onmouseover="this.parentNode.className = \'highlightrange\'" onmouseout="this.parentNode.className = \'\'">(</span>',
			'<span class="punctuation" onmouseover="this.parentNode.className = \'highlightrange\'" onmouseout="this.parentNode.className = \'\'">)</span></span>',
			'<span><span class="punctuation" onmouseover="this.parentNode.className = \'highlightrange\'" onmouseout="this.parentNode.className = \'\'">[</span>',
			'<span class="punctuation" onmouseover="this.parentNode.className = \'highlightrange\'" onmouseout="this.parentNode.className = \'\'">]</span></span>',
			),
		$code
			);
		}

		$numbers = '';
		$lines = (substr_count($code, ((substr_count($code, "\r") > substr_count($code, "\n")) ? "\r" : "\n")) + 2);

		for ($i = 1; $i < $lines; ++$i)
		{
			$numbers.= $i.'
';
		}

		return '<div class="highlight">
<pre class="numbers">'.$numbers.'</pre>
<pre class="code'.($class ? ' '.$class : '').'">'.$code.'
</pre>
</div>
';
	}

/**
 * Returns list of supported highlighting modes
 * @return array
 */

	static public function highlightModes()
	{
		return array(
	'txt' => 'Plain text',
	'c' => 'C',
	'cpp' => 'C++',
	'cs' => 'C#',
	'css' => 'CSS',
	'html' => '(X)HTML',
	'ini' => 'INI',
	'java' => 'Java',
	'javadoc' => 'JavaDoc',
	'javascript' => 'JavaScript',
	'perl' => 'Perl',
	'php' => 'PHP',
	'phpdoc' => 'PHPDoc',
	'po' => 'PO(T)',
	'python' => 'Python',
	'sql' => 'SQL',
	'xml' => 'XML',
	);
	}

/**
 * Removes highlighting
 * @param string $code
 * @param boolean $entities
 * @return string
 */

	static private function highlightClean($code, $entities = 0)
	{
		$code = preg_replace('#<(?:span|a href=".*") class="(?:[a-z]*)">(.*)</(?:span|a)>#sU', '\\1', $code);

		if ($entities)
		{
			$code = str_replace(array('&amp;', '&lt;', '&gt;'), array('&', '<', '>'), $code);
		}

		return $code;
	}

/**
 * Highlight for C
 * @param string $code
 * @return string
 */

	static private function highlightModeC($code)
	{
		return self::highlightModeCpp($code);
	}

/**
 * Highlight for C++
 * @param string $code
 * @return string
 */

	static private function highlightModeCpp($code)
	{
		$buffer = $output = $charOld = '';
		$notParse = $comment = $value = $finish = 0;

		while (!$finish)
		{
			if (strlen($code) == 0)
			{
				$finish = 1;
			}
			else
			{
				$char = substr($code, 0, 1);
			}

			if ($char == '/' && $charOld == '/')
			{
				$isComment = 1;
			}
			else if ($char == '*' && $charOld == '/')
			{
				$isComment = 2;
			}
			else
			{
				$isComment = 0;
			}

			if ($finish || (!$notParse && ($isComment || (in_array($char, array('\'', '"')) && ($charOld != '\\' || substr($buffer, -2) == '\\\\')))))
			{
				if ($isComment)
				{
					$buffer = substr($buffer, 0, -1);
				}

				$output.= preg_replace(
		array(
			'#\b(asm|class|const_cast|dynamic_cast|enum|explicit|export|extern|false|friend|inline|namespace|new|NULL|operator|private|protected|public|reinterpret_cast|restrict|sizeof|static_cast|struct|template|this|true|typedef|typeid|type_info|typename|union|using|virtual)\b#s',
			'#\b(as|case|catch|default|if|else|elseif|do|goto|for|break|continue|switch|throw|try|delete|return|while)\b#s',
			'#^(\#\s*(?:endif|if (?:def|ndef)?(?=\s+\S)|(?:el(?:se|if)|include(?:_next)?|define|undef|line|error|warning|pragma|static)|define.*|[0-9]+))#im',
			'#\b(auto|bool|const|double|float|long|mutable|register|short|(?:un)?signed|void|volatile|(?:w|u)?char(?:_t)?|u?int(?:(?:8|16|32|64)_t)?|_Imaginary|_Complex|_Bool)\b#s',
			'#(?<!">|[a-z-_])((?:-\s*)?(?:(?:\d+\.)?\d+))\b#si',
			'#(?<!class|">|"|span)(:|;|-|\||\+|=|\*|!|~|\.|,|\(|\)|\/|@|\%|&lt;|&gt;|&amp;|\{|\}|\[|\])(?!/?span)#si',
			),
		array(
			'<span class="keyword">\\1</span>',
			'<span class="control">\\1</span>',
			'<span class="preprocessor">\\1</span>',
			'<span class="datatype">\\1</span>',
			'<span class="number">\\1</span>',
			'<span class="punctuation">\\1</span>',
			),
		$buffer
		);
				if ($isComment)
				{
					$comment = $isComment;
				}
				else
				{
					$value = $char;
				}

				$notParse = 1;
				$buffer = ($isComment ? $charOld : '').$char;
			}
			else if ($notParse && (($value && $char == $value && ($charOld != '\\' || substr($buffer, -2) == '\\\\')) || ($comment && (($comment == 1 && $char == "\n") || ($char == '/' && $charOld == '*' && substr($buffer, -2, 1) != '/')))))
			{
				$output.= '<span class="'.($comment ? 'comment' : 'value').'">'.($value ? $buffer.$char : preg_replace('#\b(FIXME|NOTICE|NOTE|TODO|WARNING)\b#i', '<span class="notice">\\1</span>', $buffer.(($char == "\n") ? '':$char))).'</span>';
				$buffer = (($char == "\n") ? $char : '');
				$notParse = $comment = $value = 0;
			}
			else
			{
				$buffer.= $char;
			}

			$code = substr($code, 1);
			$charOld = $char;
		}

		return $output;
	}

/**
 * Highlight for C#
 * @param string $code
 * @return string
 */

	static private function highlightModeCs($code)
	{
		$buffer = $output = $charOld = '';
		$notParse = $comment = $value = $finish = 0;

		while (!$finish)
		{
			if (strlen($code) == 0)
			{
				$finish = 1;
			}
			else
			{
				$char = substr($code, 0, 1);
			}

			if ($char == '/' && $charOld == '/')
			{
				$isComment = 1;
			}
			else if ($char == '*' && $charOld == '/')
			{
				$isComment = 2;
			}
			else
			{
				$isComment = 0;
			}

			if ($finish || (!$notParse && ($isComment || (in_array($char, array('\'', '"')) && ($charOld != '\\' || substr($buffer, -2) == '\\\\')))))
			{
				if ($isComment)
				{
					$buffer = substr($buffer, 0, -1);
				}

				$output.= preg_replace(
		array(
			'#\b(abstract|base|class|checked|delegate|enum|event|explicit|extern|false|finally|fixed|implicit|interface|internal|is|lock|namespace|new|null|operator|out|override|params|private|protected|public|readonly|ref|sealed|sizeof|stackalloc|static|struct|this|true|typeof|unchecked|unsafe|virtual)\b#s',
			'#(using)(\s+)(.+);#m',
			'#(?<!">)\b\.([\w_-]+)\b#si',
			'#\b(as|case|catch|default|if|else|elseif|do|goto|for(?:each)?|break|continue|switch|throw|try|delete|return|while)\b#s',
			'#^(\#(?:else|elif|(?:end)?if|in|define|undef|warning|error|line))#im',
			'#\b(bool|char|const|decimal|double|float|object|u?int|u?short|u?long|s?byte|string|void)\b#s',
			'#(?<!">|[a-z-_])((?:-\s*)?(?:(?:\d+\.)?\d+))\b#si',
			'#(?<!class|">|"|span)(:|;|-|\||\+|=|\*|!|~|\.|,|\(|\)|\/|@|\%|&lt;|&gt;|&amp;|\{|\}|\[|\])(?!/?span)#si',
			),
		array(
			'<span class="keyword">\\1</span>',
			'<span class="keyword">\\1</span>\\2<span class="package">\\3</span>;',
			'.<span class="method">\\1</span>',
			'<span class="control">\\1</span>',
			'<span class="preprocessor">\\1</span>',
			'<span class="datatype">\\1</span>',
			'<span class="number">\\1</span>',
			'<span class="punctuation">\\1</span>',
			),
		$buffer
		);
				if ($isComment)
				{
					$comment = $isComment;
				}
				else
				{
					$value = $char;
				}

				$notParse = 1;
				$buffer = ($isComment ? $charOld : '').$char;
			}
			else if ($notParse && (($value && $char == $value && ($charOld != '\\' || substr($buffer, -2) == '\\\\')) || ($comment && (($comment == 1 && $char == "\n") || ($char == '/' && $charOld == '*' && substr($buffer, -2, 1) != '/')))))
			{
				$output.= '<span class="'.($comment ? 'comment' : 'value').'">'.($value ? $buffer.$char : preg_replace('#\b(FIXME|NOTICE|NOTE|TODO|WARNING)\b#i', '<span class="notice">\\1</span>', $buffer.(($char == "\n") ? '':$char))).'</span>';
				$buffer = (($char == "\n") ? $char : '');
				$notParse = $comment = $value = 0;
			}
			else
			{
				$buffer.= $char;
			}

			$code = substr($code, 1);
			$charOld = $char;
		}

		return $output;
	}

/**
 * Highlight for CSS
 * @param string $code
 * @return string
 */

	static private function highlightModeCss($code)
	{
		$buffer = $output = $charOld = '';
		$notParse = $braces = $finish = 0;

		while (!$finish)
		{
			if (strlen($code) == 0)
			{
				$finish = 1;
			}
			else
			{
				$char = substr($code, 0, 1);
			}

			if ($finish || (!$notParse && (in_array($char, array('\'', '"')) || ($char == '*' && $charOld == '/'))))
			{
				$notParse = 1;
				$valueStart = 1;
			}
			else
			{
				$valueStart = 0;
			}

			if ((!$notParse || ($notParse && $valueStart)) && !$braces && ($valueStart || $char == '{'))
			{
				if (!$valueStart)
				{
					$braces = 1;
				}

				$output.= preg_replace(
		array(
			'#(\.[a-z]\w*)#si',
			'#(?<=\n|\r|\}|,\s|,)(\#[a-z]\w*)#si',
			'#:(link|visited|active|hover|focus|lang|first-child|first-line|first-letter|before|after)#si',
			'#(?<!class|">|"|span)(:|~|\*|=|,|\(|\)|\/|&lt;|&gt;|\[|\])(?!/?span)#si',
			),
		array(
			'<span class="class">\\1</span>',
			'<span class="identify">\\1</span>',
			':<span class="pseudoclass">\\1</span>',
			'<span class="punctuation">\\1</span>',
			),
		$buffer
		).($valueStart ? '' : '<span class="punctuation">{</span><span class="value">');

				$buffer = ($valueStart ? $char : '');
			}
			else if ((!$notParse || ($notParse && $valueStart)) && $braces && ($valueStart || $char == '}'))
			{
				if (!$valueStart)
				{
					$braces = 0;
				}

				$output.= preg_replace(
		array(
			'#((?<=^|;|\s)[a-z\-]+(?=:)|(?:!important))#si',
			'#(?<!">)((?:(?:\+|-)\s*)?(?:\#\w{6}|\#\w{3}|(?:\d+\.)?\d+(?:px|pt|pc|ex|em|in|cm|mm|deg|grad|rad|ms|s|k?hz|\%)?))#si',
			'#(?<!class|">|"|span)(:|;|=|\*|,|\(|\)|\/|&lt;|&gt;)(?!/?span)#si',
			),
		array(
			'<span class="keyword">\\1</span>',
			'<span class="number">\\1</span>',
			'<span class="punctuation">\\1</span>',
			),
		$buffer
		).($valueStart ? $char : '</span><span class="punctuation">}</span>');

				$buffer = '';
			}
			else if ($notParse && (in_array($char, array('\'', '"')) || ($char == '/' && $charOld == '*')))
			{
				$output.= '<span class="'.((($char == '*' && $charOld == '/')) ? 'comment' : 'value').'">'.$buffer.$char.'</span>';
				$buffer = '';
				$notParse = 0;
			}
			else
			{
				$buffer.= $char;
			}

			$code = substr($code, 1);
			$charOld = $char;
		}

		if ($finish && $braces)
		{
			$output.= '</span>';
		}

		return $output;
	}

/**
 * Highlight for(X)HTML
 * @param string $code
 * @return string
 */

	static private function highlightModeHtml($code)
	{
		$buffer = $output = $charOld = '';
		$notParse = $comment = $value = $finish = 0;

		while (!$finish)
		{
			if (strlen($code) == 0)
			{
				$finish = 1;
			}
			else
			{
				$char = substr($code, 0, 1);
			}

			$isComment = ($char == '-' && substr($buffer, -6) == '&lt;!-');

			if ($finish || (!$notParse && (in_array($char, array('\'', '"')) || $isComment)))
			{
				if ($isComment)
				{
					$buffer = substr($buffer, 0, -6);
				}

				$output.= preg_replace(
		array(
			'#(&lt;\?xml .*\?&gt;|&lt;!DOCTYPE html.*&gt;)+#siU',
			'#&lt;(.*)&gt;#iU',
			'#&lt;(\w+)(\s+)([a-z-:]+=)$#i',
			'#(?<!span)(\s+)([a-z-:]+=)$#i',
			'#(&amp;\w{2,5};)#iU',
			),
		array(
			'<span class="doctype">\\1</span>',
			'&lt;<span class="tag">\\1</span>&gt;',
			'&lt;<span class="tag">\\1</span>\\2<span class="attribute">\\3</span>',
			'\\1<span class="attribute">\\2</span>',
			'<span class="entity">\\1</span>',
			),
		$buffer
		);
				$notParse = 1;

				if ($isComment)
				{
					$comment = 1;
				}
				else
				{
					$value = $char;
				}

				$buffer = ($isComment ? '&lt;!-' : '').$char;
			}
			else if ($notParse && (($value && $char == $value) || ($comment && $char == ';' && substr($buffer, -5) == '--&gt')))
			{
				$buffer = $buffer.$char;
				$output.= '<span class="'.($comment ? 'comment' : 'value').'">'.($value ? $buffer : preg_replace('#\b(FIXME|NOTICE|NOTE|TODO|WARNING)\b#i', '<span class="notice">\\1</span>', $buffer)).'</span>';
				$buffer = '';
				$notParse = $comment = $value = 0;
			}
			else
			{
				$buffer.= $char;
			}

			$code = substr($code, 1);
			$charOld = $char;
		}

		$output = preg_replace(
		array(
			'#(&lt;<span class="tag">style</span>.*&gt;)(.*)(&lt;<span class="tag">/style</span>&gt;)#sieU',
			'#(&lt;<span class="tag">script</span>.*&gt;)(.*)(&lt;<span class="tag">/script</span>&gt;)#sieU',
			'#(&lt;\?(?:php)?(?!xml)(?U).+\?&gt;)#sie',
			),
		array(
			'\'<span class="borders">\'.stripslashes(\'\\1\').\'</span>\'.self::highlightModeCss(self::highlightClean(\'\\2\')).\'<span class="borders">\'.stripslashes(\'\\3\').\'</span>\'',
			'\'<span class="borders">\'.stripslashes(\'\\1\').\'</span>\'.self::highlightModeJavaScript(self::highlightClean(\'\\2\')).\'<span class="borders">\'.stripslashes(\'\\3\').\'</span>\'',
			'self::highlightModePhp(self::highlightClean(\'\\1\'))',
			),
		$output
		);

		return $output;
	}

/**
 * Highlight for INI
 * @param string $code
 * @return string
 */

	static private function highlightModeIni($code)
	{
		$buffer = $output = $charOld = '';
		$notParse = $comment = $value = $finish = 0;

		while (!$finish)
		{
			if (strlen($code) == 0)
			{
				$finish = 1;
			}
			else
			{
				$char = substr($code, 0, 1);
			}

			if ($char == '#' || $char == ';')
			{
				$isComment = 1;
			}
			else
			{
				$isComment = 0;
			}

			if ($finish || (!$notParse && ($isComment || in_array($char, array('\'', '"')))))
			{
				$output.= preg_replace(
		array(
			'#^(\[\w+\])$#siUm',
			'#^([^\#;]+)(?<!<span class)= (.+)$#siUm',
			'#^\s*([\#;].*)$#siUm',
			),
		array(
			'<span class="tag">\\1</span>',
			'<span class="keyword">\\1</span><span class="punctuation">=</span><span class="value">\\2</span>',
			'<span class="comment">\\1</span>',
			),
		$buffer
		);
				if ($isComment)
				{
					$comment = 1;
				}
				else
				{
					$value = $char;
				}

				$notParse = 1;
				$buffer = (($isComment && $char != '#') ? $charOld : '').$char;
			}
			else if ($notParse && (($value && $char == $value) || ($comment && $char == "\n")))
			{
				$output.= '<span class="'.($comment ? 'comment' : 'value').'">'.($value ? $buffer.$char : preg_replace('#\b(FIXME|NOTICE|NOTE|TODO|WARNING)\b#i', '<span class="notice">\\1</span>', $buffer.(($char == "\n") ? '' : $char))).'</span>';
				$buffer = (($char == "\n") ? $char : '');
				$notParse = $comment = $value = 0;
			}
			else
			{
				$buffer.= $char;
			}

			$code = substr($code, 1);
			$charOld = $char;
		}

		return $output;
	}

/**
 * Highlight for Java
 * @param string $code
 * @return string
 */

	static private function highlightModeJava($code)
	{
		$buffer = $output = $charOld = '';
		$notParse = $comment = $value = $documentation = $finish = 0;

		while (!$finish)
		{
			if (strlen($code) == 0)
			{
				$finish = 1;
			}
			else
			{
				$char = substr($code, 0, 1);
			}

			if ($char == '/' && $charOld == '/')
			{
				$isComment = 1;
			}
			else if ($char == '*' && $charOld == '/')
			{
				$isComment = 2;
			}
			else
			{
				$isComment = 0;
			}

			if ($finish || (!$notParse && ($isComment || (in_array($char, array('\'', '"')) && ($charOld != '\\' || substr($buffer, -2) == '\\\\')))))
			{
				if ($isComment)
				{
					$buffer = substr($buffer, 0, -1);
				}

				$output.= preg_replace(
		array(
			'#\b(abstract|class|continue|enum|extends|false|finally|implements|instanceof|@?interface|native|new|null|private|protected|public|super|static|strictfp|synchronized|this|throws|transient|true|volatile)\b#s',
			'#^(package|import)(\s+)(.+);#m',
			'#(?<!">)\b\.([\w_-]+)\b#si',
			'#\b(break|case|catch|continue|default|do|else|for|goto|if|return|throw|try|while)\b#s',
			'#\b(boolean|byte|char|const|double|final|float|int|long|short|void)\b#s',
			'#(?<!">|[a-z-_])((?:-\s*)?(?:(?:\d+\.)?\d+))\b#si',
			'#(?<!class|">|"|span)(:|;|-|\||\+|=|\*|!|~|\.|,|\(|\)|\/|@|\%|&lt;|&gt;|&amp;|\{|\}|\[|\])(?!/?span)#si',
			),
		array(
			'<span class="keyword">\\1</span>',
			'<span class="keyword">\\1</span>\\2<span class="package">\\3</span>;',
			'.<span class="method">\\1</span>',
			'<span class="control">\\1</span>',
			'<span class="datatype">\\1</span>',
			'<span class="number">\\1</span>',
			'<span class="punctuation">\\1</span>',
			),
		$buffer
		);
				if ($isComment)
				{
					$comment = $isComment;

					if ($isComment == 2 && substr($code, 1, 1) == '*')
					{
						$documentation = 1;
					}
				}
				else
				{
					$value = $char;
				}

				$notParse = 1;
				$buffer = ($isComment ? $charOld : '').$char;
			}
			else if ($notParse && (($value && $char == $value && ($charOld != '\\' || substr($buffer, -2) == '\\\\')) || ($comment && (($comment == 1 && $char == "\n") || ($char == '/' && $charOld == '*' && substr($buffer, -2, 1) != '/')))))
			{
				$buffer = $buffer.(($char == "\n") ? '' : $char);

				if ($documentation)
				{
					$buffer = self::highlightModeJavaDoc($buffer);
				}

				$output.= '<span class="'.($comment ? ($documentation ? 'documentation' : 'comment') : 'value').'">'.($value ? $buffer : preg_replace('#\b(FIXME|NOTICE|NOTE|TODO|WARNING)\b#i', '<span class="notice">\\1</span>', $buffer)).'</span>';
				$buffer = (($char == "\n") ? $char : '');
				$notParse = $comment = $value = $documentation = 0;
			}
			else
			{
				$buffer.= $char;
			}

			$code = substr($code, 1);
			$charOld = $char;
		}

		return $output;
	}

/**
 * Highlight for JavaDoc
 * @param string $code
 * @return string
 */

	static private function highlightModeJavaDoc($code)
	{
		return (preg_replace(
		array(
			'#(\s*\*?\s*)(@(?:param|return|throws))(\s+)(\w+)#i',
			'#(\s*\*?\s*)(@(?:author|version|see|since|serial(?:Field|Data)?))(\s+)(.+)$#im',
			'#(\s*\*?\s*)(@deprecated)#i',
			'#(\s*\*?\s*)\{(@link)\}(\s+)(.+)#i',
			),
		array(
			'\\1<span class="documentationtag">\\2</span>\\3<span class="value">\\4</span>',
			'\\1<span class="documentationtag">\\2</span>\\3<span class="value">\\4</span>',
			'\\1<span class="documentationtag">\\2</span>',
			'\\1{<span class="documentationtag">\\2</span>\\3<span class="value">\\4</span>}',
			),
		$code)
		);
	}

/**
 * Highlight for JavaScript
 * @param string $code
 * @return string
 */

	static private function highlightModeJavaScript($code)
	{
		$buffer = $output = $charOld = '';
		$notParse = $comment = $value = $finish = 0;

		while (!$finish)
		{
			if (strlen($code) == 0)
			{
				$finish = 1;
				$char = 0;
			}
			else
			{
				$char = substr($code, 0, 1);
			}

			if ($char == '#' || ($char == '/' && $charOld == '/') || ($char == '*' && $charOld == '/'))
			{
				$isComment = 1;
			}
			else
			{
				$isComment = 0;
			}

			if ($finish || (!$notParse && ($isComment || (in_array($char, array('\'', '"')) && ($charOld != '\\' || substr($buffer, -2) == '\\\\')))))
			{
				if ($isComment && $char != '#')
				{
					$buffer = substr($buffer, 0, -1);
				}

				$output.= preg_replace(
		array(
			'#\b(in|with|try|catch|finally|new|var|function|delete|true|false|void|throw|typeof|const)\b#s',
			'#\b(onabort|onblur|onchange|onclick|onerror|onfocus|onkeypress|onkeydown|onkeyup|onload|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|onreset|onselect|onsubmit|onunload)\b#si',
			'#\b(as|case|default|if|else|elseif|while|do|for|foreach|break|continue|switch|return)\b#s',
			'#\b(Anchor|Applet|Area|Array|Boolean|Button|Checkbox|Date|document|window|Image|FileUpload|Form|Frame|Function|Hidden|Link|MimeType|Math|Max|Min|Layer|navigator|Object|Password|Plugin|Radio|RegExp|Reset|Screen|Select|String|Text|Textarea|this|Window)\b#s',
			'#(?<=\.)(above|action|alinkColor|alert|anchor|anchors|appCodeName|applets|apply|appName|appVersion|argument|arguments|arity|availHeight|availWidth|back|background|below|bgColor|border|big|blink|blur|bold|border|call|caller|charAt|charCodeAt|checked|clearInterval|clearTimeout|click|clip|close|closed|colorDepth|complete|compile|constructor|confirm|cookie|current|cursor|data|defaultChecked|defaultSelected|defaultStatus|defaultValue|description|disableExternalCapture|domain|elements|embeds|enabledPlugin|enableExternalCapture|encoding|eval|exec|fgColor|filename|find|fixed|focus|fontcolor|fontsize|form|forms|formName|forward|frames|fromCharCode|getDate|getDay|getHours|getMiliseconds|getMinutes|getMonth|getSeconds|getSelection|getTime|getTimezoneOffset|getUTCDate|getUTCDay|getUTCFullYear|getUTCHours|getUTCMilliseconds|getUTCMinutes|getUTCMonth|getUTCSeconds|getYear|global|go|hash|height|history|home|host|hostname|href|hspace|ignoreCase|images|index(?:Of)?|innerHeight|innerWidth|input|italics|javaEnabled|join|language|lastIndex|lastIndexOf|lastModified|lastParen|layers|layerX|layerY|left|leftContext|length|link|linkColor|links|location|locationbar|load|lowsrc|match|MAX_VALUE|menubar|method|mimeTypes|MIN_VALUE|modifiers|moveAbove|moveBelow|moveBy|moveTo|moveToAbsolute|multiline|name|NaN|NEGATIVE_INFINITY|negative_infinity|next|open|opener|options|outerHeight|outerWidth|pageX|pageY|pageXoffset|pageYoffset|parent|parse|pathname|personalbar|pixelDepth|platform|plugins|pop|port|POSITIVE_INFINITY|positive_infinity|preference|previous|print|prompt|protocol|prototype|push|referrer|refresh|releaseEvents|reload|replace|reset|resizeBy|resizeTo|reverse|rightContext|screenX|screenY|scroll|scrollbar|scrollBy|scrollTo|search|select|selected|selectedIndex|self|setDate|setHours|setInterval|setMinutes|setMonth|setSeconds|setTime(?:out)?|setUTCDate|setUTCDay|setUTCFullYear|setUTCHours|setUTCMilliseconds|setUTCMinutes|setUTCMonth|setUTCSeconds|setYear|shift|siblingAbove|siblingBelow|small|sort|source|splice|split|src|status|statusbar|strike|sub(?:str)?|submit|substring|suffixes|sup|taintEnabled|target|test|text|title|toGMTString|toLocaleString|toLowerCase|toolbar|toSource|toString|top|toUpperCase|toUTCString|type|URL|unshift|unwatch|userAgent|UTC|value|valueOf|visibility|vlinkColor|vspace|width|watch|which|width|write|writeln|x|y|zIndex)#s',
			'#\b(clearInterval|clearTimeout|escape|isFinite|isNaN|Number|parseFloat|parseInt|reload|taint|unescape|untaint|write)\b#s',
			'#(?<!">)((?:-\s*)?(?:\#\w{6}|\#\w{3}|(?:\d+\.)?\d+))\b#si',
			'#(?<!class|">|"|span)(:|;|-|\||\+|=|\*|!|~|\.|,|\(|\)|\/|@|\%|&lt;|&gt;|&amp;|\{|\}|\[|\])(?!/?span)#si',
			),
		array(
			'<span class="keyword">\\1</span>',
			'<span class="event">\\1</span>',
			'<span class="control">\\1</span>',
			'<span class="object">\\1</span>',
			'<span class="method">\\1</span>',
			'<span class="function">\\1</span>',
			'<span class="number">\\1</span>',
			'<span class="punctuation">\\1</span>',
			),
		$buffer
		);
				if ($isComment)
				{
					$comment = $char;
				}
				else
				{
					$value = $char;
				}

				$notParse = 1;
				$buffer = (($isComment && $char != '#') ? $charOld : '').$char;
			}
			else if ($notParse && (($value && $char == $value && ($charOld != '\\' || substr($buffer, -2) == '\\\\')) || ($comment && ((($comment == '#' || $comment == '/') && $char == "\n") || ($char == '/' && $charOld == '*' && substr($buffer, -2, 1) != '/')))))
			{
				$output.= '<span class="'.($comment ? 'comment' : 'value').'">'.($value ? $buffer.$char : preg_replace('#\b(FIXME|NOTICE|NOTE|TODO|WARNING)\b#i', '<span class="notice">\\1</span>', $buffer.(($char == "\n") ? '' : $char))).'</span>';
				$buffer = (($char == "\n") ? $char : '');
				$notParse = $comment = $value = 0;
			}
			else
			{
				$buffer.= $char;
			}

			$code = substr($code, 1);
			$charOld = $char;
		}

		return $output;
	}

/**
 * Highlight for Perl
 * @param string $code
 * @return string
 */

	static private function highlightModePerl($code)
	{
		$buffer = $output = $charOld = '';
		$notParse = $comment = $value = $finish = 0;

		while (!$finish)
		{
			if (strlen($code) == 0)
			{
				$finish = 1;
			}
			else
			{
				$char = substr($code, 0, 1);
			}

			if (!$notParse)
			{
				if ($char == '#' || ($char == '/' && $charOld == '/') || ($char == '*' && $charOld == '/'))
				{
					$isComment = 1;
				}
				else
				{
					$isComment = 0;
				}
			}
			if ($finish || (!$notParse && ($isComment || (in_array($char, array('\'', '"')) && ($charOld != '\\' || substr($buffer, -2) == '\\\\')))))
			{
				if ($isComment && $char != '#')
				{
					$buffer = substr($buffer, 0, -1);
				}

				$output.= preg_replace(
		array(
			'#(?<!<span )\b(strict|english|warnings|vars|subs|utf8|sigtrap|locale|open|less|integer|filetest|constant|bytes|diagnostics|BEGIN|END|__END__|__DATA__|__FILE__|__LINE__|__PACKAGE__)\b#',
			'#\b(if|unless|else|elsif|while|until|for|each|foreach|next|last|break|continue|return|use|no|require|my|our|local|require|package|sub|do)\b#',
			'#\b(?<!\$)(abs|accept|alarm|atan2|bind|binmode|bless|caller|chdir|chmod|chomp|chop|chown|chr|chroot|close|closedir|connect|cos|crypt|dbmclose|dbmopen|defined|delete|die|dump|endgrent|endhostent|endnetent|endprotoent|endpwent|endservent|eof|eval|exec|exists|exit|exp|fcntl|fileno|flock|fork|format|formline|getc|getgrent|getgrgid|getgrnam|gethostbyaddr|gethostbyname|gethostent|getlogin|getnetbyaddr|getnetbyname|getnetent|getpeername|getpgrp|getppid|getpriority|getprotobyname|getprotobynumber|getprotoent|getpwent|getpwnam|getpwuid|getservbyname|getservbyport|getservent|getsockname|getsockopt|glob|gmtime|goto|grep|hex|import|index|int|ioctl|join|keys|kill|last|lc|lcfirst|length|link|listen|localtime|lock|log|lstat|map|mkdir|msgctl|msgget|msgrcv|msgsnd|oct|open|opendir|ord|pack|package|pipe|pop|pos|print|printf|prototype|push|quotemeta|rand|read|readdir|readline|readlink|recv|redo|ref|rename|reset|return|reverse|rewinddir|rindex|rmdir|scalar|seek(?:dir)?|select|semctl|semget|semop|send|setgrent|sethostent|setnetent|setpgrp|setpriority|setprotoent|setpwent|setservent|setsockopt|shift|shmctl|shmget|shmread|shmwrite|shutdown|sin|sleep|socket|socketpair|sort|splice|split|sprintf|sqrt|srand|stat|study|sub|substr|symlink|syscall|sysread|sysseek|system|syswrite|tell|telldir|tie|time|times|truncate|uc(?:first)?|umask|undef|unlink|unpack|unshift|untie|utime|values|vec|wait|waitpid|wantarray|warn|write)\b#i',
			'#((?:\$|%|&|@)(?!lt;|gt;)[a-z_][\w-]*)\b#i',
			'#(?<!">|[a-z-_])((?:-\s*)?(?:(?:\d+\.)?\d+)|0x[0-9a-f]+)\b#si',
			'#(?<!class|">|"|span|&lt|&gt)(:|;|-|\||\\|\+|=|\*|!|~|\.|,|\(|\)|\/|@|\%|&lt;|&gt;|&amp;|::|\band\b|\bor\b|\bnot\b|\beq\b|\bne\b|\{|\}|\[|\])(?!/?span)#si',
			),
		array(
			'<span class="keyword">\\1</span>',
			'<span class="control">\\1</span>',
			'<span class="function">\\1</span>',
			'<span class="variable">\\1</span>',
			'<span class="number">\\1</span>',
			'<span class="punctuation">\\1</span>',
			),
		$buffer
		);

				if ($isComment)
				{
					$comment = $char;
				}
				else
				{
					$value = $char;
				}

				$notParse = 1;
				$buffer = (($isComment && $char != '#') ? $charOld : '').$char;
			}
			else if ($notParse && (($value && $char == $value && ($charOld != '\\' || substr($buffer, -2) == '\\\\')) || ($comment && ((($comment == '#' || $comment == '/') && $char == "\n") || ($char == '/' && $charOld == '*' && substr($buffer, -2, 1) != '/')))))
			{
				$buffer = $buffer.(($char == "\n") ? '' : $char);
				$output.= '<span class="'.($comment ? 'comment' : 'value').'">'.($value ? $buffer : preg_replace('#\b(FIXME|NOTICE|NOTE|TODO|WARNING)\b#i', '<span class="notice">\\1</span>', $buffer)).'</span>';
				$buffer = (($char == "\n") ? $char : '');
				$notParse = $comment = $value = 0;
			}
			else
			{
				$buffer.= $char;
			}

			$code = substr($code, 1);
			$charOld = $char;
		}

		return $output;
	}


/**
 * Highlight for PHP
 * @param string $code
 * @return string
 */

	static public function highlightModePhp($code)
	{
		$buffer = $output = $charOld = '';
		$notParse = $comment = $value = $documentation = $parse = $finish = 0;
		$functions = get_defined_functions();
		$functions = implode('|', $functions['internal']);
		$constants = implode('|', array_keys(get_defined_constants()));

		while (!$finish)
		{
			if (strlen($code) == 0)
			{
				$finish = 1;
			}
			else
			{
				$char = substr($code, 0, 1);
			}

			$parseStop = 0;

			if (!$notParse)
			{
				if ($char == '#' || ($char == '/' && $charOld == '/') || ($char == '*' && $charOld == '/'))
				{
					$isComment = 1;
				}
				else
				{
					$isComment = 0;
				}

				if ($char == '?' && substr($buffer, -4) == '&lt;')
				{
					$parse = 1;
					$output.= substr($buffer, 0, -4);

					if (substr($code, 1, 3) == 'php')
					{
						$char.= 'php';
						$code = substr($code, 3);
					}

					$buffer = '<span class="borders">'.substr($buffer, -4).'<span class="tag">'.$char.'</span></span>';
					$char = '';
				}
				else if ($char == ';' && $charOld == 't' && substr($buffer, -4) == '?&gt')
				{
					$parse = 0;
					$parseStop = 1;
				}
			}

			if ($finish || $parseStop || (!$notParse && ($isComment || (in_array($char, array('\'', '"')) && ($charOld != '\\' || substr($buffer, -2) == '\\\\')))))
			{
				if ($isComment && $char != '#')
				{
					$buffer = substr($buffer, 0, -1);
				}

				if ($parse || $parseStop)
				{
					$output.= preg_replace(
		array(
			'#(?<!\$)\b(abstract|(?<!<span )class|clone|const|exception|extends|final|function|implements|instanceof|interface|new|self|static|parent|private|protected|public|use|and|x?or|var|__FILE__|__LINE__|'.$constants.')\b#',
			'#\b(as|case|catch|default|if|isset|die|exit|else|elseif|unset|empty|while|do|for(?:each)?|break|continue|switch|throw|try|finally|yield|declare|return|require(?:_once)?|include(?:_once)?|endif|endwhile|endfor|endforeach|endswitch)\b#',
			'#\b(Exception)\b#',
			'#\b(__autoload|__call|__clone|__construct|__destruct|__get|__isset|__set(?:_state)?|__sleep|__toString|__unset|__wakeup)\b#',
			'#\b(?<!\$|function</span>\s|->)('.$functions.'|array)\b(\s*\()#i',
			'#(\(\s*)(int(?:teger)?|bool(?:ean)?|float|real|double|string|binary|array|object|unset)(\s*\))#i',
			'#(\$[a-z_][\w-]*)\b#i',
			'#(?<!">|[a-z-_])((?:-\s*)?(?:(?:\d+\.)?\d+)|0x[0-9a-f]+)\b#i',
			'#(?<!class|">|"|span|&lt|&gt)(:|;|-|\||\+|=|\*|!|~|\.|,|\(|\)|\/|@|\%|&lt;|&gt;|&amp;|\{|\}|\[|\])(?!/?span)#si',
			'#<span class="function">(.*)</span>#U'
			),
		array(
			'<span class="keyword">\\1</span>',
			'<span class="control">\\1</span>',
			'<span class="object">\\1</span>',
			'<span class="method">\\1</span>',
			'<span class="function">\\1</span>\\2',
			'\\1<span class="datatype">\\2</span>\\3',
			'<span class="variable">\\1</span>',
			'<span class="number">\\1</span>',
			'<span class="punctuation">\\1</span>',
			'<a href="http://php.net/\\1" class="function">\\1</a>',
			),
		$buffer
		);
				}
				else
				{
					$output.= $buffer;
				}

				if ($parseStop)
				{
					$output = substr($output, 0, -4).'<span class="borders"><span class="tag">?</span>&gt;</span>';
					$char = '';
				}

				if ($isComment)
				{
					$comment = $char;

					if ($char == '*' && $charOld == '/' && substr($code, 1, 1) == '*')
					{
						$documentation = 1;
					}
				}
				else
				{
					$value = $char;
				}

				$notParse = 1;
				$buffer = (($isComment && $char != '#') ? $charOld : '').$char;
			}
			else if ($notParse && (($value && $char == $value && ($charOld != '\\' || substr($buffer, -2) == '\\\\')) || ($comment && ((($comment == '#' || $comment == '/') && $char == "\n") || ($char == '/' && $charOld == '*' && substr($buffer, -2, 1) != '/')))))
			{
				$buffer = $buffer.(($char == "\n") ? '' : $char);

				if ($documentation)
				{
					$buffer = self::highlightModePhpDoc($buffer);
				}

				$output.= ($parse ? '<span class="'.($comment ? ($documentation ? 'documentation' : 'comment') : 'value').'">' : '').($value ? $buffer : preg_replace('#\b(FIXME|NOTICE|NOTE|TODO|WARNING)\b#i', '<span class="notice">\\1</span>', $buffer)).($parse ? '</span>' : '');
				$buffer = (($char == "\n") ? $char : '');
				$notParse = $comment = $value = $documentation = 0;
			}
			else
			{
				$buffer.= $char;
			}

			$code = substr($code, 1);
			$charOld = $char;
		}

		return $output;
	}

/**
 * Highlight for PHPDoc
 * @param string $code
 * @return string
 */

	static private function highlightModePhpDoc($code)
	{
		return preg_replace(
		array(
			'#(\s*\*?\s*)(@(?:))(\s+)(\w+)#i',
			'#(\s*\*?\s*)(@(?:access))(\s+)(\w+)#im',
			'#(\s*\*?\s*)(@(?:abstract|author|copyright|date|example|final|ignore|internal|license|magic|(?:sub)?package|todo|version|deprec(?:ated)?|exception|link|see|since|throws))#i',
			'#(\s*\*?\s*)(@(?:global|param))(\s+)([a-z\|\[\]]+)(\s+)(\$?[a-z_][\w\[\]\'\"]+)#i',
			'#(\s*\*?\s*)(@name)(\s+)(\w+)#i',
			'#(\s*\*?\s*)(@return)(\s+)(\w+)#i',
			),
		array(
			'\\1<span class="documentationtag">\\2</span>\\3<span class="value">\\4</span>',
			'\\1<span class="documentationtag">\\2</span>\\3<span class="keyword">\\4</span>',
			'\\1<span class="documentationtag">\\2</span>',
			'\\1<span class="documentationtag">\\2</span>\\3<span class="datatype">\\4</span>\\5<span class="variable">\\6</span>',
			'\\1<span class="documentationtag">\\2</span>\\3<span class="variable">\\4</span>',
			'\\1<span class="documentationtag">\\2</span>\\3<span class="datatype">\\4</span>',
			),
		$code);
	}

/**
 * Highlight for PO(T)
 * @param string $code
 * @return string
 */

	static public function highlightModePo($code)
	{
		$buffer = $output = $charOld = '';
		$notParse = $comment = $value = $finish = 0;

		while (!$finish)
		{
			if (strlen($code) == 0)
			{
				$finish = 1;
			}
			else
			{
				$char = substr($code, 0, 1);
			}

			if ($finish || (!$notParse && (($char == '#') || (in_array($char, array('\'', '"')) && ($charOld != '\\' || substr($buffer, -2) == '\\\\')))))
			{
				$output.= preg_replace(
		array(
			'#(msgid(?:_plural)?|msgstr)#i',
			'#(?<!">|[a-z-_])((?:-\s*)?(?:(?:\d+\.)?\d+))\b#si',
			'#(?<!class|">|"|span)(:|;|-|\||\+|=|\*|!|~|\.|,|\(|\)|\/|@|\%|&lt;|&gt;|&amp;|\{|\}|\[|\])(?!/?span)#si',
			),
		array(
			'<span class="keyword">\\1</span>',
			'<span class="number">\\1</span>',
			'<span class="punctuation">\\1</span>',
			),
		$buffer
		);
				if ($char == '#')
				{
					$comment = $char;
				}
				else
				{
					$value = $char;
				}

				$notParse = 1;
				$buffer = $char;
			}
			else if ($notParse && (($value && $char == $value && ($charOld != '\\' || substr($buffer, -2) == '\\\\')) || ($comment && $char == "\n")))
			{
				$output.= '<span class="'.($comment ? 'comment' : 'value').'">'.$buffer.($value ? $char : '').'</span>';
				$buffer = ($comment ? $char : '');
				$notParse = $comment = $value = 0;
			}
			else
			{
				$buffer.= $char;
			}

			$code = substr($code, 1);
			$charOld = $char;
		}

		return $output;
	}

/**
 * Highlight for Python
 * @param string $code
 * @return string
 */

	static public function highlightModePython($code)
	{
		$buffer = $output = $charOld = '';
		$notParse = $comment = $value = $documentation = $finish = 0;

		while (!$finish)
		{
			if (strlen($code) == 0)
			{
				$finish = 1;
			}
			else
			{
				$char = substr($code, 0, 1);
			}

			if ($char == '#' || (in_array($char, array('\'', '"')) && substr($code, 0, 3) == $char.$char.$char))
			{
				$isComment = 1;
			}
			else
			{
				$isComment = 0;
			}

			if ($finish || (!$notParse && ($isComment || (in_array($char, array('\'', '"')) && ($charOld != '\\' || substr($buffer, -2) == '\\\\')))))
			{
				$output.= preg_replace(
		array(
			'#(?<!<span )\b(None|self|True|False|NotImplemented|Ellipsis|exec|print|and|assert|in|is|not|or|class|def|del|global|lambda)\b#',
			'#\b(break|continue|elif|else|except|finally|for|if|pass|raise|return|try|while|yield)\b#',
			'#^(import)(\s+)(.+)$#m',
			'#^(from)(\s+)(.+)(\s+)(import)(\s+)(.+)$#m',
			'#(?<!">)\b\.([\w_-]+)\b#si',
			'#\b(?<!\$)(__future__|__import__|__name__|abs|all|any|apply|basestring|bool|buffer|callable|chr|classmethod|close|cmp|coerce|compile|complex|delattr|dict|dir|divmod|enumerate|eval|execfile|exit|file|filter|float|frozenset|getattr|globals|hasattr|hash|hex|id|input|int|intern|isinstance|issubclass|iter|len|list|locals|long|map|max|min|object|oct|open|ord|pow|property|range|raw_input|reduce|reload|repr|reversed|round|set(?:attr)?|slice|sorted|staticmethod|str|sum|super|tuple|type|unichr|unicode|vars|xrange|zip)\b#',
			'#(?<!">|[a-z-_])((?:-\s*)?(?:(?:\d+\.)?\d+))\b#i',
			'#(?<!class|">|"|span|&lt|&gt)(:|;|-|\||\+|=|\*|!|~|\.|,|\(|\)|\/|@|\%|&lt;|&gt;|&amp;|\{|\}|\[|\])(?!/?span)#si',
			),
		array(
			'<span class="keyword">\\1</span>',
			'<span class="control">\\1</span>',
			'<span class="keyword">\\1</span>\\2<span class="package">\\3</span>\\4<span class="keyword">\\5</span>\\6<span class="package">\\7</span>',
			'<span class="keyword">\\1</span>\\2<span class="package">\\3</span>',
			'.<span class="method">\\1</span>',
			'<span class="function">\\1</span>',
			'<span class="number">\\1</span>',
			'<span class="punctuation">\\1</span>',
			),
		$buffer
		);
				if ($isComment)
				{
					$comment = $char;

					if ($char != '#')
					{
						$documentation = 1;
					}
				}
				else
				{
					$value = $char;
				}

				$notParse = 1;
				$buffer = $char;
			}
			else if ($notParse && (($value && $char == $value && ($charOld != '\\' || substr($buffer, -2) == '\\\\')) || ($comment && (($comment == '#' && $char == "\n") || ($documentation && $char == $comment && substr($buffer, -2) == $char.$char && strlen($buffer) > 2)))))
			{
				$buffer = $buffer.(($char == "\n") ? '' : $char);
				$output.= '<span class="'.($comment ? ($documentation ? 'documentation' : 'comment') : 'value').'">'.($value ? $buffer : preg_replace('#\b(FIXME|NOTICE|NOTE|TODO|WARNING)\b#i', '<span class="notice">\\1</span>', $buffer)).'</span>';
				$buffer = (($char == "\n") ? $char : '');
				$notParse = $comment = $value = $documentation = 0;
			}
			else
			{
				$buffer.= $char;
			}

			$code = substr($code, 1);
			$charOld = $char;
		}

		return $output;
	}

/**
 * Highlight for SQL
 * @param string $code
 * @return string
 */

	static private function highlightModeSql($code)
	{
		$buffer = $output = $charOld = '';
		$notParse = $comment = $value = $finish = 0;

		while (!$finish)
		{
			if (strlen($code) == 0)
			{
				$finish = 1;
			}
			else
			{
				$char = substr($code, 0, 1);
			}

			$isComment = 0;

			if ($finish || (!$notParse && ((in_array($char, array('\'', '"', '`')) && $charOld != $char && ($charOld != '\\' || substr($buffer, -2) == '\\\\')) || ($isComment = (($char == '*' && $charOld == '/' && substr($buffer, -2, 1) != '/') || ($char == '-' && $charOld == '-'))))))
			{
				$output.= preg_replace(
		array(
			'#(\s*FOREIGN_KEY_LIST|INDEX_INFO|INDEX_LIST|TABLE_INFO|COUNT|MIN|MAX|SUM|ABS|COALESCE|GLOB|IFNULL|LAST_INSERT_ROWID|LENGTH|LIKE|LOAD_EXTENSION|LOWER|NULLIF|QUOTE|RANDOM|ROUND|SOUNDEX|SQLITE_VERSION|SUBSTR|TYPEOF|UPPER|AVG|TOTAL|RAISE)(\s*\()#si',
			'#(\s*)(N?VARCHAR|TEXT|INTEGER|FLOAT|(?:BOOL)?EAN|CLOB|BLOB|TIMESTAMP|NUMERIC)(\s*)#si',
			'#((?:^|;\s+)(?:EXPLAIN )+(?:BEGIN|COMMIT|END|ROLLBACK) TRANSACTION|(?:AT|DE)TACH DATABASE|REINDEX|PRAGMA|ALTER TABLE|DELETE|VACUUM|EXPLAIN|SELECT(?: DISTINCT| UNION(?: ALL)?| INTERSECT| EXCEPT)?|BETWEEN|REPLACE|INSERT INTO|UPDATE|(?:CREATE|DROP)(?:(?:TEMP(?:ORARY)? |VIRTUAL )?TABLE| VIEW|(?: UNIQUE)? INDEX | TRIGGER))#si',
			'#(\s+)(WHERE|(?:PRIMARY|FOREIGN) KEY|IF NOT EXISTS|COLLATE|ON|OFF|YES|FILE|MEMORY|CASE|SET|(?:LEFT|RIGHT|FULL)(?: OUTER)? JOIN|UPDATE(?: OF)?|INSTEAD OF|CHECK|ON CONFLICT|(?:NOT )?LIKE|GLOB|HAVING|AFTER|BEFORE|FOR EACH(?:ROW|STATEMENT)|BEGIN|END|ELSE|NULL|AS SELECT|FROM|VALUES|ORDER BY|GROUP BY|WHEN|THEN|IN|LIMIT|OFFSET|AS|NO(?:T(?: ?)NULL?)|DEFAULT|UNIQUE|OR|AND|DESC|ASC)#si',
			'#((?:-\s*)?(?:\d+\.)?\d+)#',
			'#(?<!class|">|"|span)(:|;|-|\||\+|=|\*|!|~|\.|,|\(|\)|\/|@|\%|&lt;|&gt;|&amp;|\{|\}|\[|\])(?!/?span)#si',
			),
		array(
			'<span class="function">\\1</span>\\2',
			'\\1<span class="datatype">\\2</span>\\3',
			'<span class="keyword">\\1</span>',
			'\\1<span class="keyword">\\2</span>\\3',
			'<span class="number">\\1</span>',
			'<span class="punctuation">\\1</span>',
			),
		$buffer
		);
				$notParse = 1;

				if ($isComment)
				{
					$comment = 1;
				}
				else
				{
					$value = $char;
				}

				$buffer = $char;
			}
			else if ($notParse && (($value && $char == $value && $charOld != $char && ($charOld != '\\' || substr($buffer, -2) == '\\\\')) || ($comment && $char == "\n" || ($char == '/' && $charOld == '*'))))
			{
				$output.= '<span class="'.($comment ? 'comment' : 'value').'">'.$buffer.$char.'</span>';
				$buffer = '';
				$notParse = $comment = $value = 0;
			}
			else
			{
				$buffer.= $char;
			}

			$code = substr($code, 1);
			$charOld = $char;
		}

		return $output;
	}

/**
 * "Highlight" for plain text
 * @param string $code
 * @return string
 */

	static private function highlightModeTxt($code)
	{
		return $code;
	}

/**
 * Highlight for XML
 * @param string $code
 * @return string
 */

	static private function highlightModeXml($code)
	{
		$buffer = $output = $charOld = '';
		$notParse = $comment = $value = $finish = 0;

		while (!$finish)
		{
			if (strlen($code) == 0)
			{
				$finish = 1;
			}
			else
			{
				$char = substr($code, 0, 1);
			}

			$isComment = 0;

			if ($finish || (!$notParse && (in_array($char, array('\'', '"'))  || ($isComment = ($char == '-' && substr($buffer, -6) == '&lt;!-')))))
			{
				if ($isComment)
				{
					$buffer = substr($buffer, 0, -6);
				}

				$output.= preg_replace(
		array(
			'#&lt;(.*)&gt;#iU',
			'#&lt;(\w+)(\s+)([a-z0-9_\-:]+=)$#i',
			'#(?<!span)(\s+)([a-z0-9_\-:]+=)$#i',
			'#(&amp;\w{2,5};)#iU',
			),
		array(
			'&lt;<span class="tag">\\1</span>&gt;',
			'&lt;<span class="tag">\\1</span>\\2<span class="attribute">\\3</span>',
			'\\1<span class="attribute">\\2</span>',
			'<span class="entity">\\1</span>',
			),
		$buffer
		);
				$notParse = 1;

				if ($isComment)
				{
					$comment = 1;
				}
				else
				{
					$value = $char;
				}

				$buffer = ($isComment ? '&lt;!-' : '').$char;
			}
			else if ($notParse && (($value && $char == $value) || ($comment && $char == ';' && substr($buffer, -5) == '--&gt')))
			{
				$output.= '<span class="'.($comment ? 'comment' : 'value').'">'.$buffer.$char.'</span>';
				$buffer = '';
				$notParse = $comment = $value = 0;
			}
			else
			{
				$buffer.= $char;
			}

			$code = substr($code, 1);
			$charOld = $char;
		}

		$output = preg_replace('#(&lt;\?xml .*\?&gt;|&lt;!DOCTYPE .*&gt;)+#siU', '<span class="doctype">\\1</span>', $output);

		return $output;
	}
}
?>