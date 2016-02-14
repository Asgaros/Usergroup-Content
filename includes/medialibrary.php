<?php

if (!defined('ABSPATH')) exit;

class uc_medialibrary {
    function __construct() {
        add_filter('attachment_fields_to_edit', array($this, 'add_usergroups_settings'), 10, 2);
        add_action('edit_attachment', array($this, 'save_usergroups_settings'));
	}

    // http://code.tutsplus.com/articles/creating-custom-fields-for-attachments-in-wordpress--net-13076
    function add_usergroups_settings($form_fields, $post) {
        global $uc_usergroups;
        $usergroups = $uc_usergroups->get_usergroups();
        $saved_usergroups = get_post_meta($post->ID, 'usergroups', true);
        $usergroups_html = '';

        foreach ($usergroups as $usergroup) {
            $checked = (!empty($saved_usergroups) && in_array($usergroup->term_id, $saved_usergroups) ? 'checked' : '');
            $usergroups_html .= '<label><input style="margin: 0;" type="checkbox" name="attachments['.$post->ID.'][usergroups][]" id="attachments['.$post->ID.'][usergroups][]" value="'.$usergroup->term_id.'" '.$checked.' /> '.$usergroup->name.'</label><br />';
        }

        $form_fields['usergroups'] = array(
            'label' => __('Restrict access', 'usergroup-content'),
            'input' => 'html',
            'helps' => __('When usergroups are selected, only users of the selected usergroups have access to the attachment.', 'usergroup-content'),
            'html' => $usergroups_html
        );

        return $form_fields;
    }

    function save_usergroups_settings($attachment_id) {
        if (isset($_REQUEST['attachments'][$attachment_id]['usergroups'])) {
            $usergroups = $_REQUEST['attachments'][$attachment_id]['usergroups'];
            update_post_meta($attachment_id, 'usergroups', $usergroups);
        }
    }
}

?>
