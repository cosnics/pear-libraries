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
 * HTML class for a image type element
 *
 * @author       Adam Daniel <adaniel1@eesus.jnj.com>
 * @author       Bertrand Mansion <bmansion@mamasam.com>
 * @version      1.0
 * @since        PHP4.04pl1
 * @access       public
 */
class HTML_QuickForm_image extends HTML_QuickForm_input
{

    /**
     * Class constructor
     *
     * @param string $elementName           (optional)Element name attribute
     * @param string $src                   (optional)Image source
     * @param mixed $attributes             (optional)Either a typical HTML attribute string
     *                                      or an associative array
     *
     * @return    void
     * @since     1.0
     * @access    public
     */
    public function __construct($elementName = null, $src = '', $attributes = null)
    {
        parent::__construct($elementName, null, $attributes);
        $this->setType('image');
        $this->setSource($src);
    } // end class constructor

    /**
     * Sets source for image element
     *
     * @param string $src source for image element
     *
     * @return    void
     * @since     1.0
     * @access    public
     */
    public function setSource($src)
    {
        $this->updateAttributes(['src' => $src]);
    }

    /**
     * Sets border size for image element
     *
     * @param string $border border for image element
     *
     * @return    void
     * @since     1.0
     * @access    public
     */
    public function setBorder($border)
    {
        $this->updateAttributes(['border' => $border]);
    }

    /**
     * Sets alignment for image element
     *
     * @param string $align alignment for image element
     *
     * @return    void
     * @since     1.0
     * @access    public
     */
    public function setAlign($align)
    {
        $this->updateAttributes(['align' => $align]);
    }

    /**
     * Freeze the element so that only its value is returned
     *
     * @access    public
     * @return    void
     */
    public function freeze()
    {
        return false;
    }

} // end class HTML_QuickForm_image

