<?php
/*
Plugin Name: Leika changing subscriptions
Description: Leika изменение подписок
Version:     1.0
Author:      N.Pikhtin
License:     GPL2
License URI: //www.gnu.org/licenses/gpl-2.0.html
*/

if ( !defined('WPINC') ) die; // If this file is called directly, abort
//if ( !defined('LEYKA_VERSION') ) die; // Если лейка не установлена
//require_once(LEYKA_PLUGIN_DIR.'inc/leyka-functions.php');
//if (! class_exists('Leyka') ) { return; }

$lch = new Leyka_CH();
class Leyka_CH {
	var $page_title = "Leika CH";
	var $lpay_post_type_campaign = 'leyka_campaign';
	var $lpay_post_type_donate = 'leyka_donation';
	var $lpay_payment_type = 'rebill';
	var $lpay_rebilling_is_active = 0;
	var $lpay_campaing_id = 0;
	var $lpay_is_finished = 1;
	var $error = 0;
	var $check_leyka = 1;

	function __construct() {
		$this->error_type = '';
		add_action('init', array(&$this, 'init') );
	}

	function init() {
		if ( $this->check_leyka && !class_exists('Leyka') ) {
			$this->Notice(); $this->error = 2;
		}
		if ( is_user_logged_in() ) {
//			$this->campaign = new Leyka_Campaign($campaign);
//		$this->lpay_admin_menu_setup();
//		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
		        add_action('admin_enqueue_scripts', array(&$this, 'load_enqueue_script'));
		}

	}

	function load_enqueue_script() {
		wp_enqueue_script($this->page_title,  plugins_url('script.js', __FILE__), array( 'jquery' ) );
		wp_enqueue_style($this->page_title.'-css',  plugins_url( 'style.css', __FILE__ ) );
	}

/********************************************************
	Add sub menu
/********************************************************/
	function admin_menu() {
/*
		add_submenu_page(
		    'leyka_donors_activ',
		    "Donor's activ",
		    "Donor's activ",
		    'leyka_manage_options',
		    'leyka_donor_info',
		    [$this, 'lpay_form']
		);
*/
		add_menu_page($this->page_title,
		 $this->page_title, 'manage_options', 'lpay_leyka', array(&$this, 'lpay_form'), plugin_dir_url( __FILE__ ) .'leyka_ico.png', 80);
		//add_options_page('Zakladka', 'Zakladka', 8, 'lpay_form', 'lpay_form');

	}


/********************************************************
	Admin form
/********************************************************/
	function lpay_form() {
		if ($this->error ==2 ) { return; } // Not Leyka
	?>

	<div id="wpbody" class="wrap">
		<?php echo $this->error_type; ?>
		<h2>Сменить подписку в Leyka</h2>

		<?php $this->Get_data_post(); ?>

		<form method="post" id="ch_form">
		<input type="hidden" name="ch_act[lpay_send]" value="lpay_send" />

		<h3>Закрытые кампании с активными подписками</h3>
		<i>Выберите кампании, которые хотите перенаправить</i>
		<br />

	<?php
//$this->lpay_post_type $lpay_post_type_donate
		$list_post_campaing = $this->lpay_get_posts_list($this->lpay_post_type_campaign);
//echo "<pre>"; print_r($list_post_campaing); echo "</pre>";
//echo "<pre>"; print_r(get_post_types()); echo "</pre>";
		foreach ($list_post_campaing as $postt) {
			$camp_post_meta = get_post_meta($postt->ID, '', true);
//echo "<pre>"; print_r(get_post_meta($postt->ID, '')); echo "</pre>";
			$finished = get_post_meta($postt->ID, 'is_finished', true);
		echo "<div class='chb'>$finished<input type='checkbox' name='ch_act[ch_close][".$postt->ID."]' id='cl_".$postt->ID."' class='ch_close' value='".$postt->ID."' /> \n";
		echo "<label class='labb' for='cl_".$postt->ID."'{$postt->ID}'>{$postt->ID}. ".$postt->post_title." /".get_the_date('m-d-Y', $postt->ID)."/</label></div>\n";
		}
	?>

		<h3>Активные кампании</h3>
		<i>Выберите кампанию на которую хотете перенаправить подписки с закрытой</i>
		<br />

		<select name="ch_act[activ_campaning]" id="activ_campaning" required>
		<option value="">--== Выберите кампанию ==--
		<?php
		$list_activ_campaing = $this->activ_get_posts_list($this->lpay_post_type_campaign);
		foreach ($list_activ_campaing as $postt) {
			//$camp_post_meta = get_post_meta($postt->ID, '', true);
			//$pid = $camp_post_meta['leyka_campaign_id'][0];
			$pid = $postt->ID;
			echo "<option value=\"$pid\">$pid. {$postt->post_title} /".get_the_date('m-d-Y', $postt->ID)."/</option>\n";
}
		?>
		</select>
		<br /><br />
		<input type="submit" id="btnSubmit" name="" value="Перенаправить" />
	</form>
	<div id="report"></div>
	</div>

	<?php
	}
/********************************************************
	Get meta date
/********************************************************/
//Закрытые кампании с активными подписками
	function lpay_get_posts_list($post_types='post') {
		if ($this->error ==2 ) { echo "Error"; return; } // Not Leyka
		if (!post_type_exists($post_types) ){
			$this->error_type = '<div id="message" class="error fade">Не верный тип поста '.$post_types.'</div>';
			return;
		}
		global $wpdb;
	if ($post_types == 'leyka_campaign') {
		$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM $wpdb->posts
		 LEFT JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id )
		 WHERE ( ($wpdb->postmeta.meta_key = 'is_finished') AND ($wpdb->postmeta.meta_value = '1')
		 AND ($wpdb->posts.post_type = '$post_types') AND ($wpdb->posts.post_parent = '0')
		 AND ($wpdb->posts.post_status = 'publish') AND ($wpdb->posts.post_password = '')
		 )
		 GROUP BY $wpdb->posts.post_date ORDER BY $wpdb->posts.post_date DESC LIMIT 0, 7";
		$res = $wpdb->get_results($sql);
	}
	if ($post_types == 'leyka_donation') {
		$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM $wpdb->posts
		 LEFT JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id )
		 WHERE (
		 ($wpdb->postmeta.meta_key = '_rebilling_is_active' AND $wpdb->postmeta.meta_value = '1')
		 AND ($wpdb->posts.post_type = '$post_types' AND $wpdb->posts.post_parent = '0')
		 AND ( $wpdb->posts.post_password = '' AND $wpdb->posts.post_status != 'failed')
		 )
		 GROUP BY $wpdb->posts.ID ORDER BY $wpdb->posts.post_date DESC";
		$args = array(
			'post_type' => $post_types, 'posts_per_page' => 5, 'post_status' => 'publish', 'cache_results' => false, 'update_post_term_cache' => false, 
			'meta_query' => array(
			      array(
				'key' => 'leyka_campaing_id',
				'value' => '',
				'meta_value_num' => '0',
				'compare' => '>=',
			      )
			   )
		);
		$res = $wpdb->get_results($sql);
	}
		wp_reset_postdata();
		return($res);
	}
// Активные кампании
	function activ_get_posts_list($post_types='post') {
		if ($this->error ==2 ) { echo "Error"; return; } // Not Leyka
		if (!post_type_exists($post_types) ){
			$this->error_type = '<div id="message" class="error fade">Не верный тип поста '.$post_types.'</div>';
			return;
		}
		global $wpdb;
	if ($post_types == 'leyka_campaign') {
		$asql = "SELECT SQL_CALC_FOUND_ROWS * FROM $wpdb->posts
		 LEFT JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id )
		 WHERE ( ($wpdb->postmeta.meta_key = 'is_finished') AND ($wpdb->postmeta.meta_value = '0')
		 AND ($wpdb->posts.post_type = '$post_types') AND ($wpdb->posts.post_parent = '0')
		 AND ($wpdb->posts.post_status = 'publish') AND ($wpdb->posts.post_password = '')
		 )
		 GROUP BY $wpdb->posts.post_date ORDER BY $wpdb->posts.post_date DESC LIMIT 0, 700";
		$ares = $wpdb->get_results($asql);
	}
		wp_reset_postdata();
		return($ares);
	}

/********************************************************
	Get data form
/********************************************************/
	function Get_data_post() {
		if ($this->post_array = $_POST['ch_act']) {
			if ($this->post_array['lpay_send'] != 'lpay_send') { return; }
//	echo $this->lpay_campaing_id . "<br />\n";
//	print_r(get_post_meta($postt->ID, 'views', true));
/*
 [lpay_send] => send
    [ch_close] => Array
        (
            [820] => 820
        )

    [activ_campaning] => 820
*/
			if ($this->post_array['ch_close'][$this->post_array['activ_campaning']]) {
				echo '<div id="message" class="error fade">
				<p></p><p style="color: red;">Нельзя сменить для одинаковых постов.<br />Выберите разные активные кампании и закрытые.</p><p></p>
				</div>';
				$this->error = 1;
			}
			elseif (!$this->post_array['activ_campaning'] || !$this->post_array['ch_close']) {
				echo '<div id="message" class="error fade">
				<p></p><p style="color: red;">Ошибка!!! Не все необходимые поля выбраны</p><p></p>
				</div>';
				$this->error = 1;
			}
			else {
				echo '<div id="message" class="updated fade">
				<p></p><p style="color: green;">Подписки изменены.</p><p></p>
				</div>';

				$this->UpdateParam($this->post_array);
				$this->error = 0;
			}
		}
	}

/********************************************************
	Update Param
/********************************************************/
	function UpdateParam() {
echo "<pre>"; print_r($this->post_array); echo "</pre>";
if ($this->post_array != '') {
	foreach ($this->post_array['ch_close'] as $close) {
echo "Переносим с кампании $close на ".$this->post_array['activ_campaning']."<br />\n";
	}
//	$rt = print_r($this->post_array, true);
//	file_put_contents(dirname(__FILE__).'/_rt.txt', $rt."\n=== Array ".date('d-m-Y H:i')." ===\n", FILE_APPEND);
}
//if ( is_numeric($this->post_array['activ_campaning']) ) {}
//if ( is_numeric($this->post_array['ch_close']) ) {}
//wp_unslash leyka_campaing_id
		//update_post_meta( 76, 'my_key', 'Steve' );
	}


/********************************************************
Вывод предложения установить лейку
/*********************************************************/
	function Notice() {
		 add_action('admin_notices', function(){
			echo '<div class="notice update-nag">Плагин LEYKA не установлен.
			 <a href="'.home_url("/wp-admin/plugin-install.php?tab=plugin-information&plugin=leyka&TB_iframe=true&width=600&height=550").'">Пройдите для установки плагина.</a></div>';
		 });
		return;
	}


} // End class Leyka_CH

?>
