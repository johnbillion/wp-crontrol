<?php
/*
 * Plugin Name: WP-Crontrol
 * Plugin URI: http://www.scompt.com/projects/wp-crontrol
 * Description: WP-Crontrol lets you take control over what's happening in the WP-Cron system.
 * Author: Edward Dale
 * Version: 0.1
 * Author URI: http://www.scompt.com
 */

class Crontrol {
    function Crontrol() {
        if( function_exists('add_action') ) {
            add_action('init', array(&$this, 'init'));
            add_action('init', array(&$this, 'handle_posts'));
        	add_action('admin_menu', array(&$this, 'admin_menu'));
        	add_action('activate_wp-crontrol.php', array(&$this, 'activate'));
        	
        	add_filter('cron_schedules', array(&$this, 'cron_schedules'));
        	add_action('wp_ajax_delete-sched', array(&$this, 'handle_ajax'));
        	add_action('wp_ajax_delete-cron', array(&$this, 'handle_ajax'));
        } 
    }
    
    function init() {
    	load_plugin_textdomain('crontrol', PLUGINDIR.'/wp-crontrol/gettext');
    }
    function scripts() {
        wp_enqueue_script( 'listman');
    }

    function handle_ajax() {
        switch( $_POST['action'] ) {
            case 'delete-sched':
                $to_delete = $_POST['id'];
                $this->delete_schedule($to_delete);
                exit('1');
                break;
            case 'delete-cron':
                break;
        }
    }
    
    function handle_posts() {
        if( isset($_POST['new_cron']) ) {
            wp_schedule_event(time(), $_POST['schedule'], $_POST['hookname']);

        } else if( isset($_POST['new_schedule']) ) {
            check_admin_referer("new-sched");
            $name = $_POST['internal_name'];
            $interval = $_POST['interval'];
            $display = $_POST['display_name'];
            $this->add_schedule($name, $interval, $display);
            wp_redirect('options-general.php?page=crontrol_admin_options_page');

        } else if( isset($_GET['action']) && $_GET['action']=='delete-sched') {
            $id = $_GET['id'];
            check_admin_referer("delete-sched_$id");
            $this->delete_schedule($id);
            wp_redirect('options-general.php?page=crontrol_admin_options_page');
        }
    }
    
    function add_schedule($name, $interval, $display) {
        $old_scheds = get_option('crontrol_schedules');
        $old_scheds[$_POST['internal_name']] = array('interval'=>$_POST['interval'], 'display'=>$_POST['display_name']);
        update_option('crontrol_schedules', $old_scheds);
    }
    function delete_schedule($name) {
        $scheds = get_option('crontrol_schedules');
        unset($scheds[$name]);
        update_option('crontrol_schedules', $scheds);
    }
    
    function activate() {
        $extra_scheds = array('twicedaily'=>array('interval'=>43200, 'display'=>'Twice Daily'));
        add_option('crontrol_schedules', $extra_scheds);
    }
    
    function admin_menu() {
        // Add a Zensor menu underneath the options and management page
	    $page = add_options_page('Crontrol', 'Crontrol', 'manage_options', 'crontrol_admin_options_page', array(&$this, 'admin_options_page') );
	    add_action("admin_print_scripts-$page", array(&$this, 'scripts') );
		
	    $page = add_management_page('Crontrol', "Crontrol", '1', 'crontrol_admin_manage_page', array(&$this, 'admin_manage_page') );

		// Add some scripts and stylesheets to the admin section
        // add_action("admin_print_scripts-$page", array(&$this, 'scripts') );
        // add_action("admin_head", array(&$this, 'styles') );
    }
    
    function cron_schedules($scheds) {
        $new_scheds = get_option('crontrol_schedules');
        return $new_scheds;
    }
    
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
     * From: http://binarybonsai.com/code/timesince.txt
     */
    function time_since($older_date, $newer_date)
    	{
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

new Crontrol();
?>