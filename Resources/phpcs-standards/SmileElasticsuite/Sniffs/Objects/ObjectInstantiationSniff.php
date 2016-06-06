<?php
/**
 * Check usgae of parenthesis with the new operator.
 *
 * @category  Smile
\Smile\Elasticsuite
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
class SmileElasticsuite_Sniffs_Objects_ObjectInstantiationSniff implements PHP_CodeSniffer_Sniff
{
    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = array(
                                   'PHP',
                                  );


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return [T_NEW];
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
        $allowed = [T_STRING, T_NS_SEPARATOR, T_VARIABLE, T_STATIC];

        $object = $stackPtr;
        $line   = $tokens[$object]['line'];

        while ($object && $tokens[$object]['line'] === $line) {
            $object = $phpcsFile->findNext($allowed, $object + 1);

            if ($tokens[$object]['line'] === $line && !in_array($tokens[$object + 1]['code'], $allowed)) {
                if ($tokens[$object + 1]['code'] !== T_OPEN_PARENTHESIS) {
                    $phpcsFile->addError(
                        'Use parentheses when instantiating classes',
                        $stackPtr,
                        'Invalid'
                    );
                }

                break;
            }
        }

    }//end process()

}//end class
