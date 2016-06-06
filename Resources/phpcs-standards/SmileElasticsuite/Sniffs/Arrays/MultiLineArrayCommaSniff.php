<?php
/**
 * Throws warnings if the last item in a multi line array does not have a
 * trailing comma.
 *
 * @category  Smile
\Smile\Elasticsuite
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
class SmileElasticsuite_Sniffs_Arrays_MultiLineArrayCommaSniff implements PHP_CodeSniffer_Sniff
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
        return [T_ARRAY, T_OPEN_SHORT_ARRAY,];
    }//end register()

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $open   = $tokens[$stackPtr];

        if ($open['code'] === T_ARRAY) {
            $closePtr = $open['parenthesis_closer'];
        } else {
            $closePtr = $open['bracket_closer'];
        }

        if ($open['line'] <> $tokens[$closePtr]['line']) {
            $lastComma = $phpcsFile->findPrevious(T_COMMA, $closePtr);

            while ($lastComma < $closePtr -1) {
                $lastComma++;

                if ($tokens[$lastComma]['code'] !== T_WHITESPACE
                    && $tokens[$lastComma]['code'] !== T_COMMENT
                ) {
                    $phpcsFile->addError(
                        'Add a comma after each item in a multi-line array',
                        $stackPtr,
                        'Invalid'
                    );
                    break;
                }
            }
        }

    }//end process()

}//end class

