<?php
/**
 * Pieforms: Advanced web forms made easy
 * @package    pieform
 * @subpackage element
 * @author     Martyn Smith <martyn@catalyst.net.nz>
 * @author     Catalyst IT Limited <mahara@catalyst.net.nz>
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL version 3 or later
 * @copyright  For copyright information on Mahara, please see the README file distributed with this software.
 *
 */

/**
 * Provides a size chooser, with a text box for a number and a
 * select box to choose the units, in bytes, kilobytes, megabytes or gigabytes
 *
 * @param Pieform $form    The form to render the element for
 * @param array   $element The element to render
 * @return string          The HTML for the element
 */
function pieform_element_bytes(Pieform $form, $element) {/*{{{*/
    $formname = $form->get_name();
    $result = '';
    $name = Pieform::hsc($element['name']);
    if (!isset($element['defaultvalue'])) {
        $element['defaultvalue'] = null;
    }

    $global = ($form->get_property('method') == 'get') ? $_GET : $_POST;

    // Get the value of the element for rendering.
    if (isset($element['value'])) {
        $bytes = $element['value'];
        $values = pieform_element_bytes_get_bytes_from_bytes($element['value']);
    }
    else if ($form->is_submitted()
             && isset($global[$element['name']])
             && isset($global[$element['name'] . '_units'])) {
        $values = array('number' => $global[$element['name']],
                        'units'  => $global[$element['name'] . '_units']);
        $bytes = $values['number'] * pieform_element_bytes_in($values['units']);
    }
    else if (isset($element['defaultvalue'])) {
        $bytes = $element['defaultvalue'];
        $values = pieform_element_bytes_get_bytes_from_bytes($bytes);
    }
    else {
        $values = array('number' => '0', 'units' => 'bytes');
        $bytes = 0;
    }

    // @todo probably create with an actual input element, as tabindex doesn't work here for one thing
    // Same with the select. And do the events using mochikit signal instead of dom events
    $numberinput = '<div class="with-dropdown js-with-dropdown text">';
    $numberinput .= '<label for="' . $formname . '_' . $name . '">' . Pieform::hsc($element['title']) . ': </label><input';
    $numberinput .= ' type="text" size="6" name="' . $name . '"';
    $numberinput .= ' id="' . $formname . '_' . $name . '" value="' . Pieform::hsc($values['number']) . '" tabindex="' . Pieform::hsc($element['tabindex']) . '"';
    $numberinput .= 'class="with-dropdown js-with-dropdown form-control text'. (isset($element['error']) ? ' error"' : '') . '"';
    if (isset($element['description'])) {
        $numberinput .= ' aria-describedby="' . $form->element_descriptors($element) . '"';
    }
    $numberinput .= "></div>\n";

    $uselect = '<div class="dropdown-connect js-dropdown-connect select">';
    $uselect .= '<label for="' . $formname . '_' . $name . '_units" class="accessible-hidden visually-hidden">' . get_string('units') . '</label>';
    $uselect .= '<span class="picker"><select class="form-control dropdown-connect js-dropdown-connect select" name="' . $name . '_units" id="' . $formname . '_' . $name . '_units"' . ' tabindex="' . Pieform::hsc($element['tabindex']) . '"';
    if (isset($element['description'])) {
        $uselect .= ' aria-describedby="' . $form->element_descriptors($element) . '"';
    }
    $uselect .= ">\n";
    foreach (pieform_element_bytes_get_bytes_units() as $u) {
        $uselect .= "\t<option value=\"$u\"" . (($values['units'] == $u) ? ' selected="selected"' : '') . '>'
            . $form->i18n('element', 'bytes', $u, $element) . "</option>\n";
    }
    $uselect .= "</select></span></div>\n";

    $fieldset = '<div id="' . $formname . '_' . $name . '_fieldset" class="dropdown-group js-dropdown-group form-group">'
    . '<fieldset class="pieform-fieldset dropdown-group js-dropdown-group">'
    . $numberinput
    . $uselect
    . '</fieldset></div>';

    return $fieldset;
}/*}}}*/

/**
 * Gets the value of the expiry element and converts it to a time in seconds.
 *
 * @param Pieform $form    The form the element is attached to
 * @param array   $element The element to get the value for
 * @return int             The number of seconds until expiry
 */
function pieform_element_bytes_get_value(Pieform $form, $element) {/*{{{*/
    $name = $element['name'];

    $global = ($form->get_property('method') == 'get') ? $_GET : $_POST;
    $unit = $global[$name . '_units'];
    $allunits = pieform_element_bytes_get_bytes_units();
    $number = $global[$name];

    if (!is_numeric($number)) {
        $form->set_error($name, $form->i18n('element', 'bytes', 'invalidvalue', $element));
    }

    if (!in_array($unit,$allunits) || $number < 0) {
        return null;
    }
    return $number * pieform_element_bytes_in($unit);
}/*}}}*/

function pieform_element_bytes_in($units) {/*{{{*/
    switch ($units) {
        case 'gigabytes':
            return 1073741824;
            break;
        case 'megabytes':
            return 1048576;
            break;
        case 'kilobytes':
            return 1024;
            break;
        default:
            return 1;
            break;
    };
}/*}}}*/

function pieform_element_bytes_get_bytes_units() {/*{{{*/
    return array('bytes', 'kilobytes', 'megabytes', 'gigabytes');
}/*}}}*/

function pieform_element_bytes_get_bytes_from_bytes($bytes) {/*{{{*/
    if ($bytes == null) {
        return array('number' => '0', 'units' => 'bytes');
    }

    foreach (array('gigabytes', 'megabytes', 'kilobytes') as $units) {
        if ( $bytes >= pieform_element_bytes_in($units) ) {
            return array('number' => $bytes / pieform_element_bytes_in($units) , 'units' => $units);
        }
    }

    return array('number' => $bytes, 'units' => 'bytes');
}/*}}}*/
