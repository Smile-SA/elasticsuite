<?php
/**
 * Check required tags and order for classes and interfaces.
 *
 * @category  Smile
\Smile\Elasticsuite
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
class SmileElasticsuite_Sniffs_Commenting_ClassCommentSniff extends PEAR_Sniffs_Commenting_ClassCommentSniff
{
    /**
     * Tags in correct order and related info.
     *
     * @var array
     */
    protected $tags = array(
        '@category'   => array(
            'required'       => false,
            'allow_multiple' => false,
            'order_text'     => 'precedes @package',
        ),
        '@package'    => array(
            'required'       => false,
            'allow_multiple' => false,
            'order_text'     => 'follows @category',
        ),
        '@subpackage' => array(
            'required'       => false,
            'allow_multiple' => false,
            'order_text'     => 'follows @package',
        ),
        '@author'     => array(
            'required'       => false,
            'allow_multiple' => true,
            'order_text'     => 'follows @subpackage (if used) or @package',
        ),
        '@copyright'  => array(
            'required'       => false,
            'allow_multiple' => true,
            'order_text'     => 'follows @author',
        ),
        '@license'    => array(
            'required'       => false,
            'allow_multiple' => false,
            'order_text'     => 'follows @copyright (if used) or @author',
        ),
        '@version'    => array(
            'required'       => false,
            'allow_multiple' => false,
            'order_text'     => 'follows @license',
        ),
        '@link'       => array(
            'required'       => false,
            'allow_multiple' => true,
            'order_text'     => 'follows @version',
        ),
        '@see'        => array(
            'required'       => false,
            'allow_multiple' => true,
            'order_text'     => 'follows @link',
        ),
        '@since'      => array(
            'required'       => false,
            'allow_multiple' => false,
            'order_text'     => 'follows @see (if used) or @link',
        ),
        '@deprecated' => array(
            'required'       => false,
            'allow_multiple' => false,
            'order_text'     => 'follows @since (if used) or @see (if used) or @link',
        ),
    );
}
