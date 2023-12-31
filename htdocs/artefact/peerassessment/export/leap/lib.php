<?php
/**
 *
 * @package    mahara
 * @subpackage artefact-peerassessment
 * @author     Catalyst IT Limited <mahara@catalyst.net.nz>
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL version 3 or later
 * @copyright  For copyright information on Mahara, please see the README file distributed with this software.
 *
 */

defined('INTERNAL') || die();

class LeapExportElementPeerassessment extends LeapExportElement {

    public static function setup_links(&$links, $viewids, $artefactids, $includefeedback, $includeprivatefeedback) {
        $viewlist = join(',', array_map('intval', $viewids));
        $artefactlist = join(',', array_map('intval', $artefactids));
        if ($includeprivatefeedback && $includefeedback) {
            $feedback = 1;
        }
        else if ($includefeedback) {
            $feedback = 0;
        }
        else {
            $feedback = -1;
        }

        // Get the peer assessments that are on these views.
        // and are not in draft status
        $records = get_records_select_array(
            'artefact_peer_assessment',
            "view IN ($viewlist) AND private <= ?",
            array($feedback),
            '',
            'assessment,view'
        );
        if ($records) {
            foreach ($records as &$r) {
                // view is reflected_on_by assessment (at the current moment).
                if (!isset($links->viewartefact[$r->view][$r->assessment])) {
                    $links->viewartefact[$r->view][$r->assessment] = array();
                }
                $links->viewartefact[$r->view][$r->assessment][] = 'reflected_on_by';

                // assessment reflects_on view (at the current moment).
                if (!isset($links->artefactview[$r->assessment][$r->view])) {
                    $links->artefactview[$r->assessment][$r->view] = array();
                }
                $links->artefactview[$r->assessment][$r->view][] = 'reflects_on';

                // Get the embedded images in the assessment.
                $sql = "SELECT fileid
                        FROM {artefact_file_embedded}
                        WHERE resourceid = ?";
                if ($files = get_records_sql_array($sql, array($r->assessment))) {
                    foreach ($files as $file) {
                        $links->attachments[$r->assessment][$file->fileid] = 1;
                    }
                }
            }
        }

    }

    public function get_content_type() {
        return 'html';
    }

    public function get_content() {
        return clean_html($this->artefact->get('description'));
    }

    public function get_categories() {
        return array(
            array(
                'scheme' => 'readiness',
                'term'   => ($this->artefact->get('allowcomments') ? 'Ready' : 'Unready'),
            ),
        );
    }

    public function get_entry_author() {
        return get_string('importedassessment', 'artefact.peerassessment');
    }

}
