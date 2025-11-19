<?php

#
#
# Parsedown
# http://parsedown.org
#
# (c) Emanuil Rusev
# http://erusev.com
#
# For the full license information, view the LICENSE file that was distributed
# with this source code.
#
#

class parsedown {
	# ~

	const version = '1.8.0-beta-7';

	# ~
	private static $instances = [];
	protected $breaksEnabled;

	#
	# Setters
	#
	protected $markupEscaped;
	protected $urlsLinked = true;
	protected $safeMode;
	protected $strictMode;
	protected $safeLinksWhitelist = [
		'http://',
		'https://',
		'ftp://',
		'ftps://',
		'mailto:',
		'tel:',
		'data:image/png;base64,',
		'data:image/gif;base64,',
		'data:image/jpeg;base64,',
		'irc:',
		'ircs:',
		'git:',
		'ssh:',
		'news:',
		'steam:',
	];
	protected $BlockTypes = [
		'#' => ['Header'],
		'*' => ['Rule', 'List'],
		'+' => ['List'],
		'-' => ['SetextHeader', 'Table', 'Rule', 'List'],
		'0' => ['List'],
		'1' => ['List'],
		'2' => ['List'],
		'3' => ['List'],
		'4' => ['List'],
		'5' => ['List'],
		'6' => ['List'],
		'7' => ['List'],
		'8' => ['List'],
		'9' => ['List'],
		':' => ['Table'],
		'<' => ['Comment', 'Markup'],
		'=' => ['SetextHeader'],
		'>' => ['Quote'],
		'[' => ['Reference'],
		'_' => ['Rule'],
		'`' => ['FencedCode'],
		'|' => ['Table'],
		'~' => ['FencedCode'],
	];
	protected $unmarkedBlockTypes = [
		'Code',
	];
	protected $InlineTypes = [
		'!' => ['Image'],
		'&' => ['SpecialCharacter'],
		'*' => ['Emphasis'],
		':' => ['Url'],
		'<' => ['UrlTag', 'EmailTag', 'Markup'],
		'[' => ['Link'],
		'_' => ['Emphasis'],
		'`' => ['Code'],
		'~' => ['Strikethrough'],
		'\\' => ['EscapeSequence'],
	];
	protected $inlineMarkerList = '!*_&[:<`~\\';
	protected $DefinitionData;
	protected $specialCharacters = [
		'\\', '`', '*', '_', '{', '}', '[', ']', '(', ')', '>', '#', '+', '-', '.', '!', '|', '~',
	];

	#
	# Lines
	#
	protected $StrongRegex = [
		'*' => '/^[*]{2}((?:\\\\\*|[^*]|[*][^*]*+[*])+?)[*]{2}(?![*])/s',
		'_' => '/^__((?:\\\\_|[^_]|_[^_]*+_)+?)__(?!_)/us',
	];

	# ~
	protected $EmRegex = [
		'*' => '/^[*]((?:\\\\\*|[^*]|[*][*][^*]+?[*][*])+?)[*](?![*])/s',
		'_' => '/^_((?:\\\\_|[^_]|__[^_]*__)+?)_(?!_)\b/us',
	];

	#
	# Blocks
	#
	protected $regexHtmlAttribute = '[a-zA-Z_:][\w:.-]*+(?:\s*+=\s*+(?:[^"\'=<>`\s]+|"[^"]*+"|\'[^\']*+\'))?+';
	protected $voidElements = [
		'area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source',
	];
	protected $textLevelElements = [
		'a', 'br', 'bdo', 'abbr', 'blink', 'nextid', 'acronym', 'basefont',
		'b', 'em', 'big', 'cite', 'small', 'spacer', 'listing',
		'i', 'rp', 'del', 'code', 'strike', 'marquee',
		'q', 'rt', 'ins', 'font', 'strong',
		's', 'tt', 'kbd', 'mark',
		'u', 'xm', 'sub', 'nobr',
		'sup', 'ruby',
		'var', 'span',
		'wbr', 'time',
	];

	/**
	 * Returns an instance of the class, potentially creating a new one if it does not already exist.
	 *
	 * @param string $name The name to identify the instance by. Defaults to 'default'.
	 *
	 * @return static The created or retrieved instance of the class
	 */
	static function instance($name = 'default') {
		if (isset(self::$instances[$name])) {
			return self::$instances[$name];
		}

		$instance = new static();

		self::$instances[$name] = $instance;

		return $instance;
	}

	/**
	 * Enable or disable breaks in the current object. Return the object instance for
	 * method chaining.
	 *
	 * @param boolean $breaksEnabled Whether to enable or disable breaks.
	 *
	 * @return self The current object instance.
	 */
	function setBreaksEnabled($breaksEnabled) {
		$this->breaksEnabled = $breaksEnabled;

		return $this;
	}

	#
	# Code

	/**
	 * Set whether markup should be escaped or not.
	 *
	 * @param bool $markupEscaped Flag to indicate whether markup should be escaped.
	 *
	 * @return self This instance for method chaining.
	 */
	function setMarkupEscaped($markupEscaped) {
		$this->markupEscaped = $markupEscaped;

		return $this;
	}

	/**
	 * Sets an array of URLs that will be linked together.
	 *
	 * @param array $urlsLinked An array of URLs to link together
	 *
	 * @return self The instance of the class for chaining method calls
	 */
	function setUrlsLinked($urlsLinked) {
		$this->urlsLinked = $urlsLinked;

		return $this;
	}

	/**
	 * Sets whether safe mode should be enabled or not.
	 *
	 * @param bool $safeMode Whether to enable safe mode
	 *
	 * @return self The instance of the class for chaining method calls
	 */
	function setSafeMode($safeMode) {
		$this->safeMode = (bool)$safeMode;

		return $this;
	}

	#
	# Comment

	/**
	 * Sets the strict mode flag.
	 *
	 * @param bool $strictMode The new value for the strict mode flag
	 *
	 * @return self The instance of the class for chaining method calls
	 */
	function setStrictMode($strictMode) {
		$this->strictMode = (bool)$strictMode;

		return $this;
	}

	/**
	 * Adds a new text element to the current context with the specified text and non-nestable elements.
	 *
	 * @param string $text         The text of the new line element
	 * @param array  $nonNestables An array of elements that cannot be nested within other elements
	 *
	 * @return self The instance of the class for chaining method calls
	 */
	public function line($text, $nonNestables = []) {
		return $this->elements($this->lineElements($text, $nonNestables));
	}

	#
	# Fenced Code

	/**
	 * Extracts line elements from the given text.
	 *
	 * @param string $text         The input text to process
	 * @param array  $nonNestables An array of non-nestable inline types
	 *
	 * @return array An array of extracted line elements
	 */
	protected function lineElements($text, $nonNestables = []) {
		# standardize line breaks
		$text = str_replace(["\r\n", "\r"], "\n", $text);

		$Elements = [];

		$nonNestables = (empty($nonNestables)
			? []
			: array_combine($nonNestables, $nonNestables)
		);

		# $excerpt is based on the first occurrence of a marker

		while ($excerpt = strpbrk($text, $this->inlineMarkerList)) {
			$marker = $excerpt[0];

			$markerPosition = strlen($text) - strlen($excerpt);

			$Excerpt = ['text' => $excerpt, 'context' => $text];

			foreach ($this->InlineTypes[$marker] as $inlineType) {
				# check to see if the current inline type is nestable in the current context

				if (isset($nonNestables[$inlineType])) {
					continue;
				}

				$Inline = $this->{"inline$inlineType"}($Excerpt);

				if (!isset($Inline)) {
					continue;
				}

				# makes sure that the inline belongs to "our" marker

				if (isset($Inline['position']) and $Inline['position'] > $markerPosition) {
					continue;
				}

				# sets a default inline position

				if (!isset($Inline['position'])) {
					$Inline['position'] = $markerPosition;
				}

				# cause the new element to 'inherit' our non nestables

				$Inline['element']['nonNestables'] = isset($Inline['element']['nonNestables'])
					? array_merge($Inline['element']['nonNestables'], $nonNestables)
					: $nonNestables;

				# the text that comes before the inline
				$unmarkedText = substr($text, 0, $Inline['position']);

				# compile the unmarked text
				$InlineText = $this->inlineText($unmarkedText);
				$Elements[] = $InlineText['element'];

				# compile the inline
				$Elements[] = $this->extractElement($Inline);

				# remove the examined text
				$text = substr($text, $Inline['position'] + $Inline['extent']);

				continue 2;
			}

			# the marker does not belong to an inline

			$unmarkedText = substr($text, 0, $markerPosition + 1);

			$InlineText = $this->inlineText($unmarkedText);
			$Elements[] = $InlineText['element'];

			$text = substr($text, $markerPosition + 1);
		}

		$InlineText = $this->inlineText($text);
		$Elements[] = $InlineText['element'];

		foreach ($Elements as &$Element) {
			if (!isset($Element['autobreak'])) {
				$Element['autobreak'] = false;
			}
		}

		return $Elements;
	}

	/**
	 * Creates an inline element with the given text.
	 *
	 * @param string $text The text to be processed for inline elements.
	 *
	 * @return array An array containing information about the inline element, including its extent and child elements.
	 */
	protected function inlineText($text) {
		$Inline = [
			'extent' => strlen($text),
			'element' => [],
		];

		$Inline['element']['elements'] = self::pregReplaceElements(
			$this->breaksEnabled ? '/[ ]*+\n/' : '/(?:[ ]*+\\\\|[ ]{2,}+)\n/',
			[
				['name' => 'br'],
				['text' => "\n"],
			],
			$text
		);

		return $Inline;
	}

	/**
	 * Replaces elements in a text using regular expressions and an array of elements to insert.
	 *
	 * This method iterates through the input text, matches the provided regular expression,
	 * and replaces the matched element with the provided elements inserted into the captured text segments.
	 *
	 * @param string $regexp   The regular expression to match in the text
	 * @param array  $Elements An array of elements to be inserted after each matched element
	 * @param string $text     The input text to replace elements in
	 *
	 * @return array A new array of elements containing the replaced text with the provided elements inserted
	 */
	protected static function pregReplaceElements($regexp, $Elements, $text) {
		$newElements = [];

		while (preg_match($regexp, $text, $matches, PREG_OFFSET_CAPTURE)) {
			$offset = $matches[0][1];
			$before = substr($text, 0, $offset);
			$after = substr($text, $offset + strlen($matches[0][0]));

			$newElements[] = ['text' => $before];

			foreach ($Elements as $Element) {
				$newElements[] = $Element;
			}

			$text = $after;
		}

		$newElements[] = ['text' => $text];

		return $newElements;
	}

	#
	# Header

	/**
	 * Parses the given text and returns its markup representation.
	 *
	 * @param string $text The text to be parsed
	 *
	 * @return object The markup representation of the parsed text
	 */
	function parse($text) {
		$markup = $this->text($text);

		return $markup;
	}

	#
	# List

	/**
	 * Convert text into markup format. This function trims line breaks and replaces
	 * elements with their markup representations.
	 *
	 * @param string $text The text to be converted.
	 *
	 * @return string The markup representation of the input text.
	 */
	function text($text) {
		$Elements = $this->textElements($text);

		# convert to markup
		$markup = $this->elements($Elements);

		# trim line breaks
		$markup = trim($markup, "\n");

		return $markup;
	}

	/**
	 * Convert a block of text into an array of elements representing the structure of the text.
	 *
	 * This method standardizes line breaks, removes surrounding line breaks, splits the text into lines,
	 * and then identifies blocks within those lines. The result is an array of elements that can be
	 * further processed to extract specific information from the text.
	 *
	 * @param string $text The input text to convert into elements.
	 *
	 * @return array An array of elements representing the structure of the input text.
	 */
	protected function textElements($text) {
		# make sure no definitions are set
		$this->DefinitionData = [];

		# standardize line breaks
		$text = str_replace(["\r\n", "\r"], "\n", $text);

		# remove surrounding line breaks
		$text = trim($text, "\n");

		# split text into lines
		$lines = explode("\n", $text);

		# iterate through lines to identify blocks
		return $this->linesElements($lines);
	}

	/**
	 * Processes an array of lines and returns a collection of elements.
	 *
	 * @param array $lines An array of lines to process
	 *
	 * @return self The instance of the class for chaining method calls
	 */
	protected function lines(array $lines) {
		return $this->elements($this->linesElements($lines));
	}

	#
	# Quote

	/**
	 * Renders a list of elements as markup.
	 *
	 * @param array $Elements An array of element data
	 *                        Each element should be an associative array with 'name' and/or 'autobreak' keys
	 *
	 * @return string The rendered markup
	 */
	protected function elements(array $Elements) {
		$markup = '';

		$autoBreak = true;

		foreach ($Elements as $Element) {
			if (empty($Element)) {
				continue;
			}

			$autoBreakNext = (isset($Element['autobreak'])
				? $Element['autobreak'] : isset($Element['name'])
			);
			// (autobreak === false) covers both sides of an element
			$autoBreak = !$autoBreak ? $autoBreak : $autoBreakNext;

			$markup .= ($autoBreak ? "\n" : '') . $this->element($Element);
			$autoBreak = $autoBreakNext;
		}

		$markup .= $autoBreak ? "\n" : '';

		return $markup;
	}

	/**
	 * Renders an HTML element.
	 *
	 * @param array $Element An associative array describing the element to render, including its attributes and
	 *                       content.
	 *
	 * @return string The rendered markup for the given element.
	 */
	protected function element(array $Element) {
		if ($this->safeMode) {
			$Element = $this->sanitiseElement($Element);
		}

		# identity map if element has no handler
		$Element = $this->handle($Element);

		$hasName = isset($Element['name']);

		$markup = '';

		if ($hasName) {
			$markup .= '<' . $Element['name'];

			if (isset($Element['attributes'])) {
				foreach ($Element['attributes'] as $name => $value) {
					if ($value === null) {
						continue;
					}

					$markup .= " $name=\"" . self::escape($value) . '"';
				}
			}
		}

		$permitRawHtml = false;

		if (isset($Element['text'])) {
			$text = $Element['text'];
		}
		// very strongly consider an alternative if you're writing an
		// extension
		elseif (isset($Element['rawHtml'])) {
			$text = $Element['rawHtml'];

			$allowRawHtmlInSafeMode = isset($Element['allowRawHtmlInSafeMode']) && $Element['allowRawHtmlInSafeMode'];
			$permitRawHtml = !$this->safeMode || $allowRawHtmlInSafeMode;
		}

		$hasContent = isset($text) || isset($Element['element']) || isset($Element['elements']);

		if ($hasContent) {
			$markup .= $hasName ? '>' : '';

			if (isset($Element['elements'])) {
				$markup .= $this->elements($Element['elements']);
			} elseif (isset($Element['element'])) {
				$markup .= $this->element($Element['element']);
			} else {
				if (!$permitRawHtml) {
					$markup .= self::escape($text, true);
				} else {
					$markup .= $text;
				}
			}

			$markup .= $hasName ? '</' . $Element['name'] . '>' : '';
		} elseif ($hasName) {
			$markup .= ' />';
		}

		return $markup;
	}

	#
	# Rule

	/**
	 * Sanitizes an element by filtering out invalid attributes and preventing XSS attacks.
	 *
	 * @param array $Element The HTML element to sanitize
	 *
	 * @return array The sanitized element
	 */
	protected function sanitiseElement(array $Element) {
		static $goodAttribute = '/^[a-zA-Z0-9][a-zA-Z0-9-_]*+$/';
		static $safeUrlNameToAtt = [
			'a' => 'href',
			'img' => 'src',
		];

		if (!isset($Element['name'])) {
			unset($Element['attributes']);
			return $Element;
		}

		if (isset($safeUrlNameToAtt[$Element['name']])) {
			$Element = $this->filterUnsafeUrlInAttribute($Element, $safeUrlNameToAtt[$Element['name']]);
		}

		if (!empty($Element['attributes'])) {
			foreach ($Element['attributes'] as $att => $val) {
				# filter out badly parsed attribute
				if (!preg_match($goodAttribute, $att)) {
					unset($Element['attributes'][$att]);
				} # dump onevent attribute
				elseif (self::striAtStart($att, 'on')) {
					unset($Element['attributes'][$att]);
				}
			}
		}

		return $Element;
	}

	#
	# Setext

	/**
	 * Filters out URLs in an element's attribute that match the unsafe URL schemes.
	 *
	 * @param array  $Element   The element object to be filtered
	 * @param string $attribute The name of the attribute to check
	 *
	 * @return array The element with any unsafely-located links replaced or removed
	 */
	protected function filterUnsafeUrlInAttribute(array $Element, $attribute) {
		foreach ($this->safeLinksWhitelist as $scheme) {
			if (self::striAtStart($Element['attributes'][$attribute], $scheme)) {
				return $Element;
			}
		}

		$Element['attributes'][$attribute] = str_replace(':', '%3A', $Element['attributes'][$attribute]);

		return $Element;
	}

	#
	# Markup

	/**
	 * Checks if the start of a string matches a given substring.
	 *
	 * This function performs a case-insensitive check to see if the provided string starts with the specified needle.
	 *
	 * @param string $string The input string to be checked
	 * @param string $needle The substring that must match at the start of the input string
	 *
	 * @return bool True if the string starts with the given needle, false otherwise
	 */
	protected static function striAtStart($string, $needle) {
		$len = strlen($needle);

		if ($len > strlen($string)) {
			return false;
		} else {
			return strtolower(substr($string, 0, $len)) === strtolower($needle);
		}
	}

	/**
	 * Handles the given element by executing its handler function if it exists.
	 *
	 * @param array $Element The element to handle, which may contain a 'handler' key
	 *
	 * @return array The handled element with any changes made by the handler function
	 */
	protected function handle(array $Element) {
		if (isset($Element['handler'])) {
			if (!isset($Element['nonNestables'])) {
				$Element['nonNestables'] = [];
			}

			if (is_string($Element['handler'])) {
				$function = $Element['handler'];
				$argument = $Element['text'];
				unset($Element['text']);
				$destination = 'rawHtml';
			} else {
				$function = $Element['handler']['function'];
				$argument = $Element['handler']['argument'];
				$destination = $Element['handler']['destination'];
			}

			$Element[$destination] = $this->{$function}($argument, $Element['nonNestables']);

			if ($destination === 'handler') {
				$Element = $this->handle($Element);
			}

			unset($Element['handler']);
		}

		return $Element;
	}

	#
	# Reference

	/**
	 * Escapes a string of text to prevent XSS attacks.
	 *
	 * @param string $text        The input text to escape
	 * @param bool   $allowQuotes Allow or disallow escaping of quotes (default: false)
	 *
	 * @return string The escaped text
	 */
	protected static function escape($text, $allowQuotes = false) {
		return htmlspecialchars($text, $allowQuotes ? ENT_NOQUOTES : ENT_QUOTES, 'UTF-8');
	}

	#
	# Table

	/**
	 * Processes an array of lines to extract elements.
	 *
	 * @param array $lines An array of lines to process
	 *
	 * @return array An array of extracted elements
	 */
	protected function linesElements(array $lines) {
		$Elements = [];
		$CurrentBlock = null;

		foreach ($lines as $line) {
			if (chop($line) === '') {
				if (isset($CurrentBlock)) {
					$CurrentBlock['interrupted'] = (isset($CurrentBlock['interrupted'])
						? $CurrentBlock['interrupted'] + 1 : 1
					);
				}

				continue;
			}

			while (($beforeTab = strstr($line, "\t", true)) !== false) {
				$shortage = 4 - mb_strlen($beforeTab, 'utf-8') % 4;

				$line = $beforeTab
					. str_repeat(' ', $shortage)
					. substr($line, strlen($beforeTab) + 1);
			}

			$indent = strspn($line, ' ');

			$text = $indent > 0 ? substr($line, $indent) : $line;

			# ~

			$Line = ['body' => $line, 'indent' => $indent, 'text' => $text];

			# ~

			if (isset($CurrentBlock['continuable'])) {
				$methodName = 'block' . $CurrentBlock['type'] . 'Continue';
				$Block = $this->$methodName($Line, $CurrentBlock);

				if (isset($Block)) {
					$CurrentBlock = $Block;

					continue;
				} else {
					if ($this->isBlockCompletable($CurrentBlock['type'])) {
						$methodName = 'block' . $CurrentBlock['type'] . 'Complete';
						$CurrentBlock = $this->$methodName($CurrentBlock);
					}
				}
			}

			# ~

			$marker = $text[0];

			# ~

			$blockTypes = $this->unmarkedBlockTypes;

			if (isset($this->BlockTypes[$marker])) {
				foreach ($this->BlockTypes[$marker] as $blockType) {
					$blockTypes [] = $blockType;
				}
			}

			#
			# ~

			foreach ($blockTypes as $blockType) {
				$Block = $this->{"block$blockType"}($Line, $CurrentBlock);

				if (isset($Block)) {
					$Block['type'] = $blockType;

					if (!isset($Block['identified'])) {
						if (isset($CurrentBlock)) {
							$Elements[] = $this->extractElement($CurrentBlock);
						}

						$Block['identified'] = true;
					}

					if ($this->isBlockContinuable($blockType)) {
						$Block['continuable'] = true;
					}

					$CurrentBlock = $Block;

					continue 2;
				}
			}

			# ~

			if (isset($CurrentBlock) and $CurrentBlock['type'] === 'Paragraph') {
				$Block = $this->paragraphContinue($Line, $CurrentBlock);
			}

			if (isset($Block)) {
				$CurrentBlock = $Block;
			} else {
				if (isset($CurrentBlock)) {
					$Elements[] = $this->extractElement($CurrentBlock);
				}

				$CurrentBlock = $this->paragraph($Line);

				$CurrentBlock['identified'] = true;
			}
		}

		# ~

		if (isset($CurrentBlock['continuable']) and $this->isBlockCompletable($CurrentBlock['type'])) {
			$methodName = 'block' . $CurrentBlock['type'] . 'Complete';
			$CurrentBlock = $this->$methodName($CurrentBlock);
		}

		# ~

		if (isset($CurrentBlock)) {
			$Elements[] = $this->extractElement($CurrentBlock);
		}

		# ~

		return $Elements;
	}

	/**
	 * Checks if a block can be completed with the given type.
	 *
	 * @param string $Type The type of block to check
	 *
	 * @return bool True if the block can be completed with the given type, false otherwise
	 */
	protected function isBlockCompletable($Type) {
		return method_exists($this, 'block' . $Type . 'Complete');
	}

	#
	# ~
	#

	/**
	 * Extracts the element from a component array.
	 *
	 * If the 'element' key is missing, it will be created based on the presence of other keys:
	 *   - If the 'markup' key is present, it will be used to create an element with 'rawHtml'.
	 *   - If the 'hidden' key is present, it will create an empty element.
	 *
	 * @param array $Component The component array containing information about a single element
	 *
	 * @return array|null The extracted element from the component array, or null if no element was found
	 */
	protected function extractElement(array $Component) {
		if (!isset($Component['element'])) {
			if (isset($Component['markup'])) {
				$Component['element'] = ['rawHtml' => $Component['markup']];
			} elseif (isset($Component['hidden'])) {
				$Component['element'] = [];
			}
		}

		return $Component['element'];
	}

	/**
	 * Determines whether a block can be continued based on its type.
	 *
	 * @param string $Type The type of the block
	 *
	 * @return bool True if the block can be continued, false otherwise
	 */
	protected function isBlockContinuable($Type) {
		return method_exists($this, 'block' . $Type . 'Continue');
	}

	#
	# Inline Elements
	#

	/**
	 * Continues a paragraph by appending the given line of text to the block.
	 *
	 * If the block is interrupted, this method does nothing and returns immediately.
	 *
	 * @param array $Line  A single line of text in the paragraph
	 * @param array $Block The block being processed, with an 'interrupted' key indicating whether it has been
	 *                     interrupted
	 *
	 * @return array The updated block with the new text appended to its element handler argument
	 */
	protected function paragraphContinue($Line, array $Block) {
		if (isset($Block['interrupted'])) {
			return;
		}

		$Block['element']['handler']['argument'] .= "\n" . $Line['text'];

		return $Block;
	}

	# ~

	/**
	 * Creates a paragraph element in the document structure.
	 *
	 * @param array $Line An array containing text to be inserted into the paragraph
	 *
	 * @return array An array representing the paragraph element, including its type and content
	 */
	protected function paragraph($Line) {
		return [
			'type' => 'Paragraph',
			'element' => [
				'name' => 'p',
				'handler' => [
					'function' => 'lineElements',
					'argument' => $Line['text'],
					'destination' => 'elements',
				],
			],
		];
	}

	#
	# ~
	#

	/**
	 * Processes a block of code and returns an array representing the block if it meets certain conditions.
	 *
	 * @param array      $Line  A line of text representing a single line in the block
	 * @param array|null $Block An optional block of text, expected to be null or an array with 'type' and optionally
	 *                          'interrupted' keys
	 *
	 * @return array|void The processed block if it meets certain conditions, otherwise void is returned
	 */
	protected function blockCode($Line, $Block = null) {
		if (isset($Block) and $Block['type'] === 'Paragraph' and !isset($Block['interrupted'])) {
			return;
		}

		if ($Line['indent'] >= 4) {
			$text = substr($Line['body'], 4);

			$Block = [
				'element' => [
					'name' => 'pre',
					'element' => [
						'name' => 'code',
						'text' => $text,
					],
				],
			];

			return $Block;
		}
	}

	/**
	 * Continues a block of code.
	 *
	 * @param array $Line  The current line of the code block
	 * @param array $Block The current block of the code being parsed
	 *
	 * @return array The modified block with continued code, or the original block if continuation is not applicable
	 */
	protected function blockCodeContinue($Line, $Block) {
		if ($Line['indent'] >= 4) {
			if (isset($Block['interrupted'])) {
				$Block['element']['element']['text'] .= str_repeat("\n", $Block['interrupted']);

				unset($Block['interrupted']);
			}

			$Block['element']['element']['text'] .= "\n";

			$text = substr($Line['body'], 4);

			$Block['element']['element']['text'] .= $text;

			return $Block;
		}
	}

	#
	# ~
	#

	/**
	 * Generates a list of suggestions for the given block of code.
	 *
	 * @param string $Block The block of code to generate suggestions for
	 *
	 * @return string The original block of code, unchanged
	 */
	protected function blockCodeComplete($Block) {
		return $Block;
	}

	/**
	 * Parses a line of text as a block comment.
	 *
	 * @param array $Line The parsed input line, with properties 'text' and 'body'.
	 *
	 * @return array|null A Block object containing the parsed comment, or null if the input is not a valid comment
	 */
	protected function blockComment($Line) {
		if ($this->markupEscaped or $this->safeMode) {
			return;
		}

		if (strpos($Line['text'], '<!--') === 0) {
			$Block = [
				'element' => [
					'rawHtml' => $Line['body'],
					'autobreak' => true,
				],
			];

			if (strpos($Line['text'], '-->') !== false) {
				$Block['closed'] = true;
			}

			return $Block;
		}
	}

	/**
	 * Continues a block comment.
	 *
	 * @param array $Line  The current line of code
	 * @param array $Block The block comment being processed
	 *
	 * @return array The updated block comment data
	 */
	protected function blockCommentContinue($Line, array $Block) {
		if (isset($Block['closed'])) {
			return;
		}

		$Block['element']['rawHtml'] .= "\n" . $Line['body'];

		if (strpos($Line['text'], '-->') !== false) {
			$Block['closed'] = true;
		}

		return $Block;
	}

	/**
	 * Parses a line of text to identify fenced code blocks.
	 *
	 * @param array $Line The input data containing the line of text
	 *                    and associated information, e.g. [ 'text' => 'text', ... ]
	 *
	 * @return array|void A block object containing parsed information,
	 *   or void if no valid fenced code block is found
	 */
	protected function blockFencedCode($Line) {
		$marker = $Line['text'][0];

		$openerLength = strspn($Line['text'], $marker);

		if ($openerLength < 3) {
			return;
		}

		$infostring = trim(substr($Line['text'], $openerLength), "\t ");

		if (strpos($infostring, '`') !== false) {
			return;
		}

		$Element = [
			'name' => 'code',
			'text' => '',
		];

		if ($infostring !== '') {
			/**
			 * https://www.w3.org/TR/2011/WD-html5-20110525/elements.html#classes
			 * Every HTML element may have a class attribute specified.
			 * The attribute, if specified, must have a value that is a set
			 * of space-separated tokens representing the various classes
			 * that the element belongs to.
			 * [...]
			 * The space characters, for the purposes of this specification,
			 * are U+0020 SPACE, U+0009 CHARACTER TABULATION (tab),
			 * U+000A LINE FEED (LF), U+000C FORM FEED (FF), and
			 * U+000D CARRIAGE RETURN (CR).
			 */
			$language = substr($infostring, 0, strcspn($infostring, " \t\n\f\r"));

			$Element['attributes'] = ['class' => "language-$language"];
		}

		$Block = [
			'char' => $marker,
			'openerLength' => $openerLength,
			'element' => [
				'name' => 'pre',
				'element' => $Element,
			],
		];

		return $Block;
	}

	/**
	 * Continues processing a fenced code block in the source input.
	 *
	 * @param array $Line  The current line being processed
	 * @param array $Block The state of the currently open code block
	 *
	 * @return array The updated state of the code block
	 */
	protected function blockFencedCodeContinue($Line, $Block) {
		if (isset($Block['complete'])) {
			return;
		}

		if (isset($Block['interrupted'])) {
			$Block['element']['element']['text'] .= str_repeat("\n", $Block['interrupted']);

			unset($Block['interrupted']);
		}

		if (($len = strspn($Line['text'], $Block['char'])) >= $Block['openerLength']
			and chop(substr($Line['text'], $len), ' ') === ''
		) {
			$Block['element']['element']['text'] = substr($Block['element']['element']['text'], 1);

			$Block['complete'] = true;

			return $Block;
		}

		$Block['element']['element']['text'] .= "\n" . $Line['body'];

		return $Block;
	}

	/**
	 * Completes block fenced code.
	 *
	 * This method completes a block fenced code by returning the provided block of code.
	 *
	 * @param mixed $Block The block of code to complete
	 *
	 * @return mixed The completed block of code
	 */
	protected function blockFencedCodeComplete($Block) {
		return $Block;
	}

	/**
	 * Creates a block header element.
	 *
	 * @param array $Line A line of text to parse for the block header
	 *
	 * @return array The created block header element or null if it does not meet the criteria
	 */
	protected function blockHeader($Line) {
		$level = strspn($Line['text'], '#');

		if ($level > 6) {
			return;
		}

		$text = trim($Line['text'], '#');

		if ($this->strictMode and isset($text[0]) and $text[0] !== ' ') {
			return;
		}

		$text = trim($text, ' ');

		$Block = [
			'element' => [
				'name' => 'h' . $level,
				'handler' => [
					'function' => 'lineElements',
					'argument' => $text,
					'destination' => 'elements',
				],
			],
		];

		return $Block;
	}

	/**
	 * Continues the block list processing from the previous line.
	 *
	 * This method checks if the current line should be added to the block list,
	 * considering the required indentation and the type of the block list.
	 *
	 * @param array $Line  The current line being processed
	 * @param array $Block The block list data structure
	 *
	 * @return array|null The updated block list data structure, or null if a block reference is encountered
	 */
	protected function blockListContinue($Line, array $Block) {
		if (isset($Block['interrupted']) and empty($Block['li']['handler']['argument'])) {
			return null;
		}

		$requiredIndent = ($Block['indent'] + strlen($Block['data']['marker']));

		if ($Line['indent'] < $requiredIndent
			and (
				(
					$Block['data']['type'] === 'ol'
					and preg_match('/^[0-9]++' . $Block['data']['markerTypeRegex'] . '(?:[ ]++(.*)|$)/', $Line['text'], $matches)
				) or (
					$Block['data']['type'] === 'ul'
					and preg_match('/^' . $Block['data']['markerTypeRegex'] . '(?:[ ]++(.*)|$)/', $Line['text'], $matches)
				)
			)
		) {
			if (isset($Block['interrupted'])) {
				$Block['li']['handler']['argument'] [] = '';

				$Block['loose'] = true;

				unset($Block['interrupted']);
			}

			unset($Block['li']);

			$text = isset($matches[1]) ? $matches[1] : '';

			$Block['indent'] = $Line['indent'];

			$Block['li'] = [
				'name' => 'li',
				'handler' => [
					'function' => 'li',
					'argument' => [$text],
					'destination' => 'elements',
				],
			];

			$Block['element']['elements'] [] = &$Block['li'];

			return $Block;
		} elseif ($Line['indent'] < $requiredIndent and $this->blockList($Line)) {
			return null;
		}

		if ($Line['text'][0] === '[' and $this->blockReference($Line)) {
			return $Block;
		}

		if ($Line['indent'] >= $requiredIndent) {
			if (isset($Block['interrupted'])) {
				$Block['li']['handler']['argument'] [] = '';

				$Block['loose'] = true;

				unset($Block['interrupted']);
			}

			$text = substr($Line['body'], $requiredIndent);

			$Block['li']['handler']['argument'] [] = $text;

			return $Block;
		}

		if (!isset($Block['interrupted'])) {
			$text = preg_replace('/^[ ]{0,' . $requiredIndent . '}+/', '', $Line['body']);

			$Block['li']['handler']['argument'] [] = $text;

			return $Block;
		}
	}

	/**
	 * Processes a line of text as part of a block list (ordered or unordered).
	 *
	 * @param array $Line         The input line, containing information about the indentation and text.
	 * @param array $CurrentBlock The current block being processed, used for context.
	 *
	 * @return array|null A block object representing the processed list, or null if not a valid list.
	 */
	protected function blockList($Line, array $CurrentBlock = null) {
		[$name, $pattern] = $Line['text'][0] <= '-' ? ['ul', '[*+-]'] : ['ol', '[0-9]{1,9}+[.\)]'];

		if (preg_match('/^(' . $pattern . '([ ]++|$))(.*+)/', $Line['text'], $matches)) {
			$contentIndent = strlen($matches[2]);

			if ($contentIndent >= 5) {
				$contentIndent -= 1;
				$matches[1] = substr($matches[1], 0, -$contentIndent);
				$matches[3] = str_repeat(' ', $contentIndent) . $matches[3];
			} elseif ($contentIndent === 0) {
				$matches[1] .= ' ';
			}

			$markerWithoutWhitespace = strstr($matches[1], ' ', true);

			$Block = [
				'indent' => $Line['indent'],
				'pattern' => $pattern,
				'data' => [
					'type' => $name,
					'marker' => $matches[1],
					'markerType' => ($name === 'ul' ? $markerWithoutWhitespace : substr($markerWithoutWhitespace, -1)),
				],
				'element' => [
					'name' => $name,
					'elements' => [],
				],
			];
			$Block['data']['markerTypeRegex'] = preg_quote($Block['data']['markerType'], '/');

			if ($name === 'ol') {
				$listStart = ltrim(strstr($matches[1], $Block['data']['markerType'], true), '0') ?: '0';

				if ($listStart !== '1') {
					if (
						isset($CurrentBlock)
						and $CurrentBlock['type'] === 'Paragraph'
						and !isset($CurrentBlock['interrupted'])
					) {
						return;
					}

					$Block['element']['attributes'] = ['start' => $listStart];
				}
			}

			$Block['li'] = [
				'name' => 'li',
				'handler' => [
					'function' => 'li',
					'argument' => !empty($matches[3]) ? [$matches[3]] : [],
					'destination' => 'elements',
				],
			];

			$Block['element']['elements'] [] = &$Block['li'];

			return $Block;
		}
	}

	/**
	 * Parses the given line to extract a block reference.
	 *
	 * @param array $Line The line to parse, containing the text of the block reference.
	 *
	 * @return array An array representing the block reference, with its element and data.
	 */
	protected function blockReference($Line) {
		if (strpos($Line['text'], ']') !== false
			and preg_match('/^\[(.+?)\]:[ ]*+<?(\S+?)>?(?:[ ]+["\'(](.+)["\')])?[ ]*+$/', $Line['text'], $matches)
		) {
			$id = strtolower($matches[1]);

			$Data = [
				'url' => $matches[2],
				'title' => isset($matches[3]) ? $matches[3] : null,
			];

			$this->DefinitionData['Reference'][$id] = $Data;

			$Block = [
				'element' => [],
			];

			return $Block;
		}
	}

	/**
	 * Completes the block list by adding a trailing argument to elements that have one.
	 *
	 * @param array $Block The block object to be completed
	 *
	 * @return array The modified block object with loose elements completed
	 */
	protected function blockListComplete(array $Block) {
		if (isset($Block['loose'])) {
			foreach ($Block['element']['elements'] as &$li) {
				if (end($li['handler']['argument']) !== '') {
					$li['handler']['argument'] [] = '';
				}
			}
		}

		return $Block;
	}

	/**
	 * Parses a line of text and returns blockquote data if the line starts with '>' followed by one or more spaces.
	 *
	 * @param array $Line The input line to be parsed
	 *
	 * @return array|null The blockquote element data, or null if no blockquote found in the line
	 */
	protected function blockQuote($Line) {
		if (preg_match('/^>[ ]?+(.*+)/', $Line['text'], $matches)) {
			$Block = [
				'element' => [
					'name' => 'blockquote',
					'handler' => [
						'function' => 'linesElements',
						'argument' => (array)$matches[1],
						'destination' => 'elements',
					],
				],
			];

			return $Block;
		}
	}

	# ~

	/**
	 * Continues a block quote by adding the given line to it.
	 *
	 * @param array $Line  The current line being processed
	 * @param array $Block The block of text that is currently being parsed
	 *
	 * @return array The updated block of text
	 */
	protected function blockQuoteContinue($Line, array $Block) {
		if (isset($Block['interrupted'])) {
			return;
		}

		if ($Line['text'][0] === '>' and preg_match('/^>[ ]?+(.*+)/', $Line['text'], $matches)) {
			$Block['element']['handler']['argument'] [] = $matches[1];

			return $Block;
		}

		if (!isset($Block['interrupted'])) {
			$Block['element']['handler']['argument'] [] = $Line['text'];

			return $Block;
		}
	}

	#
	# Handlers
	#

	/**
	 * Determines if the given line meets the block rule criteria.
	 *
	 * @param array $Line An associative array containing information about the current line
	 *
	 * @return array|null The block configuration if the line meets the block rule, otherwise null
	 */
	protected function blockRule($Line) {
		$marker = $Line['text'][0];

		if (substr_count($Line['text'], $marker) >= 3 and chop($Line['text'], " $marker") === '') {
			$Block = [
				'element' => [
					'name' => 'hr',
				],
			];

			return $Block;
		}
	}

	/**
	 * Sets the header of a Setext block.
	 *
	 * @param array $Line  The current line being processed
	 * @param array $Block The current block being processed (optional)
	 *
	 * @return array|null The updated block, or null if not applicable
	 */
	protected function blockSetextHeader($Line, array $Block = null) {
		if (!isset($Block) or $Block['type'] !== 'Paragraph' or isset($Block['interrupted'])) {
			return;
		}

		if ($Line['indent'] < 4 and chop(chop($Line['text'], ' '), $Line['text'][0]) === '') {
			$Block['element']['name'] = $Line['text'][0] === '=' ? 'h1' : 'h2';

			return $Block;
		}
	}

	/**
	 * Processes a line of markup and returns the block if it matches certain conditions.
	 *
	 * @param array $Line The line to process, containing 'text' key
	 *
	 * @return array|null The processed block data or null if no match is found
	 */
	protected function blockMarkup($Line) {
		if ($this->markupEscaped or $this->safeMode) {
			return;
		}

		if (preg_match('/^<[\/]?+(\w*)(?:[ ]*+' . $this->regexHtmlAttribute . ')*+[ ]*+(\/)?>/', $Line['text'], $matches)) {
			$element = strtolower($matches[1]);

			if (in_array($element, $this->textLevelElements)) {
				return;
			}

			$Block = [
				'name' => $matches[1],
				'element' => [
					'rawHtml' => $Line['text'],
					'autobreak' => true,
				],
			];

			return $Block;
		}
	}

	/**
	 * Continues the block markup by appending the provided line to the block's element raw HTML.
	 *
	 * @param array $Line  The line of text containing the body content
	 * @param array $Block The block object with its properties and state
	 *
	 * @return array The updated block object with the appended line
	 */
	protected function blockMarkupContinue($Line, array $Block) {
		if (isset($Block['closed']) or isset($Block['interrupted'])) {
			return;
		}

		$Block['element']['rawHtml'] .= "\n" . $Line['body'];

		return $Block;
	}

	/**
	 * Generates a table block from the given line and paragraph block.
	 * This method creates a table based on the header and divider provided in the
	 * paragraph block, and returns the modified block with the new table structure.
	 *
	 * @param array      $Line    The current line being processed.
	 * @param array|null $Block   The paragraph block to be processed. If null,
	 *                            the method will return immediately without modifying any data.
	 *
	 * @return array|null The modified paragraph block with a table structure, or
	 *                    null if the table cannot be generated (e.g., due to invalid header
	 *                    or divider).*/
	protected function blockTable($Line, array $Block = null) {
		if (!isset($Block) or $Block['type'] !== 'Paragraph' or isset($Block['interrupted'])) {
			return;
		}

		if (
			strpos($Block['element']['handler']['argument'], '|') === false
			and strpos($Line['text'], '|') === false
			and strpos($Line['text'], ':') === false
			or strpos($Block['element']['handler']['argument'], "\n") !== false
		) {
			return;
		}

		if (chop($Line['text'], ' -:|') !== '') {
			return;
		}

		$alignments = [];

		$divider = $Line['text'];

		$divider = trim($divider);
		$divider = trim($divider, '|');

		$dividerCells = explode('|', $divider);

		foreach ($dividerCells as $dividerCell) {
			$dividerCell = trim($dividerCell);

			if ($dividerCell === '') {
				return;
			}

			$alignment = null;

			if ($dividerCell[0] === ':') {
				$alignment = 'left';
			}

			if (substr($dividerCell, -1) === ':') {
				$alignment = $alignment === 'left' ? 'center' : 'right';
			}

			$alignments [] = $alignment;
		}

		# ~

		$HeaderElements = [];

		$header = $Block['element']['handler']['argument'];

		$header = trim($header);
		$header = trim($header, '|');

		$headerCells = explode('|', $header);

		if (count($headerCells) !== count($alignments)) {
			return;
		}

		foreach ($headerCells as $index => $headerCell) {
			$headerCell = trim($headerCell);

			$HeaderElement = [
				'name' => 'th',
				'handler' => [
					'function' => 'lineElements',
					'argument' => $headerCell,
					'destination' => 'elements',
				],
			];

			if (isset($alignments[$index])) {
				$alignment = $alignments[$index];

				$HeaderElement['attributes'] = [
					'style' => "text-align: $alignment;",
				];
			}

			$HeaderElements [] = $HeaderElement;
		}

		# ~

		$Block = [
			'alignments' => $alignments,
			'identified' => true,
			'element' => [
				'name' => 'table',
				'elements' => [],
			],
		];

		$Block['element']['elements'] [] = [
			'name' => 'thead',
		];

		$Block['element']['elements'] [] = [
			'name' => 'tbody',
			'elements' => [],
		];

		$Block['element']['elements'][0]['elements'] [] = [
			'name' => 'tr',
			'elements' => $HeaderElements,
		];

		return $Block;
	}

	/**
	 * Continues the block table processing.
	 *
	 * This method is called when a new line of text is encountered that continues
	 * a previously started block table. If the block has not been interrupted,
	 * it will be processed and added to the elements array.
	 *
	 * @param Line  $Line  The current line being processed.
	 * @param array $Block The current block being built up.
	 *
	 * @return array The updated Block instance if processing is successful, or
	 *   the original Block instance (no change) if an interruption occurs.
	 */
	protected function blockTableContinue($Line, array $Block) {
		if (isset($Block['interrupted'])) {
			return;
		}

		if (count($Block['alignments']) === 1 or $Line['text'][0] === '|' or strpos($Line['text'], '|')) {
			$Elements = [];

			$row = $Line['text'];

			$row = trim($row);
			$row = trim($row, '|');

			preg_match_all('/(?:(\\\\[|])|[^|`]|`[^`]++`|`)++/', $row, $matches);

			$cells = array_slice($matches[0], 0, count($Block['alignments']));

			foreach ($cells as $index => $cell) {
				$cell = trim($cell);

				$Element = [
					'name' => 'td',
					'handler' => [
						'function' => 'lineElements',
						'argument' => $cell,
						'destination' => 'elements',
					],
				];

				if (isset($Block['alignments'][$index])) {
					$Element['attributes'] = [
						'style' => 'text-align: ' . $Block['alignments'][$index] . ';',
					];
				}

				$Elements [] = $Element;
			}

			$Element = [
				'name' => 'tr',
				'elements' => $Elements,
			];

			$Block['element']['elements'][1]['elements'] [] = $Element;

			return $Block;
		}
	}

	/**
	 * Processes an excerpt and returns its inline code representation.
	 *
	 * @param array $Excerpt The excerpt to process
	 *
	 * @return array An array containing the extent of the inline code and its element details
	 */
	protected function inlineCode($Excerpt) {
		$marker = $Excerpt['text'][0];

		if (preg_match('/^([' . $marker . ']++)[ ]*+(.+?)[ ]*+(?<![' . $marker . '])\1(?!' . $marker . ')/s', $Excerpt['text'], $matches)) {
			$text = $matches[2];
			$text = preg_replace('/[ ]*+\n/', ' ', $text);

			return [
				'extent' => strlen($matches[0]),
				'element' => [
					'name' => 'code',
					'text' => $text,
				],
			];
		}
	}

	/**
	 * Inline email tag in the given excerpt.
	 *
	 * @param array $Excerpt The excerpt to extract inline email tags from
	 *
	 * @return array|null|null An object representing the extracted email tag, or null if no match was found
	 */
	protected function inlineEmailTag($Excerpt) {
		$hostnameLabel = '[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?';

		$commonMarkEmail = '[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]++@'
			. $hostnameLabel . '(?:\.' . $hostnameLabel . ')*';

		if (strpos($Excerpt['text'], '>') !== false
			and preg_match("/^<((mailto:)?$commonMarkEmail)>/i", $Excerpt['text'], $matches)
		) {
			$url = $matches[1];

			if (!isset($matches[2])) {
				$url = "mailto:$url";
			}

			return [
				'extent' => strlen($matches[0]),
				'element' => [
					'name' => 'a',
					'text' => $matches[1],
					'attributes' => [
						'href' => $url,
					],
				],
			];
		}
	}

	/**
	 * Inlines emphasis text, adding it as an element to the document.
	 *
	 * @param array $Excerpt The excerpt containing the text to inline
	 *
	 * @return array|null An array describing the newly added element and its extent,
	 *                    or null if inlining was unsuccessful
	 */
	protected function inlineEmphasis($Excerpt) {
		if (!isset($Excerpt['text'][1])) {
			return;
		}

		$marker = $Excerpt['text'][0];

		if ($Excerpt['text'][1] === $marker and preg_match($this->StrongRegex[$marker], $Excerpt['text'], $matches)) {
			$emphasis = 'strong';
		} elseif (preg_match($this->EmRegex[$marker], $Excerpt['text'], $matches)) {
			$emphasis = 'em';
		} else {
			return;
		}

		return [
			'extent' => strlen($matches[0]),
			'element' => [
				'name' => $emphasis,
				'handler' => [
					'function' => 'lineElements',
					'argument' => $matches[1],
					'destination' => 'elements',
				],
			],
		];
	}

	# ~

	/**
	 * Escapes a special inline escape sequence.
	 *
	 * @param array $Excerpt The excerpt to extract the escape sequence from
	 *
	 * @return array|void An object representing the escaped element, or null if no escape was found
	 */
	protected function inlineEscapeSequence($Excerpt) {
		if (isset($Excerpt['text'][1]) and in_array($Excerpt['text'][1], $this->specialCharacters)) {
			return [
				'element' => ['rawHtml' => $Excerpt['text'][1]],
				'extent' => 2,
			];
		}
	}

	#
	# AST Convenience
	#

	/**
	 * Inserts an inline image element into the parsed excerpt.
	 *
	 * @param array $Excerpt The parsed excerpt containing text and other data
	 *
	 * @return array|null An array representing the inserted inline image, or null if the excerpt does not contain a
	 *                    valid link to insert as an image
	 */
	protected function inlineImage($Excerpt) {
		if (!isset($Excerpt['text'][1]) or $Excerpt['text'][1] !== '[') {
			return;
		}

		$Excerpt['text'] = substr($Excerpt['text'], 1);

		$Link = $this->inlineLink($Excerpt);

		if ($Link === null) {
			return;
		}

		$Inline = [
			'extent' => $Link['extent'] + 1,
			'element' => [
				'name' => 'img',
				'attributes' => [
					'src' => $Link['element']['attributes']['href'],
					'alt' => $Link['element']['handler']['argument'],
				],
				'autobreak' => true,
			],
		];

		$Inline['element']['attributes'] += $Link['element']['attributes'];

		unset($Inline['element']['attributes']['href']);

		return $Inline;
	}

	#
	# Deprecated Methods
	#

	/**
	 * Processes an excerpt and extracts inline links.
	 *
	 * @param array $Excerpt An excerpt object containing text data
	 *
	 * @return array|null A result object containing the extent of processing and the element, or null if no link was
	 *                    found
	 */
	protected function inlineLink($Excerpt) {
		$Element = [
			'name' => 'a',
			'handler' => [
				'function' => 'lineElements',
				'argument' => null,
				'destination' => 'elements',
			],
			'nonNestables' => ['Url', 'Link'],
			'attributes' => [
				'href' => null,
				'title' => null,
			],
		];

		$extent = 0;

		$remainder = $Excerpt['text'];

		if (preg_match('/\[((?:[^][]++|(?R))*+)\]/', $remainder, $matches)) {
			$Element['handler']['argument'] = $matches[1];

			$extent += strlen($matches[0]);

			$remainder = substr($remainder, $extent);
		} else {
			return;
		}

		if (preg_match('/^[(]\s*+((?:[^ ()]++|[(][^ )]+[)])++)(?:[ ]+("[^"]*+"|\'[^\']*+\'))?\s*+[)]/', $remainder, $matches)) {
			$Element['attributes']['href'] = $matches[1];

			if (isset($matches[2])) {
				$Element['attributes']['title'] = substr($matches[2], 1, -1);
			}

			$extent += strlen($matches[0]);
		} else {
			if (preg_match('/^\s*\[(.*?)\]/', $remainder, $matches)) {
				$definition = strlen($matches[1]) ? $matches[1] : $Element['handler']['argument'];
				$definition = strtolower($definition);

				$extent += strlen($matches[0]);
			} else {
				$definition = strtolower($Element['handler']['argument']);
			}

			if (!isset($this->DefinitionData['Reference'][$definition])) {
				return;
			}

			$Definition = $this->DefinitionData['Reference'][$definition];

			$Element['attributes']['href'] = $Definition['url'];
			$Element['attributes']['title'] = $Definition['title'];
		}

		return [
			'extent' => $extent,
			'element' => $Element,
		];
	}

	protected function inlineMarkup($Excerpt) {
		if ($this->markupEscaped or $this->safeMode or strpos($Excerpt['text'], '>') === false) {
			return;
		}

		if ($Excerpt['text'][1] === '/' and preg_match('/^<\/\w[\w-]*+[ ]*+>/s', $Excerpt['text'], $matches)) {
			return [
				'element' => ['rawHtml' => $matches[0]],
				'extent' => strlen($matches[0]),
			];
		}

		if ($Excerpt['text'][1] === '!' and preg_match('/^<!---?[^>-](?:-?+[^-])*-->/s', $Excerpt['text'], $matches)) {
			return [
				'element' => ['rawHtml' => $matches[0]],
				'extent' => strlen($matches[0]),
			];
		}

		if ($Excerpt['text'][1] !== ' ' and preg_match('/^<\w[\w-]*+(?:[ ]*+' . $this->regexHtmlAttribute . ')*+[ ]*+\/?>/s', $Excerpt['text'], $matches)) {
			return [
				'element' => ['rawHtml' => $matches[0]],
				'extent' => strlen($matches[0]),
			];
		}
	}

	/**
	 * Inlines a special character within the provided excerpt.
	 *
	 * @param array $Excerpt The excerpt object containing 'text'
	 *
	 * @return array|null An array containing an inline element and extent, or null if no match
	 */
	protected function inlineSpecialCharacter($Excerpt) {
		if (substr($Excerpt['text'], 1, 1) !== ' ' and strpos($Excerpt['text'], ';') !== false
			and preg_match('/^&(#?+[0-9a-zA-Z]++);/', $Excerpt['text'], $matches)
		) {
			return [
				'element' => ['rawHtml' => '&' . $matches[1] . ';'],
				'extent' => strlen($matches[0]),
			];
		}

		return;
	}

	#
	# Static Methods
	#

	/**
	 * Applies inline strikethrough formatting to the provided excerpt.
	 *
	 * @param array $Excerpt The excerpt containing text that may need strikethrough formatting
	 *
	 * @return array|null An object with 'extent' and 'element' properties if strikethrough formatting is applied,
	 *                    otherwise null
	 */
	protected function inlineStrikethrough($Excerpt) {
		if (!isset($Excerpt['text'][1])) {
			return;
		}

		if ($Excerpt['text'][1] === '~' and preg_match('/^~~(?=\S)(.+?)(?<=\S)~~/', $Excerpt['text'], $matches)) {
			return [
				'extent' => strlen($matches[0]),
				'element' => [
					'name' => 'del',
					'handler' => [
						'function' => 'lineElements',
						'argument' => $matches[1],
						'destination' => 'elements',
					],
				],
			];
		}
	}

	/**
	 * Inlines a URL within the given excerpt.
	 *
	 * If an array of linked URLs is set, and the excerpt's text is not empty at index 2 (i.e. it starts with
	 * 'http://') or the context already contains a full URL, this method creates an inline element (an anchor tag)
	 * that links to the extracted URL.
	 *
	 * @param array $Excerpt The excerpt containing the text and context
	 *
	 * @return array|null An array representing the inlined element, or null if no link is created
	 */
	protected function inlineUrl($Excerpt) {
		if ($this->urlsLinked !== true or !isset($Excerpt['text'][2]) or $Excerpt['text'][2] !== '/') {
			return;
		}

		if (strpos($Excerpt['context'], 'http') !== false
			and preg_match('/\bhttps?+:[\/]{2}[^\s<]+\b\/*+/ui', $Excerpt['context'], $matches, PREG_OFFSET_CAPTURE)
		) {
			$url = $matches[0][0];

			$Inline = [
				'extent' => strlen($matches[0][0]),
				'position' => $matches[0][1],
				'element' => [
					'name' => 'a',
					'text' => $url,
					'attributes' => [
						'href' => $url,
					],
				],
			];

			return $Inline;
		}
	}

	/**
	 * Inserts an inline URL tag into the excerpt.
	 *
	 * @param array $Excerpt The excerpt object
	 *
	 * @return array|null An array containing the extent and element details if a valid URL is found, otherwise null
	 */
	protected function inlineUrlTag($Excerpt) {
		if (strpos($Excerpt['text'], '>') !== false and preg_match('/^<(\w++:\/{2}[^ >]++)>/i', $Excerpt['text'], $matches)) {
			$url = $matches[1];

			return [
				'extent' => strlen($matches[0]),
				'element' => [
					'name' => 'a',
					'text' => $url,
					'attributes' => [
						'href' => $url,
					],
				],
			];
		}
	}

	/**
	 * Returns an element containing inline text content.
	 *
	 * @param string $text The text to be displayed as inline content
	 *
	 * @return mixed An element representing the unmarked text content
	 */
	protected function unmarkedText($text) {
		$Inline = $this->inlineText($text);
		return $this->element($Inline['element']);
	}

	#
	# Fields
	#

	/**
	 * Recursively handles an element using the 'handle' method.
	 *
	 * @param array $Element The element to be handled recursively
	 *
	 * @return mixed The result of handling the element recursively
	 */
	protected function handleElementRecursive(array $Element) {
		return $this->elementApplyRecursive([$this, 'handle'], $Element);
	}

	#
	# Read-Only

	/**
	 * Recursively applies a closure to an element.
	 *
	 * @param callable $closure A function that will be applied to the element
	 * @param array    $Element The element to which the closure should be applied
	 *
	 * @return array The modified element with any child elements also updated
	 */
	protected function elementApplyRecursive($closure, array $Element) {
		$Element = call_user_func($closure, $Element);

		if (isset($Element['elements'])) {
			$Element['elements'] = $this->elementsApplyRecursive($closure, $Element['elements']);
		} elseif (isset($Element['element'])) {
			$Element['element'] = $this->elementApplyRecursive($closure, $Element['element']);
		}

		return $Element;
	}

	/**
	 * Recursively applies a closure to each element in the given collection of elements.
	 *
	 * @param callable $closure  The function to apply to each element
	 * @param array    $Elements A collection of elements to apply the closure to
	 *
	 * @return array The modified collection of elements with the closure applied
	 */
	protected function elementsApplyRecursive($closure, array $Elements) {
		foreach ($Elements as &$Element) {
			$Element = $this->elementApplyRecursive($closure, $Element);
		}

		return $Elements;
	}

	/**
	 * Recursively applies the element handling logic to the given elements.
	 *
	 * @param array $Elements The elements to process
	 *
	 * @return self The instance of the class for chaining method calls
	 */
	protected function handleElementsRecursive(array $Elements) {
		return $this->elementsApplyRecursive([$this, 'handle'], $Elements);
	}

	/**
	 * Applies a closure to an element recursively in depth-first order.
	 *
	 * @param callable $closure A function that will be applied to the element
	 * @param array    $element An array containing the element and its properties
	 *
	 * @return array The modified element after applying the closure
	 */
	protected function elementApplyRecursiveDepthFirst($closure, array $Element) {
		if (isset($Element['elements'])) {
			$Element['elements'] = $this->elementsApplyRecursiveDepthFirst($closure, $Element['elements']);
		} elseif (isset($Element['element'])) {
			$Element['element'] = $this->elementsApplyRecursiveDepthFirst($closure, $Element['element']);
		}

		$Element = call_user_func($closure, $Element);

		return $Element;
	}

	/**
	 * Applies a closure recursively to an array of elements using depth-first order.
	 *
	 * @param callable $closure  A function that will be applied to each element
	 * @param array    $Elements An array of elements to apply the closure to
	 *
	 * @return array The modified array of elements with the closure applied
	 */
	protected function elementsApplyRecursiveDepthFirst($closure, array $Elements) {
		foreach ($Elements as &$Element) {
			$Element = $this->elementApplyRecursiveDepthFirst($closure, $Element);
		}

		return $Elements;
	}

	/**
	 * Process an array of lines to determine the HTML elements contained within.
	 *
	 * @param array $lines An array of lines that may contain HTML elements
	 *
	 * @return array An array containing parsed HTML element data for each line
	 */
	protected function li($lines) {
		$Elements = $this->linesElements($lines);

		if (!in_array('', $lines)
			and isset($Elements[0]) and isset($Elements[0]['name'])
			and $Elements[0]['name'] === 'p'
		) {
			unset($Elements[0]['name']);
		}

		return $Elements;
	}
}
