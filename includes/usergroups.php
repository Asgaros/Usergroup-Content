<?php

if (!defined('ABSPATH')) exit;

class uc_usergroups {
    var $directory = '';

    function __construct($directory) {
        $this->directory = $directory;

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('init', array($this, 'register_user_taxonomy'));

        // Users list stuff
        add_filter('manage_users_columns', array($this, 'manage_users_columns'));
		add_action('manage_users_custom_column', array($this, 'manage_users_custom_column'), 10, 3);
        add_action('delete_user', array($this, 'delete_term_relationships'));
        add_action('pre_user_query', array($this, 'user_query'));
		add_filter('views_users', array($this, 'views'));

        // User profile stuff
		add_action('show_user_profile', array($this, 'edit_user_usergroups'));
		add_action('edit_user_profile', array($this, 'edit_user_usergroups'));
        add_action('personal_options_update', array($this, 'save_user_usergroups'), 10, 3);
		add_action('edit_user_profile_update', array($this, 'save_user_usergroups'), 10, 3);

        // Taxonomy stuff
        add_filter('manage_edit-user-group_columns', array($this,'manage_usergroup_columns'));
        add_action('manage_user-group_custom_column', array($this,'manage_usergroup_custom_column'), 10, 3);
        add_action('user-group_add_form_fields', array($this, 'add_color_form_field'));
		add_action('user-group_edit_form_fields', array($this, 'edit_color_form_field'));
        add_action('create_user-group', array($this, 'save_usergroup'));
		add_action('edit_user-group', array($this, 'save_usergroup'));

		/* Bulk edit */
		//add_action('admin_init', array(&$this, 'bulk_edit_action'));
		//add_filter('views_users', array(&$this, 'bulk_edit'));
	}

    function add_admin_menu() {
        add_users_page(__('Usergroups', 'usergroup-content'), __('Usergroups', 'usergroup-content'), 'administrator', 'edit-tags.php?taxonomy=user-group');
	}

    function enqueue_admin_scripts($hook) {
        global $submenu_file;
        wp_enqueue_style('usergroup-content-css', $this->directory.'css/style.css');
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('usergroup-content-js', $this->directory.'js/script.js');

        if ($submenu_file == 'edit-tags.php?taxonomy=user-group') {
            wp_enqueue_style('usergroup-content-hide-css', $this->directory.'css/style-hide.css');
            wp_enqueue_script('usergroup-content-hide-js', $this->directory.'js/script-hide.js', array('wp-color-picker'), false, true);
        }
    }

    function register_user_taxonomy() {
		register_taxonomy(
			'user-group',
			null,
			array(
				'labels' => array(
					'name'          => __('Usergroups', 'usergroup-content'),
					'singular_name' => __('Usergroup', 'usergroup-content'),
                    'edit_item'     => __('Edit Usergroup', 'usergroup-content'),
                    'update_item'   => __('Update Usergroup', 'usergroup-content'),
                    'add_new_item'  => __('Add New Usergroup', 'usergroup-content'),
                    'search_items'  => __('Search Usergroups', 'usergroup-content'),
                    'not_found'     => __('No Usergroups found.', 'usergroup-content')
				),
                'public' => false,
                'show_ui' => true,
                'show_tagcloud' => false,
				'rewrite' => false,
                'capabilities' => array(
                    'manage_terms'  => 'edit_users',
					'edit_terms'    => 'edit_users',
					'delete_terms'  => 'edit_users',
					'assign_terms'  => 'edit_users'
				)
			)
		);
	}

    /* USERS LIST STUFF */
	function manage_users_columns($columns) {
		$columns['user-group'] = __('Usergroups', 'usergroup-content');
		return $columns;
	}

    function manage_users_custom_column($out, $column, $user_id) {
		if ($column === 'user-group') {
            $terms = $this->get_user_usergroups($user_id);

    		if (!empty($terms)) {
        		$tags = '';

        		foreach($terms as $term) {
        			$href = add_query_arg(array('user-group' => $term->slug), admin_url('users.php'));
        			$color = $this->get_meta('user-group-color', $term->term_id);
        			$tags .= '<a class="usergroup-tag" style="border: 3px solid '.$color.';" href="'.$href.'" title="'.$term->description.'">'.$term->name.'</a>';
        		}

        		return $tags;
            } else {
                return false;
            }
		} else {
            return $out;
        }
	}

    function get_user_usergroups($user_id) {
        if (!empty($user_id)) {
            return wp_get_object_terms($user_id, 'user-group');
        } else {
            return false;
		}
	}

    function delete_term_relationships($user_id) {
		wp_delete_object_term_relationships($user_id, 'user-group');
	}

    /* USER PROFILE STUFF */
    function edit_user_usergroups($user) {
        $tax = get_taxonomy('user-group');

		// Make sure the user can assign terms of the taxonomy before proceeding.
		if (!current_user_can($tax->cap->assign_terms)) {
			return;
        }

		$terms = get_terms('user-group', array('hide_empty' => false));

		echo '<h2>'.__('Usergroups', 'usergroup-content').'</h2>';
        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th>';
        echo '<label>'.sprintf(_n(__('Add to Usergroup', 'usergroup-content'), __('Add to Usergroups', 'usergroup-content'), count($terms))).'</label>';
        echo '</th>';
        echo '<td>';

        if (!empty($terms)) {
            foreach ($terms as $term) {
                $color = $this->get_meta('user-group-color', $term->term_id);

				echo '<input type="checkbox" name="user-group[]" id="user-group-'.$term->slug.'" value="'.$term->slug.'" '.checked(true, is_object_in_term($user->ID, 'user-group', $term->slug), false).' />';
                echo '<label class="usergroup-label" for="user-group-'.$term->slug.'" style="border: 3px solid '.$color.';">';
                echo $term->name;
                echo '</label>';
                echo '<br />';
			}
		} else {
			echo __('There are no Usergroups defined.', 'usergroup-content');
		}

		echo '</td>';
		echo '</tr>';
		echo '</table>';
	}

    function save_user_usergroups($user_id, $user_groups = array(), $bulk = false) {
        $tax = get_taxonomy('user-group');

		// Make sure the current user can edit the user and assign terms before proceeding.
		if (!current_user_can($tax->cap->assign_terms)) {
			return;
		}

		if (empty($user_groups) && !$bulk) {
            $user_groups = isset($_POST['user-group']) ? $_POST['user-group'] : null;
		}

		if (is_null($user_groups) || empty($user_groups)) {
            wp_delete_object_term_relationships($user_id, 'user-group');
		} else {
			wp_set_object_terms($user_id, $user_groups, 'user-group', false);
		}

		clean_object_term_cache($user_id, 'user-group');
	}

    /* TAXONOMY STUFF */
    function manage_usergroup_columns($columns) {
		unset($columns['description'], $columns['posts'], $columns['slug']);

        $columns['users'] = __('Users', 'usergroup-content');
		$columns['color'] = __('Color', 'usergroup-content');

		return $columns;
	}

    function manage_usergroup_custom_column($out, $column, $term_id) {
        global $wpdb;

        if ($column === 'users') {
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d", $term_id));
			$term = get_term($term_id, 'user-group');
			$out = '<a href="'.admin_url('users.php?user-group='.$term->slug).'">'.sprintf(_n(__('%s User'), __('%s Users'), $count), $count).'</a>';
		} else if ($column === 'color') {
			$color = $this->get_meta('user-group-color', $term_id);
			$out = '<div class="usergroup-color" style="background-color: '.$color.';"></div>';
		}

		return $out;
	}

    function add_color_form_field() {
        echo '<div class="form-field">';
            echo '<input type="text" value="#333333" class="custom-color" name="user-group[user-group-color]" data-default-color="#333333" />';
        echo '</div>';
    }

    function edit_color_form_field($term) {
        echo '<tr class="form-field">';
            echo '<th scope="row">'.__('Color', 'usergroup-content').'</th>';
            echo '<td>';
                $color = $this->get_meta('user-group-color');
                echo '<input type="text" value="'.$color.'" class="custom-color" name="user-group[user-group-color]" data-default-color="#333333" />';
            echo '</td>';
        echo '</tr>';
    }

    function save_usergroup($term_id) {
        if (isset($_POST['user-group'])) {
			$term_meta = (array)get_option('user-group-meta');
			$term_meta[$term_id] =  (array)$_POST['user-group'];
			update_option('user-group-meta', $term_meta);
		}
	}

    function get_meta($key = '', $term_id = 0) {
		if(isset($_GET['tag_ID'])) { $term_id = absint( $_GET['tag_ID'] ); }
		if(empty($term_id)) { return false; }

		$term_meta = (array) get_option('user-group-meta');

		if(!isset($term_meta[$term_id])) { return false; }

		if(!empty($key)) {
			return isset($term_meta[$term_id][$key]) ? $term_meta[$term_id][$key] : false;
		} else {
			return $term_meta[$term_id];
		}
	}

    /* NOT YET IMPLEMENTED */
    /*
	function bulk_edit_action() {
		if (!isset( $_REQUEST['bulkedituser-groupsubmit'] ) || empty($_POST['user-group'])) { return; }

		check_admin_referer('bulk-edit-user-group');

		// Get an array of users from the string
		parse_str(urldecode($_POST['users']), $users);

		if(empty($users)) { return; }

		$action = $_POST['groupaction'];

        foreach($users['users'] as $user) {
			$update_groups = array();
			$groups = $this->get_user_usergroups($user);
			foreach($groups as $group) {
				$update_groups[$group->slug] = $group->slug;
			}

			if($action === 'add') {
				if(!in_array($_POST['user-group'], $update_groups)) {
					$update_groups[] = $_POST['user-group'];
				}
			} elseif($action === 'remove') {
				unset($update_groups[$_POST['user-group']]);
			}

			// Delete all user groups if they're empty
			if(empty($update_groups)) { $update_groups = null; }

			self::save_user_usergroups( $user, $update_groups, true);
		}
	}

	function bulk_edit($views) {
		if (!current_user_can('edit_users') ) { return $views; }
		$terms = get_terms('user-group', array('hide_empty' => false));
		?>
		<form method="post" id="bulkedituser-groupform" class="alignright" style="clear:right; margin:0 10px;">
			<fieldset>
				<legend class="screen-reader-text"><?php _e('Update User Groups', 'usergroup-content'); ?></legend>
				<div>
					<label for="groupactionadd" style="margin-right:5px;"><input name="groupaction" value="add" type="radio" id="groupactionadd" checked="checked" /> <?php _e('Add users to', 'usergroup-content'); ?></label>
					<label for="groupactionremove"><input name="groupaction" value="remove" type="radio" id="groupactionremove" /> <?php _e('Remove users from', 'usergroup-content'); ?></label>
				</div>
				<div>
					<input name="users" value="" type="hidden" id="bulkedituser-groupusers" />

					<label for="usergroups-select" class="screen-reader-text"><?php _('User Group', 'usergroup-content'); ?></label>
					<select name="user-group" id="usergroups-select" style="max-width: 300px;">
						<?php
						$select = '<option value="">'.__( 'Select User Group&hellip;', 'usergroup-content').'</option>';
						foreach($terms as $term) {
							$select .= '<option value="'.$term->slug.'">'.$term->name.'</option>'."\n";
						}
						echo $select;
						?>
					</select>
					<?php wp_nonce_field('bulk-edit-user-group') ?>
				</div>
				<div class="clear" style="margin-top:.5em;">
					<?php submit_button( __( 'Update' ), 'small', 'bulkedituser-groupsubmit', false ); ?>
				</div>
			</fieldset>
		</form>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$('#bulkedituser-groupform').remove().insertAfter('ul.subsubsub');
				$('#bulkedituser-groupform').live('submit', function() {
					var users = $('.wp-list-table.users .check-column input:checked').serialize();
					$('#bulkedituser-groupusers').val(users);
				});
			});
		</script>
		<?php
		return $views;
	}*/

	function views($views) {
        $select = false;
        $current = false;
        $current_slug = '';
		$terms = get_terms('user-group', array('hide_empty' => false));

        if ($terms) {
            $select = '<select name="user-group" id="usergroups-select"><option value="0">'.__('All Users', 'usergroup-content').'</option>';
    		foreach($terms as $term) {
    			if(isset($_GET['user-group']) && $_GET['user-group'] === $term->slug) {
    				$current = $term;
                    $current_slug = $current->slug;
    			}
    			$select .= '<option value="'.$term->slug.'"'.selected($term->slug, $current_slug, false).'>'.$term->name.'</option>';
    		}

    		$select .= '</select>';
        }




		if ($current) {
			$color = $this->get_meta('user-group-color', $current->term_id);
			$colorblock = ( $color === '#' || empty($color) ) ? '' : '<div class="userlist-color" style="background-color: '.$color.';"></div>';

			?>
			<div id="user-group-header">
				<h2><?php echo $colorblock; echo $current->name; ?> </h2>
			</div>
			<div class="clear"></div>
		<?php
		}

		ob_start();

		$args = array();
		if(isset($_GET['s'])) { $args['s'] = $_GET['s']; }
		if(isset($_GET['role'])) { $args['role'] = $_GET['role']; }


        if ($select) {
		?>
		<label for="usergroups-select"><?php _e('User Groups:', 'usergroup-content'); ?></label>

		<form method="get" action="<?php echo esc_url( preg_replace('/(.*?)\/users/ism', 'users', add_query_arg($args, remove_query_arg('user-group'))) ); ?>" style="display:inline;">
			<?php echo $select; ?>
		</form>
		<style type="text/css">
			.subsubsub li.user-group { display: inline-block!important; }
		</style>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				<?php if(isset($_GET['user-group'])) { ?>
				$('ul.subsubsub li a').each(function() {
					var $that = $(this);
					$(this).attr('href', function() {
						var sep = $that.attr('href').match(/\?/i) ? '&' : '?';
						return $(this).attr('href') + sep +'user-group=<?php echo esc_attr($_GET['user-group']); ?>';
					});
				});
				<?php } ?>
				$("#usergroups-select").change(function() {
					var action = $(this).parents("form").attr('action');
					if(action.match(/\?/i)) {
						action = action + '&user-group=' + $(this).val();
					} else {
						action = action + '?user-group=' + $(this).val();
					}

					window.location = action;
				});
			});
		</script>

		<?php
        }
		$form = ob_get_clean();

		$views['user-group'] = $form;
		return $views;
	}

	function user_query($Query = '') {
		global $pagenow,$wpdb;

		if($pagenow !== 'users.php') { return; }

		if(!empty($_GET['user-group'])) {

			$groups = explode(',',$_GET['user-group']);
			$ids = array();
			foreach($groups as $group) {
				$term = get_term_by('slug', esc_attr($group), 'user-group');
				$user_ids = get_objects_in_term($term->term_id, 'user-group');
				$ids = array_merge($user_ids, $ids);
			}
			$ids = implode(',', wp_parse_id_list( $user_ids ) );

			$Query->query_where .= " AND $wpdb->users.ID IN ($ids)";
		}

	}
}

?>
