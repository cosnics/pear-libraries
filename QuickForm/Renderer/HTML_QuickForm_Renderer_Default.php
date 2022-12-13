<?php
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Alexey Borzov <borz_off@cs.msu.su>                          |
// |          Adam Daniel <adaniel1@eesus.jnj.com>                        |
// |          Bertrand Mansion <bmansion@mamasam.com>                     |
// +----------------------------------------------------------------------+

/**
 * A concrete renderer for HTML_QuickForm, based on QuickForm 2.x built-in one
 */
class HTML_QuickForm_Renderer_Default extends HTML_QuickForm_Renderer
{

    protected string $_elementTemplate = "\n\t<tr>\n\t\t<td align=\"right\" valign=\"top\"><!-- BEGIN required --><span style=\"color: #ff0000\">*</span><!-- END required --><b>{label}</b></td>\n\t\t<td valign=\"top\" align=\"left\"><!-- BEGIN error --><span style=\"color: #ff0000\">{error}</span><br /><!-- END error -->\t{element}</td>\n\t</tr>";

    protected string $_formTemplate = "\n<form{attributes}>\n<div>\n{hidden}<table border=\"0\">\n{content}\n</table>\n</div>\n</form>";

    protected string $_groupElementTemplate = '';

    /**
     * Array with HTML generated for group elements
     */
    protected array $_groupElements = [];

    protected string $_groupTemplate = '';

    /**
     * Array containing the templates for elements within groups
     */
    protected array $_groupTemplates = [];

    protected string $_groupWrap = '';

    /**
     * Array containing the templates for group wraps.
     * These templates are wrapped around group elements and groups' own
     * templates wrap around them. This is set by setGroupTemplate().
     */
    protected array $_groupWraps = [];

    protected string $_headerTemplate = "\n\t<tr>\n\t\t<td style=\"white-space: nowrap; background-color: #CCCCCC;\" align=\"left\" valign=\"top\" colspan=\"2\"><b>{header}</b></td>\n\t</tr>";

    protected string $_hiddenHtml = '';

    protected string $_html = '';

    /**
     * True if we are inside a group
     */
    protected bool $_inGroup = false;

    protected string $_requiredNoteTemplate = "\n\t<tr>\n\t\t<td></td>\n\t<td align=\"left\" valign=\"top\">{requiredNote}</td>\n\t</tr>";

    /**
     * Array containing the templates for customised elements
     */
    protected array $_templates = [];

    protected function _prepareTemplate(string $name, $label, bool $required, ?string $error = null): string
    {
        if (is_array($label))
        {
            $nameLabel = array_shift($label);
        }
        else
        {
            $nameLabel = $label;
        }

        if (isset($this->_templates[$name]))
        {
            $html = str_replace('{label}', $nameLabel, $this->_templates[$name]);
        }
        else
        {
            $html = str_replace('{label}', $nameLabel, $this->_elementTemplate);
        }

        if ($required)
        {
            $html = str_replace('<!-- BEGIN required -->', '', $html);
            $html = str_replace('<!-- END required -->', '', $html);
        }
        else
        {
            $html = preg_replace(
                "/([ \t\n\r]*)?<!-- BEGIN required -->(\s|\S)*<!-- END required -->([ \t\n\r]*)?/iU", '', $html
            );
        }

        if (isset($error))
        {
            $html = str_replace('{error}', $error, $html);
            $html = str_replace('<!-- BEGIN error -->', '', $html);
            $html = str_replace('<!-- END error -->', '', $html);
        }
        else
        {
            $html =
                preg_replace("/([ \t\n\r]*)?<!-- BEGIN error -->(\s|\S)*<!-- END error -->([ \t\n\r]*)?/iU", '', $html);
        }

        if (is_array($label))
        {
            foreach ($label as $key => $text)
            {
                $key = is_int($key) ? $key + 2 : $key;
                $html = str_replace('{label_' . $key . '}', $text, $html);
                $html = str_replace('<!-- BEGIN label_' . $key . ' -->', '', $html);
                $html = str_replace('<!-- END label_' . $key . ' -->', '', $html);
            }
        }

        if (strpos($html, '{label_'))
        {
            $html = preg_replace('/\s*<!-- BEGIN label_(\S+) -->.*<!-- END label_\1 -->\s*/i', '', $html);
        }

        return $html;
    }

    /**
     * Clears all the HTML out of the templates that surround notes, elements, etc. Useful when you want to use
     * addData() to create a completely custom form look
     */
    public function clearAllTemplates()
    {
        $this->setElementTemplate('{element}');
        $this->setFormTemplate("\n\t<form{attributes}>{content}\n\t</form>\n");
        $this->setRequiredNoteTemplate('');
        $this->_templates = [];
    }

    /**
     * Called when visiting a form, after processing all form elements
     *
     * @throws \QuickformException
     */
    public function finishForm(HTML_QuickForm $form)
    {
        // add a required note, if one is needed
        if ($form->hasRequirements() && !$form->isFrozen())
        {
            $this->_html .= str_replace('{requiredNote}', $form->getRequiredNote(), $this->_requiredNoteTemplate);
        }

        // add form attributes and content
        $html = str_replace('{attributes}', $form->getAttributes(true), $this->_formTemplate);

        if (strpos($this->_formTemplate, '{hidden}'))
        {
            $html = str_replace('{hidden}', $this->_hiddenHtml, $html);
        }
        else
        {
            $this->_html .= $this->_hiddenHtml;
        }

        $this->_hiddenHtml = '';
        $this->_html = str_replace('{content}', $this->_html, $html);

        // add a validation script
        if ('' != ($script = $form->getValidationScript()))
        {
            $this->_html = $script . "\n" . $this->_html;
        }
    }

    /**
     * Called when visiting a group, after processing all group elements
     */
    public function finishGroup(HTML_QuickForm_group $group)
    {
        $separator = $group->getSeparator();

        if (is_array($separator))
        {
            $count = count($separator);
            $html = '';

            for ($i = 0; $i < count($this->_groupElements); $i ++)
            {
                $html .= (0 == $i ? '' : $separator[($i - 1) % $count]) . $this->_groupElements[$i];
            }
        }
        else
        {
            if (is_null($separator))
            {
                $separator = '&nbsp;';
            }

            $html = implode((string) $separator, $this->_groupElements);
        }

        if (!empty($this->_groupWrap))
        {
            $html = str_replace('{content}', $html, $this->_groupWrap);
        }

        $this->_html .= str_replace('{element}', $html, $this->_groupTemplate);
        $this->_inGroup = false;
    }

    /**
     * Called when visiting an element
     */
    public function renderElement(HTML_QuickForm_element $element, bool $required, ?string $error = null)
    {
        if (!$this->_inGroup)
        {
            $html = $this->_prepareTemplate($element->getName(), $element->getLabel(), $required, $error);
            $this->_html .= str_replace('{element}', $element->toHtml(), $html);
        }
        elseif (!empty($this->_groupElementTemplate))
        {
            $html = str_replace('{label}', $element->getLabel(), $this->_groupElementTemplate);

            if ($required)
            {
                $html = str_replace('<!-- BEGIN required -->', '', $html);
                $html = str_replace('<!-- END required -->', '', $html);
            }
            else
            {
                $html = preg_replace(
                    "/([ \t\n\r]*)?<!-- BEGIN required -->(\s|\S)*<!-- END required -->([ \t\n\r]*)?/iU", '', $html
                );
            }

            $this->_groupElements[] = str_replace('{element}', $element->toHtml(), $html);
        }
        else
        {
            $this->_groupElements[] = $element->toHtml();
        }
    }

    /**
     * Called when visiting a header element
     */
    public function renderHeader(HTML_QuickForm_header $header)
    {
        $name = $header->getName();

        if (!empty($name) && isset($this->_templates[$name]))
        {
            $this->_html .= str_replace('{header}', $header->toHtml(), $this->_templates[$name]);
        }
        else
        {
            $this->_html .= str_replace('{header}', $header->toHtml(), $this->_headerTemplate);
        }
    }

    /**
     * Called when visiting a hidden element
     *
     * @param HTML_QuickForm_hidden|\HTML_QuickForm_hiddenselect $element
     */
    public function renderHidden($element)
    {
        $this->_hiddenHtml .= $element->toHtml() . "\n";
    }

    /**
     * Called when visiting a raw HTML/text pseudo-element
     * Seems that this should not be used when using a template-based renderer
     */
    public function renderHtml(HTML_QuickForm_html $data)
    {
        $this->_html .= $data->toHtml();
    }

    public function setElementTemplate(string $html, ?string $element = null)
    {
        if (is_null($element))
        {
            $this->_elementTemplate = $html;
        }
        else
        {
            $this->_templates[$element] = $html;
        }
    }

    public function setFormTemplate(string $html)
    {
        $this->_formTemplate = $html;
    }

    public function setGroupElementTemplate(string $html, string $group)
    {
        $this->_groupTemplates[$group] = $html;
    }

    /**
     * Sets template for a group wrapper. This template is contained within a group-as-element template set via
     * setTemplate() and contains group's element templates, set via setGroupElementTemplate()
     */
    public function setGroupTemplate(string $html, string $group)
    {
        $this->_groupWraps[$group] = $html;
    }

    public function setHeaderTemplate(string $html)
    {
        $this->_headerTemplate = $html;
    }

    public function setRequiredNoteTemplate(string $html)
    {
        $this->_requiredNoteTemplate = $html;
    }

    /**
     * Called when visiting a form, before processing any form elements
     */
    public function startForm(HTML_QuickForm $form)
    {
        $this->_html = '';
        $this->_hiddenHtml = '';
    }

    /**
     * Called when visiting a group, before processing any group elements
     */
    public function startGroup(HTML_QuickForm_group $group, bool $required, ?string $error = null)
    {
        $name = $group->getName();
        $this->_groupTemplate = $this->_prepareTemplate($name, $group->getLabel(), $required, $error);
        $this->_groupElementTemplate = empty($this->_groupTemplates[$name]) ? '' : $this->_groupTemplates[$name];
        $this->_groupWrap = empty($this->_groupWraps[$name]) ? '' : $this->_groupWraps[$name];
        $this->_groupElements = [];
        $this->_inGroup = true;
    }

    public function toHtml(): string
    {
        // _hiddenHtml is cleared in finishForm(), so this only matters when
        // finishForm() was not called (e.g. group::toHtml(), bug #3511)
        return $this->_hiddenHtml . $this->_html;
    }
}