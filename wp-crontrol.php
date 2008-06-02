<?php
/*
 * Plugin Name: WP-Crontrol
 * Plugin URI: http://www.scompt.com/projects/wp-crontrol
 * Description: WP-Crontrol lets you take control over what's happening in the WP-Cron system.
 * Author: Edward Dale
 * Version: 0.3
 * Author URI: http://www.scompt.com
 */

// TODO: 
// allow PHP entry, 
// one-offs
// DONE: localization for times
// DONE: sorting of dropdown in entry field, 
// DONE: cron job arguments
// jobs for times not in schedules

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
    var $json;
    /**
     * Hook onto all of the actions and filters needed by the plugin.
     */
    function Crontrol() {
        $this->json = new asdf_JSON();
        if( function_exists('add_action') ) {
            add_action('init', array(&$this, 'init'));
            add_action('init', array(&$this, 'handle_posts'));
        	add_action('admin_menu', array(&$this, 'admin_menu'));

            // Make sure the activation works from subdirectories as well as
            // directly in the plugin directory.
            $activate_action = str_replace(ABSPATH.PLUGINDIR.'/', 'activate_', __FILE__);
        	add_action($activate_action, array(&$this, 'activate'));
        	
        	add_filter('cron_schedules', array(&$this, 'cron_schedules'));
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
     * Handles any POSTs made by the plugin.  Run using the 'init' action.
     */
    function handle_posts() {
        if( isset($_POST['new_cron']) ) {
            if( !current_user_can('manage_options') ) die(__('You are not allowed to add new cron events.', 'crontrol'));
            check_admin_referer("new-cron");
            extract($_POST, EXTR_PREFIX_ALL, 'in');
            $in_args = $this->json->decode(stripslashes($in_args));
            $this->add_cron($in_next_run, $in_schedule, $in_hookname, $in_args);
            wp_redirect("edit.php?page=crontrol_admin_manage_page&crontrol_message=5&crontrol_name={$in_hookname}");

        } else if( isset($_POST['edit_cron']) ) {
            if( !current_user_can('manage_options') ) die(__('You are not allowed to add new cron events.', 'crontrol'));

            extract($_POST, EXTR_PREFIX_ALL, 'in');
            check_admin_referer("edit-cron_{$in_original_hookname}_{$in_original_sig}_{$in_original_next_run}");
            $in_args = $this->json->decode(stripslashes($in_args));
            $i=$this->delete_cron($in_original_hookname, $in_original_sig, $in_original_next_run);
            $i=$this->add_cron($in_next_run, $in_schedule, $in_hookname, $in_args);
            wp_redirect("edit.php?page=crontrol_admin_manage_page&crontrol_message=4&crontrol_name={$in_hookname}");

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
            $sig = $_GET['sig'];
            $next_run = $_GET['next_run'];
            check_admin_referer("delete-cron_$id_$sig_{$next_run}");
            if( $this->delete_cron($id, $sig, $next_run) ) {
                wp_redirect("edit.php?page=crontrol_admin_manage_page&crontrol_message=6&crontrol_name=$id");
            } else {
                wp_redirect("edit.php?page=crontrol_admin_manage_page&crontrol_message=7&crontrol_name=$id");
            };

        } else if( isset($_GET['action']) && $_GET['action']=='run-cron') {
            if( !current_user_can('manage_options') ) die(__('You are not allowed to run cron events.', 'crontrol'));
            $id = $_GET['id'];
            $sig = $_GET['sig'];
            check_admin_referer("run-cron_$id_$sig");
            if( $this->run_cron($id, $sig) ) {
                wp_redirect("edit.php?page=crontrol_admin_manage_page&crontrol_message=1&crontrol_name=$id");
            } else {
                wp_redirect("edit.php?page=crontrol_admin_manage_page&crontrol_message=8&crontrol_name=$id");
            }
        }
    }
    
    /**
     * Executes a cron entry immediately.
     *
     * Executes an entry by scheduling a new single event with the same arguments.
     * TODO: Make this prettier
     *
     * @param string $hookname The hookname of the cron entry to run
     */
    function run_cron($hookname, $sig) {
    	$crons = _get_cron_array();
        foreach( $crons as $time=>$cron ) {
            foreach( $cron as $hook=>$data) {
                foreach( $data as $cron_sig=>$data ) {
                    if( $hook == $hookname && $sig == $cron_sig ) {
                        wp_schedule_single_event(time(), $hook, $data['args']);
                        return true;
                    }
                }
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
        return wp_schedule_event( $next_run, $schedule, $hookname, $args ) === NULL;
    }
    
    /**
     * Deletes a cron entry.
     *
     * TODO: Make this prettier
     *
     * @param string $name The hookname of the entry to delete.
     */
    function delete_cron($to_delete, $sig, $next_run) {
    	$crons = _get_cron_array();
        foreach( $crons as $time=>$cron ) {
            if( $next_run == $time ) {
                foreach( $cron as $hook=>$data) {
                    if( $hook == $to_delete ) {
                        foreach( $data as $cron_sig=>$data ) {
                            if( $sig == $cron_sig ) {
                                wp_unschedule_event($time, $hook, $data['args']);
                                return true;
                            }
                        }
                    }
                }
            }
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
                <p class="submit"><input id="schedadd-submit" type="submit" value="<?php _e('Add Cron Schedule &raquo;', 'crontrol'); ?>" name="new_schedule"/></p>
                <?php wp_nonce_field('new-sched') ?>
            </form>
        </div>
        <?php
    }

    function schedules_dropdown($current=false) {
        $schedules = wp_get_schedules();
        uasort($schedules, create_function('$a,$b', 'return $a["interval"]-$b["interval"];'));
        echo '<select class="postform" name="schedule">';
        foreach( $schedules as $sched_name=>$sched_data ) { ?>
            <option <?php selected($current, $sched_name) ?> value="<?php echo $sched_name ?>">
                <?php echo $sched_data['display'] ?> (<?php echo $this->interval($sched_data['interval']) ?>)
            </option>
        <?php }
        echo "</select>\n";
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
        $schedules = wp_get_schedules();
        uasort($schedules, create_function('$a,$b', 'return $a["interval"]-$b["interval"];'));
        $doing_edit = (isset( $_GET['action']) && $_GET['action']=='edit-cron') ? $_GET['id'] : false ;
        ?>
        <div class="wrap">
        <h2><?php _e('WP-Cron Entries', 'crontrol'); ?></h2>
        <p></p>
        <table class="widefat" id="the-list">
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
            <tr colspan="6"><td><?php _e('You currently have no cron entries.  Add one below!', 'crontrol') ?></td></tr>
            <?php
        } else {
            $class = "";
            foreach( $crons as $time=>$cron ) {
                foreach( $cron as $hook=>$dings) {
                    foreach( $dings as $sig=>$data ) {
                        if( $doing_edit && $doing_edit==$hook && $time == $_GET['next_run'] && $sig==$_GET['sig'] ) {
                            $doing_edit = array('hookname'=>$hook,
                                                'next_run'=>$time,
                                                'schedule'=>$data['schedule'],
                                                'sig'=>$sig,
                                                'args'=>$data['args']);
                        }

                        echo "<tr id=\"cron-$hook\" class=\"$class\">";
                        echo "<td>$hook</td>";
                        echo "<td>".$this->json->encode($data['args'])."</td>";
                        echo "<td>".strftime("%D %T", $time)." (".$this->time_since(time(), $time).")</td>";
                        echo "<td>{$data['interval']} (".$this->interval($data['interval']).")</td>";
                        echo "<td><a class='view' href='edit.php?page=crontrol_admin_manage_page&amp;action=edit-cron&amp;id=$hook&amp;sig=$sig&amp;next_run=$time#crontrol_edit'>Edit</a></td>";
                        echo "<td><a class='view' href='".wp_nonce_url("edit.php?page=crontrol_admin_manage_page&amp;action=run-cron&amp;id=$hook&amp;sig=$sig", "run-cron_$hook_$sig")."'>Do Now</a></td>";
                        echo "<td><a class='delete' href='".wp_nonce_url("edit.php?page=crontrol_admin_manage_page&amp;action=delete-cron&amp;id=$hook&amp;sig=$sig&amp;next_run=$time", "delete-cron_$hook_$sig_$time")."'>Delete</a></td>";
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
        <?php if( is_array( $doing_edit ) ): ?>
        <div id="crontrol_edit" class="wrap narrow">
            <h2><?php _e('Edit cron entry', 'crontrol'); ?> (<a href="edit.php?page=crontrol_admin_manage_page"><?php _e('Add new', 'crontrol'); ?></a>)</h2>
            <form method="post">
                <?php wp_nonce_field("edit-cron_{$doing_edit['hookname']}_{$doing_edit['sig']}_{$doing_edit['next_run']}") ?>
                <input name="original_hookname" type="hidden" value="<?php echo $doing_edit['hookname'] ?>" />
                <input name="original_sig" type="hidden" value="<?php echo $doing_edit['sig'] ?>" />
                <input name="original_next_run" type="hidden" value="<?php echo $doing_edit['next_run'] ?>" />
                <table width="100%" cellspacing="2" cellpadding="5" class="editform form-table">
            		<tbody><tr>
            			<th width="33%" valign="top" scope="row"><label for="hookname"><?php _e('Hook name', 'crontrol'); ?>:</label></th>
            			<td width="67%"><input type="text" size="40" id="hookname" name="hookname" value="<?php echo $doing_edit['hookname'] ?>"/></td>
            		</tr><tr>
            			<th width="33%" valign="top" scope="row"><label for="args"><?php _e('Arguments', 'crontrol'); ?>:</label><br /><span style="font-size:xx-small"><?php _e('e.g., [], [25], ["asdf"], or ["i","want",25,"cakes"]', 'crontrol') ?></span></th>
            			<td width="67%"><input type="text" size="40" id="args" name="args" value="<?php echo htmlentities($this->json->encode($doing_edit['args'])) ?>"/></td>
            		</tr><tr>
            			<th width="33%" valign="top" scope="row"><label for="next_run"><?php _e('Next run', 'crontrol'); ?>:</label></th>
            			<td width="67%"><input type="text" size="40" id="next_run" name="next_run" value="<?php echo strftime("%D %T", $doing_edit['next_run']) ?>"/></td>
            		</tr><tr>
            			<th valign="top" scope="row"><label for="schedule"><?php _e('Entry schedule', 'crontrol'); ?>:</label></th>
            			<td>
                			<?php $this->schedules_dropdown($doing_edit['schedule']) ?>
            	  		</td>
            		</tr>
            	</tbody></table>
                <p class="submit"><input type="submit" value="<?php _e('Modify Cron Entry &raquo;', 'crontrol'); ?>" name="edit_cron"/></p>
            </form>
        </div>
        <?php else: ?>
        <div id="crontrol_edit" class="wrap narrow">
            <?php if( $doing_edit ) {
                echo "<div id=\"message\" class=\"updated fade\"><p>".sprintf(__('Could not load cron entry <b>%s</b>', 'crontrol'), $doing_edit)."</p></div>";
            }?>
        
            <h2><?php _e('Add new cron entry', 'crontrol'); ?></h2>
            <p><?php _e('Cron entries trigger actions in your code.  After adding a new cron entry here, you will need to add a corresponding action hook somewhere in code, perhaps the <code>functions.php</code> file in your theme.', 'crontrol'); ?></p>
            <form method="post">
                <?php wp_nonce_field('new-cron') ?>
                <table width="100%" cellspacing="2" cellpadding="5" class="editform form-table">
            		<tbody><tr>
            			<th width="33%" valign="top" scope="row"><label for="hookname"><?php _e('Hook name', 'crontrol'); ?>:</label></th>
            			<td width="67%"><input type="text" size="40" value="" id="hookname" name="hookname"/></td>
            		</tr><tr>
            			<th width="33%" valign="top" scope="row"><label for="args"><?php _e('Arguments', 'crontrol'); ?>:</label><br /><span style="font-size:xx-small"><?php _e('e.g., [], [25], ["asdf"], or ["i","want",25,"cakes"]', 'crontrol') ?></span></th>
            			<td width="67%"><input type="text" size="40" id="args" name="args" value="[]"/></td>
            		</tr><tr>
            			<th width="33%" valign="top" scope="row"><label for="next_run"><?php _e('Next run', 'crontrol'); ?>:</label></th>
            			<td width="67%"><input type="text" size="40" value="now" id="next_run" name="next_run"/></td>
            		</tr><tr>
            			<th valign="top" scope="row"><label for="schedule"><?php _e('Entry schedule', 'crontrol'); ?>:</label></th>
            			<td>
                            <?php $this->schedules_dropdown() ?>
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
        	array(60 * 60 * 24 * 365 , __ngettext_noop('%s year', '%s years', 'crontrol')),
        	array(60 * 60 * 24 * 30 , __ngettext_noop('%s month', '%s months', 'crontrol')),
        	array(60 * 60 * 24 * 7, __ngettext_noop('%s week', '%s weeks', 'crontrol')),
        	array(60 * 60 * 24 , __ngettext_noop('%s day', '%s days', 'crontrol')),
        	array(60 * 60 , __ngettext_noop('%s hour', '%s hours', 'crontrol')),
        	array(60 , __ngettext_noop('%s minute', '%s minutes', 'crontrol')),
        	array( 1 , __ngettext_noop('%s second', '%s seconds', 'crontrol')),
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
    	$output = sprintf(__ngettext($name[0], $name[1], $count, 'crontrol'), $count);

    	// step two: the second chunk
    	if ($i + 1 < $j)
    		{
    		$seconds2 = $chunks[$i + 1][0];
    		$name2 = $chunks[$i + 1][1];

    		if (($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0)
    			{
    			// add to output var
    			$output .= ' '.sprintf(__ngettext($name2[0], $name2[1], $count2, 'crontrol'), $count2);
    			}
    		}

    	return $output;
	}
}

if( !function_exists('json_encode' ) ) {
    if( !class_exists('Services_JSON') ) 
        require_once('JSON.php');
        
    class asdf_JSON {
        var $json;
        function asdf_JSON() {
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
    class asdf_JSON {
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