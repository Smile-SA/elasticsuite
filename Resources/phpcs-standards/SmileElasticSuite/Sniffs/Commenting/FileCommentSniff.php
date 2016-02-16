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

    protected function processVersion(PHP_CodeSniffer_File $phpcsFile, array $tags)
    {
        return;
    }
}
