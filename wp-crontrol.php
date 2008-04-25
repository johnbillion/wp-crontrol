<?php
/*
 * Plugin Name: WP-Crontrol
 * Plugin URI: http://www.scompt.com/projects/wp-crontrol
 * Description: WP-Crontrol lets you take control over what's happening in the WP-Cron system.
 * Author: Edward Dale
 * Version: 0.2
 * Author URI: http://www.scompt.com
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
  * @author     Edward Dale <scompt@scompt.com>
  * @copyright  Copyright 2007 Edward Dale
  * @license    http://www.gnu.org/licenses/gpl.txt GPL 2.0
  * @version    $Id$
  * @link       http://www.scompt.com/projects/wp-crontrol
  * @since      0.2
  */
class Crontrol {

    /**
     * Hook onto all of the actions and filters needed by the plugin.
     */
    function Crontrol() {
        if( function_exists('add_action') ) {
            add_action('init', array(&$this, 'init'));
            add_action('init', array(&$this, 'handle_posts'));
        	add_action('admin_menu', array(&$this, 'admin_menu'));

            // Make sure the activation works from subdirectories as well as
            // directly in the plugin directory.
            $activate_action = str_replace(ABSPATH.PLUGINDIR.'/', 'activate_', __FILE__);
        	add_action($activate_action, array(&$this, 'activate'));
        	
        	add_filter('cron_schedules', array(&$this, 'cron_schedules'));
        	add_action('wp_ajax_delete-sched', array(&$this, 'handle_ajax'));
        	add_action('wp_ajax_delete-cron', array(&$this, 'handle_ajax'));
        } 
    }
    
    /**
     * Run using the 'init' action.
     */
    function init() {
    	load_plugin_textdomain('crontrol', str_replace(ABSPATH, '', dirname(__FILE__).'/gettext'));
    }
    
    /**
     * Run using the 'admin_print_scripts' action.  Added in the 'admin_menu'
     * hook.
     */
    function scripts() {
        wp_enqueue_script( 'listman');
    }

    /**
     * Handles any ajax requests made by the plugin.  Run using 
     * the 'wp_ajax_*' actions.
     */
    function handle_ajax() {
        switch( $_POST['action'] ) {
            case 'delete-sched':
                if( !current_user_can('manage_options') ) die('-1');
                $to_delete = $_POST['id'];
                $this->delete_schedule($to_delete);
                exit('1');
                break;
            case 'delete-cron':
                if( !current_user_can('manage_options') ) die('-1');
                $to_delete = $_POST['id'];
                $this->delete_cron($to_delete);
                exit('1');
                break;
        }
    }
    
    /**
     * Handles any POSTs made by the plugin.  Run using the 'init' action.
     */
    function handle_posts() {
        if( isset($_POST['new_cron']) ) {
            if( !current_user_can('manage_options') ) die(__('You are not allowed to add new cron events.', 'crontrol'));
            check_admin_referer("new-cron");
            $next_run = $_POST['nextrun'];
            $schedule = $_POST['schedule'];
            $hookname = $_POST['hookname'];
            $this->add_cron($next_run, $schedule, $hookname);
            wp_redirect("edit.php?page=crontrol_admin_manage_page&crontrol_message=5&crontrol_name=$hookname");

        } else if( isset($_POST['edit_cron']) ) {
            if( !current_user_can('manage_options') ) die(__('You are not allowed to add new cron events.', 'crontrol'));
            $next_run = $_POST['nextrun'];
            $schedule = $_POST['schedule'];
            $hookname = $_POST['hookname'];
            $original_hookname = $_POST['original_hookname'];
            check_admin_referer("edit-cron_{$original_hookname}");
            $this->delete_cron($original_hookname);
            $this->add_cron($next_run, $schedule, $hookname);
            wp_redirect("edit.php?page=crontrol_admin_manage_page&crontrol_message=4&crontrol_name=$hookname");

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
            check_admin_referer("delete-sched_$id");
            $this->delete_schedule($id);
            wp_redirect("options-general.php?page=crontrol_admin_options_page&crontrol_message=2&crontrol_name=$id");

        } else if( isset($_GET['action']) && $_GET['action']=='delete-cron') {
            if( !current_user_can('manage_options') ) die(__('You are not allowed to delete cron events.', 'crontrol'));
            $id = $_GET['id'];
            check_admin_referer("delete-cron_$id");
            $this->delete_cron($id);
            wp_redirect("edit.php?page=crontrol_admin_manage_page&crontrol_message=6&crontrol_name=$id");

        } else if( isset($_GET['action']) && $_GET['action']=='run-cron') {
            if( !current_user_can('manage_options') ) die(__('You are not allowed to run cron events.', 'crontrol'));
            $id = $_GET['id'];
            check_admin_referer("run-cron_$id");
            $this->run_cron($id);
            wp_redirect("edit.php?page=crontrol_admin_manage_page&crontrol_message=1&crontrol_name=$id");
        }
    }
    
    /**
     * Executes a cron entry immediately.
     *
     * Executes an entry by deleting it and adding it again with a
     * run time of 'now'.
     *
     * @param string $hookname The hookname of the cron entry to run
     */
    function run_cron($hookname) {
        $sched = wp_get_schedule($hookname);
        wp_clear_scheduled_hook($hookname);
        $this->add_cron('now', $sched, $hookname);
        
    }
    
    /**
     * Adds a new cron entry.
     *
     * @param string $next_run A human-readable (strtotime) time that the entry should be run at
     * @param string $schedule The recurrence of the cron entry
     * @param string $hookname The name of the hook to execute
     */
    function add_cron($next_run, $schedule, $hookname) {
        $next_run = strtotime($next_run);
        if( $next_run===FALSE || $next_run==-1 ) $next_run=time();
        if( !wp_schedule_event( $next_run, $schedule, $hookname ) ) {
            $error=True;
        }
    }
    
    /**
     * Deletes a cron entry.
     *
     * @param string $name The hookname of the entry to delete.
     */
    function delete_cron($to_delete) {
        wp_clear_scheduled_hook($to_delete);
    }
    
    /**
     * Adds a new custom cron schedule.
     *
     * @param string $name     The internal name of the schedule
     * @param int    $interval The interval between executions of the new schedule
     * @param string $display  The display name of the schedule
     */
    function add_schedule($name, $interval, $display) {
        $old_scheds = get_option('crontrol_schedules');
        $old_scheds[$name] = array('interval'=>$interval, 'display'=>$display);
        update_option('crontrol_schedules', $old_scheds);
    }
    
    /**
     * Deletes a custom cron schedule.
     *
     * @param string $name The internal_name of the schedule to delete.
     */
    function delete_schedule($name) {
        $scheds = get_option('crontrol_schedules');
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
	    add_action("admin_print_scripts-$page", array(&$this, 'scripts') );
		
	    $page = add_management_page('Crontrol', "Crontrol", 'manage_options', 'crontrol_admin_manage_page', array(&$this, 'admin_manage_page') );
	    add_action("admin_print_scripts-$page", array(&$this, 'scripts') );
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
        $new_scheds = get_option('crontrol_schedules');
        return array_merge($new_scheds, $scheds);
    }
    
    /**
     * Displays the options page for the plugin.
     */
    function admin_options_page() {
        $schedules = wp_get_schedules();
        $custom_schedules = get_option('crontrol_schedules');
        $custom_keys = array_keys($custom_schedules);
        uasort($schedules, create_function('$a,$b', 'return $a["interval"]-$b["interval"];'));
        
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
        <h2><?php _e("Cron Schedules", "crontrol"); ?></h2>
        <p><?php _e('Cron schedules are the time intervals that are available to WordPress and plugin developers to schedule events.  You can only delete cron schedules that you have created with WP-Crontrol.', 'crontrol'); ?></p>
        <div id="ajax-response"></div>
        <table class="widefat" id="the-list">
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
        			echo "<td><a href='" . wp_nonce_url( "options-general.php?page=crontrol_admin_options_page&amp;action=delete-sched&amp;id=$name", 'delete-sched_' . $name ) . "' onclick=\"return deleteSomething( 'sched', '$name', '" . js_escape(sprintf( __("You are about to delete the schedule '%s'.\n'OK' to delete, 'Cancel' to stop.", 'crontrol' ), $name)) . "' );\" class='delete'>".__( 'Delete' )."</a></td>";
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
            <h2><?php _e('Add new cron schedule', 'crontrol'); ?></h2>
            <p><?php _e('Adding a new cron schedule will allow you to schedule events that re-occur at the given interval.', 'crontrol'); ?></p>
            <form method="post" action="options-general.php?page=crontrol_admin_options_page">
                <table width="100%" cellspacing="2" cellpadding="5" class="editform">
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
                <p class="submit"><input id="schedadd-submit" type="submit" value="<?php _e('Add Cron Schedule &raquo;', 'crontrol'); ?>" name="new_schedule"/></p>
                <?php wp_nonce_field('new-sched') ?>
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
                               '6' => __('Successfully deleted the cron entry <b>%s</b>', 'crontrol'));
            $hook = $_GET['crontrol_name'];
            $msg = sprintf($messages[$_GET['crontrol_message']], $hook);

            echo "<div id=\"message\" class=\"updated fade\"><p>$msg</p></div>";
        }
        $crons = _get_cron_array();
        $schedules = wp_get_schedules();
        $doing_edit = (isset( $_GET['action']) && $_GET['action']=='edit-cron') ? $_GET['id'] : false ;
        ?>
        <div class="wrap">
        <h2><?php _e('WP-Cron Entries', 'crontrol'); ?></h2>
        <p></p>
            <div id="ajax-response"></div>
        <table class="widefat" id="the-list">
        <thead>
            <tr>
                <th><?php _e('Hook Name', 'crontrol'); ?></th>
                <th><?php _e('Next Run', 'crontrol'); ?></th>
                <th><?php _e('Recurrence', 'crontrol'); ?></th>
                <th colspan="3"><?php _e('Actions', 'crontrol'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php
        if( empty($crons) ) {
            ?>
            <tr colspan="6"><td><?php _e('You currently have no cron entries.  Add one below!', 'crontrol') ?></td></tr>
            <?php
        } else {
            $class = "";
            foreach( $crons as $time=>$cron ) {
                foreach( $cron as $hook=>$data) {
                    $data = array_shift($data);
                    if( $doing_edit && $doing_edit==$hook ) {
                        $doing_edit = array('hookname'=>$hook,
                                            'nextrun'=>$time,
                                            'schedule'=>$data['schedule']);
                    }

                    echo "<tr id=\"cron-$hook\" class=\"$class\">";
                    echo "<td>$hook</td>";
                    echo "<td>".strftime("%D %T", $time)." (".$this->time_since(time(), $time).")</td>";
                    echo "<td>{$data['interval']} (".$this->interval($data['interval']).")</td>";
                    echo "<td><a class='view' href='edit.php?page=crontrol_admin_manage_page&amp;action=edit-cron&amp;id=$hook'>Edit</a></td>";
                    echo "<td><a class='view' href='".wp_nonce_url("edit.php?page=crontrol_admin_manage_page&amp;action=run-cron&amp;id=$hook", 'run-cron_' . $hook)."' onclick=\"return confirm('". js_escape(sprintf(__("You are about to execute a cron entry.\nPress 'OK' to continue or 'Cancel' to stop.", 'crontrol')))."');\">Do Now</a></td>";
                    echo "<td><a class='delete' href='".wp_nonce_url("edit.php?page=crontrol_admin_manage_page&amp;action=delete-cron&amp;id=$hook", 'delete-cron_' . $hook)."' onclick=\"return deleteSomething( 'cron', '$hook', '" . js_escape(sprintf( __("You are about to delete the cron entry '%s'.\n'OK' to delete, 'Cancel' to stop.", 'crontrol'), $hook)) . "' );\">Delete</a></td>";
                    echo "</tr>";
                    $class = empty($class)?"alternate":"";
                }
            }
        }
        ?>
        </tbody>
        </table>
        </div>
        <?php if( is_array( $doing_edit ) ): ?>
        <div class="wrap narrow">
            <h2><?php _e('Edit cron entry', 'crontrol'); ?> (<a href="edit.php?page=crontrol_admin_manage_page"><?php _e('Add new', 'crontrol'); ?></a>)</h2>
            <form method="post">
                <?php wp_nonce_field('edit-cron_'.$doing_edit['hookname']) ?>
                <input name="original_hookname" type="hidden" value="<?php echo $doing_edit['hookname'] ?>" />
                <table width="100%" cellspacing="2" cellpadding="5" class="editform">
            		<tbody><tr>
            			<th width="33%" valign="top" scope="row"><label for="hookname"><?php _e('Hook name', 'crontrol'); ?>:</label></th>
            			<td width="67%"><input type="text" size="40" id="hookname" name="hookname" value="<?php echo $doing_edit['hookname'] ?>"/></td>
            		</tr><tr>
            			<th width="33%" valign="top" scope="row"><label for="nextrun"><?php _e('Next run', 'crontrol'); ?>:</label></th>
            			<td width="67%"><input type="text" size="40" id="nextrun" name="nextrun" value="<?php echo strftime("%D %T", $doing_edit['nextrun']) ?>"/></td>
            		</tr><tr>
            			<th valign="top" scope="row"><label for="schedule"><?php _e('Entry schedule', 'crontrol'); ?>:</label></th>
            			<td>
                        <select class="postform" name="schedule">
                        <?php
                        foreach( $schedules as $sched_name=>$sched_data ) {
                            $selected = $doing_edit['schedule']==$sched_name ? 'selected="selected"' : '';
                            echo "<option $selected value=\"$sched_name\">{$sched_data['display']} (".$this->interval($sched_data['interval']).")</option>\n";
                        }
                        ?>
                        </select>
            	  		</td>
            		</tr>
            	</tbody></table>
                <p class="submit"><input type="submit" value="<?php _e('Modify Cron Entry &raquo;', 'crontrol'); ?>" name="edit_cron"/></p>
            </form>
        </div>
        <?php else: ?>
        <div class="wrap narrow">
            <h2><?php _e('Add new cron entry', 'crontrol'); ?></h2>
            <p><?php _e('Cron entries trigger actions in your code.  After adding a new cron entry here, you will need to add a corresponding action hook somewhere in code, perhaps the <code>functions.php</code> file in your theme.', 'crontrol'); ?></p>
            <form method="post">
                <?php wp_nonce_field('new-cron') ?>
                <table width="100%" cellspacing="2" cellpadding="5" class="editform">
            		<tbody><tr>
            			<th width="33%" valign="top" scope="row"><label for="hookname"><?php _e('Hook name', 'crontrol'); ?>:</label></th>
            			<td width="67%"><input type="text" size="40" value="" id="hookname" name="hookname"/></td>
            		</tr><tr>
            			<th width="33%" valign="top" scope="row"><label for="nextrun"><?php _e('Next run', 'crontrol'); ?>:</label></th>
            			<td width="67%"><input type="text" size="40" value="now" id="nextrun" name="nextrun"/></td>
            		</tr><tr>
            			<th valign="top" scope="row"><label for="schedule"><?php _e('Entry schedule', 'crontrol'); ?>:</label></th>
            			<td>
                        <select class="postform" name="schedule">
                        <?php
                        foreach( $schedules as $sched_name=>$sched_data ) {
                            echo "<option value=\"$sched_name\">{$sched_data['display']} (".$this->interval($sched_data['interval']).")</option>\n";
                        }
                        ?>
                        </select>
            	  		</td>
            		</tr>
            	</tbody></table>
                <p class="submit"><input type="submit" value="<?php _e('Add Cron Entry &raquo;', 'crontrol'); ?>" name="new_cron"/></p>
            </form>
        </div>
        <?php endif; 
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
        	array(60 * 60 * 24 * 365 , 'year'),
        	array(60 * 60 * 24 * 30 , 'month'),
        	array(60 * 60 * 24 * 7, 'week'),
        	array(60 * 60 * 24 , 'day'),
        	array(60 * 60 , 'hour'),
        	array(60 , 'minute'),
        	array( 1 , 'second'),
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
    	$output = ($count == 1) ? '1 '.$name : "$count {$name}s";

    	// step two: the second chunk
    	if ($i + 1 < $j)
    		{
    		$seconds2 = $chunks[$i + 1][0];
    		$name2 = $chunks[$i + 1][1];

    		if (($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0)
    			{
    			// add to output var
    			$output .= ($count2 == 1) ? ', 1 '.$name2 : ", $count2 {$name2}s";
    			}
    		}

    	return $output;
	}
}

// Get this show on the road
new Crontrol();
?>