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
 */
class HTML_QuickForm_image extends HTML_QuickForm_input
{

    public function __construct(?string $elementName = null, string $src = '', null|array|string $attributes = null)
    {
        parent::__construct($elementName, null, $attributes);

        $this->setType('image');
        $this->setSource($src);
    }

    public function freeze(): void
    {
    }

    public function setAlign(string $align): void
    {
        $this->updateAttributes(['align' => $align]);
    }

    public function setBorder(int $border): void
    {
        $this->updateAttributes(['border' => $border]);
    }

    public function setSource(string $src): void
    {
        $this->updateAttributes(['src' => $src]);
    }

}

