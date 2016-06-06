<?php
/**
 * Check trailing withespaces.
 *
 * @category  Smile
\Smile\Elasticsuite
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
class SmileElasticsuite_Sniffs_WhiteSpace_DiscourageFitzinatorSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = ['PHP'];


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return [T_WHITESPACE];

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
        $tokens = $phpcsFile->getTokens();

        // Make sure this is trailing whitespace.
        $line = $tokens[$stackPtr]['line'];
        if (($stackPtr < count($tokens) - 1) && $tokens[($stackPtr + 1)]['line'] === $line) {
            return;
        }

        if (strpos($tokens[$stackPtr]['content'], "\n") > 0 || strpos($tokens[$stackPtr]['content'], "\r") > 0) {
            $warning = 'Please trim any trailing whitespace';
            $phpcsFile->addWarning($warning, $stackPtr);
        }

    }

}
