<?php
/**
 * Check declaration order (public, protected and then private) of methods inside a class.
 *
 * @category  Smile
 * @package   Smile_ElasticSuite
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
class SmileElasticSuite_Sniffs_Functions_ScopeOrderSniff implements PHP_CodeSniffer_Sniff
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
        return [T_CLASS, T_INTERFACE];
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
        $function = $stackPtr;

        $scopes = array(
            0 => T_PUBLIC,
            1 => T_PROTECTED,
            2 => T_PRIVATE,
        );

        $whitelisted = array(
            '__construct',
            'setUp',
            'tearDown',
        );

        while ($function) {
            $function = $phpcsFile->findNext(
                T_FUNCTION,
                $function + 1,
                isset($tokens[$stackPtr]['scope_closer']) ? $tokens[$stackPtr]['scope_closer'] : null
            );

            if (isset($tokens[$function]['parenthesis_opener'])) {
                $scope = $phpcsFile->findPrevious($scopes, $function -1, $stackPtr);
                $name = $phpcsFile->findNext(T_STRING, $function + 1, $tokens[$function]['parenthesis_opener']);

                if ($scope && $name && !in_array($tokens[$name]['content'], $whitelisted)) {
                    $current = array_keys($scopes,  $tokens[$scope]['code']);
                    $current = $current[0];

                    if (isset($previous) && $current < $previous) {
                        $phpcsFile->addError(
                            'Declare public methods first, then protected ones and finally private ones',
                            $scope,
                            'Invalid'
                        );
                    }

                    $previous = $current;
                }
            }
        }
    }//end process()

}//end class
