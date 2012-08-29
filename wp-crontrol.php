<?php
/*
 * Plugin Name: WP-Crontrol
 * Plugin URI: http://www.scompt.com/projects/wp-crontrol
 * Description: WP-Crontrol lets you take control over what's happening in the WP-Cron system.  See my series on <a target="_blank" href="http://scompt.com/archives/series/taking-control-of-wp-cron-using-wp-crontrol">Taking control of WP-Cron using WP-Crontrol</a> for usage ideas.
 * Author: <a href="http://www.scompt.com/">Edward Dale</a> & <a href="http://lud.icro.us/">John Blackbourn</a>
 * Version: 1.1
 * Text Domain: crontrol
 * Domain Path: /gettext/
 */

 /**
  * WP-Crontrol lets you take control over what's happening in the WP-Cron system.
  *
  * LICENSE
  * This file is part of WP-Crontrol.
  *
  * WP-Crontrol is free software; you can redistribute it and/or
  * modify it under the terms of the GNU General Public License
  * as published by the Free Software Foundation; either version 2
  * of the License, or (at your option) any later version.
  *
  * This program is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with this program; if not, write to the Free Software
  * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
  *
  * @package    WP-Crontrol
  * @author     Edward Dale <scompt@scompt.com> & John Blackbourn <john@johnblackbourn.com>
  * @copyright  Copyright 2012 Edward Dale & John Blackbourn
  * @license    http://www.gnu.org/licenses/gpl.txt GPL 2.0
  * @link       http://www.scompt.com/projects/wp-crontrol
  * @since      0.2
  */
class Crontrol {
    var $json;

    /**
     * Hook onto all of the actions and filters needed by the plugin.
     */
    function Crontrol() {
        define( 'CRONTROL_CRON_JOB', 'crontrol_cron_job');
        $this->json = new Crontrol_JSON();
        if( function_exists('add_action') ) {
            add_action('init', array(&$this, 'init'));
            add_action('init', array(&$this, 'handle_posts'));
        	add_action('admin_menu', array(&$this, 'admin_menu'));

            // Make sure the activation works from subdirectories as well as
            // directly in the plugin directory.
            $activate_action = str_replace(ABSPATH.PLUGINDIR.'/', 'activate_', __FILE__);
        	add_action($activate_action, array(&$this, 'activate'));

        	add_filter('cron_schedules', array(&$this, 'cron_schedules'));
            add_action(CRONTROL_CRON_JOB, array(&$this, 'php_cron_entry'));
        }
    }

    /**
     * Evaluates the provided code using eval.
     */
    function php_cron_entry($code) {
        eval($code);
    }

    /**
     * Run using the 'init' action.
     */
    function init() {
    	load_plugin_textdomain( 'crontrol', false, dirname( plugin_basename( __FILE__ ) ) . '/gettext' );
    }

    /**
     * Handles any POSTs made by the plugin.  Run using the 'init' action.
     */
    function handle_posts() {
        if( isset($_POST['new_cron']) ) {
            if( !current_user_can('manage_options') ) die(__('You are not allowed to add new cron events.', 'crontrol'));
            check_admin_referer("new-cron");
            extract($_POST, EXTR_PREFIX_ALL, 'in');
            $in_args = $this->json->decode(stripslashes($in_args));
            $this->add_cron($in_next_run, $in_schedule, $in_hookname, $in_args);
            wp_redirect("tools.php?page=crontrol_admin_manage_page&crontrol_message=5&crontrol_name={$in_hookname}");

        } else if( isset($_POST['new_php_cron']) ) {
            if( !current_user_can('manage_options') ) die(__('You are not allowed to add new cron events.', 'crontrol'));
            check_admin_referer("new-cron");
            extract($_POST, EXTR_PREFIX_ALL, 'in');
            $args = array('code'=>stripslashes($in_hookcode));
            $hookname = CRONTROL_CRON_JOB;
            $this->add_cron($in_next_run, $in_schedule, $hookname, $args);
            wp_redirect("tools.php?page=crontrol_admin_manage_page&crontrol_message=5&crontrol_name={$in_hookname}");

        } else if( isset($_POST['edit_cron']) ) {
            if( !current_user_can('manage_options') ) die(__('You are not allowed to edit cron events.', 'crontrol'));

            extract($_POST, EXTR_PREFIX_ALL, 'in');
            check_admin_referer("edit-cron_{$in_original_hookname}_{$in_original_sig}_{$in_original_next_run}");
            $in_args = $this->json->decode(stripslashes($in_args));
            $i=$this->delete_cron($in_original_hookname, $in_original_sig, $in_original_next_run);
            $i=$this->add_cron($in_next_run, $in_schedule, $in_hookname, $in_args);
            wp_redirect("tools.php?page=crontrol_admin_manage_page&crontrol_message=4&crontrol_name={$in_hookname}");

        } else if( isset($_POST['edit_php_cron']) ) {
            if( !current_user_can('manage_options') ) die(__('You are not allowed to edit cron events.', 'crontrol'));

            extract($_POST, EXTR_PREFIX_ALL, 'in');
            check_admin_referer("edit-cron_{$in_original_hookname}_{$in_original_sig}_{$in_original_next_run}");
            $args['code'] = stripslashes($in_hookcode);
            $hookname = CRONTROL_CRON_JOB;
            $args = array('code'=>stripslashes($in_hookcode));
            $i=$this->delete_cron($in_original_hookname, $in_original_sig, $in_original_next_run);
            $i=$this->add_cron($in_next_run, $in_schedule, $hookname, $args);
            wp_redirect("tools.php?page=crontrol_admin_manage_page&crontrol_message=4&crontrol_name={$in_hookname}");

        } else if( isset($_POST['new_schedule']) ) {
            if( !current_user_can('manage_options') ) die(__('You are not allowed to add new cron schedules.', 'crontrol'));
            check_admin_referer("new-sched");
            $name = $_POST['internal_name'];
            $interval = $_POST['interval'];
            $display = $_POST['display_name'];

            // The user entered something that wasn't a number.
            // Try to convert it with strtotime
            if( !is_numeric($interval) ) {
                $now = time();
                $future = strtotime($interval, $now);
                if( $future===FALSE || $future == -1 || $now>$future) {
                    wp_redirect("options-general.php?page=crontrol_admin_options_page&crontrol_message=7&crontrol_name=".urlencode($interval));
                    return;
                }
                $interval = $future-$now;
            } else if( $interval<=0 ) {
                wp_redirect("options-general.php?page=crontrol_admin_options_page&crontrol_message=7&crontrol_name=".urlencode($interval));
                return;
            }

            $this->add_schedule($name, $interval, $display);
            wp_redirect("options-general.php?page=crontrol_admin_options_page&crontrol_message=3&crontrol_name=$name");

        } else if( isset($_GET['action']) && $_GET['action']=='delete-sched') {
            if( !current_user_can('manage_options') ) die(__('You are not allowed to delete cron schedules.', 'crontrol'));
            $id = $_GET['id'];
            check_admin_referer("delete-sched_{$id}");
            $this->delete_schedule($id);
            wp_redirect("options-general.php?page=crontrol_admin_options_page&crontrol_message=2&crontrol_name=$id");

        } else if( isset($_GET['action']) && $_GET['action']=='delete-cron') {
            if( !current_user_can('manage_options') ) die(__('You are not allowed to delete cron events.', 'crontrol'));
            $id = $_GET['id'];
            $sig = $_GET['sig'];
            $next_run = $_GET['next_run'];
            check_admin_referer("delete-cron_{$id}_{$sig}_{$next_run}");
            if( $this->delete_cron($id, $sig, $next_run) ) {
                wp_redirect("tools.php?page=crontrol_admin_manage_page&crontrol_message=6&crontrol_name=$id");
            } else {
                wp_redirect("tools.php?page=crontrol_admin_manage_page&crontrol_message=7&crontrol_name=$id");
            };

        } else if( isset($_GET['action']) && $_GET['action']=='run-cron') {
            if( !current_user_can('manage_options') ) die(__('You are not allowed to run cron events.', 'crontrol'));
            $id = $_GET['id'];
            $sig = $_GET['sig'];
            check_admin_referer("run-cron_{$id}_{$sig}");
            if( $this->run_cron($id, $sig) ) {
                wp_redirect("tools.php?page=crontrol_admin_manage_page&crontrol_message=1&crontrol_name=$id");
            } else {
                wp_redirect("tools.php?page=crontrol_admin_manage_page&crontrol_message=8&crontrol_name=$id");
            }
        }
    }

    /**
     * Executes a cron entry immediately.
     *
     * Executes an entry by scheduling a new single event with the same arguments.
     *
     * @param string $hookname The hookname of the cron entry to run
     */
    function run_cron($hookname, $sig) {
    	$crons = _get_cron_array();
        foreach( $crons as $time=>$cron ) {
            if( isset( $cron[$hookname][$sig] ) ) {
                $args = $cron[$hookname][$sig]['args'];
				delete_transient( 'doing_cron' );
                wp_schedule_single_event(time()-1, $hookname, $args);
                spawn_cron();
                return true;
            }
        }
        return false;
    }

    /**
     * Adds a new cron entry.
     *
     * @param string $next_run A human-readable (strtotime) time that the entry should be run at
     * @param string $schedule The recurrence of the cron entry
     * @param string $hookname The name of the hook to execute
     * @param array $args Arguments to add to the cron entry
     */
    function add_cron($next_run, $schedule, $hookname, $args) {
        $next_run = strtotime($next_run);
        if( $next_run===FALSE || $next_run==-1 ) $next_run=time();
        if( !is_array($args) ) $args=array();
        if( $schedule == '_oneoff' ) {
            return wp_schedule_single_event($next_run, $hookname, $args) === NULL;
        } else {
            return wp_schedule_event( $next_run, $schedule, $hookname, $args ) === NULL;
        }
    }

    /**
     * Deletes a cron entry.
     *
     * @param string $name The hookname of the entry to delete.
     */
    function delete_cron($to_delete, $sig, $next_run) {
    	$crons = _get_cron_array();
    	if( isset( $crons[$next_run][$to_delete][$sig] ) ) {
    	    $args = $crons[$next_run][$to_delete][$sig]['args'];
            wp_unschedule_event($next_run, $to_delete, $args);
            return true;
    	}
        return false;
    }

    /**
     * Adds a new custom cron schedule.
     *
     * @param string $name     The internal name of the schedule
     * @param int    $interval The interval between executions of the new schedule
     * @param string $display  The display name of the schedule
     */
    function add_schedule($name, $interval, $display) {
        $old_scheds = get_option('crontrol_schedules',array());
        $old_scheds[$name] = array('interval'=>$interval, 'display'=>$display);
        update_option('crontrol_schedules', $old_scheds);
    }

    /**
     * Deletes a custom cron schedule.
     *
     * @param string $name The internal_name of the schedule to delete.
     */
    function delete_schedule($name) {
        $scheds = get_option('crontrol_schedules',array());
        unset($scheds[$name]);
        update_option('crontrol_schedules', $scheds);
    }

    /**
     * Sets up the plugin environment upon first activation.
     *
     * Run using the 'activate_' action.
     */
    function activate() {
        $extra_scheds = array('twicedaily'=>array('interval'=>43200, 'display'=>__('Twice Daily', 'crontrol')));
        add_option('crontrol_schedules', $extra_scheds);

        // if there's never been a cron entry, _get_cron_array will return FALSE
        if( _get_cron_array() === FALSE ) {
        	_set_cron_array(array());
        }
    }

    /**
     * Adds options & management pages to the admin menu.
     *
     * Run using the 'admin_menu' action.
     */
    function admin_menu() {
	    $page = add_options_page('Crontrol', 'Crontrol', 'manage_options', 'crontrol_admin_options_page', array(&$this, 'admin_options_page') );
	    $page = add_management_page('Crontrol', "Crontrol", 'manage_options', 'crontrol_admin_manage_page', array(&$this, 'admin_manage_page') );
    }

    /**
     * Gives WordPress the plugin's set of cron schedules.
     *
     * Called by the 'cron_schedules' filter.
     *
	 * @param array $scheds The current cron schedules.  Usually an empty array.
	 * @return array The existing cron schedules along with the plugin's schedules.
     */
    function cron_schedules($scheds) {
        $new_scheds = get_option('crontrol_schedules',array());
        return array_merge($new_scheds, $scheds);
    }

    /**
     * Displays the options page for the plugin.
     */
    function admin_options_page() {
        $schedules = $this->get_schedules();
        $custom_schedules = get_option('crontrol_schedules',array());
        $custom_keys = array_keys($custom_schedules);

        if( isset($_GET['crontrol_message']) ) {
            $messages = array( '2' => __("Successfully deleted the cron schedule <b>%s</b>", 'crontrol'),
                               '3' => __("Successfully added the cron schedule <b>%s</b>", 'crontrol'),
                               '7' => __("Cron schedule not added because there was a problem parsing <b>%s</b>", 'crontrol'));
            $hook = $_GET['crontrol_name'];
            $msg = sprintf($messages[$_GET['crontrol_message']], $hook);

            echo "<div id=\"message\" class=\"updated fade\"><p>$msg</p></div>";
        }

        ?>
        <div class="wrap">
		<?php screen_icon(); ?>
        <h2><?php _e("Cron Schedules", "crontrol"); ?></h2>
        <p><?php _e('Cron schedules are the time intervals that are available to WordPress and plugin developers to schedule events.  You can only delete cron schedules that you have created with WP-Crontrol.', 'crontrol'); ?></p>
        <div id="ajax-response"></div>
        <table class="widefat">
        <thead>
            <tr>
                <th><?php _e('Name', 'crontrol'); ?></th>
                <th><?php _e('Interval', 'crontrol'); ?></th>
                <th><?php _e('Display Name', 'crontrol'); ?></th>
                <th><?php _e('Actions', 'crontrol'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php
        if( empty($schedules) ) {
            ?>
            <tr colspan="4"><td><?php _e('You currently have no cron schedules.  Add one below!', 'crontrol') ?></td></tr>
            <?php
        } else {
            $class = "";
            foreach( $schedules as $name=>$data ) {
                echo "<tr id=\"sched-$name\" class=\"$class\">";
                echo "<td>$name</td>";
                echo "<td>{$data['interval']} (".$this->interval($data['interval']).")</td>";
                echo "<td>{$data['display']}</td>";
                if( in_array($name, $custom_keys) ) {
        			echo "<td><a href='" . wp_nonce_url( "options-general.php?page=crontrol_admin_options_page&amp;action=delete-sched&amp;id=$name", 'delete-sched_' . $name ) . "' class='delete'>".__( 'Delete' )."</a></td>";
                } else {
                    echo "<td>&nbsp;</td>\n";
                }
                echo "</tr>";
                $class = empty($class)?"alternate":"";
            }
        }
        ?>
        </tbody>
        </table>
        </div>
        <div class="wrap narrow">
			<?php screen_icon(); ?>
            <h2><?php _e('Add new cron schedule', 'crontrol'); ?></h2>
            <p><?php _e('Adding a new cron schedule will allow you to schedule events that re-occur at the given interval.', 'crontrol'); ?></p>
            <form method="post" action="options-general.php?page=crontrol_admin_options_page">
                <table width="100%" cellspacing="2" cellpadding="5" class="editform form-table">
            		<tbody>
            		<tr>
            			<th width="33%" valign="top" scope="row"><label for="internal_name"><?php _e('Internal name', 'crontrol'); ?>:</label></th>
            			<td width="67%"><input type="text" size="40" value="" id="internal_name" name="internal_name"/></td>
            		</tr>
            		<tr>
            			<th width="33%" valign="top" scope="row"><label for="interval"><?php _e('Interval', 'crontrol'); ?>:</label></th>
            			<td width="67%"><input type="text" size="40" value="" id="interval" name="interval"/></td>
            		</tr>
            		<tr>
            			<th width="33%" valign="top" scope="row"><label for="display_name"><?php _e('Display name', 'crontrol'); ?>:</label></th>
            			<td width="67%"><input type="text" size="40" value="" id="display_name" name="display_name"/></td>
            		</tr>
            	</tbody></table>
                <p class="submit"><input id="schedadd-submit" type="submit" class="button-primary" value="<?php _e('Add Cron Schedule &raquo;', 'crontrol'); ?>" name="new_schedule"/></p>
                <?php wp_nonce_field('new-sched') ?>
            </form>
        </div>
        <?php
    }

    /**
     * Gets a sorted (according to interval) list of the cron schedules
     */
    function get_schedules() {
        $schedules = wp_get_schedules();
        uasort($schedules, create_function('$a,$b', 'return $a["interval"]-$b["interval"];'));
        return $schedules;
    }

    /**
     * Displays a dropdown filled with the possible schedules, including non-repeating.
     *
     * @param boolean $current The currently selected schedule
     */
    function schedules_dropdown($current=false) {
        $schedules = $this->get_schedules();
        echo '<select class="postform" name="schedule">';
        foreach( $schedules as $sched_name=>$sched_data ) { ?>
            <option <?php selected($current, $sched_name) ?> value="<?php echo $sched_name ?>">
                <?php echo $sched_data['display'] ?> (<?php echo $this->interval($sched_data['interval']) ?>)
            </option>
        <?php } ?>
        <option <?php selected($current, '_oneoff') ?> value="_oneoff"><?php _e('Non-repeating', 'crontrol') ?></option>
        </select><?php
    }

    /**
     * Gets the status of WP-Cron functionality on the site by performing a test spawn. Cached for one hour when all is well.
     *
     */
	function test_cron_spawn() {

		if ( defined('ALTERNATE_WP_CRON') && ALTERNATE_WP_CRON )
			return true;

		$cached_status = get_transient( 'wp-cron-test-ok' );

		if ( $cached_status )
			return true;

		$doing_wp_cron = sprintf( '%.22F', microtime( true ) );
		$cron_url = site_url( 'wp-cron.php?doing_wp_cron=' . $doing_wp_cron );

		$result = wp_remote_post( $cron_url, array(
			'timeout'   => 3,
			'blocking'  => true,
			'sslverify' => apply_filters( 'https_local_ssl_verify', true )
		) );

		if ( is_wp_error( $result ) ) {
			return $result;
		} else {
			set_transient( 'wp-cron-test-ok', 1, 3600 );
			return true;
		}

	}

    /**
     * Shows the status of WP-Cron functionality on the site. Only displays a message when there's a problem.
     *
     */
	function show_cron_status() {

		$status = $this->test_cron_spawn();

		if ( is_wp_error( $status ) ) {
			?>
			<div id="cron-status-error" class="error">
				<p><?php printf( __( 'There was a problem spawning a call to the WP-Cron system on your site. This means WP-Cron jobs on your site may not work. The problem was:<br /><strong>%s</strong>', 'crontrol' ), esc_html( $status->get_error_message() ) ); ?></p>
			</div>
			<?php
		}

	}

    /**
     * Shows the form used to add/edit cron entries.
     *
     * @param boolean $is_php Whether this is a PHP cron entry
     * @param mixed $existing An array of existing values for the cron entry, or NULL
     */
    function show_cron_form($is_php, $existing) {
        if( $is_php ) {
            $helper_text = __('Cron entries trigger actions in your code.  Using the form below, you can enter the schedule of the action, as well as the PHP code for the action itself.  Alternatively, the schedule can be specified from within WordPress and the code for the action in a file on on your server using <a href="tools.php?page=crontrol_admin_manage_page&action=new-cron#crontrol_form">this form</a>.', 'crontrol');
            $link = ' (<a href="tools.php?page=crontrol_admin_manage_page#crontrol_form">'. __('Add new entry', 'crontrol') .'</a>)';
        } else {
            $helper_text = __('Cron entries trigger actions in your code.  A cron entry added using the form below needs a corresponding action hook somewhere in code, perhaps the <code>functions.php</code> file in your theme.  It is also possible to create your action hook from within WordPress using <a href="tools.php?page=crontrol_admin_manage_page&action=new-php-cron#crontrol_form">this form</a>.', 'crontrol');
            $link = ' (<a href="tools.php?page=crontrol_admin_manage_page&amp;action=new-php-cron#crontrol_form">'. __('Add new PHP entry', 'crontrol') .'</a>)';
        }
        if( is_array($existing) ) {
            $other_fields  = wp_nonce_field( "edit-cron_{$existing['hookname']}_{$existing['sig']}_{$existing['next_run']}", "_wpnonce", true, false );
            $other_fields .= '<input name="original_hookname" type="hidden" value="'. $existing['hookname'] .'" />';
            $other_fields .= '<input name="original_sig" type="hidden" value="'. $existing['sig'] .'" />';
            $other_fields .= '<input name="original_next_run" type="hidden" value="'. $existing['next_run'] .'" />';
            $existing['args'] = $is_php ? htmlentities($existing['args']['code']) : htmlentities($this->json->encode($existing['args']));
            $existing['next_run'] = strftime("%D %T", $existing['next_run']);
            $action = $is_php ? 'edit_php_cron' : 'edit_cron';
            $button = $is_php ? __('Modify PHP Cron Entry', 'crontrol') : __('Modify Cron Entry', 'crontrol');
            $link = false;
        } else {
            $other_fields  = wp_nonce_field( "new-cron", "_wpnonce", true, false );
            $existing = array('hookname'=>'','hookcode'=>'','args'=>'','next_run'=>'now','schedule'=>false);
            $action = $is_php ? 'new_php_cron' : 'new_cron';
            $button = $is_php ? __('Add PHP Cron Entry', 'crontrol') : __('Add Cron Entry', 'crontrol');
        }
        ?>
        <div id="crontrol_form" class="wrap narrow">
			<?php screen_icon(); ?>
            <h2><?php echo $button; if($link) echo '<span style="font-size:xx-small">'.$link.'</span>'; ?></h2>
            <p><?php echo $helper_text ?></p>
            <form method="post">
                <?php echo $other_fields ?>
                <table width="100%" cellspacing="2" cellpadding="5" class="editform form-table"><tbody>
                    <?php if( $is_php ): ?>
            		    <tr>
                			<th width="33%" valign="top" scope="row"><label for="hookcode"><?php _e('Hook code', 'crontrol'); ?>:</label></th>
                			<td width="67%"><textarea style="width:95%" name="hookcode"><?php echo $existing['args'] ?></textarea></td>
                		</tr>
            		<?php else: ?>
                		<tr>
                			<th width="33%" valign="top" scope="row"><label for="hookname"><?php _e('Hook name', 'crontrol'); ?>:</label></th>
                			<td width="67%"><input type="text" size="40" id="hookname" name="hookname" value="<?php echo $existing['hookname'] ?>"/></td>
                		</tr>
                		<tr>
                			<th width="33%" valign="top" scope="row"><label for="args"><?php _e('Arguments', 'crontrol'); ?>:</label><br /><span style="font-size:xx-small"><?php _e('e.g., [], [25], ["asdf"], or ["i","want",25,"cakes"]', 'crontrol') ?></span></th>
                			<td width="67%"><input type="text" size="40" id="args" name="args" value="<?php echo $existing['args'] ?>"/></td>
                		</tr>
            		<?php endif; ?>
            		<tr>
            			<th width="33%" valign="top" scope="row"><label for="next_run"><?php _e('Next run', 'crontrol'); ?>:</label><br /><span style="font-size:xx-small"><?php _e('e.g., "now", "tomorrow", "+2 days", or "06/04/08 15:27:09"', 'crontrol') ?></th>
            			<td width="67%"><input type="text" size="40" id="next_run" name="next_run" value="<?php echo $existing['next_run'] ?>"/></td>
            		</tr><tr>
            			<th valign="top" scope="row"><label for="schedule"><?php _e('Entry schedule', 'crontrol'); ?>:</label></th>
            			<td>
                			<?php $this->schedules_dropdown($existing['schedule']) ?>
            	  		</td>
            		</tr>
            	</tbody></table>
                <p class="submit"><input type="submit" class="button-primary" value="<?php echo $button ?> &raquo;" name="<?php echo $action ?>"/></p>
            </form>
        </div>
        <?php
    }

    /**
     * Displays the manage page for the plugin.
     */
    function admin_manage_page() {
        if( isset($_GET['crontrol_message']) ) {
            $messages = array( '1' => __('Successfully executed the cron entry <b>%s</b>', 'crontrol'),
                               '4' => __('Successfully edited the cron entry <b>%s</b>', 'crontrol'),
                               '5' => __('Successfully created the cron entry <b>%s</b>', 'crontrol'),
                               '6' => __('Successfully deleted the cron entry <b>%s</b>', 'crontrol'),
                               '7' => __('Failed to the delete the cron entry <b>%s</b>', 'crontrol'),
                               '8' => __('Failed to the execute the cron entry <b>%s</b>', 'crontrol'));
            $hook = $_GET['crontrol_name'];
            $msg = sprintf($messages[$_GET['crontrol_message']], $hook);

            echo "<div id=\"message\" class=\"updated fade\"><p>$msg</p></div>";
        }
        $crons = _get_cron_array();
        $schedules = $this->get_schedules();
        $doing_edit = (isset( $_GET['action']) && $_GET['action']=='edit-cron') ? $_GET['id'] : false ;
        $this->show_cron_status();
        ?>
        <div class="wrap">
		<?php screen_icon(); ?>
        <h2><?php _e('WP-Cron Entries', 'crontrol'); ?></h2>
        <p></p>
        <table class="widefat">
        <thead>
            <tr>
                <th><?php _e('Hook Name', 'crontrol'); ?></th>
                <th><?php _e('Arguments', 'crontrol'); ?></th>
                <th><?php _e('Next Run', 'crontrol'); ?></th>
                <th><?php _e('Recurrence', 'crontrol'); ?></th>
                <th colspan="3"><?php _e('Actions', 'crontrol'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php
        if( empty($crons) ) {
            ?>
            <tr><td colspan="7"><?php _e('You currently have no cron entries.  Add one below!', 'crontrol') ?></td></tr>
            <?php
        } else {
            $class = "";
            foreach( $crons as $time=>$cron ) {
                foreach( $cron as $hook=>$dings) {
                    foreach( $dings as $sig=>$data ) {
                        if( $doing_edit && $doing_edit==$hook && $time == $_GET['next_run'] && $sig==$_GET['sig'] ) {
                            $doing_edit = array('hookname'=>$hook,
                                                'next_run'=>$time,
                                                'schedule'=>($data['schedule'] ? $data['schedule'] : '_oneoff'),
                                                'sig'=>$sig,
                                                'args'=>$data['args']);
                        }

                        echo "<tr id=\"cron-$hook-$sig\" class=\"$class\">";
                        echo "<td>".($hook==CRONTROL_CRON_JOB ? __('<i>PHP Cron</i>', 'crontrol') : $hook)."</td>";
                        echo "<td>".($hook==CRONTROL_CRON_JOB ? __('<i>PHP Code</i>', 'crontrol') : $this->json->encode($data['args']))."</td>";
                        echo "<td>".strftime("%Y/%m/%d %H:%M:%S", $time)." (".$this->time_since(time(), $time).")</td>";
                        echo "<td>".($data['schedule'] ? $data['interval'].' ('.$this->interval($data['interval']).')' : __('Non-repeating', 'crontrol'))."</td>";
                        echo "<td><a class='view' href='tools.php?page=crontrol_admin_manage_page&amp;action=edit-cron&amp;id=$hook&amp;sig=$sig&amp;next_run=$time#crontrol_form'>Edit</a></td>";
                        echo "<td><a class='view' href='".wp_nonce_url("tools.php?page=crontrol_admin_manage_page&amp;action=run-cron&amp;id=$hook&amp;sig=$sig", "run-cron_{$hook}_{$sig}")."'>Run Now</a></td>";
                        echo "<td><a class='delete' href='".wp_nonce_url("tools.php?page=crontrol_admin_manage_page&amp;action=delete-cron&amp;id=$hook&amp;sig=$sig&amp;next_run=$time", "delete-cron_{$hook}_{$sig}_{$time}")."'>Delete</a></td>";
                        echo "</tr>";
                        $class = empty($class)?"alternate":"";
                    }
                }
            }
        }
        ?>
        </tbody>
        </table>
        </div>
        <?php
        if( is_array( $doing_edit ) ) {
            $this->show_cron_form($doing_edit['hookname']==CRONTROL_CRON_JOB, $doing_edit);
        } else {
            $this->show_cron_form((isset($_GET['action']) and $_GET['action']=='new-php-cron'), false);
        }
    }

    /**
     * Pretty-prints the difference in two times.
     *
     * @param time $older_date
     * @param time $newer_date
     * @return string The pretty time_since value
     * @link http://binarybonsai.com/code/timesince.txt
     */
    function time_since($older_date, $newer_date) {
        return $this->interval( $newer_date - $older_date );
	}

	function interval( $since ) {
        // array of time period chunks
    	$chunks = array(
        	array(60 * 60 * 24 * 365 , _n_noop('%s year', '%s years', 'crontrol')),
        	array(60 * 60 * 24 * 30 , _n_noop('%s month', '%s months', 'crontrol')),
        	array(60 * 60 * 24 * 7, _n_noop('%s week', '%s weeks', 'crontrol')),
        	array(60 * 60 * 24 , _n_noop('%s day', '%s days', 'crontrol')),
        	array(60 * 60 , _n_noop('%s hour', '%s hours', 'crontrol')),
        	array(60 , _n_noop('%s minute', '%s minutes', 'crontrol')),
        	array( 1 , _n_noop('%s second', '%s seconds', 'crontrol')),
    	);


    	if( $since <= 0 ) {
    	    return __('now', 'crontrol');
    	}

    	// we only want to output two chunks of time here, eg:
    	// x years, xx months
    	// x days, xx hours
    	// so there's only two bits of calculation below:

    	// step one: the first chunk
    	for ($i = 0, $j = count($chunks); $i < $j; $i++)
    		{
    		$seconds = $chunks[$i][0];
    		$name = $chunks[$i][1];

    		// finding the biggest chunk (if the chunk fits, break)
    		if (($count = floor($since / $seconds)) != 0)
    			{
    			break;
    			}
    		}

    	// set output var
    	$output = sprintf(_n($name[0], $name[1], $count, 'crontrol'), $count);

    	// step two: the second chunk
    	if ($i + 1 < $j)
    		{
    		$seconds2 = $chunks[$i + 1][0];
    		$name2 = $chunks[$i + 1][1];

    		if (($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0)
    			{
    			// add to output var
    			$output .= ' '.sprintf(_n($name2[0], $name2[1], $count2, 'crontrol'), $count2);
    			}
    		}

    	return $output;
	}
}

// PHP4 doesn't have json_encode built-in.
if( !function_exists('json_encode' ) ) {
    if( !class_exists('Services_JSON') )
        require_once('JSON.php');

    class Crontrol_JSON {
        var $json;
        function Crontrol_JSON() {
            $this->json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
        }
        function encode($in) {
            return $this->json->encode($in);
        }
        function decode($in) {
            return $this->json->decode($in);
        }
    }
} else {
    class Crontrol_JSON {
        function encode($in) {
            return json_encode($in);
        }
        function decode($in) {
            return json_decode($in, true);
        }
    }
}

// Get this show on the road
new Crontrol();
?>