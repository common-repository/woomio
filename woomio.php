<?php
/*
Plugin Name: Woomio for Bloggers
Plugin URI: https://www.woomio.com/en/
Description: This plugin eases the use of Woomio for WordPress users. With Woomio, anyone can post their purchases - and get a revenue from it.
Author: Woomio.com
Author URI: https://woomio.com
Version: 1.1.0
*/

if (!defined('ABSPATH') || !function_exists('is_admin')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}



if(!class_exists("Woomio_Blogger")) {

	class Woomio_Blogger {
		const WOOMIO_VERSION = '1.1.0';
		//const WOOMIO_URL = 'http://test.woomio.com';
		const WOOMIO_URL = 'https://api.woomio.com';

		function __construct() {
            if (is_admin()) {
                if (!class_exists("WoomioBloggerSettingsPage")) {
                    require_once plugin_dir_path(__FILE__) . 'woomio-settings.php';
                }
                $this->settings = new WoomioBloggerSettingsPage();
            }
            else {
            	add_action('wp_head', array($this, 'woomio_statis_js'));

				/*if($this->woomio_convertlink_check()) {
					add_action('wp_head', array($this, 'woomio_convert_link_js'));
				}*/
            }
			add_action('init',array($this, 'getData'));
        }

        function woomio_convertlink_check() {
			$data = get_option("woomio_blogger_option_name");
			if(isset($data["woomio_convertlink_checkbox"])) {
				return $data["woomio_convertlink_checkbox"]=="on" ? true : false;
			}
			else {
				return false;
			}
		}

        function woomio_statis_js() {
			$data = get_option("woomio_blogger_option_name");
  			echo '<script src="' . self::WOOMIO_URL . '/assets/js/analytics/co.js" id="wac" data-u="' . $data["woomio_blogger_id"] . '" data-v="' . self::WOOMIO_VERSION . '"></script>';
		}

		/*function woomio_convert_link_js() {
 			$data = get_option("woomio_blogger_option_name");
			echo '<script src="' . self::WOOMIO_URL . '/assets/js/tools/lnk.js" id="wlnk" data-u="' . $data["woomio_blogger_id"] . '" data-v="' . self::WOOMIO_VERSION . '"></script>';
		}*/

		function getData()
		{
			if(isset($_GET['woomio']) && $_GET['woomio'] === 'pc')
			{
				if(isset($_GET['posturl']))
				{
					$this -> woomio_get_comments();
				}
				else if(isset($_GET['blogurl']))
				{
					$this -> woomio_get_total_posts_comments();
				}
			}
		}

		function woomio_get_comments()
		{
			$url = $_GET['posturl'];
			if(parse_url($url,PHP_URL_HOST) === NULL)
			{
			  return;
			}
			if(parse_url($url,PHP_URL_HOST) === $_SERVER['SERVER_NAME'] )
			{
				global $wpdb;
				$response = new stdClass();
				$postId = url_to_postid($url);
				$table_posts = $wpdb->prefix."posts";
				$sql ="SELECT * FROM ".$table_posts." WHERE ID=%d";
				$query = $wpdb->prepare($sql,$postId);
				$post = $wpdb->get_row($query);
				$comment_count = $post->comment_count;

				$response -> count = $comment_count;
				echo json_encode($response);
				die;
			}
		}

		function woomio_get_total_posts_comments()
		{
			$url = $_GET['blogurl'];
			if(parse_url($url,PHP_URL_HOST) === NULL)
			{
			  return;
			}
			if(parse_url($url,PHP_URL_HOST) === $_SERVER['SERVER_NAME'] )
			{
				global $wpdb;
				$response = new stdClass();
				$table_posts = $wpdb->prefix."posts";

				$sql ="SELECT COUNT(*) AS totalposts, SUM(comment_count) AS totalcomments FROM ".$table_posts." WHERE post_status = %s";
				$query = $wpdb->prepare($sql,'publish');
				$result = $wpdb->get_row($query);

				$totalposts = $result ->totalposts;
				$totalcomments = $result->totalcomments;

				$response -> posts = $totalposts;
				$response -> comments = $totalcomments;
				echo json_encode($response);
				die;
			}

		}
	}

}

global $woomioblogger;
if(!$woomioblogger) {
	$woomioblogger = new Woomio_Blogger();
}

?>
