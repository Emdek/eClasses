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
 * Formatting: add line numbers
 */

const FORMAT_LINENUMBERS = 1;

/**
 * Formatting: mark tabs and stray whitespace
 */

const FORMAT_WHITESPACE = 2;

/**
 * Formatting: allow to highlight ranges
 */

const FORMAT_RANGES = 4;

/**
 * Formatting: allow to fold code blocks
 */

const FORMAT_FOLDING = 8;

/**
 * Formatting: apply all of above options
 */

const FORMAT_ALL = 15;

/**
 * Formatting: special case for embedding code of different type
 */

const FORMAT_EMBEDDED = 16;

/**
 * Variable for storing formatting options for embedded code
 */

static private $options = 0;

/**
 * Highlights string for given type
 * @param string $code
 * @param string $mode
 * @param integer $options
 * @return string
 */

static public function highlightString($code, $mode = '', $options = self::FORMAT_ALL)
{
	if (empty($mode))
	{
		$sample = ltrim(substr($code, 0, 200), "\r\n\t ");

		if (substr($sample, 0, 5) == '<?xml')
		{
			if (stristr($sample, '<html') !== FALSE)
			{
				$mode = 'html';
			}
			else
			{
				$mode = 'xml';
			}
		}
		else if (substr($sample, 0, 2) == '<?')
		{
			$mode = 'php';
		}
		else if (stristr($sample, '<html') !== FALSE)
		{
			$mode = 'html';
		}
		else if (preg_match('#^import java#msi', $sample))
		{
			$mode = 'java';
		}
		else if (preg_match('#^using [a-z]#msi', $sample))
		{
			$mode = 'cs';
		}
		else if (preg_match('#^use [a-z]#msi', $sample))
		{
			$mode = 'perl';
		}
		else if (preg_match('#^import [a-z]#msi', $sample))
		{
			$mode = 'python';
		}
		else if (preg_match('#^\#include\s*("|<)#msi', $sample))
		{
			$mode = 'cpp';
		}
		else if (preg_match('#(^|;)\s*(CREATE|SELECT|DELETE|UPDATE|ALTER)\s+#msi', $sample))
		{
			$mode = 'sql';
		}
		else if (preg_match('#(if\s*\(|for\s*\()#msi', $sample))
		{
			$mode = 'javascript';
		}
		else if (preg_match('#{.*[a-z]:.*}#msi', $sample))
		{
			$mode = 'css';
		}
		else if (stristr($sample, 'msgid ""') !== FALSE)
		{
			$mode = 'gettext';
		}
	}

	$code = str_replace(array('&', '<', '>', "\r\n", "\r"), array('&amp;', '&lt;', '&gt;', "\n", "\n"), $code);
	$method = 'mode'.ucfirst(strtolower($mode));

	if (!method_exists('SyntaxHighlight', $method))
	{
		return $code;
	}

	return self::$method($code, $options);
}

/**
 * Highlights file for given type
 * @param string $path
 * @param string $mode
 * @param integer $options
 * @return string
 * @throws Exception
 */

static public function highlightFile($path, $mode = '', $options = self::FORMAT_ALL)
{
	if (!file_exists($path))
	{
		throw new Exception('File does not exists!');
	}

	return self::highlightString(file_get_contents($path), $mode, $options);
}

/**
 * Returns list of supported highlighting modes
 * @return array
 */

static public function getModes()
{
	return array(
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
	'gettext' => 'Gettext',
	'python' => 'Python',
	'sql' => 'SQL',
	'xml' => 'XML',
	);
}

/**
 * Removes highlighting
 * @param string $code
 * @return string
 */

static private function removeHighlighting($code)
{
	return preg_replace('#<(?:span|a href=".*") class="(?:[a-z]*)">(.*)</(?:span|a)>#sU', '\\1', $code);
}

/**
 * Additional formatting for parsed code
 * @param string $code
 * @param integer $options
 * @return string
 */

static private function formatCode($code, $options)
{
	$script = '';

	if ($options & self::FORMAT_WHITESPACE)
	{
		$code = preg_replace_callback('#( |\t)+$#m', 'self::markStray', $code);
		$code = preg_replace('#(?<!<span class="tab">)(\t)#', '<span class="tab">\\1</span>', $code);
	}

	if ($options & self::FORMAT_FOLDING)
	{
		$script.= '
var switchers = parent.getElementsByClassName(\'switcher\');

for (var i = 0; i < switchers.length; ++i)
{
	switchers[i].addEventListener(\'click\', function()
	{
		this.nextSibling.style.visibility = ((this.nextSibling.style.visibility == \'hidden\') ? \'visible\' : \'hidden\');
	});
}
';
	}

	if ($options & self::FORMAT_FOLDING || $options & self::FORMAT_RANGES)
	{
		$code = str_replace(
	array(
		'<span class="punctuation">{</span>',
		'<span class="punctuation">}</span>',
		),
	array(
		'<span><span class="punctuation'.(($options & self::FORMAT_RANGES) ? ' range' : '').(($options & self::FORMAT_FOLDING) ? ' switcher' : '').'">{</span>'.(($options & self::FORMAT_FOLDING) ? '<span>' : ''),
		'<span class="punctuation'.(($options & self::FORMAT_RANGES) ? ' range' : '').'">}</span></span>'.(($options & self::FORMAT_FOLDING) ? '</span>' : ''),
		),
	$code
	);
	}

	if ($options & self::FORMAT_RANGES)
	{
		$code = str_replace(
	array(
		'<span class="punctuation">(</span>',
		'<span class="punctuation">)</span>',
		'<span class="punctuation">[</span>',
		'<span class="punctuation">]</span>',
		),
	array(
		'<span><span class="punctuation range">(</span>',
		'<span class="punctuation range">)</span></span>',
		'<span><span class="punctuation range">[</span>',
		'<span class="punctuation range">]</span></span>',
		),
	$code
	);

		$script.= '
var ranges = parent.getElementsByClassName(\'range\');

for (var i = 0; i < ranges.length; ++i)
{
	ranges[i].addEventListener(\'mouseover\', function()
	{
		this.parentNode.className = \'highlightrange\';
	});
	ranges[i].addEventListener(\'mouseout\', function()
	{
		this.parentNode.className = \'\';
	});
}
';
	}

	if ($options & self::FORMAT_EMBEDDED)
	{
		return $code;
	}

	if (!empty($script))
	{
		$script = '
var scripts = document.getElementsByTagName(\'script\');
var parent = scripts[scripts.length - 1].parentNode;
'.$script;
	}

	if ($options & self::FORMAT_LINENUMBERS)
	{
		$numbers = '';
		$lines = (substr_count($code, ((substr_count($code, "\r") > substr_count($code, "\n")) ? "\r" : "\n")) + 2);

		for ($i = 1; $i < $lines; ++$i)
		{
			$numbers.= $i.'
';
		}

		return '<div class="highlight">
<pre class="numbers">'.$numbers.'</pre>
<pre class="code">'.$code.'
</pre>
'.($script ? '<script type="text/javascript">'
.$script.'</script>
' : '').'</div>
';
	}

	return '<div class="highlight">
<pre class="code">'.$code.'
</pre>
'.($script ? '<script type="text/javascript">'
.$script.'</script>
' : '').'</div>
';
}

/**
 * Marks stray whitespace
 * @param array $matches
 * @return string
 */

static private function markStray($matches)
{
	$array = str_split($matches[0]);
	$string = '';

	for ($i = 0, $c = count($array); $i < $c; ++$i)
	{
		if ($array[$i] == ' ')
		{
			$string.= '<span class="space"> </span>';
		}
		else if ($array[$i] == "\t")
		{
			$string.= '<span class="tab">	</span>';
		}
		else
		{
			$string.= $array[$i];
		}
	}

	return '<span class="stray">'.$string.'</span>';
}

/**
 * Highlight for embedded code
 * @param array $matches
 * @return string
 */

static private function highlightEmbedded($matches)
{
	if (count($matches) == 2)
	{
		return self::modePhp(self::removeHighlighting($matches[1]), (self::$options | self::FORMAT_EMBEDDED));
	}
	else if (substr($matches[1], 0, 34) == '&lt;<span class="tag">style</span>')
	{
		return '<span class="borders">'.$matches[1].'</span>'.self::modeCss(self::removeHighlighting($matches[2]), (self::$options | self::FORMAT_EMBEDDED)).'<span class="borders">'.$matches[3].'</span>';
	}

	return '<span class="borders">'.$matches[1].'</span>'.self::modeJavaScript(self::removeHighlighting($matches[2]), (self::$options | self::FORMAT_EMBEDDED)).'<span class="borders">'.$matches[3].'</span>';
}

/**
 * Highlight for C
 * @param string $code
 * @param integer $options
 * @return string
 */

static private function modeC($code, $options)
{
	return self::modeCpp($code, $options);
}

/**
 * Highlight for C++
 * @param string $code
 * @param integer $options
 * @return string
 */

static private function modeCpp($code, $options)
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
		'#\b(asm|class|const_cast|dynamic_cast|enum|explicit|export|extern|false|friend|inline|namespace|new|NULL|operator|private|protected|public|reinterpret_cast|restrict|sizeof|static_cast|struct|template|this|true|typedef|typeid|type_info|typename|union|using|virtual)\b#Ss',
		'#\b(as|case|catch|default|if|else|elseif|do|goto|for|break|continue|switch|throw|try|delete|return|while)\b#Ss',
		'#^(\#\s*(?:endif|if (?:def|ndef)?(?=\s+\S)|(?:el(?:se|if)|include(?:_next)?|define|undef|line|error|warning|pragma|static)|define.*|[0-9]+))#im',
		'#\b(auto|bool|const|double|float|long|mutable|register|short|(?:un)?signed|void|volatile|(?:w|u)?char(?:_t)?|u?int(?:(?:8|16|32|64)_t)?|_Imaginary|_Complex|_Bool)\b#Ss',
		'#(?<!">|[a-z-_])((?:-\s*)?(?:(?:\d+\.)?\d+))\b#Ssi',
		'#(?<!class|">|"|span)(:|;|-|\||\+|=|\*|!|~|\.|,|\(|\)|\/|@|\%|&lt;|&gt;|&amp;|\{|\}|\[|\])(?!/?span)#Ssi',
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

	return self::formatCode($output, $options);
}

/**
 * Highlight for C#
 * @param string $code
 * @param integer $options
 * @return string
 */

static private function modeCs($code, $options)
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
		'#\b(abstract|base|class|checked|delegate|enum|event|explicit|extern|false|finally|fixed|implicit|interface|internal|is|lock|namespace|new|null|operator|out|override|params|private|protected|public|readonly|ref|sealed|sizeof|stackalloc|static|struct|this|true|typeof|unchecked|unsafe|virtual)\b#Ss',
		'#(using)(\s+)(.+);#m',
		'#(?<!">)\b\.([\w_-]+)\b#Ssi',
		'#\b(as|case|catch|default|if|else|elseif|do|goto|for(?:each)?|break|continue|switch|throw|try|delete|return|while)\b#Ss',
		'#^(\#(?:else|elif|(?:end)?if|in|define|undef|warning|error|line))#Sim',
		'#\b(bool|char|const|decimal|double|float|object|u?int|u?short|u?long|s?byte|string|void)\b#Ss',
		'#(?<!">|[a-z-_])((?:-\s*)?(?:(?:\d+\.)?\d+))\b#Ssi',
		'#(?<!class|">|"|span)(:|;|-|\||\+|=|\*|!|~|\.|,|\(|\)|\/|@|\%|&lt;|&gt;|&amp;|\{|\}|\[|\])(?!/?span)#Ssi',
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

	return self::formatCode($output, $options);
}

/**
* Highlight for CSS
* @param string $code
* @param integer $options
* @return string
*/

static private function modeCss($code, $options)
{
	$buffer = $output = $charOld = '';
	$notParse = $media = $definition = $finish = 0;

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

		if ((!$notParse || ($notParse && $valueStart)) && !$definition && ($valueStart || $char == '{'))
		{
			if (!$valueStart)
			{
				if (stristr($buffer, '@media'))
				{
					$media = 1;
				}
				else
				{
					$definition = 1;
				}
			}

			$output.= preg_replace(
	array(
		'#@(charset|import|media|page|font-face|namespace)#Ssi',
		'#(\.[a-z]\w*)#Ssi',
		'#(?<=\n|\r|\}|,\s|,)(\#[a-z]\w*)#Ssi',
		'#(:{1,2})(link|visited|active|hover|focus|lang|nth-child|nth-last-child|nth-of-type|nth-last-of-type|first-child|last-child|first-of-type|last-of-type|only-child|only-of-type|root|empty|target|enabled|disabled|checked|not|first-line|first-letter|before|after|selection)#Ssi',
		'#([a-z]+)(\s*\()#Ssi',
		'#(?<!class|">|"|span)(~|\*|=|,|\(|\)|\/|&lt;|&gt;|\[|\])(?!/?span)#Ssi',
		),
	array(
		'<span class="control">@\\1</span>',
		'<span class="class">\\1</span>',
		'<span class="identifier">\\1</span>',
		'<span class="pseudoclass">\\1\\2</span>',
		'<span class="function">\\1</span>\\2',
		'<span class="punctuation">\\1</span>',
		),
	$buffer
	).($valueStart ? '' : '<span class="punctuation">{</span>'.($definition ? '<span class="definition">' : ''));

			$buffer = ($valueStart ? $char : '');
		}
		else if ((!$notParse || ($notParse && $valueStart)) && ($media || $definition) && ($valueStart || $char == '}'))
		{
			$output.= preg_replace(
	array(
		'#([a-z]+)(\s*\()#Ssi',
		'#((?<=^|;|\s)[a-z\-]+(?=:)|(?:!important))#Ssi',
		'#(?<!">)((?:(?:\+|-)\s*)?(?:\#\w{6}|\#\w{3}|(?:\d+\.)?\d+(?:px|pt|pc|ex|em|in|cm|mm|deg|grad|rad|ms|s|k?hz|\%)?))#Ssi',
		'#(?<!class|">|"|span)(:|;|=|\*|,|\(|\)|\/|&lt;|&gt;)(?!/?span)#Ssi',
		),
	array(
		'<span class="function">\\1</span>\\2',
		'<span class="keyword">\\1</span>',
		'<span class="number">\\1</span>',
		'<span class="punctuation">\\1</span>',
		),
	$buffer
	).($valueStart ? $char : ($definition ? '</span>' : '').'<span class="punctuation">}</span>');

			if (!$valueStart)
			{
				if ($definition)
				{
					$definition = 0;
				}
				else
				{
					$media = 0;
				}
			}

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

	if ($definition)
	{
		$output.= '</span>';
	}

	return self::formatCode($output, $options);
}

/**
* Highlight for(X)HTML
* @param string $code
* @param integer $options
* @return string
*/

static private function modeHtml($code, $options)
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
		'#(&lt;\?xml .*\?&gt;|&lt;!DOCTYPE html.*&gt;)+#SsiU',
		'#&lt;(.*)&gt;#SiU',
		'#&lt;(\w+)(\s+)([a-z-:]+=)$#Si',
		'#(?<!span)(\s+)([a-z-:]+=)$#Si',
		'#(&amp;\w{2,5};)#SiU',
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

	self::$options = &$options;

	$output = preg_replace_callback(array('#(&lt;<span class="tag">style</span>.*&gt;)(.*)(&lt;<span class="tag">/style</span>&gt;)#siU', '#(&lt;<span class="tag">script</span>.*&gt;)(.*)(&lt;<span class="tag">/script</span>&gt;)#siU', '#(&lt;\?(?:php)?(?!xml)(?U).+\?&gt;)#si'), 'self::highlightEmbedded', $output);

	return self::formatCode($output, $options);
}

/**
* Highlight for INI
* @param string $code
* @param integer $options
* @return string
*/

static private function modeIni($code, $options)
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
		'#^(\[\w+\])$#SsiUm',
		'#^([^\#;]+)(?<!<span class)= (.+)$#SsiUm',
		'#^\s*([\#;].*)$#SsiUm',
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

	return self::formatCode($output, $options);
}

/**
* Highlight for Java
* @param string $code
* @param integer $options
* @return string
*/

static private function modeJava($code, $options)
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
		'#\b(abstract|class|continue|enum|extends|false|finally|implements|instanceof|@?interface|native|new|null|private|protected|public|super|static|strictfp|synchronized|this|throws|transient|true|volatile)\b#Ss',
		'#^(package|import)(\s+)(.+);#Sm',
		'#(?<!">)\b\.([\w_-]+)\b#Ssi',
		'#\b(break|case|catch|continue|default|do|else|for|goto|if|return|throw|try|while)\b#Ss',
		'#\b(boolean|byte|char|const|double|final|float|int|long|short|void)\b#Ss',
		'#(?<!">|[a-z-_])((?:-\s*)?(?:(?:\d+\.)?\d+))\b#Ssi',
		'#(?<!class|">|"|span)(:|;|-|\||\+|=|\*|!|~|\.|,|\(|\)|\/|@|\%|&lt;|&gt;|&amp;|\{|\}|\[|\])(?!/?span)#Ssi',
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
				$buffer = self::modeJavaDoc($buffer, $options);
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

	return self::formatCode($output, $options);
}

/**
* Highlight for JavaDoc
* @param string $code
* @param integer $options
* @return string
*/

static private function modeJavaDoc($code, $options)
{
	return preg_replace(
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
	$code);
}

/**
* Highlight for JavaScript
* @param string $code
* @param integer $options
* @return string
*/

static private function modeJavaScript($code, $options)
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
		'#\b(in|with|try|catch|finally|new|var|function|delete|true|false|void|throw|typeof|const)\b#Ss',
		'#\b(onabort|onblur|onchange|onclick|onerror|onfocus|onkeypress|onkeydown|onkeyup|onload|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|onreset|onselect|onsubmit|onunload)\b#Ssi',
		'#\b(as|case|default|if|else|elseif|while|do|for|foreach|break|continue|switch|return)\b#Ss',
		'#\b(Anchor|Applet|Area|Array|Boolean|Button|Checkbox|Date|document|window|Image|FileUpload|Form|Frame|Function|Hidden|Link|MimeType|Math|Max|Min|Layer|navigator|Object|Password|Plugin|Radio|RegExp|Reset|Screen|Select|String|Text|Textarea|this|Window)\b#Ss',
		'#(?<=\.)(above|action|alinkColor|alert|anchor|anchors|appCodeName|applets|apply|appName|appVersion|argument|arguments|arity|availHeight|availWidth|back|background|below|bgColor|border|big|blink|blur|bold|border|call|caller|charAt|charCodeAt|checked|clearInterval|clearTimeout|click|clip|close|closed|colorDepth|complete|compile|constructor|confirm|cookie|current|cursor|data|defaultChecked|defaultSelected|defaultStatus|defaultValue|description|disableExternalCapture|domain|elements|embeds|enabledPlugin|enableExternalCapture|encoding|eval|exec|fgColor|filename|find|fixed|focus|fontcolor|fontsize|form|forms|formName|forward|frames|fromCharCode|getDate|getDay|getHours|getMiliseconds|getMinutes|getMonth|getSeconds|getSelection|getTime|getTimezoneOffset|getUTCDate|getUTCDay|getUTCFullYear|getUTCHours|getUTCMilliseconds|getUTCMinutes|getUTCMonth|getUTCSeconds|getYear|global|go|hash|height|history|home|host|hostname|href|hspace|ignoreCase|images|index(?:Of)?|innerHeight|innerWidth|input|italics|javaEnabled|join|language|lastIndex|lastIndexOf|lastModified|lastParen|layers|layerX|layerY|left|leftContext|length|link|linkColor|links|location|locationbar|load|lowsrc|match|MAX_VALUE|menubar|method|mimeTypes|MIN_VALUE|modifiers|moveAbove|moveBelow|moveBy|moveTo|moveToAbsolute|multiline|name|NaN|NEGATIVE_INFINITY|negative_infinity|next|open|opener|options|outerHeight|outerWidth|pageX|pageY|pageXoffset|pageYoffset|parent|parse|pathname|personalbar|pixelDepth|platform|plugins|pop|port|POSITIVE_INFINITY|positive_infinity|preference|previous|print|prompt|protocol|prototype|push|referrer|refresh|releaseEvents|reload|replace|reset|resizeBy|resizeTo|reverse|rightContext|screenX|screenY|scroll|scrollbar|scrollBy|scrollTo|search|select|selected|selectedIndex|self|setDate|setHours|setInterval|setMinutes|setMonth|setSeconds|setTime(?:out)?|setUTCDate|setUTCDay|setUTCFullYear|setUTCHours|setUTCMilliseconds|setUTCMinutes|setUTCMonth|setUTCSeconds|setYear|shift|siblingAbove|siblingBelow|small|sort|source|splice|split|src|status|statusbar|strike|sub(?:str)?|submit|substring|suffixes|sup|taintEnabled|target|test|text|title|toGMTString|toLocaleString|toLowerCase|toolbar|toSource|toString|top|toUpperCase|toUTCString|type|URL|unshift|unwatch|userAgent|UTC|value|valueOf|visibility|vlinkColor|vspace|width|watch|which|width|write|writeln|x|y|zIndex)#Ss',
		'#\b(clearInterval|clearTimeout|escape|isFinite|isNaN|Number|parseFloat|parseInt|reload|taint|unescape|untaint|write)\b#Ss',
		'#(?<!">)((?:-\s*)?(?:\#\w{6}|\#\w{3}|(?:\d+\.)?\d+))\b#Ssi',
		'#(?<!class|">|"|span)(:|;|-|\||\+|=|\*|!|~|\.|,|\(|\)|\/|@|\%|&lt;|&gt;|&amp;|\{|\}|\[|\])(?!/?span)#Ssi',
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

	return self::formatCode($output, $options);
}

/**
* Highlight for Perl
* @param string $code
* @param integer $options
* @return string
*/

static private function modePerl($code, $options)
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
		'#(?<!">|[a-z-_])((?:-\s*)?(?:(?:\d+\.)?\d+)|0x[0-9a-f]+)\b#Ssi',
		'#(?<!class|">|"|span|&lt|&gt)(:|;|-|\||\\|\+|=|\*|!|~|\.|,|\(|\)|\/|@|\%|&lt;|&gt;|&amp;|::|\band\b|\bor\b|\bnot\b|\beq\b|\bne\b|\{|\}|\[|\])(?!/?span)#Ssi',
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

	return self::formatCode($output, $options);
}

/**
* Highlight for PHP
* @param string $code
* @param integer $options
* @return string
*/

static public function modePhp($code, $options)
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
		'#(?<!\$)\b(abstract|(?<!<span )class|clone|const|exception|extends|final|function|implements|instanceof|interface|new|self|static|parent|private|protected|public|use|and|x?or|var|__FILE__|__LINE__|'.$constants.')\b#S',
		'#\b(as|case|catch|default|if|isset|die|exit|else|elseif|unset|empty|while|do|for(?:each)?|break|continue|switch|throw|try|finally|yield|declare|return|require(?:_once)?|include(?:_once)?|endif|endwhile|endfor|endforeach|endswitch)\b#S',
		'#\b(Exception)\b#S',
		'#\b(__autoload|__call|__clone|__construct|__destruct|__get|__isset|__set(?:_state)?|__sleep|__toString|__unset|__wakeup)\b#S',
		'#\b(?<!\$|function</span>\s|->)('.$functions.'|array)\b(\s*\()#i',
		'#(\(\s*)(int(?:teger)?|bool(?:ean)?|float|real|double|string|binary|array|object|unset)(\s*\))#Si',
		'#(\$[a-z_][\w-]*)\b#Si',
		'#(?<!">|[a-z-_])((?:-\s*)?(?:(?:\d+\.)?\d+)|0x[0-9a-f]+)\b#Si',
		'#(?<!class|">|"|span|&lt|&gt)(:|;|-|\||\+|=|\*|!|~|\.|,|\(|\)|\/|@|\%|&lt;|&gt;|&amp;|\{|\}|\[|\])(?!/?span)#Ssi',
		'#<span class="function">(.*)</span>#SU'
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
				$buffer = self::modePhpDoc($buffer, $options);
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

	return self::formatCode($output, $options);
}

/**
* Highlight for PHPDoc
* @param string $code
* @param integer $options
* @return string
*/

static private function modePhpDoc($code, $options)
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
* Highlight for Gettext files
* @param string $code
* @return string
*/

static public function modeGettext($code, $options)
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
		'#(?<!">|[a-z-_])((?:-\s*)?(?:(?:\d+\.)?\d+))\b#Ssi',
		'#(?<!class|">|"|span)(:|;|-|\||\+|=|\*|!|~|\.|,|\(|\)|\/|@|\%|&lt;|&gt;|&amp;|\{|\}|\[|\])(?!/?span)#Ssi',
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

	return self::formatCode($output, $options);
}

/**
* Highlight for Python
* @param string $code
* @param integer $options
* @return string
*/

static public function modePython($code, $options)
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
		'#(?<!<span )\b(None|self|True|False|NotImplemented|Ellipsis|exec|print|and|assert|in|is|not|or|class|def|del|global|lambda)\b#S',
		'#\b(break|continue|elif|else|except|finally|for|if|pass|raise|return|try|while|yield)\b#S',
		'#^(import)(\s+)(.+)$#Sm',
		'#^(from)(\s+)(.+)(\s+)(import)(\s+)(.+)$#Sm',
		'#(?<!">)\b\.([\w_-]+)\b#Ssi',
		'#\b(?<!\$)(__future__|__import__|__name__|abs|all|any|apply|basestring|bool|buffer|callable|chr|classmethod|close|cmp|coerce|compile|complex|delattr|dict|dir|divmod|enumerate|eval|execfile|exit|file|filter|float|frozenset|getattr|globals|hasattr|hash|hex|id|input|int|intern|isinstance|issubclass|iter|len|list|locals|long|map|max|min|object|oct|open|ord|pow|property|range|raw_input|reduce|reload|repr|reversed|round|set(?:attr)?|slice|sorted|staticmethod|str|sum|super|tuple|type|unichr|unicode|vars|xrange|zip)\b#S',
		'#(?<!">|[a-z-_])((?:-\s*)?(?:(?:\d+\.)?\d+))\b#Si',
		'#(?<!class|">|"|span|&lt|&gt)(:|;|-|\||\+|=|\*|!|~|\.|,|\(|\)|\/|@|\%|&lt;|&gt;|&amp;|\{|\}|\[|\])(?!/?span)#Ssi',
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

	return self::formatCode($output, $options);
}

/**
* Highlight for SQL
* @param string $code
* @param integer $options
* @return string
*/

static private function modeSql($code, $options)
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
		'#(\s*FOREIGN_KEY_LIST|INDEX_INFO|INDEX_LIST|TABLE_INFO|COUNT|MIN|MAX|SUM|ABS|COALESCE|GLOB|IFNULL|LAST_INSERT_ROWID|LENGTH|LIKE|LOAD_EXTENSION|LOWER|NULLIF|QUOTE|RANDOM|ROUND|SOUNDEX|SQLITE_VERSION|SUBSTR|TYPEOF|UPPER|AVG|TOTAL|RAISE)(\s*\()#Ssi',
		'#(\s*)(N?VARCHAR|TEXT|INTEGER|FLOAT|(?:BOOL)?EAN|CLOB|BLOB|TIMESTAMP|NUMERIC)(\s*)#Ssi',
		'#((?:^|;\s+)(?:EXPLAIN )+(?:BEGIN|COMMIT|END|ROLLBACK) TRANSACTION|(?:AT|DE)TACH DATABASE|REINDEX|PRAGMA|ALTER TABLE|DELETE|VACUUM|EXPLAIN|SELECT(?: DISTINCT| UNION(?: ALL)?| INTERSECT| EXCEPT)?|BETWEEN|REPLACE|INSERT INTO|UPDATE|(?:CREATE |DROP )(?:(?:TEMP(?:ORARY)? |VIRTUAL )?TABLE| VIEW|(?: UNIQUE)? INDEX | TRIGGER))#Ssi',
		'#(\s+)(WHERE|(?:PRIMARY|FOREIGN) KEY|IF NOT EXISTS|COLLATE|ON|OFF|YES|FILE|MEMORY|CASE|SET|(?:LEFT|RIGHT|FULL)(?: OUTER)? JOIN|UPDATE(?: OF)?|INSTEAD OF|CHECK|ON CONFLICT|(?:NOT )?LIKE|GLOB|HAVING|AFTER|BEFORE|FOR EACH(?:ROW|STATEMENT)|BEGIN|END|ELSE|NULL|AS SELECT|FROM|VALUES|ORDER BY|GROUP BY|WHEN|THEN|IN|LIMIT|OFFSET|AS|NO(?:T(?: ?)NULL?)|DEFAULT|UNIQUE|OR|AND|DESC|ASC)#Ssi',
		'#((?:-\s*)?(?:\d+\.)?\d+)#S',
		'#(?<!class|">|"|span)(:|;|-|\||\+|=|\*|!|~|\.|,|\(|\)|\/|@|\%|&lt;|&gt;|&amp;|\{|\}|\[|\])(?!/?span)#Ssi',
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

	return self::formatCode($output, $options);
}

/**
* Highlight for XML
* @param string $code
* @param integer $options
* @return string
*/

static private function modeXml($code, $options)
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

	return self::formatCode($output, $options);
}

}
?>