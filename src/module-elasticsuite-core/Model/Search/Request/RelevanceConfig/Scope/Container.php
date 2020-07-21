<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig\Scope;

/**
 * Search Relevance Container Scope
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Container extends \Magento\Framework\DataObject implements \Magento\Framework\App\ScopeInterface
{
    /**
     * {@inheritDoc}
     */
    public function getCode()
    {
        return $this->getData('code');
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return $this->getCode();
    }

    /**
     * {@inheritDoc}
     */
    public function getScopeType()
    {
        return $this->getData('scope_type');
    }

    /**
     * {@inheritDoc}
     */
    public function getScopeTypeName()
    {
        return $this->getData('scope_type_name');
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->getData('name');
    }
}
