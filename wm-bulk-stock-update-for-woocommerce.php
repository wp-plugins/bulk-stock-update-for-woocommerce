<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if(!class_exists("WM_WooCommerce_Update_Stock_Lite")){
	class WM_WooCommerce_Update_Stock_Lite{
		
		var $constants	= array();
		
		function __construct($file){
			$this->constants['plugin_file_path']= $file;
			
			$this->constants['plugin_key'] 		= "wmwcuslite";
			
			$this->constants['plugin_name'] 	= "WooCommerce Update Stock Lite";
			
			$this->constants['plugin_role'] 	= "manage_woocommerce";
			
			$this->constants['plugin_page'] 	= $this->constants['plugin_key']."_bulk_stock_update";
			
			$this->constants['plugin_file']		= plugin_basename($this->constants['plugin_file_path']);
			
			add_action('admin_init', 			array( &$this, 'admin_init'),51);
			
			add_action('admin_menu', 			array( &$this, 'admin_menu'),51);
			
			add_action('plugins_loaded', 		array($this, 'plugins_loaded'),51);
			
			add_filter( 'plugin_action_links_'.$this->constants['plugin_file'], array( $this, 'plugin_action_links' ), 51, 2 );
		}
		
		function admin_init(){
			
			$page	= $this->get_request('page',NULL,false);
			
			if($page != $this->constants['plugin_page']) return;
			
			$this->constants['plugin_action'] 	= $this->constants['plugin_key']."_wp_ajax_action";
			
			$this->constants['default_tab'] 	= "simple_product";
			
			add_action('admin_init', 			array(&$this, 'save_changes'),51);
			
			add_action('admin_footer',  		array(&$this, 'admin_footer'),51);
			
			add_action('admin_enqueue_scripts', array($this, 'wp_localize_script'),51);
			
			add_action('wp_ajax_'.$this->constants['plugin_action'], array($this, 'wp_ajax_action'),51);
		}
		
		function admin_menu(){
			add_submenu_page('edit.php?post_type=product',__( 'Bulk Stock Update', 'wmwcuslite_textdomains'),__( 'Bulk Stock Update','wmwcuslite_textdomains' ),$this->constants['plugin_role'],$this->constants['plugin_page'],array( $this, 'add_page' ));
		}
		
		function plugins_loaded() {
			
			$this->constants['plugin_folder']			= isset($this->constants['plugin_folder']) ? $this->constants['plugin_folder'] : dirname($this->constants['plugin_file']);
			
			$this->create_directory('languages',WP_PLUGIN_DIR.'/'.$this->constants['plugin_folder']);
			
			load_plugin_textdomain('wmwcuslite_textdomains', WP_PLUGIN_DIR.'/'.$this->constants['plugin_folder'].'/languages',$this->constants['plugin_folder'].'/languages');
		}
		
		function plugin_action_links($plugin_links = array(), $file = ""){
			if ( ! current_user_can( $this->constants['plugin_role'])) return;
			if ( $file == $this->constants['plugin_file']) {
				$plugin_links[] = '<a href="'.admin_url('edit.php?post_type=product&page='.$this->constants['plugin_page']).'" title="'.__('Update Stock Lite','wmwcuslite_textdomains').'">'.__('Update Stock','wmwcuslite_textdomains').'</a>';
			}		
			return $plugin_links;
		}
			
		function wp_localize_script($hook) {
			
			$this->constants['plugin_url'] 	= isset($this->constants['plugin_url']) ? $this->constants['plugin_url'] : plugins_url("", $this->constants['plugin_file_path']);
			
			wp_enqueue_script( $this->constants['plugin_key'].'_ajax-script', $this->constants['plugin_url'].'/assets/js/scripts.js', true);
			
			wp_localize_script(
				$this->constants['plugin_key'].'_ajax-script', 
				'wm_ajax_object', 
				array( 
					'ajaxurl' 			=> admin_url( 'admin-ajax.php' )
					,'wm_ajax_action' 	=> $this->constants['plugin_action']
					,'please_wait' 	=> __('Please Wait!',	'wmwcuslite_textdomains')
				)
			);
		}
		
		function admin_footer(){
			
			$this->constants['plugin_url'] 	= isset($this->constants['plugin_url']) ? $this->constants['plugin_url'] : plugins_url("", $this->constants['plugin_file_path']);
			
			wp_enqueue_style( $this->constants['plugin_key'].'style', $this->constants['plugin_url'].'/assets/css/style.css' );
		}
		
		function wp_ajax_action() {														
			$subaction	= $this->get_request('subaction');
			
			if($subaction){
				if($subaction == "update_product_stock"){
					$c		= $this->constants;
					$filter_page_name	= $this->get_request('filter_page_name','simple_product',true);
					$this->save_changes();
					$output = $this->get_grid($filter_page_name);
					echo $output;
					die();exit;
				}
				
				if($subaction == "search_product_stock_list"){
					$c		= $this->constants;
					$filter_page_name	= $this->get_request('filter_page_name','simple_product',true);
					$output = $this->get_grid($filter_page_name);
					echo $output;
					die();exit;
				}
			}
			
			die();exit; // this is required to return a proper result
			
		}
		
		function add_page(){
			
			$output =  "";
			$page_titles = array(
				'simple_product'			=> __('Simple Products',	'wmwcuslite_textdomains')	
				,'variable_product'			=> __('Variable Products',	'wmwcuslite_textdomains')	
				,'variation_product'		=> __('Variation Products',	'wmwcuslite_textdomains')
			);
			
			$fields 			= $this->get_form_request();
			
			$filter_page_name	= $fields['filter_page_name'];
			$page				= $fields['page'];
			
			$page_title 		= isset($page_titles[$filter_page_name]) ? $page_titles[$filter_page_name] : $filter_page_name;				
			$page_title 		= apply_filters($page.'_page_name_'.$filter_page_name, $page_title);
			
			
			
			$label_yes			= __('Yes',				'wmwcuslite_textdomains');
			$label_no			= __('No',				'wmwcuslite_textdomains');
			$label_do_not_allow	= __('Do not allow',	'wmwcuslite_textdomains');
			$label_allow_but_notify	= __('Allow, but notify customer',	'wmwcuslite_textdomains');
			$label_allow		= __('Allow',			'wmwcuslite_textdomains');
			$label_all			= __('All',				'wmwcuslite_textdomains');
			$label_out_of_stock	= __('Out of Stock',	'wmwcuslite_textdomains');
			$label_in_stock		= __('Instock',			'wmwcuslite_textdomains');
			$output 			= "";
			
			?>
			<div class="wrap">
				<h2 class="hide_for_print"><?php echo $page_title;?> </h2>
		   
			
				<div class="wm_tab_nav">
					<h2 class="wm_nav_tab_wrapper nav-tab-wrapper woo-nav-tab-wrapper hide_for_print">
					<div class="responsive-menu"><a href="#" id="menu-icon"></a></div>
					
					<?php
						$admin_url = admin_url("edit.php?post_type=product&page={$page}");      	
						foreach ( $page_titles as $key => $value ) {
							echo '<a href="'.$admin_url.'&filter_page_name=' . urlencode( $key ).'" class="nav-tab ';
							if ( $filter_page_name == $key ) echo 'nav-tab-active';
							echo '">' . esc_html( $value ) . '</a>';
						}
						
						//echo '<a href="'.$admin_url.'&filter_page_name=update_stock" class="nav-tab" id="tab_btn_update_stock">'.__('Update Stock',	'wmwcuslite_textdomains').'</a>';
					?></h2>
                    <form name="frm_search_results" id="frm_search_results" method="post" action="<?php echo $admin_url;?>">
                    <?php
						$output .=  "<input type=\"hidden\" class=\"input-hidden\" name=\"action\" 						value=\"{$this->constants['plugin_action']}\" />";
						$output .=  "<input type=\"hidden\" class=\"input-hidden\" name=\"search_product_stock_list\" 	value=\"1\" />";
						$output .=  "<input type=\"hidden\" class=\"input-hidden\" name=\"subaction\" 					value=\"search_product_stock_list\" />";
						$output .=  "<input type=\"hidden\" class=\"input-hidden\" name=\"page\" 						value=\"{$fields['page']}\" />";
						$output .=  "<input type=\"hidden\" class=\"input-hidden\" name=\"filter_page_name\" 			value=\"{$fields['filter_page_name']}\" />";
						$output .=  "<input type=\"hidden\" class=\"input-hidden\" name=\"post_type\" 					value=\"{$fields['post_type']}\" />";
						echo $output;
					?>
                    </form>
				</div>                    
				<div class="searched_content"><?php //echo $this->get_grid($filter_page_name);?></div>
			</div>
			<?php
		}			
		
		
		function get_items($filter_page_name){
			
			$items = array();
			switch($filter_page_name){
				case "simple_product":
					$items = $this->get_products($filter_page_name,'product');
					break;
				case "variable_product":
					$items = $this->get_products($filter_page_name,'product');
					break;
				case "variation_product":
					$items = $this->get_products($filter_page_name,'product_variation');
					break;
				default:
					break;					 
			}
			return $items;
		}
		
		function get_columns($filter_page_name = "simple_product"){
			
			$columns = array('ID' 						=> 'ID');
			
			if($filter_page_name == "simple_product"){
				$columns = array(
					'post_title' 				=> __('Product Name',		'wmwcuslite_textdomains'),	
					'update_manage_stock'		=> __('Manage Stock',		'wmwcuslite_textdomains'),
					'update_stock' 				=> __('Stock',				'wmwcuslite_textdomains'),
					'manage_backorders' 		=> __('Backorders',			'wmwcuslite_textdomains'),
					'manage_status' 			=> __('Stock Status',		'wmwcuslite_textdomains'),					
					'manage_sold_individually' 	=> __('Sold Individually',	'wmwcuslite_textdomains'),					
					'edit_product' 				=> __('Edit',				'wmwcuslite_textdomains')
				);
			}elseif($filter_page_name == "variable_product"){
				$columns = array(
					'post_title' 				=> __('Product Name',		'wmwcuslite_textdomains'),					
					'update_manage_stock'		=> __('Manage Stock',		'wmwcuslite_textdomains'),
					'update_stock' 				=> __('Stock',				'wmwcuslite_textdomains'),
					'manage_backorders' 		=> __('Backorders',			'wmwcuslite_textdomains'),
					'manage_sold_individually' 	=> __('Sold Individually',	'wmwcuslite_textdomains'),						
					'edit_product' 				=> __('Edit',				'wmwcuslite_textdomains'),
					'post_id' 					=> __('ID',					'wmwcuslite_textdomains')
				);
			}elseif($filter_page_name == "variation_product"){
				$columns = array(
					'parent_name' 				=> __('Parent Name',		'wmwcuslite_textdomains'),
					'update_manage_stock'		=> __('Manage Stock',		'wmwcuslite_textdomains'),
					'update_stock' 				=> __('Stock',				'wmwcuslite_textdomains'),
					'manage_backorders' 		=> __('Backorders',			'wmwcuslite_textdomains'),
					'manage_status' 			=> __('Stock Status',		'wmwcuslite_textdomains'),
					'variation_enabled' 		=> __('Enabled',			'wmwcuslite_textdomains'),
					'menu_order'				=> __('Variation Order',	'wmwcuslite_textdomains'),
					'parent_edit' 				=> __('Edit',				'wmwcuslite_textdomains')
				);
			}
			
			if(!isset($columns['post_id'])){$columns['hide_post_id']=__('ID','wmwcuslite_textdomains');}
			
			return $columns;
		}
		
		function get_grid($filter_page_name = ''){
			$items		= $this->get_items($filter_page_name);
			$output = "";
			if(count($items)>0){
						
						
						$product_url					=  admin_url('post.php?action=edit');
						$columns						=  $this->get_columns($filter_page_name);
						$menu_order_list				=  array();
						$menu_order						= 0;
						$label_yes						= __('Yes',				'wmwcuslite_textdomains');
						$label_no						= __('No',				'wmwcuslite_textdomains');
						$label_do_not_allow				= __('Do not allow',	'wmwcuslite_textdomains');
						$label_allow_but_notify			= __('Allow, but notify customer',				'wmwcuslite_textdomains');
						$label_allow					= __('Allow',			'wmwcuslite_textdomains');
						$label_all						= __('All',				'wmwcuslite_textdomains');
						$label_out_of_stock				= __('Out of Stock',	'wmwcuslite_textdomains');
						$label_in_stock					= __('Instock',			'wmwcuslite_textdomains');
						$label_edit						= __('Edit',			'wmwcuslite_textdomains');
						$label_btn_update				= __('Update',			'wmwcuslite_textdomains');
						//$this->print_r($items);
						
						$tabindex = 1;
						$output .=  '<form method="post" action="" id="update_stocks_form">';
						
							$output .=  "<div class=\"submit_btn_box\">";
							$output .=  "<input type=\"submit\" class=\"button-primary onformprocess\" name=\"update_stocks\" value=\"{$label_btn_update}\" tabindex=\"{$tabindex}\" />";
							$output .=  "</div>";
							$tabindex++;
							
							$output .=  "<div class=\"grid_box\">";
							$output .=  '<table style="width:100%" class="widefat" cellpadding="0" cellspacing="0">';
							$output .=  "<thead>";
							$output .=  "<tr>";
							foreach($columns as $ckey => $citem):
								$td_value = $citem;
								$td_class = $ckey;
								switch($ckey){
									case 'post_title';
										break;
									case 'hide_post_id';
										$td_class .= ' hide_column';
										break;
									default:
										//$td_value = $citem;
										break;
								}
								
								$output .= "<th class=\"{$td_class}\">{$td_value}</th>";
							endforeach;
							
							
							foreach($items as $key => $item):
								$post_meta = get_post_meta($item->ID);								
								foreach($post_meta as $mkey => $mvalue):								
									$items[$key]->$mkey = isset($mvalue[0]) ? $mvalue[0] : '';
								endforeach;																		
							endforeach;
							
							//$this->print_r($items);
							
							$output .= "<tr>";
							$output .=  "</thead>";
							$output .=  "<tbody>";
							foreach($items as $key => $item):
								
								$output .=  "<tr>";
									$td_value = "";
									$_id 	= isset($item->ID) ? $item->ID : 0;
									foreach($columns as $ckey => $citem):
										$td_value = "";
										$td_class = $ckey;
										switch($ckey){
											case 'hide_post_id';
												$td_value .= $_id;
												$td_class .= ' hide_column';
												$td_value .= "\n<input type=\"hidden\" name=\"post_id[{$_id}]\" id=\"post_id_{$_id}\" value=\"{$_id}\" />";
												break;
											case 'post_id';
												$td_value .= $_id;
												$td_value .= "\n<input type=\"hidden\" name=\"post_id[{$_id}]\" id=\"post_id_{$_id}\" value=\"{$_id}\" />";
												break;
											case 'post_title';
												$post_title = isset($item->$ckey) ? $item->$ckey : '';
												$post_link = get_permalink($item->ID);
												$td_value = "<a href=\"{$post_link}\" target=\"_blank\">{$post_title}</a>";
												break;
											
											case '_stock_status';
												$td_value = isset($item->_stock_status) ? $item->_stock_status : '';
												$td_value = $td_value == "instock" ? "<strong style=\"color:green\">Instock</strong>" : "<strong style=\"color:red\">Out of Stock</strong>"; 
												break;
											case '_manage_stock';
												$manage_stock = isset($item->_manage_stock) ? $item->_manage_stock : 'no';
												$td_value = $manage_stock == "yes" ? $label_yes : $label_no;													
												break;
												
											case '_sold_individually';
												$td_value = isset($item->_sold_individually) ? $item->_sold_individually : 'no';
												$td_value = $td_value == "yes" ? $label_yes : $label_no; 
												break;
												
											case 'update_manage_stock';
												$value 	= isset($item->_manage_stock) ? $item->_manage_stock : 'no';
												$data = array("no"=>$label_no,"yes"=>$label_yes);													
												$td_value = $this->create_dropdown($data,"{$ckey}[{$_id}]","{$ckey}_{$_id}",'',"form_select {$ckey} {$ckey}_{$_id}",$value,'array',$tabindex);													
												$td_value .= "<input type=\"hidden\" name=\"hidden_{$ckey}[{$_id}]\" id=\"hidden_{$ckey}_{$_id}\" value=\"{$value}\" />";
												$tabindex++;
												break;
												
											case 'update_stock';
												$manage_stock 	= isset($item->_manage_stock) ? $item->_manage_stock : 'no';
												$value 			= isset($item->_stock) ? $item->_stock : '';													
												$hide_fild 		= " hide_fields";
												$disabled 		= ' disabled="disabled"';
												if($manage_stock == "yes"){
													$hide_fild 		= "";
													$disabled 		= "";
												}													
												$td_value = "<input type=\"number\" name=\"{$ckey}[{$_id}]\" id=\"{$ckey}_{$_id}\" class=\"form_input {$ckey} {$ckey}_{$_id} update_manage_stock_{$_id}_update_stock {$hide_fild}\"{$disabled} value=\"{$value}\" tabindex=\"{$tabindex}\" size=\"7\" maxlength=\"7\" step=\"any\" max=\"1000000000\" min=\"0\" data-id=\"{$_id}\"  />";
												$td_value .= "<input type=\"hidden\" name=\"hidden_{$ckey}[{$_id}]\" id=\"hidden_{$ckey}_{$_id}\" value=\"{$value}\" />";
												$tabindex++;
												break;
											
											
											case 'manage_backorders';
												$value 	= isset($item->_backorders) ? $item->_backorders : 'no';
												$manage_stock 	= isset($item->_manage_stock) ? $item->_manage_stock : 'no';
												
												
												
												$hide_fild 		= " hide_fields";
												$disabled 		= ' disabled="disabled"';
												if($manage_stock == "yes"){
													$hide_fild 		= "";
													$disabled 		= "";
												}
												
												$data = array("no"=>$label_do_not_allow,"notify"=>$label_allow_but_notify,"yes"=>$label_allow);
												$td_value = $this->create_dropdown($data,"{$ckey}[{$_id}]","{$ckey}_{$_id}",'',"form_select {$ckey}  update_manage_stock_{$_id}_update_stock {$hide_fild} {$ckey}_{$_id}",$value,'array',$tabindex,false,0,$disabled);													
												$td_value .= "<input type=\"hidden\" name=\"hidden_{$ckey}[{$_id}]\" id=\"hidden_{$ckey}_{$_id}\" value=\"{$value}\" />";
												$tabindex++;
												break;
											
											case 'manage_status';
												$value 	= isset($item->_stock_status) ? $item->_stock_status : 'outofstock';
												$data = array("outofstock"=>$label_out_of_stock, "instock"=>$label_in_stock);
												$td_value = $this->create_dropdown($data,"{$ckey}[{$_id}]","{$ckey}_{$_id}",'',"form_select {$ckey} update_manage_stock_{$_id}_update_stock {$ckey}_{$_id} $value",$value,'array',$tabindex);													
												$td_value .= "<input type=\"hidden\" name=\"hidden_{$ckey}[{$_id}]\" id=\"hidden_{$ckey}_{$_id}\" value=\"{$value}\" />";
												$tabindex++;
												break;
												
											
												
											case 'manage_sold_individually';
												$value 	= isset($item->_sold_individually) ? (strlen($item->_sold_individually>=0) ? $item->_sold_individually: "no") : 'no';
												$data = array("no"=>$label_no,"yes"=>$label_yes);
												$td_value = $this->create_dropdown($data,"{$ckey}[{$_id}]","{$ckey}_{$_id}",'',"form_select {$ckey} {$ckey}_{$_id}",$value,'array',$tabindex);													
												$td_value .= "<input type=\"hidden\" name=\"hidden_{$ckey}[{$_id}]\" id=\"hidden_{$ckey}_{$_id}\" value=\"{$value}\" />";
												$tabindex++;
												break;
											
											
											
											case 'parent_name';
												$post_title = isset($item->$ckey) ? $item->$ckey : '';
												$post_link = get_permalink($item->post_parent);
												$td_value = "<a href=\"{$post_link}\" target=\"_blank\">{$post_title}</a>";
												break;
												
											case 'product_status';
												$value 		= isset($item->post_status) ? $item->post_status : 'draft';
												$data 		= array("draft"=>"Draft","publish"=>"Publish");
												
												$td_value .= $this->create_dropdown($data,"{$ckey}[{$_id}]","{$ckey}_{$_id}",'',"form_select {$ckey} {$ckey}_{$_id}",$value,'array',$tabindex);													
												$td_value .= "<input type=\"text\" name=\"hidden_{$ckey}[{$_id}]\" id=\"hidden_{$ckey}_{$_id}\" value=\"{$value}\" />";
												$tabindex++;
												break;
												
											case 'variation_status';
												$value 		 = isset($item->post_status) ? $item->post_status : 'private';
												$post_parent = isset($item->post_parent) ? $item->post_parent : 0;
												$data 		= array("private"=>$label_no,"publish"=>$label_yes);													
												$td_value = $this->create_dropdown($data,"{$ckey}[{$_id}]","{$ckey}_{$_id}",'',"form_select {$ckey} {$ckey}_{$_id}",$value,'array',$tabindex);													
												$td_value .= "<input type=\"hidden\" name=\"hidden_{$ckey}[{$_id}]\" id=\"hidden_{$ckey}_{$_id}\" value=\"{$value}\" />";
												$td_value .= "<input type=\"hidden\" name=\"hidden_parent_{$ckey}[{$_id}]\" id=\"hidden_parent_{$ckey}_{$_id}\" value=\"{$post_parent}\" />";
												
												
												$tabindex++;
												break;
											case 'variation_enabled';
												$value 		= isset($item->post_status) ? $item->post_status : 'private';
												$td_value 	= ($item->post_status == "draft") ? $label_no : $label_yes;
												break;
											case 'menu_order';
												
												$menu_order = $menu_order + 1;
												$value =	$menu_order;											
												$td_value = "<input type=\"number\" name=\"{$ckey}[{$_id}]\" id=\"{$ckey}_{$_id}\" class=\"form_input {$ckey} {$ckey}_{$_id}\" value=\"{$value}\" tabindex=\"{$tabindex}\" size=\"7\" maxlength=\"7\" step=\"any\" max=\"10000000\" min=\"0\" />";
												$td_value .= "<input type=\"hidden\" name=\"hidden_{$ckey}[{$_id}]\" id=\"hidden_{$ckey}_{$_id}\" value=\"{$value}\" />";
												$tabindex++;
												break;
											
											case 'parent_edit';
												$post_title = isset($item->$ckey) ? $item->$ckey : '';
												$post_link =  $product_url."&post={$item->post_parent}";
												$td_value = "<a href=\"{$post_link}\" target=\"_blank\">{$label_edit}</a>";
												break;
											case 'edit_product';
												$post_title = isset($item->$ckey) ? $item->$ckey : '';
												$post_link =  $product_url."&post={$_id}";
												$td_value = "<a href=\"{$post_link}\" target=\"_blank\">{$label_edit}</a>";
												break;
												
											default:
												$td_value = $ckey;
												//$td_value = isset($item->$ckey) ? $item->$ckey : $ckey;
												break;
										}										
										$output .= "<td class=\"{$td_class}\">{$td_value}</td>";
									endforeach;
								$output .= "<tr>";
							endforeach;
							$output .=  "</tbody>";
							$output .=  "</table>";	
							$output .=  '<div class="grid_loading"></div>';							
							$output .=  "</div>";
							
							$output .=  "<div class=\"submit_btn_box\">";
							
							$output .=  "<input type=\"hidden\" class=\"input-hidden\" name=\"action\" 					value=\"{$this->constants['plugin_action']}\" />";
							$output .=  "<input type=\"hidden\" class=\"input-hidden\" name=\"update_product_stock\" 	value=\"1\" />";
							$output .=  "<input type=\"hidden\" class=\"input-hidden\" name=\"subaction\" 				value=\"update_product_stock\" />";
							
							$fields = $this->get_form_request();
							
							if(isset($fields['action'])) 							unset($fields['action']);								
							if(isset($fields['subaction'])) 						unset($fields['subaction']);
							if(isset($fields['update_stocks'])) 					unset($fields['update_stocks']);
							if(isset($fields['update_manage_stock'])) 				unset($fields['update_manage_stock']);
							if(isset($fields['hidden_update_manage_stock'])) 		unset($fields['hidden_update_manage_stock']);
							if(isset($fields['update_stock'])) 						unset($fields['update_stock']);
							if(isset($fields['hidden_update_stock'])) 				unset($fields['hidden_update_stock']);
							if(isset($fields['manage_status'])) 					unset($fields['manage_status']);
							if(isset($fields['hidden_manage_status']))				unset($fields['hidden_manage_status']);
							if(isset($fields['manage_backorders'])) 				unset($fields['manage_backorders']);
							if(isset($fields['hidden_manage_backorders'])) 			unset($fields['hidden_manage_backorders']);
							if(isset($fields['manage_sold_individually'])) 			unset($fields['manage_sold_individually']);
							if(isset($fields['hidden_manage_sold_individually'])) 	unset($fields['hidden_manage_sold_individually']);								
							if(isset($fields['menu_order'])) 						unset($fields['menu_order']);
							if(isset($fields['hidden_menu_order'])) 				unset($fields['hidden_menu_order']);
							if(isset($fields['hidden_menu_order'])) 				unset($fields['hidden_menu_order']);								
							if(isset($fields['variation_status'])) 					unset($fields['variation_status']);
							if(isset($fields['hidden_variation_status'])) 			unset($fields['hidden_variation_status']);
							if(isset($fields['hidden_parent_variation_status'])) 	unset($fields['hidden_parent_variation_status']);								
							if(isset($fields['product_status'])) 					unset($fields['product_status']);
							if(isset($fields['hidden_product_status'])) 			unset($fields['hidden_product_status']);
							if(isset($fields['post_id'])) 							unset($fields['post_id']);
							if(isset($fields['update_product_stock'])) 				unset($fields['update_product_stock']);
							if(isset($fields['search_product_stock_list'])) 		unset($fields['search_product_stock_list']);
							
							foreach($fields as $key => $value)$output .=  "<input type=\"hidden\" class=\"input-hidden\" name=\"{$key}\" value=\"{$value}\" />";
															
							$output .=  "<input type=\"submit\" class=\"button-primary onformprocess\" name=\"update_stocks\" value=\"{$label_btn_update}\" tabindex=\"{$tabindex}\" />";
							$output .=  "</div>";
						$output .=  '</form>';							
						$output;
					}
					
				return $output;
		}
		
		
		
		function get_products($filter_page_name, $post_type = 'product'){
			
			$fields = $this->get_form_request();extract($fields);
			
			if($filter_page_name == "variable_product"){
				$terms_slug = "variable";
				
			}else if ($filter_page_name == "variation_product"){
				$terms_slug = "";
				$post_type  = "product_variation";	
			}else if($filter_page_name == "simple_product"){
				$terms_slug = "simple";
			}else{
				$terms_slug = "simple";
			}
			
			global $wpdb;
			
			$sql = "SELECT posts.ID as ID, posts.post_title AS post_title, posts.post_status AS post_status, posts.menu_order AS menu_order, posts.post_parent  AS post_parent, posts.post_type AS post_type";
			
			if ($filter_page_name == "variation_product"){
				$sql .= ", parent_product.post_title AS parent_name, parent_product.post_status AS parent_post_status";
			}
			
			if($terms_slug){
				$sql .= ", terms.slug as product_type, term_relationships.term_taxonomy_id, term_taxonomy.term_id";
			}
			
			$sql .= " FROM {$wpdb->prefix}posts 							as posts ";
			
			if($terms_slug){
				$sql .= " 
				LEFT JOIN  {$wpdb->prefix}term_relationships 			as term_relationships 							ON term_relationships.object_id			=	posts.ID
				LEFT JOIN  {$wpdb->prefix}term_taxonomy 				as term_taxonomy 								ON term_taxonomy.term_taxonomy_id		=	term_relationships.term_taxonomy_id
				LEFT JOIN  {$wpdb->prefix}terms 						as terms 										ON terms.term_id						=	term_taxonomy.term_id";
			
			}
			if ($filter_page_name == "variation_product"){
				$sql .= " LEFT JOIN  {$wpdb->prefix}posts 					as parent_product 								ON parent_product.ID					=	posts.post_parent";
			}
			
			$sql .= " 
			WHERE 1*1";
			
			$sql .= "  AND posts.post_type = '{$post_type}'";
				
			if($terms_slug){
				
				$sql .= " AND term_taxonomy.taxonomy = 'product_type'";
				
				$sql .= "  AND terms.slug IN ('{$terms_slug}')";
			}
			
			if ($filter_page_name == "variation_product"){
				$sql .= " AND posts.post_parent > 0 ";
				$sql .= " AND parent_product.post_status IN ('publish','draft')";
			}elseif ($filter_page_name == "variable_product"){
				$sql .= " AND posts.post_status IN ('publish','draft')";
			}else{
				$sql .= " AND posts.post_status = 'publish'";
			}
			
			
			
			if ($filter_page_name == "variation_product"){
				
				//$sql .= " ORDER BY menu_order ASC, parent_product.post_title ASC";
				$sql .= " ORDER BY menu_order ASC";
				
			}else{
				$sql .= " 
				ORDER BY posts.post_title";
			}
			
			//echo $sql ;
			
			$items = $wpdb->get_results($sql);
			
			//$this->print_r($sql);
			//$this->print_r($items);
			
			if($wpdb->num_rows <= 0){
				return array();
			}
			
			foreach($items as $key => $item):
					$post_meta = get_post_meta($item->ID);								
					foreach($post_meta as $mkey => $mvalue):								
						$items[$key]->$mkey = isset($mvalue[0]) ? $mvalue[0] : '';
					endforeach;	
			endforeach;
						
			//$this->print_r($items);
						
			return $items;
		}
		
		function get_postmeta($post_id = 0,$meta_key = '_stock'){
			global $wpdb;				
			$sql = "SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key = '{$meta_key}'  AND  post_id = {$post_id} LIMIT 1";
			$results = $wpdb->get_row($sql);
			if($wpdb->num_rows <= 0){
				return array();
			}
			return $results;
		}
		
		function save_changes(){
			if(isset($_POST['update_product_stock'])){
				global $wpdb;					
				
				
				$this->update_product_post_meta('manage_sold_individually','_sold_individually','no');					
				$this->update_product_stock('update_stock');
				
								
				$changed_status = $this->update_post_status('variation_status','post_status','ID');
				$this->update_post_status2('product_status','post_status','ID');
				$this->update_post_status2('menu_order','menu_order','ID');					
				//$this->print_r($changed_status);
				
				if(count($changed_status) > 0){
					$changed_status = array_unique($changed_status);
					global $wpdb;
					$changed_status_string = implode(",", $changed_status);
					$parent_products = $wpdb->get_results("SELECT post_parent AS id FROM {$wpdb->prefix}posts WHERE post_parent IN({$changed_status_string}) AND post_status = 'publish'");						
					if(count($parent_products)>0){
						foreach($parent_products as $key => $product){
							if(isset($changed_status[$product->id])) unset($changed_status[$product->id]);
						}
					}
					
					if(count($changed_status)>0){
						$post_table = $wpdb->prefix."posts";
						$where_field = 'ID';
						$update_field = 'post_status';
							
						foreach($changed_status as $key => $product_id){
							
							$wpdb->update($post_table, array('post_status'=>'draft'),array('ID'=>$product_id));
						}
					}
					
					
				}
				
			}
		}
		
		function update_product_post_meta($form_post_key = '',$meta_key = '',$default = ''){
			$post_data			= isset($_POST[$form_post_key]) 	? $_POST[$form_post_key] : array();
			
			if(is_array($post_data) and count($post_data)>0){
				$hidden 			= isset($_POST['hidden_'.$form_post_key]) ? $_POST['hidden_'.$form_post_key] : array();
				foreach($post_data as $post_id => $value):
					if($hidden[$post_id] != $value){
						$value = strlen($value) > 0 ? $value : $default; 
						update_post_meta($post_id,$meta_key,$value);
					}
				endforeach;
			}
		}
		
		function update_post_status($form_post_key = 'variation_status',$update_field = 'post_status',$where_field = 'ID'){
			global $wpdb;
			
			$variation_parent = array();				
			$variation_status 	= isset($_POST[$form_post_key]) 	? $_POST[$form_post_key] : array();
			if(is_array($variation_status) and count($variation_status)>0){
				$hidden 			= isset($_POST['hidden_'.$form_post_key]) ? $_POST['hidden_'.$form_post_key] : array();
				$hidden_parent		= isset($_POST['hidden_parent_'.$form_post_key]) ? $_POST['hidden_parent_'.$form_post_key] : array();
		
				$post_table = $wpdb->prefix."posts";
				foreach($variation_status as $post_id => $status):
					//if(isset($hidden[$post_id]) and $hidden[$post_id] != $status){
						if(isset($hidden_parent[$post_id]))	$variation_parent[$hidden_parent[$post_id]] = $hidden_parent[$post_id];
						$wpdb->update($post_table, array($update_field=>$status),array($where_field=>$post_id));
					//}
				endforeach;					
			}	//END of Update Variaiton Status /
			return $variation_parent;
		}
		
		function update_post_status2($form_post_key = 'variation_status',$update_field = 'post_status',$where_field = 'ID'){
			global $wpdb;				
			$variation_status 	= isset($_POST[$form_post_key]) 	? $_POST[$form_post_key] : array();
			if(is_array($variation_status) and count($variation_status)>0){
				$hidden 			= isset($_POST['hidden_'.$form_post_key]) ? $_POST['hidden_'.$form_post_key] : array();					
				$post_table = $wpdb->prefix."posts";
				foreach($variation_status as $post_id => $status):
					if(isset($hidden[$post_id]) and $hidden[$post_id] != $status){							
						$wpdb->update($post_table, array($update_field=>$status),array($where_field=>$post_id));
					}
				endforeach;					
			}	//END of Update Variaiton Status /
		}
		
		function update_product_stock($form_post_key = 'update_stock'){
			$return 						= false;
			$new_inventory_data				= array();
			
			$post_id 			= isset($_POST['post_id']) ? $_POST['post_id'] : array();
			
			if(is_array($post_id) and count($post_id)>0){
				
				$update_manage_stocks 		= isset($_POST['update_manage_stock']) 				? $_POST['update_manage_stock'] 		: array();
				$manage_backorders 			= isset($_POST['manage_backorders']) 				? $_POST['manage_backorders'] 			: array();
				$update_stocks 				= isset($_POST['update_stock']) 					? $_POST['update_stock'] 				: array();
				$manage_statusses			= isset($_POST['manage_status']) 					? $_POST['manage_status'] 				: array();
				
				$hidden_manage_stock		= isset($_POST['hidden_update_manage_stock']) 		? $_POST['hidden_update_manage_stock'] 	: array();
				$hidden_stock_value 		= isset($_POST['hidden_update_stock']) 				? $_POST['hidden_update_stock'] 		: array();
				$hidden_manage_backorders 	= isset($_POST['hidden_manage_backorders']) 		? $_POST['hidden_manage_backorders'] 	: array();
				$hidden_manage_status 		= isset($_POST['hidden_manage_status']) 			? $_POST['hidden_manage_status'] 		: array();
										
				foreach($post_id as $key => $post_id):
						
						$manage_stock		= isset($update_manage_stocks[$post_id]) 			? trim($update_manage_stocks[$post_id])	: "no";
						$update_stock2 		= isset($update_stocks[$post_id]) 					? trim($update_stocks[$post_id]) 		: "";
						$manage_backorder	= isset($manage_backorders[$post_id]) 				? trim($manage_backorders[$post_id])	: "no";
						$manage_status		= isset($manage_statusses[$post_id]) 				? trim($manage_statusses[$post_id])		: "outofstock";
						
						$update_stock		= strlen($update_stock2) > 0 						? $update_stock2 						: 0;
						$manage_backorder	= strlen($manage_backorder) > 0 					? $manage_backorder 					: "no";
						$manage_status		= strlen($manage_status) > 0 						? $manage_status 						: "outofstock";							
						$manage_stock		= strlen($manage_stock) > 0 						? $manage_stock 						: "no";
						
						if($manage_stock == "yes"){
							if($update_stock == 0){
								if($manage_backorder == "no"){
									$manage_status = "outofstock";
								}
							}
							
							
							if(isset($hidden_stock_value[$post_id]) and $hidden_stock_value[$post_id] != $update_stock || strlen($update_stock2)<=0 )
								$new_inventory_data[$post_id]['_stock']			=	($update_stock + 0);
								
						}else{
							$update_stock		=	(strlen($update_stock) > 0 and $update_stock != 0) ? $update_stock : $hidden_stock_value[$post_id];//WooCommerce set it to default zero
							$manage_backorder	=	"no";
							
							if(isset($hidden_stock_value[$post_id]) and $hidden_stock_value[$post_id] != $update_stock)
								$new_inventory_data[$post_id]['_stock']			=	($update_stock + 0);
						}
						   
						   
						   
					   if(isset($hidden_manage_stock[$post_id]) and $hidden_manage_stock[$post_id] != $manage_stock)
							$new_inventory_data[$post_id]['_manage_stock']	=	$manage_stock;
						
				
						
						if(isset($hidden_manage_backorders[$post_id]) and $hidden_manage_backorders[$post_id] != $manage_backorder)
							$new_inventory_data[$post_id]['_backorders']	=	$manage_backorder;
						
						if(isset($hidden_manage_status[$post_id]) and $hidden_manage_status[$post_id] != $manage_status)
							$new_inventory_data[$post_id]['_stock_status']	=	$manage_status;
				endforeach;
				
				if(count($new_inventory_data) > 0){
					foreach($new_inventory_data as $post_id => $fields):
						if(isset($new_inventory_data[$post_id]['_manage_stock']))	update_post_meta($post_id,'_manage_stock',$fields['_manage_stock']);
						if(isset($new_inventory_data[$post_id]['_stock']))			update_post_meta($post_id,'_stock',$fields['_stock']);
						if(isset($new_inventory_data[$post_id]['_backorders']))		update_post_meta($post_id,'_backorders',$fields['_backorders']);
						if(isset($new_inventory_data[$post_id]['_stock_status']))	update_post_meta($post_id,'_stock_status',$fields['_stock_status']);
					endforeach;
					$return = true;
					
					//$this->print_r($new_inventory_data);
				}					
			}
			
			return $return;
		}
		
		function update_variation_product_stock($form_post_key = 'variation_update_stock'){
			$v_update_stock	= isset($_POST[$form_post_key]) ? $_POST[$form_post_key] : array();
			
			if(is_array($v_update_stock) and count($v_update_stock)>0){
				$hidden 		= isset($_POST['hidden_'.$form_post_key]) ? $_POST['hidden_'.$form_post_key] : array();
				foreach($v_update_stock as $post_id => $stock_value):
					if($hidden[$post_id] != $stock_value){
						$stock_value 	= trim($stock_value);							
						$post_id 		= trim($post_id);
						
						$results = $this->get_postmeta($post_id);
						
						$count_result = count($results);
						
						if(strlen($stock_value) > 0){
							$stock_value = is_numeric($stock_value) ? $stock_value :  (isset($results->meta_value) ? $results->meta_value : 0);								
							$stock_value = $stock_value + 0;
						}
						
						if($count_result > 0){								
								
								if($count_result == 1){
									update_post_meta($post_id,'_stock',$stock_value);
								}elseif($count_result >= 2){
									delete_post_meta($post_id,'_stock');
									add_post_meta($post_id,'_stock',$stock_value);
								}
								
								if(strlen($stock_value) > 0 and $stock_value <= 0){
									update_post_meta($post_id,'_stock_status','outofstock');
								}
							
						}else{
							delete_post_meta($post_id,'_stock');
							add_post_meta($post_id,'_stock',$stock_value);
						}
					}//END Hidden Check
				endforeach;
			}//END of Update Variation Stock
		}
		
		
		
		function update_product_stock_archive($form_post_key = 'update_stock'){
			$update_stocks 	= isset($_POST['update_stock']) ? $_POST['update_stock'] : array();
			
			//$this->print_r($_REQUEST);
			
			if(is_array($update_stocks) and count($update_stocks)>0){
					$manage_stocks 			= isset($_POST['update_manage_stock']) ? $_POST['update_manage_stock'] : array();
					$hidden 				= isset($_POST['hidden_'.$form_post_key]) ? $_POST['hidden_'.$form_post_key] : array();
					$manage_backorders 		= isset($_POST['manage_backorders']) ? $_POST['manage_backorders'] : array();
					
					
					foreach($update_stocks as $post_id => $stock_value):
						//if($hidden[$post_id] != $stock_value){
							$stock_value 		= trim($stock_value);							
							$post_id 			= trim($post_id);
			
					
							$results 			= $this->get_postmeta($post_id);
															
							$count_result 		= count($results);
							
							$manage_stock 		= isset($manage_stocks[$post_id]) ? $manage_stocks[$post_id] : "no";
							$manage_backorder 	= isset($manage_backorders[$post_id]) ? $manage_backorders[$post_id] : "no";
							
							if(strlen($stock_value) > 0){
								if($manage_stock == "yes"){
									$stock_value = is_numeric($stock_value) ? $stock_value :  (isset($results->meta_value) ? $results->meta_value : 0);								
									$stock_value = $stock_value + 0;
								}else{
									$stock_value = 0;
								}
								
							}else{
								
								if($manage_stock == "yes"){
									$stock_value = 0;
								}
							}
							
							if($count_result > 0){								
									
									
									if($manage_backorder == "no"){
										if($stock_value == 0){
											if($manage_stock == "no"){
												update_post_meta($post_id,'_stock_status','outofstock');
											}else{
												update_post_meta($post_id,'_stock_status','instock');
											}
										}elseif($stock_value >= 1){
											if($manage_stock == "no"){
												update_post_meta($post_id,'_stock_status','outofstock');
											}else{
												update_post_meta($post_id,'_stock_status','instock');
											}
											
										}
									}
									
								
							}else{
								delete_post_meta($post_id,'_stock');
								add_post_meta($post_id,'_stock',$stock_value);
							}
						//}//END Hidden Check
					endforeach;
				}	//END of Update Product Stock /
		}
		
		
					
		
		function create_dropdown($data = NULL, $name = "",$id='', $show_option_none="Select One", $class='', $default ="-1", $type = "array",$tabindex = 0, $multiple = false, $size = 0, $disabled = ''){
			$count 				= count($data);
			$dropdown_multiple 	= '';
			$dropdown_size 		= '';
			
			if($count<=0) return '';
			
			if($multiple == true and $size >= 0){
				//$this->print_r($data);
				
				if($count < $size) $size = $count + 1;
				$dropdown_multiple 	= ' multiple="multiple"';
				//echo $count;
				$dropdown_size 		= ' size="'.$size.'"  data-size="'.$size.'"';
			}
			
			$tabindex_attr = $tabindex>0 ? " tabindex=\"{$tabindex}\"" : '';
			
			$output = "";
			$output .= '<select name="'.$name.'" id="'.$id.'" class="'.$class.'"'.$dropdown_multiple.$dropdown_size.$tabindex_attr.$disabled.'>';
			
			//if(!$dropdown_multiple)
			
			//$output .= '<option value="-1">'.$show_option_none.'</option>';
			
			if($show_option_none){
				if($default == "all"){
					$output .= '<option value="-1" selected="selected">'.$show_option_none.'</option>';
				}else{
					$output .= '<option value="-1">'.$show_option_none.'</option>';
				}
			}			
			
			if($type == "object"){
				foreach($data as $key => $value):
					$s = '';
					if($value->id == $default ) $s = ' selected="selected"';
					
					$c = (isset($value->counts) and $value->counts > 0) ? " (".$value->counts.")" : '';
					
					$output .= "\n<option value=\"".$value->id."\"{$s}>".$value->label.$c."</option>";
				endforeach;
			}else if($type == "array"){
				foreach($data as $key => $value):
					$s = '';
					if($key== $default ) $s = ' selected="selected"';
					$output .= "\n".'<option value="'.$key.'"'.$s.'>'.$value.'</option>';
				endforeach;
			}else{
				foreach($data as $key => $value):
					$s = '';
					if($key== $default ) $s = ' selected="selected"';
					$output .= "\n".'<option value="'.$key.'"'.$s.'>'.$value.'</option>';
				endforeach;
			}
						
			$output .= '</select>';
			
			return $output ;
		
		}
		
		function save_search_form(){
			$filter_save_search	= $this->get_request('filter_save_search',"no");
			if($filter_save_search == "yes"){
				$filter_page_name	= $this->get_request('filter_page_name');
				update_option($this->constants['plugin_key']."_".$filter_page_name,$_REQUEST);
			}
			//update_option($this->constants['plugin_key']."_".$filter_page_name);
		}
		
		function get_form_request(){
			if(!isset($this->constants['plugin_request'])){
				$request = array();
				$start	 = 0;
				$filter_page_name				= $this->get_request('filter_page_name');
				$form_values 					= get_option($this->constants['plugin_key']."_".$filter_page_name);	
				$default_request				= array(
					"filter_limit"				=> isset($form_values['filter_limit']) ? $form_values['filter_limit'] : "15000",
					"filter_sort_by"			=> isset($form_values['filter_sort_by']) ? $form_values['filter_sort_by'] : "ID",
					"filter_order_by"			=> isset($form_values['filter_order_by']) ? $form_values['filter_order_by'] : "DESC",
					"filter_page_name"			=> isset($form_values['filter_page_name']) ? $form_values['filter_page_name'] : $this->constants['default_tab'],
					"post_type"					=> isset($form_values['post_type']) ? $form_values['post_type'] : "",
					"p"							=>	"1"
				);
				
				$request 	= array_merge((array)$_REQUEST, (array)$default_request);
				
				if(isset($_REQUEST)){
					foreach($_REQUEST as $key => $value ):
						$request[$key]		= $this->get_request($key);	
					endforeach;
				}
				
				$this->constants['plugin_request'] = $request;	
							
			}else{		
					
				$request = $this->constants['plugin_request'];
			}
			
			return $request;
		}
		
		function print_list($list = NULL,$display = true){
			
			$output = "";
			
			if(is_array($list)){
				$output .= "<pre>";
				$output .= print_r($list,true);
				$output .= "</pre>";
			}else{
				$output .= "<pre>";
				$output .= print_r($list,true);
				$output .= "</pre>";
			}
			
			if($display){
				
				echo $output;
				
			}else{
				
				return $output;
				
			}
			
		}//End Function
		
		public function get_request($request_name, $default = NULL,$default_set = false){
			if(isset($_REQUEST[$request_name])){
				
				$return = $_REQUEST[$request_name];
				
				if(is_array($return)){
					
					$return = implode(",", $return);
					
				}else{
					
					$return = trim($return);
					
				}
				
				if($default_set) $_REQUEST[$request_name] = $return;
				
				return $return;
				
			}else{
				
				if($default_set) $_REQUEST[$request_name] = $default;
				
				return $default;
			}
		}
		
		function create_directory($directory_name = '',$path = '') {
			if (!file_exists($path.'/'.$directory_name)) {
				mkdir($path.'/'.$directory_name, 0777, true);
			}
		}
		
		
		
	}//End Class
	
	
}//End of Class Exists Check