<?php
/**
 * Copyright (c) 2013-2017
 *
 * @category  Library
 * @package   Dwoo\Plugins\Functions
 * @author    Jordi Boggiano <j.boggiano@seld.be>
 * @author    David Sanchez <david38sanchez@gmail.com>
 * @copyright 2008-2013 Jordi Boggiano
 * @copyright 2013-2017 David Sanchez
 * @license   http://dwoo.org/LICENSE Modified BSD License
 * @version   1.3.2
 * @date      2017-01-06
 * @link      http://dwoo.org/
 */

namespace Dwoo\Plugins\Functions;

use Dwoo\Plugin;

/**
 * Outputs a mailto link with optional spam-proof (okay probably not) encoding
 * <pre>
 *  * address : target email address
 *  * text : display text to show for the link, defaults to the address if not provided
 *  * subject : the email subject
 *  * encode : one of the available encoding (none, js, jscharcode or hex)
 *  * cc : address(es) to carbon copy, comma separated
 *  * bcc : address(es) to blind carbon copy, comma separated
 *  * newsgroups : newsgroup(s) to post to, comma separated
 *  * followupto : address(es) to follow up, comma separated
 *  * extra : additional attributes to add to the &lt;a&gt; tag
 * </pre>
 * This software is provided 'as-is', without any express or implied warranty.
 * In no event will the authors be held liable for any damages arising from the use of this software.
 */
class PluginMailto extends Plugin
{
    /**
     * @param string $address
     * @param string $text
     * @param string $subject
     * @param string $encode
     * @param string $cc
     * @param string $bcc
     * @param string $newsgroups
     * @param string $followupto
     * @param string $extra
     *
     * @return string
     */
    public function process($address, $text = '', $subject = '', $encode = 'none', $cc = '', $bcc = '', $newsgroups = '', $followupto = '', $extra = '')
    {
        if (empty($address)) {
            return '';
        }
        if (empty($text)) {
            $text = $address;
        }

        // build address string
        $address .= '?';

        if (!empty($subject)) {
            $address .= 'subject=' . rawurlencode($subject) . '&';
        }
        if (!empty($cc)) {
            $address .= 'cc=' . rawurlencode($cc) . '&';
        }
        if (!empty($bcc)) {
            $address .= 'bcc=' . rawurlencode($bcc) . '&';
        }
        if (!empty($newsgroups)) {
            $address .= 'newsgroups=' . rawurlencode($newsgroups) . '&';
        }
        if (!empty($followupto)) {
            $address .= 'followupto=' . rawurlencode($followupto) . '&';
        }

        $address = rtrim($address, '?&');

        // output
        switch ($encode) {

            case 'none':
            case null:
                return '<a href="mailto:' . $address . '" ' . $extra . '>' . $text . '</a>';

            case 'js':
            case 'javascript':
                $str = 'document.write(\'<a href="mailto:' . $address . '" ' . $extra . '>' . $text . '</a>\');';
                $len = strlen($str);

                $out = '';
                for ($i = 0; $i < $len; ++ $i) {
                    $out .= '%' . bin2hex($str[$i]);
                }

                return '<script type="text/javascript">eval(unescape(\'' . $out . '\'));</script>';

                break;
            case 'javascript_charcode':
            case 'js_charcode':
            case 'jscharcode':
            case 'jschar':
                $str = '<a href="mailto:' . $address . '" ' . $extra . '>' . $text . '</a>';
                $len = strlen($str);

                $out = '<script type="text/javascript">' . "\n<!--\ndocument.write(Str.fromCharCode(";
                for ($i = 0; $i < $len; ++ $i) {
                    $out .= ord($str[$i]) . ',';
                }

                return rtrim($out, ',') . "));\n-->\n</script>\n";

                break;

            case 'hex':
                if (strpos($address, '?') !== false) {
                    $this->core->triggerError('Mailto: Hex encoding is not possible with extra attributes, use one of : <em>js, jscharcode or none</em>.', E_USER_WARNING);
                }

                $out = '<a href="&#109;&#97;&#105;&#108;&#116;&#111;&#58;';
                $len = strlen($address);
                for ($i = 0; $i < $len; ++ $i) {
                    if (preg_match('#\w#', $address[$i])) {
                        $out .= '%' . bin2hex($address[$i]);
                    } else {
                        $out .= $address[$i];
                    }
                }
                $out .= '" ' . $extra . '>';
                $len = strlen($text);
                for ($i = 0; $i < $len; ++ $i) {
                    $out .= '&#x' . bin2hex($text[$i]);
                }

                return $out . '</a>';

            default:
                $this->core->triggerError('Mailto: <em>encode</em> argument is invalid, it must be one of : <em>none (= no value), js, js_charcode or hex</em>', E_USER_WARNING);
        }
        // If we get this far, retun an empty string.
        return '';
    }
}