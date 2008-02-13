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
    	load_plugin_textdomain('crontrol', PLUGINDIR.'/wp-crontrol/gettext');
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
                break;
        }
    }
    
    /**
     * Handles any POSTs made by the plugin.  Run using the 'init' action.
     */
    function handle_posts() {
        if( isset($_POST['new_cron']) ) {
            if( !current_user_can('manage_options') ) die('You are not allowed to add new cron events.');
            wp_schedule_event(time(), $_POST['schedule'], $_POST['hookname']);

        } else if( isset($_POST['new_schedule']) ) {
            if( !current_user_can('manage_options') ) die('You are not allowed to add new cron schedules.');
            check_admin_referer("new-sched");
            $name = $_POST['internal_name'];
            $interval = $_POST['interval'];
            $display = $_POST['display_name'];
            $this->add_schedule($name, $interval, $display);
            wp_redirect('options-general.php?page=crontrol_admin_options_page');

        } else if( isset($_GET['action']) && $_GET['action']=='delete-sched') {
            if( !current_user_can('manage_options') ) die('You are not allowed to delete cron schedules.');
            $id = $_GET['id'];
            check_admin_referer("delete-sched_$id");
            $this->delete_schedule($id);
            wp_redirect('options-general.php?page=crontrol_admin_options_page');
        }
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
        $extra_scheds = array('twicedaily'=>array('interval'=>43200, 'display'=>'Twice Daily'));
        add_option('crontrol_schedules', $extra_scheds);
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
        
        ?>
        <div class="wrap">
        <h2>Cron Schedules (<a href="#new">add new</a>)</h2>
        <p></p>
            <div id="ajax-response"></div>
        <table class="widefat" id="the-list">
        <thead>
            <tr>
                <th>Name</th>
                <th>Interval</th>
                <th>Display Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $class = "";
        foreach( $schedules as $name=>$data ) {
            echo "<tr id=\"sched-$name\" class=\"$class\">";
            echo "<td>$name</td>";
            echo "<td>{$data['interval']} (".$this->time_since(time(), time()+$data['interval']).")</td>";
            echo "<td>{$data['display']}</td>";
            if( in_array($name, $custom_keys) ) {
    			echo "<td><a href='" . wp_nonce_url( "options-general.php?page=crontrol_admin_options_page&amp;action=delete-sched&amp;id=$name", 'delete-sched_' . $name ) . "' onclick=\"return deleteSomething( 'sched', '$name', '" . js_escape(sprintf( __("You are about to delete the schedule '%s'.\n'OK' to delete, 'Cancel' to stop." ), $name)) . "' );\" class='delete'>".__( 'Delete' )."</a></td>";
            }
            echo "</tr>";
            $class = empty($class)?"alternate":"";
        }
        
        ?>
        </tbody>
        </table>
        </div>
        <div class="wrap narrow">
            <a name="new" id="new"></a>
            <h2>Add new cron schedule</h2>
        
            <form method="post" action="options-general.php?page=crontrol_admin_options_page">
                <table width="100%" cellspacing="2" cellpadding="5" class="editform">
            		<tbody>
            		<tr>
            			<th width="33%" valign="top" scope="row"><label for="internal_name">Internal name:</label></th>
            			<td width="67%"><input type="text" size="40" value="" id="internal_name" name="internal_name"/></td>
            		</tr>
            		<tr>
            			<th width="33%" valign="top" scope="row"><label for="interval">Interval:</label></th>
            			<td width="67%"><input type="text" size="40" value="" id="interval" name="interval"/></td>
            		</tr>
            		<tr>
            			<th width="33%" valign="top" scope="row"><label for="display_name">Display name:</label></th>
            			<td width="67%"><input type="text" size="40" value="" id="display_name" name="display_name"/></td>
            		</tr>
            	</tbody></table>
                <p class="submit"><input id="schedadd-submit" type="submit" value="Add Cron Schedule &raquo;" name="new_schedule"/></p>
                <?php wp_nonce_field('new-sched') ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Displays the manage page for the plugin.
     */
    function admin_manage_page() {
        $crons = _get_cron_array();
        $schedules = wp_get_schedules();

        ?>
        <div class="wrap">
        <h2>WP-Cron Entries (<a href="#new">add new</a>)</h2>
        <p></p>
        <table class="widefat">
        <thead>
            <tr>
                <th>Hook Name</th>
                <th>Next Run</th>
                <th>Recurrence</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $class = "";
        foreach( $crons as $time=>$cron ) {
            foreach( $cron as $hook=>$data) {
                $data = array_shift($data);
                echo "<tr class=\"$class\">";
                echo "<td>$hook</td>";
                echo "<td>".strftime("%D %T", $time)." (".$this->time_since(time(), $time).")</td>";
                echo "<td>{$data['interval']} (".$this->time_since(time(), time()+$data['interval']).")</td>";
                echo "<td></td>";
                echo "</tr>";
                $class = empty($class)?"alternate":"";
            }
        }
        ?>
        </tbody>
        </table>
        </div>
        <div class="wrap narrow">
            <a name="new" id="new"></a>
            <h2>Add new cron entry</h2>
            <form method="post">
                <table width="100%" cellspacing="2" cellpadding="5" class="editform">
            		<tbody><tr>
            			<th width="33%" valign="top" scope="row"><label for="hookname">Hook name:</label></th>
            			<td width="67%"><input type="text" size="40" value="" id="hookname" name="hookname"/></td>
            		</tr>
            		<tr>
            			<th valign="top" scope="row"><label for="schedule">Entry schedule:</label></th>
            			<td>
                        <select class="postform" name="schedule">
                        <?php
                        foreach( $schedules as $sched_name=>$sched_data ) {
                            echo "<option value=\"$sched_name\">{$sched_data['display']} (".$this->time_since(time(), time()+$sched_data['interval']).")</option>\n";
                        }
                        ?>
                        </select>
            	  		</td>
            		</tr>
            	</tbody></table>
                <p class="submit"><input type="submit" value="Add Cron Entry &raquo;" name="new_cron"/></p>
            </form>
  </div>
        <?php
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

    	// difference in seconds
    	$since = $newer_date - $older_date;
    	if( $since <= 0 ) {
    	    return "now";
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