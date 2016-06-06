<?php
/**
 * Check there is a blank line before the return statement in function containing several instructions.
 *
 * @category  Smile
\Smile\Elasticsuite
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
class SmileElasticsuite_Sniffs_Formatting_BlankLineBeforeReturnSniff implements PHP_CodeSniffer_Sniff
{
    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = ['PHP', 'JS'];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return [T_RETURN];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile All the tokens found in the document.
     * @param int                  $stackPtr  The position of the current token in
     *                                        the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens          = $phpcsFile->getTokens();
        $current         = $stackPtr;
        $previousLine    = $tokens[$stackPtr]['line'] - 1;
        $prevLineTokens  = array();

        while ($current >= 0 && $tokens[$current]['line'] >= $previousLine) {
            if ($tokens[$current]['line'] == $previousLine
                && $tokens[$current]['type'] !== 'T_WHITESPACE'
                && $tokens[$current]['type'] !== 'T_COMMENT'
                && $tokens[$current]['type'] !== 'T_DOC_COMMENT_CLOSE_TAG'
                && $tokens[$current]['type'] !== 'T_DOC_COMMENT_WHITESPACE'
            ) {
                $prevLineTokens[] = $tokens[$current]['type'];
            }
            $current--;
        }

        if (isset($prevLineTokens[0])
            && ($prevLineTokens[0] === 'T_OPEN_CURLY_BRACKET'
            || $prevLineTokens[0] === 'T_COLON')
        ) {
            return;
        } else if (count($prevLineTokens) > 0) {
            $phpcsFile->addError(
                'Missing blank line before return statement',
                $stackPtr
            );
        }

        return;
    }
}
