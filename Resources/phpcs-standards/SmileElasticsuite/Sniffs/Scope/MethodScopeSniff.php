<?php
/**
 * Check all methods have a scope.
 *
 * @category  Smile
\Smile\Elasticsuite
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
class SmileElasticsuite_Sniffs_Scope_MethodScopeSniff extends PHP_CodeSniffer_Standards_AbstractScopeSniff
{
    /**
     * Constructs a Symfony2_Sniffs_Scope_MethodScopeSniff.
     */
    public function __construct()
    {
        parent::__construct([T_CLASS], [T_FUNCTION]);

    }//end __construct()

    /**
     * Processes the function tokens within the class.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file where this token was found.
     * @param int                  $stackPtr  The position where the token was found.
     * @param int                  $currScope The current scope opener token.
     *
     * @return void
     */
    protected function processTokenWithinScope(PHP_CodeSniffer_File $phpcsFile, $stackPtr, $currScope)
    {
        $tokens = $phpcsFile->getTokens();

        $methodName = $phpcsFile->getDeclarationName($stackPtr);
        if ($methodName === null) {
            // Ignore closures.
            return;
        }

        $modifier = $phpcsFile->findPrevious(PHP_CodeSniffer_Tokens::$scopeModifiers, $stackPtr);
        if (($modifier === false) || ($tokens[$modifier]['line'] !== $tokens[$stackPtr]['line'])) {
            $error = 'No scope modifier specified for function "%s"';
            $data  = [$methodName];
            $phpcsFile->addError($error, $stackPtr, 'Missing', $data);
        }

    }//end processTokenWithinScope()
}//end class
