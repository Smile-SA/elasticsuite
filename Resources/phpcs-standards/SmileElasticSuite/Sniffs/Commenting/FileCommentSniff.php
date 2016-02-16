<?php
/**
 * Check required tags and order for files.
 *
 * @category  Smile
 * @package   Smile_ElasticSuite
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
class SmileElasticSuite_Sniffs_Commenting_FileCommentSniff extends PEAR_Sniffs_Commenting_FileCommentSniff
{
    /**
     * Tags in correct order and related info.
     *
     * @var array
     */
    protected $tags = array(
        '@category' => array(
            'required' => true,
            'allow_multiple' => false,
            'order_text' => 'precedes @package',
        ),
        '@package' => array(
            'required' => true,
            'allow_multiple' => false,
            'order_text' => 'follows @category',
        ),
        '@subpackage' => array(
            'required' => false,
            'allow_multiple' => false,
            'order_text' => 'follows @package',
        ),
        '@author' => array(
            'required' => true,
            'allow_multiple' => true,
            'order_text' => 'follows @subpackage (if used) or @package',
        ),
        '@copyright' => array(
            'required' => true,
            'allow_multiple' => true,
            'order_text' => 'follows @author',
        ), // Copyright made required
        '@license' => array(
            'required' => false,
            'allow_multiple' => false,
            'order_text' => 'follows @copyright (if used) or @author',
        ), // License made optional
        '@version' => array(
            'required' => false,
            'allow_multiple' => false,
            'order_text' => 'follows @license',
        ),
        '@link' => array(
            'required' => false,
            'allow_multiple' => true,
            'order_text' => 'follows @version',
        ), // Link made optional
        '@see' => array(
            'required' => false,
            'allow_multiple' => true,
            'order_text' => 'follows @link',
        ),
        '@since' => array(
            'required' => false,
            'allow_multiple' => false,
            'order_text' => 'follows @see (if used) or @link',
        ),
        '@deprecated' => array(
            'required' => false,
            'allow_multiple' => false,
            'order_text' => 'follows @since (if used) or @see (if used) or @link',
        ),
    );

    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Find the next non whitespace token.
        $commentStart = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);

        // Allow declare() statements at the top of the file.
        if ($tokens[$commentStart]['code'] === T_DECLARE) {
            $semicolon    = $phpcsFile->findNext(T_SEMICOLON, ($commentStart + 1));
            $commentStart = $phpcsFile->findNext(T_WHITESPACE, ($semicolon + 1), null, true);
        }

        // Ignore vim header.
        if ($tokens[$commentStart]['code'] === T_COMMENT) {
            if (strstr($tokens[$commentStart]['content'], 'vim:') !== false) {
                $commentStart = $phpcsFile->findNext(
                    T_WHITESPACE,
                    ($commentStart + 1),
                    null,
                    true
                    );
            }
        }

        $errorToken = ($stackPtr + 1);
        if (isset($tokens[$errorToken]) === false) {
            $errorToken--;
        }

        if ($tokens[$commentStart]['code'] === T_CLOSE_TAG) {
            // We are only interested if this is the first open tag.
            return ($phpcsFile->numTokens + 1);
        } else if ($tokens[$commentStart]['code'] === T_COMMENT) {
            $error = 'You must use "/**" style comments for a file comment';
            $phpcsFile->addError($error, $errorToken, 'WrongStyle');
            $phpcsFile->recordMetric($stackPtr, 'File has doc comment', 'yes');
            return ($phpcsFile->numTokens + 1);
        } else if ($commentStart === false
            || $tokens[$commentStart]['code'] !== T_DOC_COMMENT_OPEN_TAG
            ) {
                $phpcsFile->addError('Missing file doc comment', $errorToken, 'Missing');
                $phpcsFile->recordMetric($stackPtr, 'File has doc comment', 'no');
                return ($phpcsFile->numTokens + 1);
            }

            $commentEnd = $tokens[$commentStart]['comment_closer'];

            $nextToken = $phpcsFile->findNext(
                T_WHITESPACE,
                ($commentEnd + 1),
                null,
                true
                );

            $ignore = array(
                T_CLASS,
                T_INTERFACE,
                T_TRAIT,
                T_FUNCTION,
                T_CLOSURE,
                T_PUBLIC,
                T_PRIVATE,
                T_PROTECTED,
                T_FINAL,
                T_STATIC,
                T_ABSTRACT,
                T_CONST,
                T_PROPERTY,
                T_INCLUDE,
                T_INCLUDE_ONCE,
                T_REQUIRE,
                T_REQUIRE_ONCE,
            );

            if (in_array($tokens[$nextToken]['code'], $ignore) === true) {
                $phpcsFile->addError('Missing file doc comment', $stackPtr, 'Missing');
                $phpcsFile->recordMetric($stackPtr, 'File has doc comment', 'no');
                return ($phpcsFile->numTokens + 1);
            }

            $phpcsFile->recordMetric($stackPtr, 'File has doc comment', 'yes');

            // Check each tag.
            $this->processTags($phpcsFile, $stackPtr, $commentStart);

            // Ignore the rest of the file.
            return ($phpcsFile->numTokens + 1);

    }//end process()

    protected function processVersion(PHP_CodeSniffer_File $phpcsFile, array $tags)
    {
        return;
    }
}
