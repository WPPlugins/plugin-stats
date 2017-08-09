<?php
/*
 * Plugin Name: Plugin Stats
 * Plugin URI: http://scompt.com/projects/plugin-stats
 * Description: Plugin Stats provides a shortcode, template function, and dashboard widget which graphs the downloads completed for plugins hosted at WordPress.org.
 * Author: Edward Dale
 * Version: 1.1
 * Author URI: http://scompt.com
 */
 
/**
 * Plugin Stats provides a shortcode, template function, and dashboard widget
 * which graphs the downloads completed for plugins hosted at WordPress.org.
 *
 * LICENSE
 * This file is part of Plugin Stats.
 *
 * Plugin Stats is free software; you can redistribute it and/or
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
 * @package    Plugin Stats
 * @author     Edward Dale <scompt@scompt.com>
 * @copyright  Copyright 2008 Edward Dale
 * @license    http://www.gnu.org/licenses/gpl.txt GPL 2.0
 * @version    $Id: immerstat.php 45292 2008-05-11 19:20:29Z scompt $
 * @link       http://www.scompt.com/projects/plugin-stats
 * @since      1.0
 */
class PluginStats {
    var $snoop;   // For grabbing the xml from WordPress.org
    
    /**
     * Adds hooks for the shortcode, template function, and initialization
     */
    function PluginStats() {
        add_action('admin_init', array(&$this, 'init'));
        add_shortcode('plugin-stats', array(&$this, 'shortcode'));
        add_action('plugin-stats', array(&$this, 'template_function'));
    }
    
    /**
     * Adds dashboard filters and loads translations.
     */
    function init() {
        add_action('wp_dashboard_setup', array(&$this, 'wp_dashboard_setup'));
		add_filter( 'wp_dashboard_widgets', array(&$this, 'add_widget') );
		add_filter('dashwidman_safewidgets', array(&$this, 'add_safe_widget'));

    	load_plugin_textdomain('plugin-stats', str_replace(ABSPATH, '', dirname(__FILE__)));    	
    }
    
    /**
     * Action hook for the plugin-stats template function.
     */
    function template_function($slugname='default', $size='360x100', $addlink='0') {
        echo $this->shortcode(array('slugname'=>$slugname, 'size'=>$size, 'addlink'=>$addlink));
    }
    
    /**
     * Handler for the plugin-stats shortcode.
     */
    function shortcode($atts) {
    	extract(shortcode_atts(array(
    		'slugname' => 'default',
    		'size' => '360x100',
    		'addlink' => '0',
    	), $atts));

        $slugname = preg_replace('/[^a-zA-Z0-9_-]/', '', $slugname);
        if( $addlink ) add_filter('plugin-stats_img-link', array(&$this, 'add_link'), 10, 2);
        $imgLink = $this->getImageLink($slugname, $size);
        remove_filter('plugin-stats_img-link', array(&$this, 'add_link'), 10, 2);
        
        return $imgLink;
    }

    /////////////////////////
    // Dashboard functions //
    /////////////////////////
    
    /**
     * Register the widget and control function.
     */
    function wp_dashboard_setup() {
        wp_register_sidebar_widget( 'plugin-stats', __( 'Plugin Stats', 'plugin-stats'), 'wp_dashboard_empty', array(), array(&$this, 'widget_output')	);
        wp_register_widget_control( 'plugin-stats', __( 'Plugin Stats', 'plugin-stats' ),  array(&$this, 'widget_control'), array(), array( 'widget_id' => 'plugin-stats' ) );
    }
    
    /**
     * Handles the 'edit' side of the widget.
     */
    function widget_control() {
        global $user_ID;

		if ( 'POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['plugin-stats_slugs']) ) {
		    // sanitize the plugin names and save them
            $slugs = $_POST['plugin-stats_slugs'];
            $slugs = preg_replace('/[^a-zA-Z0-9_,-]/', '', $slugs);
            update_usermeta($user_ID, 'plugin-stats_slugs', $slugs);
        } else {
            // display the plugin names
            $slugs = get_usermeta($user_ID, 'plugin-stats_slugs');
            ?>
            <p>
        		<label for="plugin-stats_slugs"><?php _e('Enter a comma-separated list of plugin slugs.', 'plugin-stats' ); ?>
                    <input id="plugin-stats_slugs" name="plugin-stats_slugs" type="text" value="<?php echo $slugs ?>" />
        		</label>
        	</p>
        	<?php
        }
    }
    
    /**
     * Handles the output side of the widget
     */
    function widget_output() {
        global $user_ID;

        // grab the plugins and split 'em, should already be sanitized
        $slugs = get_usermeta($user_ID, 'plugin-stats_slugs');
        if( empty($slugs) ) {
            _e('Thanks for installing the <a href="http://scompt.com/projects/plugin-stats">Plugin Stats</a> plugin!  <a href="index.php?edit=plugin-stats#plugin-stats">Add some plugins</a> to start tracking your downloads in your dashboard.', 'plugin-stats');
            return;
        }
        $slugs = explode(',', $slugs);
        
        // We want the dashboard output linked
        add_filter('plugin-stats_img-link', array(&$this, 'add_link'), 10, 2);

        foreach( $slugs as $slug ) {
            echo $this->getImageLink($slug, '360x100');
        }
    }
    
    /**
     * Add widget to the current widgets.
     */
    function add_widget( $widgets ) {
		global $wp_registered_widgets;
		array_splice( $widgets, 0, 0, 'plugin-stats' );
		return $widgets;
	}
	
    /**
     * Mark the plugin as safe for http://wordpress.org/extend/plugins/dashboard-widget-manager/
     */
    function add_safe_widget($safe) {
        $safe[]='plugin-stats';
        return $safe;
    }
    
    /**
     * Makes a link to the stats page on WP.org out of the provided image link.
     */
	function add_link($imgLink, $slugname) {
	    return "<a href='http://wordpress.org/extend/plugins/$slugname/stats/'>$imgLink</a>";
	}
	
	/**
	 * Builds an image link out of the stats that have already been retrieved
	 */
    function getImageLink($slugname, $imgSize) {
        global $wp_locale;

        $new_stats = apply_filters('plugin-stats_build-link', $this->fetchStats($slugname));
        
        // Put out the processed data
        $all_stats = get_option('plugin-stats');
        $plugin = $all_stats[$slugname];
        $processed = $plugin['processed'];

        if( !$new_stats && !empty($plugin["link_$imgSize"])) {
            $chart = $plugin["link_$imgSize"];
        } else {
            // Trim down to what we need
            $size = min(count($processed['dates']), apply_filters('plugin-stats_num-days', 180));
            $dates = array_slice($processed['dates'], -$size);
            $downloads = array_slice($processed['downloads'], -$size);
        
            // Figure out where the month labels should go based on the first day of the month
            $monthBegins = array();
            $labels = array();
            for ($i=0; $i < $size; $i++) { 
                $day = $dates[$i];
                if( preg_match('/\d{4}-\d{2}-01/', $day)) {
                    $monthBegins []= (float)$i*100/$size;
                    $pieces = explode('-', $day);
                    $labels []= $wp_locale->get_month($pieces[1]);
                }
            }
            // If no months have begun in the interval, stick the current month name in the middle
            if( empty($labels) ) {
                $labels[]= $wp_locale->get_month(date('m'));   
                $monthBegins[]= 50;
            }
        
            // Build all of the parameters for the Google Charts API
            $data = $this->simpleEncode($downloads, $processed['max'], $processed['min']);
            $chartArgs = array( 'chxr'=>"1,{$processed['min']},{$processed['max']}",
                'chxp'=>'0,'.implode(',',$monthBegins), 'chxt'=>'x,y',
                'chxl'=>'0:|'.implode('|', $labels), 'chs'=>$imgSize,
                'cht'=>'lc', 'chd'=>$data, 'chtt'=>$slugname );
         
             if( $processed['max']!=$processed['min'] ) {
                 $current_ratio = (float)((end($downloads)-$processed['min'])/($processed['max']-$processed['min']));
                 $chartArgs['chm'] = "h,FF0000,0,$current_ratio,0.5";

                 $avgDev = $this->averageDeviation($downloads);
                 $begin = max(0,($avgDev[0]-$avgDev[1]-$processed['min'])/($processed['max']-$processed['min']));
                 $end = min(1,($avgDev[0]+$avgDev[1]-$processed['min'])/($processed['max']-$processed['min']));
                 $chartArgs['chm'] .= "|r,99FF99,0,$begin,$end";
             }

            // Build the image link, pass it through some filters, and return it
            $chartArgs = apply_filters('plugin-stats_img-link-args', $chartArgs, $slugname);
            $chart = add_query_arg($chartArgs, 'http://chart.apis.google.com/chart?');
        
            $plugin["link_$imgSize"] = $chart;
            $all_stats[$slugname] = $plugin;
            update_option('plugin-stats', $all_stats);
        }
        return apply_filters('plugin-stats_img-link', "<img src='$chart' />", $slugname);
    }
    
    /**
     * Fetches and processes the stats for $slugname
     */
    function fetchStats($slugname) {
        $allStats = get_option('plugin-stats');
        
        $cacheTime = apply_filters('plugin-stats_cache-time', 600); // 10 Minute cache
        if( !isset($allStats[$slugname]) || time() - $allStats[$slugname]['retrieved'] > $cacheTime ) { 
            
            $oldStats = $allStats[$slugname];
            if( empty($oldStats) ) $oldStats = array();
            
            // 1. Get the stats
            $stats = $this->downloadStats($slugname);
            if( !$stats ) return false;
            
            // 2. Parse the stats
            $parser = new PluginStatsParser();
            $processed = $parser->parse($stats);

            // 3. Process the stats (store a max of 180 days)
            $processed['dates'] = array_slice($processed['dates'], -180);
            $processed['downloads'] = array_slice($processed['downloads'], -180);
            $processed['min'] = min($processed['downloads']);
            $processed['max'] = max($processed['downloads']);
            
            // 4. Store the stats
            $newStats = array('retrieved'=>time(), 'raw'=>$stats, 'processed'=>$processed);
            $allStats[$slugname] = array_merge($oldStats, $newStats);
            update_option('plugin-stats', $allStats);
            return true;
        }
        return false;
    }
    
    /**
     * Downloads and returns the stats for $slugname from WP.org
     */
    function downloadStats($slugname) {
        require_once(ABSPATH.WPINC.'/class-snoopy.php');
        if( is_null($this->snoop) ) $this->snoop = new Snoopy;
        $this->snoop->fetch("http://wordpress.org/extend/stats/plugin-xml.php?slug=$slugname");
        if( $this->snoop->status == '200' ) {
            return $this->snoop->results;
        }
        return false;
    }

    /**
     * Encodes an array of values using Google Charts API Simple Encoding
     * From: http://groups.google.com/group/google-chart-api/browse_thread/thread/7552496ccef00d96
     */
    function simpleEncode($values, $max = 61, $min = 0){
        $simple_table = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $chardata = 's:';
        $delta = $max - $min;
        $size = strlen($simple_table)-1;

        // prevent a divide-by-zero if $max==$min
        if( $delta == 0 ) return $chardata.str_repeat('A', count($values));
        foreach($values as $k => $v){
                if($v >= $min && $v <= $max){
                        $chardata .= $simple_table[round($size * ($v - $min) / $delta)];
                }else{
                        $chardata .= '_';
                }
        }
        return $chardata;
    }
    
    /**
     * Calculates the average of all the elements in the array.
     */
    function average($array){
        if( empty($array) ) return 0;
        $sum   = array_sum($array);
        $count = count($array);
        return $sum/$count;
    }

    /**
     * Calculates the average and standard deviation of all the elements in the array.
     */
    function averageDeviation($array){
        $avg = $this->average($array);
        foreach ($array as $value) {
            $variance[] = pow($value-$avg, 2);
        }
        $deviation = sqrt($this->average($variance));
        return array($avg, $deviation);
    }
}

/**
 * Parser for WordPress.org plugin stats
 */
class PluginStatsParser {
    var $parserState;
    var $parsed;

    function parse($xml) {
        $parser = xml_parser_create();
        xml_set_object($parser, $this);
        xml_set_element_handler($parser, "tag_open", "tag_close");
        xml_set_character_data_handler($parser, "cdata");

        $this->parserState = 0;
        $this->parsed = array('dates'=>array(), 'downloads'=>array());
        xml_parse($parser, trim($xml), true);
        xml_parser_free($parser);

        return $this->parsed;
    }
 
    /**
     * Tag started, modify parser state
     */
    function tag_open($parser, $tag, $attributes) {
        if( $tag == 'ROW' ) {
            $this->parserState+=3;
        } else if( $tag == 'STRING' ) {
            $this->parserState+=1;
        } else if( $tag == 'NUMBER' ) {
            $this->parserState+=2;
        }
    }
    
    /**
     * Got some data, save it if we're in the right state
     */
    function cdata($parser, $cdata) {
        if( $this->parserState == 4 ) {
            $this->parsed['dates'] []= $cdata;
        } else if( $this->parserState == 8) {
            $this->parsed['downloads'] []= $cdata;
        }
    }

    /**
     * Tag ended, modify parser state
     */
    function tag_close($parser, $tag) {
        if( $tag == 'STRING' ) {
            $this->parserState-=1;
        } else if( $tag == 'NUMBER' ) {
            $this->parserState-=2;
        }
    }
    
}

// Let's get this show on the road!
new PluginStats();
?>