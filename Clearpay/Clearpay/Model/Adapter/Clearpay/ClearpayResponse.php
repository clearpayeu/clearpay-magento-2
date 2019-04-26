<?php
/**
 * Magento 2 extensions for Clearpay Payment
 *
 * @author Clearpay
 * @copyright 2016-2018 Clearpay https://www.clearpay.co.uk
 */
namespace Clearpay\Clearpay\Model\Adapter\Clearpay;

/**
 * Class ClearpayResponse
 * @package Clearpay\Clearpay\Model\Adapter\Clearpay
 * @see \Zend\Http\Response
 */
class ClearpayResponse
{
	/**
     * The Response Body
     */
	private $body;
	
	/**
     * The Response Status
     */
	private $status;
	
	/**
     * Get Response Status
     *
     * @return string
     */
	public function getStatus()
    {
    	return $this->status;
    }

    /**
     * Set Response Status
     *
     * @param string $status 	HTTP Status
     */
	public function setStatus($status) 
    {
    	$this->status = $status;
    }
	
	/**
     * Get Response Body
     *
     * @return string
     */
	public function getBody() 
    {
    	return $this->body;
    }

    /**
     * Set Response Body
     *
     * @param string $body 	HTTP Body
     */
	public function setBody($body) 
    {
    	$this->body = $body;
    }
}