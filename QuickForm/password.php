<?php

// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997, 1998, 1999, 2000, 2001 The PHP Group             |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Adam Daniel <adaniel1@eesus.jnj.com>                        |
// |          Bertrand Mansion <bmansion@mamasam.com>                     |
// +----------------------------------------------------------------------+

/**
 * HTML class for a password type field
 *
 * @author       Adam Daniel <adaniel1@eesus.jnj.com>
 * @author       Bertrand Mansion <bmansion@mamasam.com>
 * @version      1.1
 * @since        PHP4.04pl1
 * @access       public
 */
class HTML_QuickForm_password extends HTML_QuickForm_input
{

    /**
     * Class constructor
     *
     * @param string $elementName           (optional)Input field name attribute
     * @param string $elementLabel          (optional)Input field label
     * @param mixed $attributes             (optional)Either a typical HTML attribute string
     *                                      or an associative array
     *
     * @return    void
     * @throws
     * @since     1.0
     * @access    public
     */
    public function __construct($elementName = null, $elementLabel = null, $attributes = null)
    {
        parent::__construct($elementName, $elementLabel, $attributes);
        $this->setType('password');
    }

    /**
     * Returns the value of field without HTML tags (in this case, value is changed to a mask)
     *
     * @return    string
     * @throws
     * @since     1.0
     * @access    public
     */
    public function getFrozenHtml()
    {
        $value = $this->getValue();

        return ('' != $value ? '**********' : '&nbsp;') . $this->_getPersistantData();
    }

    /**
     * Sets maxlength of password element
     *
     * @param string $maxlength Maximum length of password field
     *
     * @return    void
     * @since     1.0
     * @access    public
     */
    public function setMaxlength($maxlength)
    {
        $this->updateAttributes(['maxlength' => $maxlength]);
    }

    /**
     * Sets size of password element
     *
     * @param string $size Size of password field
     *
     * @return    void
     * @since     1.0
     * @access    public
     */
    public function setSize($size)
    {
        $this->updateAttributes(['size' => $size]);
    }

}

