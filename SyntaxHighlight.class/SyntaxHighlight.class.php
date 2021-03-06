<?php
/**
 * Syntax highlighting class
 * @author Emdek <http://emdek.pl>
 * @version v0.9.07
 * @date 2013-10-08 19:04:09
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
 * Formatting: highlight active (hovered) line
 */

const FORMAT_ACTIVELINE = 16;

/**
 * Formatting: allow to mark lines by clicking
 */

const FORMAT_MARKLINES = 32;

/**
 * Formatting: apply all of above options
 */

const FORMAT_ALL = 63;

const STATE_NONE = 0;
const STATE_CODE = 1;
const STATE_VALUE = 2;
const STATE_DOCUMENTATION = 3;
const STATE_COMMENT = 4;
const STATE_SELECTORS = 5;

/**
 * Variable for storing formatting options for embedded code
 */

static public $options = 0;

/**
 * Highlights string for given type
 * @param string $code
 * @param string $mode
 * @param integer $options
 * @param boolean $raw
 * @return string
 */

static public function highlightString($code, $mode = '', $options = self::FORMAT_ALL, $raw = FALSE)
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

	if ($mode == 'c')
	{
		$mode = 'cpp';
	}
	else if ($mode == 'xml')
	{
		$mode = 'html';
	}

	$code = str_replace(array('&', '<', '>', "\r\n", "\r"), array('&amp;', '&lt;', '&gt;', "\n", "\n"), $code);
	$method = 'mode'.ucfirst($mode);

	if (method_exists('SyntaxHighlight', $method))
	{
		$code = self::$method($code, $options);
	}

	if ($raw)
	{
		return $code;
	}

	$script = array();

	if ($options & self::FORMAT_ACTIVELINE)
	{
		$script[] = 'activeline';
	}

	if ($options & self::FORMAT_MARKLINES)
	{
		$script[] = 'marklines';
	}

	if ($options & self::FORMAT_FOLDING)
	{
		$script[] = 'folding';
	}

	if ($options & self::FORMAT_RANGES)
	{
		$script[] = 'ranges';
	}

	if ($options & self::FORMAT_WHITESPACE)
	{
		$code = preg_replace_callback('#( |\t)+$#m', function ($matches)
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
}, $code);
		$code = preg_replace('#(?<!<span class="tab">)(\t)#', '<span class="tab">\\1</span>', $code);
	}

	$numbers = '';

	if ($options & self::FORMAT_LINENUMBERS)
	{
		$script[] = 'linenumbers';
		$lines = (substr_count($code, ((substr_count($code, "\r") > substr_count($code, "\n")) ? "\r" : "\n")) + 2);

		for ($i = 1; $i < $lines; ++$i)
		{
			$numbers.= $i.'
';
		}

		$numbers = '<pre class="numbers">'.$numbers.'</pre>
';
	}

	return '<div class="code" data-options="'.implode(',', $script).'">
'.$numbers.'<pre>'.$code.'
</pre>
</div>
';
}

/**
 * Highlights file for given type
 * @param string $path
 * @param string $mode
 * @param integer $options
 * @param boolean $raw
 * @return string
 * @throws Exception
 */

static public function highlightFile($path, $mode = '', $options = self::FORMAT_ALL, $raw = FALSE)
{
	if (!file_exists($path))
	{
		throw new Exception('File does not exists!');
	}

	return self::highlightString(file_get_contents($path), $mode, $options, $raw);
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
 * Highlight for C++
 * @param string $code
 * @param integer $options
 * @return string
 */

static private function modeCpp($code, $options)
{
	$buffer = $output = '';
	$state = self::STATE_CODE;
	$levels = array('{' => 0, '(' => 0, '[' => 0);
	$map = array('}' => '{', ')' => '(', ']' => '[');

	while (TRUE)
	{
		$char = (empty($code) ? '' : substr($code, 0, 1));
		$code = substr($code, 1);
		$oldState = $state;

		if (empty($code))
		{
			$buffer.= $char;
			$char = '';
			$state = self::STATE_NONE;
		}
		else if ($state == self::STATE_CODE)
		{
			if ($char == '/' && (substr($code, 0, 1) == '/' || substr($code, 0, 1) == '*'))
			{
				$state = self::STATE_COMMENT;
			}
			else if (($char == '\'' || $char ==  '"') && (substr($buffer, -1) != '\\' || substr($buffer, -2) == '\\\\'))
			{
				$state = self::STATE_VALUE;
			}
			else if (isset($levels[$char]))
			{
				++$levels[$char];

				$char = (($options & self::FORMAT_RANGES || ($options & self::FORMAT_FOLDING && $char == '{')) ? '<span class="'.(($options & self::FORMAT_RANGES) ? 'range' : '').(($options & self::FORMAT_FOLDING && $char == '{') ? ' foldable' : '').'">' : '').'<span class="punctuation">'.$char.'</span>';
			}
			else if (isset($map[$char]))
			{
				--$levels[$map[$char]];

				$char = '<span class="punctuation">'.$char.'</span>'.(((($options & self::FORMAT_RANGES || ($options & self::FORMAT_FOLDING && $char == '}')) && $levels[$map[$char]] >= 0)) ? '</span>' : '');
			}
		}
		else if ($state == self::STATE_COMMENT && (($char == "\n" && substr($buffer, 0, 2) == '//') || ($char == '/' && substr($buffer, 0, 2) == '/*' && substr($buffer, -1) == '*')))
		{
			$state = self::STATE_CODE;
		}
		else if ($state == self::STATE_VALUE && ($char == '\'' || $char ==  '"') && $char == substr($buffer, 0, 1) && (substr($buffer, -1) != '\\' || substr($buffer, -2) == '\\\\'))
		{
			$state = self::STATE_CODE;
		}

		if ($state !== $oldState)
		{
			if ($oldState == self::STATE_CODE)
			{
				$output.= preg_replace(
	array(
		'#\b(asm|(?<!<span )class|const_cast|dynamic_cast|enum|explicit|export|extern|false|friend|inline|namespace|new|NULL|operator|private|protected|public|reinterpret_cast|restrict|sizeof|static_cast|struct|template|this|true|typedef|typeid|type_info|typename|union|using|virtual)\b#Ss',
		'#\b(as|case|catch|default|if|else|elseif|do|goto|for|break|continue|switch|throw|try|delete|return|while)\b#Ss',
		'#^(\#\s*(?:endif|if (?:def|ndef)?(?=\s+\S)|(?:el(?:se|if)|include(?:_next)?|define|undef|line|error|warning|pragma|static)|define.*|[0-9]+))#im',
		'#\b(auto|bool|const|double|float|long|mutable|register|short|(?:un)?signed|void|volatile|(?:w|u)?char(?:_t)?|u?int(?:(?:8|16|32|64)_t)?|_Imaginary|_Complex|_Bool)\b#Ss',
		'#(?<!">|[a-z-_])((?:-\s*)?(?:(?:\d+\.)?\d+))\b#Ssi',
		'#(?<!class|">|"|span)((?::|;|-|\||\+|=|\*|!|~|\.|,|\/|@|\%|&lt;|&gt;|&amp;)+)(?!/?span)#Ssi',
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
			}
			else if ($oldState == self::STATE_COMMENT)
			{
				if ($char == "\n")
				{
					$code = "\n".$code;
					$char = '';
				}

				$output.= '<span class="comment">'.preg_replace('#\b(FIXME|NOTICE|NOTE|TODO|WARNING)\b#i', '<span class="notice">\\1</span>', $buffer.$char).'</span>';
				$char = '';
			}
			else if ($oldState == self::STATE_VALUE)
			{
				$output.= '<span class="value">'.$buffer.$char.'</span>';
				$char = '';
			}

			$buffer = '';
		}

		$buffer.= $char;

		if (empty($code))
		{
			if ($options & self::FORMAT_RANGES || $options & self::FORMAT_FOLDING)
			{
				$buffer.= str_repeat('</span>', (($options & self::FORMAT_RANGES) ? array_sum($levels) : $levels['{']));
			}

			$output.= $buffer;

			break;
		}
	}

	return $output;
}

/**
 * Highlight for C#
 * @param string $code
 * @param integer $options
 * @return string
 */

static private function modeCs($code, $options)
{
	$buffer = $output = '';
	$state = self::STATE_CODE;
	$levels = array('{' => 0, '(' => 0, '[' => 0);
	$map = array('}' => '{', ')' => '(', ']' => '[');

	while (TRUE)
	{
		$char = (empty($code) ? '' : substr($code, 0, 1));
		$code = substr($code, 1);
		$oldState = $state;

		if (empty($code))
		{
			$buffer.= $char;
			$char = '';
			$state = self::STATE_NONE;
		}
		else if ($state == self::STATE_CODE)
		{
			if ($char == '/' && substr($code, 0, 1) == '/')
			{
				$state = ((substr($code, 0, 2) == '//') ? self::STATE_DOCUMENTATION : self::STATE_COMMENT);
			}
			else if (($char == '\'' || $char ==  '"') && (substr($buffer, -1) != '\\' || substr($buffer, -2) == '\\\\'))
			{
				$state = self::STATE_VALUE;
			}
			else if (isset($levels[$char]))
			{
				++$levels[$char];

				$char = (($options & self::FORMAT_RANGES || ($options & self::FORMAT_FOLDING && $char == '{')) ? '<span class="'.(($options & self::FORMAT_RANGES) ? 'range' : '').(($options & self::FORMAT_FOLDING && $char == '{') ? ' foldable' : '').'">' : '').'<span class="punctuation">'.$char.'</span>';
			}
			else if (isset($map[$char]))
			{
				--$levels[$map[$char]];

				$char = '<span class="punctuation">'.$char.'</span>'.(((($options & self::FORMAT_RANGES || ($options & self::FORMAT_FOLDING && $char == '}')) && $levels[$map[$char]] >= 0)) ? '</span>' : '');
			}
		}
		else if (($state == self::STATE_COMMENT || $state == self::STATE_DOCUMENTATION) && $char == "\n")
		{
			$state = self::STATE_CODE;
		}
		else if ($state == self::STATE_VALUE && ($char == '\'' || $char ==  '"') && $char == substr($buffer, 0, 1) && (substr($buffer, -1) != '\\' || substr($buffer, -2) == '\\\\'))
		{
			$state = self::STATE_CODE;
		}

		if ($state !== $oldState)
		{
			if ($oldState == self::STATE_CODE)
			{
				$output.= preg_replace(
	array(
		'#\b(abstract|base|(?<!<span )class|checked|delegate|enum|event|explicit|extern|false|finally|fixed|implicit|interface|internal|is|lock|namespace|new|null|operator|out|override|params|private|protected|public|readonly|ref|sealed|sizeof|stackalloc|static|struct|this|true|typeof|unchecked|unsafe|virtual)\b#Ss',
		'#(using)(\s+)(.+);#m',
		'#(?<!">)\b\.([\w_-]+)\b#Ssi',
		'#\b(as|case|catch|default|if|else|elseif|do|goto|for(?:each)?|break|continue|switch|throw|try|delete|return|while)\b#Ss',
		'#^(\#(?:else|elif|(?:end)?if|in|define|undef|warning|error|line))#Sim',
		'#\b(bool|char|const|decimal|double|float|object|u?int|u?short|u?long|s?byte|string|void)\b#Ss',
		'#(?<!">|[a-z-_])((?:-\s*)?(?:(?:\d+\.)?\d+))\b#Ssi',
		'#(?<!class|">|"|span)((?::|;|-|\||\+|=|\*|!|~|\.|,|\(|\)|\/|@|\%|&lt;|&gt;|&amp;|\{|\}|\[|\])+)(?!/?span)#Ssi',
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
			}
			else if ($oldState == self::STATE_COMMENT)
			{
				if ($char == "\n")
				{
					$code = "\n".$code;
					$char = '';
				}

				$output.= '<span class="comment">'.preg_replace('#\b(FIXME|NOTICE|NOTE|TODO|WARNING)\b#i', '<span class="notice">\\1</span>', $buffer.$char).'</span>';
				$char = '';
			}
			else if ($oldState == self::STATE_DOCUMENTATION)
			{
				if ($char == "\n")
				{
					$code = "\n".$code;
					$char = '';
				}

				$output.= '<span class="documentation">'.$buffer.$char.'</span>';
				$char = '';
			}
			else if ($oldState == self::STATE_VALUE)
			{
				$output.= '<span class="value">'.$buffer.$char.'</span>';
				$char = '';
			}

			$buffer = '';
		}

		$buffer.= $char;

		if (empty($code))
		{
			if ($options & self::FORMAT_RANGES || $options & self::FORMAT_FOLDING)
			{
				$buffer.= str_repeat('</span>', (($options & self::FORMAT_RANGES) ? array_sum($levels) : $levels['{']));
			}

			$output.= $buffer;

			break;
		}
	}

	return $output;
}

/**
* Highlight for CSS
* @param string $code
* @param integer $options
* @return string
*/

static private function modeCss($code, $options)
{
	$buffer = $output = '';
	$state = self::STATE_SELECTORS;
	$levels = array('{' => 0, '(' => 0,  '[' => 0, 'def' => 0);
	$map = array('}' => '{', ')' => '(', ']' => '[');

	while (TRUE)
	{
		$char = (empty($code) ? '' : substr($code, 0, 1));
		$code = substr($code, 1);
		$oldState = $state;

		if (empty($code) && $char !== '}')
		{
			$buffer.= $char;
			$char = '';
			$state = self::STATE_NONE;
		}
		else if ($state == self::STATE_CODE || $state == self::STATE_SELECTORS)
		{
			if ($char == '/' && substr($code, 0, 1) == '*')
			{
				$state = self::STATE_COMMENT;
			}
			else if (($char == '\'' || $char ==  '"') && (substr($buffer, -1) != '\\' || substr($buffer, -2) == '\\\\'))
			{
				$state = self::STATE_VALUE;
			}
			else if (isset($levels[$char]))
			{
				++$levels[$char];

				if ($char == '{' && (!stristr($buffer, '@media') || $levels['{'] > 1))
				{
					++$levels['def'];

					$state = self::STATE_CODE;
				}

				$char = (($options & self::FORMAT_RANGES || ($options & self::FORMAT_FOLDING && $char == '{')) ? '<span class="'.(($options & self::FORMAT_RANGES) ? 'range' : '').(($options & self::FORMAT_FOLDING && $char == '{') ? ' foldable' : '').'">' : '').'<span class="punctuation">'.$char.'</span>'.(($char == '{' && $levels['def'] == 1) ? '<span class="definition">' : '');
			}
			else if (isset($map[$char]))
			{
				--$levels[$map[$char]];

				if ($char == '}' && $levels['def'] > 0)
				{
					--$levels['def'];

					$buffer.= '</span>';
					$state = self::STATE_SELECTORS;
				}

				$char = '<span class="punctuation">'.$char.'</span>'.(((($options & self::FORMAT_RANGES || ($options & self::FORMAT_FOLDING && $char == '}')) && $levels[$map[$char]] >= 0)) ? '</span>' : '');
			}
		}
		else if ($state == self::STATE_COMMENT && $char == '/' && substr($buffer, -1) == '*')
		{
			$state = self::STATE_CODE;
		}
		else if ($state == self::STATE_VALUE && ($char == '\'' || $char ==  '"') && $char == substr($buffer, 0, 1) && (substr($buffer, -1) != '\\' || substr($buffer, -2) == '\\\\'))
		{
			$state = self::STATE_CODE;
		}

		if ($state !== $oldState)
		{
			if ($oldState == self::STATE_SELECTORS)
			{
				$output.= preg_replace(
	array(
		'#@(charset|import|media|page|font-face|namespace)#Ssi',
		'#(\.[a-z]\w*)#Ssi',
		'#(?<=\n|\r|\}|,\s|,)(\#[a-z]\w*)#Ssi',
		'#(:{1,2})(link|visited|active|hover|focus|lang|nth-child|nth-last-child|nth-of-type|nth-last-of-type|first-child|last-child|first-of-type|last-of-type|only-child|only-of-type|root|empty|target|enabled|disabled|checked|not|first-line|first-letter|before|after|selection)#Ssi',
		'#\b([a-z]+)\b(\s*'.(($options & self::FORMAT_RANGES) ? '<span class="range">' : '').'<span class="punctuation">\()#Ssi',
		'#(?<!class|">|"|span)((?:~|\*|=|,|\(|\)|\/|&lt;|&gt;|\[|\])+)(?!/?span)#Ssi',
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
	);
			}
			else if ($oldState == self::STATE_CODE)
			{
				$output.= preg_replace(
	array(
		'#\b([a-z]+)\b(\s*'.(($options & self::FORMAT_RANGES) ? '<span class="range">' : '').'<span class="punctuation">\()#Ssi',
		'#((?<=^|;|\s)[a-z\-]+(?=:)|(?:!important))#Ssi',
		'#(?<!">)((?:(?:\+|-)\s*)?(?:\#\w{6}|\#\w{3}|(?:\d+\.)?\d+(?:px|pt|pc|ex|em|in|cm|mm|deg|grad|rad|ms|s|k?hz|\%)?))#Ssi',
		'#(?<!class|">|"|span)((?::|;|=|\*|,|\(|\)|\/|&lt;|&gt;)+)(?!/?span)#Ssi',
		),
	array(
		'<span class="function">\\1</span>\\2',
		'<span class="keyword">\\1</span>',
		'<span class="number">\\1</span>',
		'<span class="punctuation">\\1</span>',
	),
	$buffer
	);
			}
			else if ($oldState == self::STATE_COMMENT)
			{
				$output.= '<span class="comment">'.preg_replace('#\b(FIXME|NOTICE|NOTE|TODO|WARNING)\b#i', '<span class="notice">\\1</span>', $buffer.$char).'</span>';
				$char = '';
			}
			else if ($oldState == self::STATE_VALUE)
			{
				$output.= '<span class="value">'.$buffer.$char.'</span>';
				$char = '';
			}

			$buffer = '';
		}

		$buffer.= $char;

		if (empty($code))
		{
			$output.= $buffer.str_repeat('</span>', (($options & self::FORMAT_RANGES) ? array_sum($levels) : ((($options & self::FORMAT_FOLDING) ? $levels['{'] : 0) + $levels['def'])));

			break;
		}
	}

	return $output;
}

/**
* Highlight for Gettext files
* @param string $code
* @return string
*/

static private function modeGettext($code, $options)
{
	$buffer = $output = '';
	$state = self::STATE_CODE;

	while (TRUE)
	{
		$char = (empty($code) ? '' : substr($code, 0, 1));
		$code = substr($code, 1);
		$oldState = $state;

		if (empty($code))
		{
			$buffer.= $char;
			$char = '';
			$state = self::STATE_NONE;
		}
		else if ($state == self::STATE_CODE)
		{
			if ($char == '#')
			{
				$state = self::STATE_COMMENT;
			}
			else if ($char ==  '"' && (substr($buffer, -1) != '\\' || substr($buffer, -2) == '\\\\'))
			{
				$state = self::STATE_VALUE;
			}
		}
		else if ($state == self::STATE_COMMENT && $char == "\n")
		{
			$state = self::STATE_CODE;
		}
		else if ($state == self::STATE_VALUE && $char ==  '"' && $char == substr($buffer, 0, 1) && (substr($buffer, -1) != '\\' || substr($buffer, -2) == '\\\\'))
		{
			$state = self::STATE_CODE;
		}

		if ($state !== $oldState)
		{
			if ($oldState == self::STATE_CODE)
			{
				$output.= preg_replace(
	array(
		'#(msgid(?:_plural)?|msgstr)#i',
		'#(?<!">|[a-z-_])((?:-\s*)?(?:(?:\d+\.)?\d+))\b#Ssi',
		'#(?<!class|">|"|span)((?::|;|-|\||\+|=|\*|!|~|\.|,|\/|@|\%|&lt;|&gt;|&amp;|\(|\)|\{|\}|\[|\])+)(?!/?span)#Ssi',
		),
	array(
		'<span class="keyword">\\1</span>',
		'<span class="number">\\1</span>',
		'<span class="punctuation">\\1</span>',
		),
	$buffer
	);
			}
			else if ($oldState == self::STATE_COMMENT)
			{
				$output.= '<span class="comment">'.$buffer.'</span>'.$char;
				$char = '';
			}
			else if ($oldState == self::STATE_VALUE)
			{
				$output.= '<span class="value">'.str_replace('\n', '<span class="punctuation">\n</span>', $buffer.$char).'</span>';
				$char = '';
			}

			$buffer = '';
		}

		$buffer.= $char;

		if (empty($code))
		{
			break;
		}
	}

	return $output;
}

/**
* Highlight for(X)HTML
* @param string $code
* @param integer $options
* @return string
*/

static private function modeHtml($code, $options)
{
	$buffer = $output = '';
	$state = self::STATE_CODE;

	while (TRUE)
	{
		$char = (empty($code) ? '' : substr($code, 0, 1));
		$code = substr($code, 1);
		$oldState = $state;

		if (empty($code))
		{
			$buffer.= $char;
			$char = '';
			$state = self::STATE_NONE;
		}
		else if ($state == self::STATE_CODE)
		{
			if ($char == '&' && substr($code, 0, 6) == 'lt;!--')
			{
				$state = self::STATE_COMMENT;
			}
			else if (($char == '\'' || $char ==  '"') && (substr($buffer, -1) != '\\' || substr($buffer, -2) == '\\\\'))
			{
				$state = self::STATE_VALUE;
			}
		}
		else if ($state == self::STATE_COMMENT && $char == ';' && substr($buffer, -5) == '--&gt')
		{
			$state = self::STATE_CODE;
		}
		else if ($state == self::STATE_VALUE && ($char == '\'' || $char ==  '"') && $char == substr($buffer, 0, 1) && (substr($buffer, -1) != '\\' || substr($buffer, -2) == '\\\\'))
		{
			$state = self::STATE_CODE;
		}

		if ($state !== $oldState)
		{
			if ($oldState == self::STATE_CODE)
			{
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
			}
			else if ($oldState == self::STATE_COMMENT)
			{
				if ($char == "\n")
				{
					$code = "\n".$code;
					$char = '';
				}

				$output.= '<span class="comment">'.preg_replace('#\b(FIXME|NOTICE|NOTE|TODO|WARNING)\b#i', '<span class="notice">\\1</span>', $buffer.$char).'</span>';
				$char = '';
			}
			else if ($oldState == self::STATE_VALUE)
			{
				$output.= '<span class="value">'.$buffer.$char.'</span>';
				$char = '';
			}

			$buffer = '';
		}

		$buffer.= $char;

		if (empty($code))
		{
			$output.= $buffer;

			break;
		}
	}

	self::$options = &$options;

	$output = preg_replace_callback(array('#(&lt;<span class="tag">style</span>.*&gt;)(.*)(&lt;<span class="tag">/style</span>&gt;)#siU', '#(&lt;<span class="tag">script</span>.*&gt;)(.*)(&lt;<span class="tag">/script</span>&gt;)#siU', '#(&lt;\?(?:php)?(?!xml)(?U).+\?&gt;)#si'), function ($matches)
{
	$code = &$matches[1];
	$region = NULL;
	$mode = 'php';

	if (count($matches) == 4)
	{
		$code = &$matches[2];
		$region = array(&$matches[1], $matches[3]);
		$mode = ((substr($matches[1], 0, 34) == '&lt;<span class="tag">style</span>') ? 'css' : 'javascript');
	}

	return ($region ? ((SyntaxHighlight::$options & SyntaxHighlight::FORMAT_FOLDING) ? '<span class="foldable">' : '').'<span class="region">'.$region[0].'</span>' : '').SyntaxHighlight::highlightString(str_replace(array('&amp;', '&lt;', '&gt;'), array('&', '<', '>'), preg_replace('#<span class="(?:[a-z]*)">(.*)</span>#sU', '\\1', $code)), $mode, SyntaxHighlight::$options, TRUE).($region ? '<span class="region">'.$region[1].'</span>'.((SyntaxHighlight::$options & SyntaxHighlight::FORMAT_FOLDING) ? '</span>' : '') : '');
}, $output);

	return $output;
}

/**
* Highlight for INI
* @param string $code
* @param integer $options
* @return string
*/

static private function modeIni($code, $options)
{
	$buffer = $output = '';
	$state = self::STATE_CODE;

	while (TRUE)
	{
		$char = (empty($code) ? '' : substr($code, 0, 1));
		$code = substr($code, 1);
		$oldState = $state;

		if (empty($code))
		{
			$buffer.= $char;
			$char = '';
			$state = self::STATE_NONE;
		}
		else if ($state == self::STATE_CODE)
		{
			if ($char == '/' || $char == ';')
			{
				$state = self::STATE_COMMENT;
			}
			else if (($char == '\'' || $char ==  '"') && (substr($buffer, -1) != '\\' || substr($buffer, -2) == '\\\\'))
			{
				$state = self::STATE_VALUE;
			}
		}
		else if ($state == self::STATE_COMMENT && $char == "\n")
		{
			$state = self::STATE_CODE;
		}
		else if ($state == self::STATE_VALUE && ($char == '\'' || $char ==  '"') && $char == substr($buffer, 0, 1) && (substr($buffer, -1) != '\\' || substr($buffer, -2) == '\\\\'))
		{
			$state = self::STATE_CODE;
		}

		if ($state !== $oldState)
		{
			if ($oldState == self::STATE_CODE)
			{
				$output.= preg_replace(
	array(
		'#^(\[\w+\])$#SsiUm',
		'#^([^\#;]+)(?<!<span class)= (.+)$#SsiUm',
		),
	array(
		'<span class="tag">\\1</span>',
		'<span class="keyword">\\1</span><span class="punctuation">=</span><span class="value">\\2</span>',
		),
	$buffer
	);
			}
			else if ($oldState == self::STATE_COMMENT)
			{
				$code = "\n".$code;
				$output.= '<span class="comment">'.preg_replace('#\b(FIXME|NOTICE|NOTE|TODO|WARNING)\b#i', '<span class="notice">\\1</span>', $buffer).'</span>';
				$char = '';
			}
			else if ($oldState == self::STATE_VALUE)
			{
				$output.= '<span class="value">'.$buffer.$char.'</span>';
				$char = '';
			}

			$buffer = '';
		}

		$buffer.= $char;

		if (empty($code))
		{
			$output.= $buffer;

			break;
		}
	}

	return $output;
}

/**
* Highlight for Java
* @param string $code
* @param integer $options
* @return string
*/

static private function modeJava($code, $options)
{
	$buffer = $output = '';
	$state = self::STATE_CODE;
	$levels = array('{' => 0, '(' => 0, '[' => 0);
	$map = array('}' => '{', ')' => '(', ']' => '[');

	while (TRUE)
	{
		$char = (empty($code) ? '' : substr($code, 0, 1));
		$code = substr($code, 1);
		$oldState = $state;

		if (empty($code))
		{
			$buffer.= $char;
			$char = '';
			$state = self::STATE_NONE;
		}
		else if ($state == self::STATE_CODE)
		{
			if ($char == '/' && (substr($code, 0, 1) == '/' || substr($code, 0, 1) == '*'))
			{
				$state = (($char == '/' && substr($code, 0, 2) == '**') ? self::STATE_DOCUMENTATION : self::STATE_COMMENT);
			}
			else if (($char == '\'' || $char ==  '"') && (substr($buffer, -1) != '\\' || substr($buffer, -2) == '\\\\'))
			{
				$state = self::STATE_VALUE;
			}
			else if (isset($levels[$char]))
			{
				++$levels[$char];

				$char = (($options & self::FORMAT_RANGES || ($options & self::FORMAT_FOLDING && $char == '{')) ? '<span class="'.(($options & self::FORMAT_RANGES) ? 'range' : '').(($options & self::FORMAT_FOLDING && $char == '{') ? ' foldable' : '').'">' : '').'<span class="punctuation">'.$char.'</span>';
			}
			else if (isset($map[$char]))
			{
				--$levels[$map[$char]];

				$char = '<span class="punctuation'.(($options & self::FORMAT_RANGES && $levels[$map[$char]] >= 0) ? ' range' : '').'">'.$char.'</span>'.(((($options & self::FORMAT_RANGES || ($options & self::FORMAT_FOLDING && $char == '}')) && $levels[$map[$char]] >= 0)) ? '</span>' : '');
			}
		}
		else if ($state == self::STATE_DOCUMENTATION && $char == '/' && substr($buffer, -1) == '*')
		{
			$state = self::STATE_CODE;
		}
		else if ($state == self::STATE_COMMENT && (($char == "\n" && substr($buffer, 0, 2) == '//') || ($char == '/' && substr($buffer, 0, 2) == '/*' && substr($buffer, -1) == '*')))
		{
			$state = self::STATE_CODE;
		}
		else if ($state == self::STATE_VALUE && ($char == '\'' || $char ==  '"') && $char == substr($buffer, 0, 1) && (substr($buffer, -1) != '\\' || substr($buffer, -2) == '\\\\'))
		{
			$state = self::STATE_CODE;
		}

		if ($state !== $oldState)
		{
			if ($oldState == self::STATE_CODE)
			{
				$output.= preg_replace(
	array(
		'#\b(abstract|(?<!<span )class|continue|enum|extends|false|finally|implements|instanceof|@?interface|native|new|null|private|protected|public|super|static|strictfp|synchronized|this|throws|transient|true|volatile)\b#Ss',
		'#^(package|import)(\s+)(.+);#Sm',
		'#(?<!">)\b\.([\w_-]+)\b#Ssi',
		'#\b(break|case|catch|continue|default|do|else|for|goto|if|return|throw|try|while)\b#Ss',
		'#\b(boolean|byte|char|const|double|final|float|int|long|short|void)\b#Ss',
		'#(?<!">|[a-z-_])((?:-\s*)?(?:(?:\d+\.)?\d+))\b#Ssi',
		'#(?<!class|">|"|span)((?::|;|-|\||\+|=|\*|!|~|\.|,|\/|@|\%|&lt;|&gt;|&amp;)+)(?!/?span)#Ssi',
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
			}
			else if ($oldState == self::STATE_DOCUMENTATION)
			{
				$output.= '<span class="documentation">'.self::modeJavadoc($buffer.$char, $options).'</span>';
				$char = '';
			}
			else if ($oldState == self::STATE_COMMENT)
			{
				if ($char == "\n")
				{
					$code = "\n".$code;
					$char = '';
				}

				$output.= '<span class="comment">'.preg_replace('#\b(FIXME|NOTICE|NOTE|TODO|WARNING)\b#i', '<span class="notice">\\1</span>', $buffer.$char).'</span>';
				$char = '';
			}
			else if ($oldState == self::STATE_VALUE)
			{
				$output.= '<span class="value">'.$buffer.$char.'</span>';
				$char = '';
			}

			$buffer = '';
		}

		$buffer.= $char;

		if (empty($code))
		{
			if ($options & self::FORMAT_RANGES || $options & self::FORMAT_FOLDING)
			{
				$buffer.= str_repeat('</span>', (($options & self::FORMAT_RANGES) ? array_sum($levels) : $levels['{']));
			}

			$output.= $buffer;

			break;
		}
	}

	return $output;
}

/**
* Highlight for JavaDoc
* @param string $code
* @param integer $options
* @return string
*/

static private function modeJavadoc($code, $options)
{
	return preg_replace(
	array(
		'#(\s*\*?\s*)(@(?:param|return|throws))(\s+)(\w+)#i',
		'#(\s*\*?\s*)(@(?:author|version|see|since|serial(?:Field|Data)?))(\s+)(.+)$#im',
		'#(\s*\*?\s*)(@deprecated)#i',
		'#(\s*\*?\s*)\{(@link)\}(\s+)(.+)#i',
		),
	array(
		'\\1<span class="tag">\\2</span>\\3<span class="value">\\4</span>',
		'\\1<span class="tag">\\2</span>\\3<span class="value">\\4</span>',
		'\\1<span class="tag">\\2</span>',
		'\\1{<span class="tag">\\2</span>\\3<span class="value">\\4</span>}',
		),
	$code);
}

/**
* Highlight for JavaScript
* @param string $code
* @param integer $options
* @return string
*/

static private function modeJavascript($code, $options)
{
	$buffer = $output = '';
	$state = self::STATE_CODE;
	$levels = array('{' => 0, '(' => 0, '[' => 0);
	$map = array('}' => '{', ')' => '(', ']' => '[');

	while (TRUE)
	{
		$char = (empty($code) ? '' : substr($code, 0, 1));
		$code = substr($code, 1);
		$oldState = $state;

		if (empty($code))
		{
			$buffer.= $char;
			$char = '';
			$state = self::STATE_NONE;
		}
		else if ($state == self::STATE_CODE)
		{
			if ($char == '/' && (substr($code, 0, 1) == '/' || substr($code, 0, 1) == '*'))
			{
				$state = self::STATE_COMMENT;
			}
			else if (($char == '\'' || $char ==  '"') && (substr($buffer, -1) != '\\' || substr($buffer, -2) == '\\\\'))
			{
				$state = self::STATE_VALUE;
			}
			else if (isset($levels[$char]))
			{
				++$levels[$char];

				$char = (($options & self::FORMAT_RANGES || ($options & self::FORMAT_FOLDING && $char == '{')) ? '<span class="'.(($options & self::FORMAT_RANGES) ? 'range' : '').(($options & self::FORMAT_FOLDING && $char == '{') ? ' foldable' : '').'">' : '').'<span class="punctuation">'.$char.'</span>';
			}
			else if (isset($map[$char]))
			{
				--$levels[$map[$char]];

				$char = '<span class="punctuation">'.$char.'</span>'.(((($options & self::FORMAT_RANGES || ($options & self::FORMAT_FOLDING && $char == '}')) && $levels[$map[$char]] >= 0)) ? '</span>' : '');
			}
		}
		else if ($state == self::STATE_COMMENT && (($char == "\n" && substr($buffer, 0, 2) == '//') || ($char == '/' && substr($buffer, 0, 2) == '/*' && substr($buffer, -1) == '*')))
		{
			$state = self::STATE_CODE;
		}
		else if ($state == self::STATE_VALUE && ($char == '\'' || $char ==  '"') && $char == substr($buffer, 0, 1) && (substr($buffer, -1) != '\\' || substr($buffer, -2) == '\\\\'))
		{
			$state = self::STATE_CODE;
		}

		if ($state !== $oldState)
		{
			if ($oldState == self::STATE_CODE)
			{
				$output.= preg_replace(
	array(
		'#\b(in|with|try|catch|finally|new|var|function|delete|true|false|void|throw|typeof|const)\b#Ss',
		'#\b(as|case|default|if|else|elseif|while|do|for|foreach|break|continue|switch|return)\b#Ss',
		'#\b(Anchor|Applet|Area|Array|Boolean|Button|Checkbox|Date|document|window|Image|FileUpload|Form|Frame|Function|Hidden|Link|MimeType|Math|Max|Min|Layer|navigator|Object|Password|Plugin|Radio|RegExp|Reset|Screen|Select|String|Text|Textarea|this|Window)\b#Ss',
		'#(?<=\.)\b([a-z]+)\b(\s*'.(($options & self::FORMAT_RANGES) ? '<span class="range">' : '').'<span class="punctuation">\()#Ssi',
		'#\b([a-z]+)\b(\s*'.(($options & self::FORMAT_RANGES) ? '<span class="range">' : '').'<span class="punctuation">\()#Ssi',
		'#(?<=\.)\b([a-z]+)\b#Ssi',
		'#(?<!">)((?:-\s*)?(?:\#\w{6}|\#\w{3}|(?:\d+\.)?\d+))\b#Ssi',
		'#(?<!class|">|"|span)((?::|;|-|\||\+|=|\*|!|~|\.|,|\/|@|\%|&lt;|&gt;|&amp;)+)(?!/?span)#Ssi',
		),
	array(
		'<span class="keyword">\\1</span>',
		'<span class="control">\\1</span>',
		'<span class="object">\\1</span>',
		'<span class="method">\\1</span>\\2',
		'<span class="function">\\1</span>\\2',
		'<span class="property">\\1</span>',
		'<span class="number">\\1</span>',
		'<span class="punctuation">\\1</span>',
		),
	$buffer
	);
			}
			else if ($oldState == self::STATE_COMMENT)
			{
				if ($char == "\n")
				{
					$code = "\n".$code;
					$char = '';
				}

				$output.= '<span class="comment">'.preg_replace('#\b(FIXME|NOTICE|NOTE|TODO|WARNING)\b#i', '<span class="notice">\\1</span>', $buffer.$char).'</span>';
				$char = '';
			}
			else if ($oldState == self::STATE_VALUE)
			{
				$output.= '<span class="value">'.$buffer.$char.'</span>';
				$char = '';
			}

			$buffer = '';
		}

		$buffer.= $char;

		if (empty($code))
		{
			if ($options & self::FORMAT_RANGES || $options & self::FORMAT_FOLDING)
			{
				$buffer.= str_repeat('</span>', (($options & self::FORMAT_RANGES) ? array_sum($levels) : $levels['{']));
			}

			$output.= $buffer;

			break;
		}
	}

	return $output;
}

/**
* Highlight for Perl
* @param string $code
* @param integer $options
* @return string
*/

static private function modePerl($code, $options)
{
	$buffer = $output = '';
	$state = self::STATE_CODE;
	$levels = array('{' => 0, '(' => 0, '[' => 0);
	$map = array('}' => '{', ')' => '(', ']' => '[');

	while (TRUE)
	{
		$char = (empty($code) ? '' : substr($code, 0, 1));
		$code = substr($code, 1);
		$oldState = $state;

		if (empty($code))
		{
			$buffer.= $char;
			$char = '';
			$state = self::STATE_NONE;
		}
		else if ($state == self::STATE_CODE)
		{
			if ($char =='#' || ($char == '/' && (substr($code, 0, 1) == '/' || substr($code, 0, 1) == '*')))
			{
				$state = self::STATE_COMMENT;
			}
			else if (($char == '\'' || $char ==  '"') && (substr($buffer, -1) != '\\' || substr($buffer, -2) == '\\\\'))
			{
				$state = self::STATE_VALUE;
			}
			else if (isset($levels[$char]))
			{
				++$levels[$char];

				$char = (($options & self::FORMAT_RANGES || ($options & self::FORMAT_FOLDING && $char == '{')) ? '<span class="'.(($options & self::FORMAT_RANGES) ? 'range' : '').(($options & self::FORMAT_FOLDING && $char == '{') ? ' foldable' : '').'">' : '').'<span class="punctuation">'.$char.'</span>';
			}
			else if (isset($map[$char]))
			{
				--$levels[$map[$char]];

				$char = '<span class="punctuation">'.$char.'</span>'.(((($options & self::FORMAT_RANGES || ($options & self::FORMAT_FOLDING && $char == '}')) && $levels[$map[$char]] >= 0)) ? '</span>' : '');
			}
		}
		else if ($state == self::STATE_COMMENT && (($char == "\n" && (substr($buffer, 0, 1) == '#' || substr($buffer, 0, 2) == '//')) || ($char == '/' && substr($buffer, 0, 2) == '/*' && substr($buffer, -1) == '*')))
		{
			$state = self::STATE_CODE;
		}
		else if ($state == self::STATE_VALUE && ($char == '\'' || $char ==  '"') && $char == substr($buffer, 0, 1) && (substr($buffer, -1) != '\\' || substr($buffer, -2) == '\\\\'))
		{
			$state = self::STATE_CODE;
		}

		if ($state !== $oldState)
		{
			if ($oldState == self::STATE_CODE)
			{
				$output.= preg_replace(
	array(
		'#(?<!<span )\b(strict|english|warnings|vars|subs|utf8|sigtrap|locale|open|less|integer|filetest|constant|bytes|diagnostics|BEGIN|END|__END__|__DATA__|__FILE__|__LINE__|__PACKAGE__)\b#',
		'#\b(if|unless|else|elsif|while|until|for|each|foreach|next|last|break|continue|return|use|no|require|my|our|local|require|package|sub|do)\b#',
		'#\b(?<!\$)(abs|accept|alarm|atan2|bind|binmode|bless|caller|chdir|chmod|chomp|chop|chown|chr|chroot|close|closedir|connect|cos|crypt|dbmclose|dbmopen|defined|delete|die|dump|endgrent|endhostent|endnetent|endprotoent|endpwent|endservent|eof|eval|exec|exists|exit|exp|fcntl|fileno|flock|fork|format|formline|getc|getgrent|getgrgid|getgrnam|gethostbyaddr|gethostbyname|gethostent|getlogin|getnetbyaddr|getnetbyname|getnetent|getpeername|getpgrp|getppid|getpriority|getprotobyname|getprotobynumber|getprotoent|getpwent|getpwnam|getpwuid|getservbyname|getservbyport|getservent|getsockname|getsockopt|glob|gmtime|goto|grep|hex|import|index|int|ioctl|join|keys|kill|last|lc|lcfirst|length|link|listen|localtime|lock|log|lstat|map|mkdir|msgctl|msgget|msgrcv|msgsnd|oct|open|opendir|ord|pack|package|pipe|pop|pos|print|printf|prototype|push|quotemeta|rand|read|readdir|readline|readlink|recv|redo|ref|rename|reset|return|reverse|rewinddir|rindex|rmdir|scalar|seek(?:dir)?|select|semctl|semget|semop|send|setgrent|sethostent|setnetent|setpgrp|setpriority|setprotoent|setpwent|setservent|setsockopt|shift|shmctl|shmget|shmread|shmwrite|shutdown|sin|sleep|socket|socketpair|sort|splice|split|sprintf|sqrt|srand|stat|study|sub|substr|symlink|syscall|sysread|sysseek|system|syswrite|tell|telldir|tie|time|times|truncate|uc(?:first)?|umask|undef|unlink|unpack|unshift|untie|utime|values|vec|wait|waitpid|wantarray|warn|write)\b#i',
		'#((?:\$|%|&|@)(?!lt;|gt;)[a-z_][\w-]*)\b#i',
		'#(?<!">|[a-z-_])((?:-\s*)?(?:(?:\d+\.)?\d+)|0x[0-9a-f]+)\b#Ssi',
		'#(?<!class|">|"|span|&lt|&gt)((?::|;|-|\||\\|\+|=|\*|!|~|\.|,|\/|@|\%|&lt;|&gt;|&amp;|::|\band\b|\bor\b|\bnot\b|\beq\b|\bne\b)+)(?!/?span)#Ssi',
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
			}
			else if ($oldState == self::STATE_COMMENT)
			{
				if ($char == "\n")
				{
					$code = "\n".$code;
					$char = '';
				}

				$output.= '<span class="comment">'.preg_replace('#\b(FIXME|NOTICE|NOTE|TODO|WARNING)\b#i', '<span class="notice">\\1</span>', $buffer.$char).'</span>';
				$char = '';
			}
			else if ($oldState == self::STATE_VALUE)
			{
				$output.= '<span class="value">'.$buffer.$char.'</span>';
				$char = '';
			}

			$buffer = '';
		}

		$buffer.= $char;

		if (empty($code))
		{
			if ($options & self::FORMAT_RANGES || $options & self::FORMAT_FOLDING)
			{
				$buffer.= str_repeat('</span>', (($options & self::FORMAT_RANGES) ? array_sum($levels) : $levels['{']));
			}

			$output.= $buffer;

			break;
		}
	}

	return $output;
}

/**
* Highlight for PHP
* @param string $code
* @param integer $options
* @return string
*/

static private function modePhp($code, $options)
{
	$buffer = $output = '';
	$state = self::STATE_NONE;
	$levels = array();
	$map = array('}' => '{', ')' => '(', ']' => '[');

	while (TRUE)
	{
		$char = (empty($code) ? '' : substr($code, 0, 1));
		$code = substr($code, 1);
		$oldState = $state;

		if (empty($code) && !($char == ';' && substr($buffer, -4) == '?&gt'))
		{
			$buffer.= $char;
			$char = '';
			$state = self::STATE_NONE;
		}
		else if ($state == self::STATE_NONE && $char == '?' && substr($buffer, -4) == '&lt;')
		{
			if (substr($code, 0, 3) == 'php')
			{
				$char.= 'php';
				$code = substr($code, 3);
			}

			$output.= substr($buffer, 0, -4).'<span class="region">'.substr($buffer, -4).'<span class="tag">'.$char.'</span></span>';
			$buffer = $char = '';
			$state = self::STATE_CODE;
			$levels = array('{' => 0, '(' => 0, '[' => 0);
		}
		else if ($state == self::STATE_CODE)
		{
			if ($char == ';' && substr($buffer, -4) == '?&gt')
			{
				$buffer = substr($buffer, 0, -4);
				$char = '<span class="region"><span class="tag">?</span>&gt;</span>';
				$state = self::STATE_NONE;
			}
			else if ($char == '#' || ($char == '/' && (substr($code, 0, 1) == '/' || substr($code, 0, 1) == '*')))
			{
				$state = (($char == '/' && substr($code, 0, 2) == '**') ? self::STATE_DOCUMENTATION : self::STATE_COMMENT);
			}
			else if (($char == '\'' || $char ==  '"') && (substr($buffer, -1) != '\\' || substr($buffer, -2) == '\\\\'))
			{
				$state = self::STATE_VALUE;
			}
			else if (isset($levels[$char]))
			{
				++$levels[$char];

				$char = (($options & self::FORMAT_RANGES || ($options & self::FORMAT_FOLDING && $char == '{')) ? '<span class="'.(($options & self::FORMAT_RANGES) ? 'range' : '').(($options & self::FORMAT_FOLDING && $char == '{') ? ' foldable' : '').'">' : '').'<span class="punctuation">'.$char.'</span>';
			}
			else if (isset($map[$char]))
			{
				--$levels[$map[$char]];

				$char = '<span class="punctuation">'.$char.'</span>'.(((($options & self::FORMAT_RANGES || ($options & self::FORMAT_FOLDING && $char == '}')) && $levels[$map[$char]] >= 0)) ? '</span>' : '');
			}
		}
		else if ($state == self::STATE_DOCUMENTATION && $char == '/' && substr($buffer, -1) == '*')
		{
			$state = self::STATE_CODE;
		}
		else if ($state == self::STATE_COMMENT && (($char == "\n" && (substr($buffer, 0, 1) == '#' || substr($buffer, 0, 2) == '//')) || ($char == '/' && substr($buffer, 0, 2) == '/*' && substr($buffer, -1) == '*')))
		{
			$state = self::STATE_CODE;
		}
		else if ($state == self::STATE_VALUE && ($char == '\'' || $char ==  '"') && $char == substr($buffer, 0, 1) && (substr($buffer, -1) != '\\' || substr($buffer, -2) == '\\\\'))
		{
			$state = self::STATE_CODE;
		}

		if ($state !== $oldState)
		{
			if ($oldState == self::STATE_CODE)
			{
				$output.= preg_replace(
	array(
		'#(?<!\$)\b(abstract|(?<!<span )class|clone|const|exception|extends|final|function|implements|instanceof|interface|new|self|static|parent|private|protected|public|use|and|x?or|var|FALSE|TRUE|NULL|DEFAULT_INCLUDE_PATH|__(?:FILE|LINE|DIR|FUNCTION|CLASS|TRAIT|NAMESPACE|COMPILER_HALT_OFFSET)__|E_(?:ERROR|WARNING|PARSE|NOTICE|DEPRECATED|STRICT|ALL|CORE_(?:ERROR|WARNING)|COMPILE_(?:ERROR|WARNING)|USER_(ERROR|WARNING|NOTICE|DEPRECATED))|(?:PHP|PEAR)_[A-Z0-9_]+)\b#Si',
		'#\b(as|case|catch|default|if|isset|die|exit|else|elseif|unset|empty|while|do|for(?:each)?|break|continue|switch|throw|try|finally|yield|declare|return|require(?:_once)?|include(?:_once)?|endif|endwhile|endfor|endforeach|endswitch)\b#S',
		'#\b(Exception)\b#S',
		'#(?<!\$|->|::)(\s*)\b([a-z0-9_\-]+)\b(\s*'.(($options & self::FORMAT_RANGES) ? '<span class="range">' : '').'<span class="punctuation">\()#Ssi',
		'#(?<=->|::)(\s*)\b([a-z0-9_\-]+)\b(\s*'.(($options & self::FORMAT_RANGES) ? '<span class="range">' : '').'<span class="punctuation">\()#Ssi',
		'#(\(\s*)(int(?:teger)?|bool(?:ean)?|float|real|double|string|binary|array|object|unset)(\s*\))#Si',
		'#(\$[a-z_][\w-]*)\b#Si',
		'#<span class="variable">(\$_(?:POST|GET|SESSION|SERVER|FILES|REQUEST|COOKIE|ENV))\b#Si',
		'#(?<!">|[a-z-_])((?:-\s*)?(?:(?:\d+\.)?\d+)|0x[0-9a-f]+)\b#Si',
		'#(?<!class|">|"|span|&lt|&gt)((?::|;|-|\||\+|=|\*|!|~|\.|,|\/|@|\%|&lt;|&gt;|&amp;)+)(?!/?span)#Ssi',
		'#(<span class="keyword">function</span>\s+)<span class="function">([a-z0-9_]+)</span>#Ssi',
		),
	array(
		'<span class="keyword">\\1</span>',
		'<span class="control">\\1</span>',
		'<span class="object">\\1</span>',
		'\\1<span class="function">\\2</span>\\3',
		'\\1<span class="method">\\2</span>\\3',
		'\\1<span class="datatype">\\2</span>\\3',
		'<span class="variable">\\1</span>',
		'<span class="identifier variable">\\1',
		'<span class="number">\\1</span>',
		'<span class="punctuation">\\1</span>',
		'\\1\\2',
		),
	$buffer
	);

				if ($state == self::STATE_NONE && ($options & self::FORMAT_RANGES || $options & self::FORMAT_FOLDING))
				{
					$buffer.= str_repeat('</span>', (($options & self::FORMAT_RANGES) ? array_sum($levels) : $levels['{']));
				}
			}
			else if ($oldState == self::STATE_DOCUMENTATION)
			{
				$output.= '<span class="documentation">'.self::modePhpdoc($buffer.$char, $options).'</span>';
				$char = '';
			}
			else if ($oldState == self::STATE_COMMENT)
			{
				if ($char == "\n")
				{
					$code = "\n".$code;
					$char = '';
				}

				$output.= '<span class="comment">'.preg_replace('#\b(FIXME|NOTICE|NOTE|TODO|WARNING)\b#i', '<span class="notice">\\1</span>', $buffer.$char).'</span>';
				$char = '';
			}
			else if ($oldState == self::STATE_VALUE)
			{
				$output.= '<span class="value">'.$buffer.$char.'</span>';
				$char = '';
			}

			$buffer = '';
		}

		$buffer.= $char;

		if (empty($code))
		{
			$output.= $buffer;

			break;
		}
	}

	return $output;
}

/**
* Highlight for PHPDoc
* @param string $code
* @param integer $options
* @return string
*/

static private function modePhpdoc($code, $options)
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
		'\\1<span class="tag">\\2</span>\\3<span class="value">\\4</span>',
		'\\1<span class="tag">\\2</span>\\3<span class="keyword">\\4</span>',
		'\\1<span class="tag">\\2</span>',
		'\\1<span class="tag">\\2</span>\\3<span class="datatype">\\4</span>\\5<span class="variable">\\6</span>',
		'\\1<span class="tag">\\2</span>\\3<span class="variable">\\4</span>',
		'\\1<span class="tag">\\2</span>\\3<span class="datatype">\\4</span>',
		),
	$code);
}

/**
* Highlight for Python
* @param string $code
* @param integer $options
* @return string
*/

static private function modePython($code, $options)
{
	$buffer = $output = '';
	$state = self::STATE_CODE;
	$levels = array('{' => 0, '(' => 0, '[' => 0);
	$map = array('}' => '{', ')' => '(', ']' => '[');

	while (TRUE)
	{
		$char = (empty($code) ? '' : substr($code, 0, 1));
		$code = substr($code, 1);
		$oldState = $state;

		if (empty($code))
		{
			$buffer.= $char;
			$char = '';
			$state = self::STATE_NONE;
		}
		else if ($state == self::STATE_CODE)
		{
			if (($char == '\'' || $char == '"') && substr($code, 0, 2) == $char.$char)
			{
				$state = self::STATE_DOCUMENTATION;
			}
			else if ($char == '#')
			{
				$state = self::STATE_COMMENT;
			}
			else if (($char == '\'' || $char ==  '"') && (substr($buffer, -1) != '\\' || substr($buffer, -2) == '\\\\'))
			{
				$state = self::STATE_VALUE;
			}
			else if (isset($levels[$char]))
			{
				++$levels[$char];

				$char = (($options & self::FORMAT_RANGES) ? '<span class="range">' : '').'<span class="punctuation">'.$char.'</span>';
			}
			else if (isset($map[$char]))
			{
				--$levels[$map[$char]];

				$char = '<span class="punctuation">'.$char.'</span>'.((($options & self::FORMAT_RANGES && $levels[$map[$char]] >= 0)) ? '</span>' : '');
			}
		}
		else if ($state == self::STATE_DOCUMENTATION && ($char == '\'' || $char == '"') && substr($buffer, -2) == $char.$char)
		{
			$state = self::STATE_CODE;
		}
		else if ($state == self::STATE_COMMENT && $char == "\n")
		{
			$state = self::STATE_CODE;
		}
		else if ($state == self::STATE_VALUE && ($char == '\'' || $char ==  '"') && $char == substr($buffer, 0, 1) && (substr($buffer, -1) != '\\' || substr($buffer, -2) == '\\\\'))
		{
			$state = self::STATE_CODE;
		}

		if ($state !== $oldState)
		{
			if ($oldState == self::STATE_CODE)
			{
				$output.= preg_replace(
	array(
		'#(?<!<span )\b(None|self|True|False|NotImplemented|Ellipsis|exec|print|and|assert|in|is|not|or|class|def|del|global|lambda)\b#S',
		'#\b(break|continue|elif|else|except|finally|for|if|pass|raise|return|try|while|yield)\b#S',
		'#^(import)(\s+)(.+)$#Sm',
		'#^(from)(\s+)(.+)(\s+)(import)(\s+)(.+)$#Sm',
		'#(?<!\.)(\s*)\b([a-z0-9_\-]+)\b(\s*'.(($options & self::FORMAT_RANGES) ? '<span class="range">' : '').'<span class="punctuation">\()#Ssi',
		'#(?<=\.)(\s*)\b([a-z0-9_\-]+)\b(\s*'.(($options & self::FORMAT_RANGES) ? '<span class="range">' : '').'<span class="punctuation">\()#Ssi',
		'#(?<=\.)\b([a-z]+)\b#Ssi',
		'#(?<!">|[a-z-_])((?:-\s*)?(?:(?:\d+\.)?\d+))\b#Si',
		'#(?<!class|">|"|span|&lt|&gt)((?::|;|-|\||\+|=|\*|!|~|\.|,|\/|@|\%|&lt;|&gt;|&amp;)+)(?!/?span)#Ssi',
		),
	array(
		'<span class="keyword">\\1</span>',
		'<span class="control">\\1</span>',
		'<span class="keyword">\\1</span>\\2<span class="package">\\3</span>\\4<span class="keyword">\\5</span>\\6<span class="package">\\7</span>',
		'<span class="keyword">\\1</span>\\2<span class="package">\\3</span>',
		'\\1<span class="function">\\2</span>\\3',
		'\\1<span class="method">\\2</span>\\3',
		'<span class="property">\\1</span>',
		'<span class="number">\\1</span>',
		'<span class="punctuation">\\1</span>',
		),
	$buffer
	);
			}
			else if ($oldState == self::STATE_DOCUMENTATION)
			{
				$output.= '<span class="documentation">'.self::modeJavadoc($buffer.$char, $options).'</span>';
				$char = '';
			}
			else if ($oldState == self::STATE_COMMENT)
			{
				$code = "\n".$code;
				$output.= '<span class="comment">'.preg_replace('#\b(FIXME|NOTICE|NOTE|TODO|WARNING)\b#i', '<span class="notice">\\1</span>', $buffer).'</span>';
				$char = '';
			}
			else if ($oldState == self::STATE_VALUE)
			{
				$output.= '<span class="value">'.$buffer.$char.'</span>';
				$char = '';
			}

			$buffer = '';
		}

		$buffer.= $char;

		if (empty($code))
		{
			if ($options & self::FORMAT_RANGES)
			{
				$buffer.= str_repeat('</span>', array_sum($levels));
			}

			$output.= $buffer;

			break;
		}
	}

	return $output;
}

/**
* Highlight for SQL
* @param string $code
* @param integer $options
* @return string
*/

static private function modeSql($code, $options)
{
	$buffer = $output = '';
	$state = self::STATE_CODE;
	$levels = array('{' => 0, '(' => 0,  '[' => 0);
	$map = array('}' => '{', ')' => '(', ']' => '[');

	while (TRUE)
	{
		$char = (empty($code) ? '' : substr($code, 0, 1));
		$code = substr($code, 1);
		$oldState = $state;

		if (empty($code))
		{
			$buffer.= $char;
			$char = '';
			$state = self::STATE_NONE;
		}
		else if ($state == self::STATE_CODE)
		{
			if ($char == '#' || ($char == '/' && substr($code, 0, 1) == '*') || ($char == '-' && substr($code, 0, 1) == '-'))
			{
				$state = self::STATE_COMMENT;
			}
			else if (($char == '\'' || $char ==  '"' || $char ==  '`') && (substr($buffer, -1) != '\\' || substr($buffer, -2) == '\\\\'))
			{
				$state = self::STATE_VALUE;
			}
			else if (isset($levels[$char]))
			{
				++$levels[$char];

				$char = (($options & self::FORMAT_RANGES) ? '<span class="range">' : '').'<span class="punctuation">'.$char.'</span>';
			}
			else if (isset($map[$char]))
			{
				--$levels[$map[$char]];

				$char = '<span class="punctuation">'.$char.'</span>'.(((($options & self::FORMAT_RANGES) && $levels[$map[$char]] >= 0)) ? '</span>' : '');
			}
		}
		else if ($state == self::STATE_COMMENT && (($char == "\n" && substr($buffer, 0, 1) != '/') || ($char == '/' && substr($buffer, -1) == '*')))
		{
			$state = self::STATE_CODE;
		}
		else if ($state == self::STATE_VALUE && ($char == '\'' || $char ==  '"' || $char ==  '`') && $char == substr($buffer, 0, 1) && (substr($buffer, -1) != '\\' || substr($buffer, -2) == '\\\\'))
		{
			$state = self::STATE_CODE;
		}

		if ($state !== $oldState)
		{
			if ($oldState == self::STATE_CODE)
			{
				$output.= preg_replace(
	array(
		'#(\s*FOREIGN_KEY_LIST|INDEX_INFO|INDEX_LIST|TABLE_INFO|COUNT|MIN|MAX|SUM|ABS|COALESCE|GLOB|IFNULL|LAST_INSERT_ROWID|LENGTH|LIKE|LOAD_EXTENSION|LOWER|NULLIF|QUOTE|RANDOM|ROUND|SOUNDEX|SQLITE_VERSION|SUBSTR|TYPEOF|UPPER|AVG|TOTAL|RAISE)(\s*'.(($options & self::FORMAT_RANGES) ? '<span class="range">' : '').'<span class="punctuation">\()#Ssi',
		'#(\s*)(N?VARCHAR|TEXT|INTEGER|FLOAT|(?:BOOL)?EAN|CLOB|BLOB|TIMESTAMP|NUMERIC)(\s*)#Ssi',
		'#((?:^|;\s+)(?:EXPLAIN )+(?:BEGIN|COMMIT|END|ROLLBACK) TRANSACTION|(?:AT|DE)TACH DATABASE|REINDEX|PRAGMA|ALTER TABLE|DELETE|VACUUM|EXPLAIN|SELECT(?: DISTINCT| UNION(?: ALL)?| INTERSECT| EXCEPT)?|BETWEEN|REPLACE|INSERT INTO|UPDATE|(?:CREATE |DROP )(?:(?:TEMP(?:ORARY)? |VIRTUAL )?TABLE| VIEW|(?: UNIQUE)? INDEX | TRIGGER))#Ssi',
		'#(\s+)(WHERE|(?:PRIMARY|FOREIGN) KEY|IF NOT EXISTS|COLLATE|ON|OFF|YES|FILE|MEMORY|CASE|SET|(?:LEFT|RIGHT|FULL)(?: OUTER)? JOIN|UPDATE(?: OF)?|INSTEAD OF|CHECK|ON CONFLICT|(?:NOT )?LIKE|GLOB|HAVING|AFTER|BEFORE|FOR EACH(?:ROW|STATEMENT)|BEGIN|END|ELSE|NULL|AS SELECT|FROM|VALUES|ORDER BY|GROUP BY|WHEN|THEN|IN|LIMIT|OFFSET|AS|NO(?:T(?: ?)NULL?)|DEFAULT|UNIQUE|OR|AND|DESC|ASC)#Ssi',
		'#((?:-\s*)?(?:\d+\.)?\d+)#S',
		'#(?<!class|">|"|span)((?::|;|-|\||\+|=|\*|!|~|\.|,|\/|@|\$|\%|&lt;|&gt;|&amp;)+)(?!/?span)#Ssi',
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
			}
			else if ($oldState == self::STATE_COMMENT)
			{
				if ($char == "\n")
				{
					$code = "\n".$code;
					$char = '';
				}

				$output.= '<span class="comment">'.preg_replace('#\b(FIXME|NOTICE|NOTE|TODO|WARNING)\b#i', '<span class="notice">\\1</span>', $buffer.$char).'</span>';
				$char = '';
			}
			else if ($oldState == self::STATE_VALUE)
			{
				$output.= '<span class="value">'.$buffer.$char.'</span>';
				$char = '';
			}

			$buffer = '';
		}

		$buffer.= $char;

		if (empty($code))
		{
			$output.= $buffer.str_repeat('</span>', (($options & self::FORMAT_RANGES) ? array_sum($levels) : 0));

			break;
		}
	}

	return $output;
}

}
?>