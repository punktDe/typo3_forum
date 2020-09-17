<?php
namespace Mittwald\Typo3Forum\TextParser\Service;

class AbstractGeshiService {

	/**
	 * all allowed
	 */
	protected $languages = ['PHP', 'JavaScript', 'TypoScript', 'CSS', 'html4strict'];

	/**
	 * @param string $sourcode
	 * @param string $language
	 * @param array $configuration
	 */
	public function getFormattedText($sourceCode, $language = 'TypoScript', $configuration = []){
		$geshi = new \GeSHi($sourceCode, $language);
		$geshi->strict_mode = false;
		$geshi->line_numbers = true;
		return $geshi->parse_code();
	}
}
