<?php

namespace XLite\Module\XC\PayuTurkey;

/**
 * Development module that simplifies the process of implementing design changes
 *
 */
abstract class Main extends \XLite\Module\AModule
{
    /**
     * Author name
     *
     * @return string
     */
    public static function getAuthorName()
    {
        return 'Lucid Softech Team';
    }

    /**
     * Module name
     *
     * @return string
     */
    public static function getModuleName()
    {
        return 'PayU Turkey';
    }

    /**
     * Get module major version
     *
     * @return string
     */
    public static function getMajorVersion()
    {
        return '5.2';
    }

    /**
     * Module version
     *
     * @return string
     */
    public static function getMinorVersion()
    {
        return '4';
    }

    /**
     * Module description
     *
     * @return string
     */
    public static function getDescription()
    {
        return 'Development module that work for PayU turkey payment gatway';
    }
	
/*	public static function runBuildCacheHandler()
	{
		parent::runBuildCacheHandler();
	}

    *
     * The following pathes are defined as substitutional skins:
     *
     * admin interface:     skins/custom_skin/admin/en/
     * customer interface:  skins/custom_skin/default/en/
     * mail interface:      skins/custom_skin/mail/en
     *
     * @return array
     
    public static function getSkins()
    {
        return array(
            \XLite::ADMIN_INTERFACE    => array('custom_skin' . LC_DS . 'admin'),
            \XLite::CUSTOMER_INTERFACE => array('custom_skin' . LC_DS . 'default'),
            \XLite::MAIL_INTERFACE     => array('custom_skin' . LC_DS . 'mail'),
        );
    }*/
}
