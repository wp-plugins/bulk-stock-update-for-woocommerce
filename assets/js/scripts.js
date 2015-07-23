
jQuery(document).ready(function($){
	
		$(document).on('change', 'select.manage_status', function(){
			var val = $(this).val();
			if(val == "instock"){
				 $(this).removeClass('outofstock');	
				 $(this).addClass('instock');	
			}else{
				$(this).removeClass('instock');	
				$(this).addClass('outofstock');	
			}
		});//update_stock
	
		$(document).on('keyup', 'input.update_stock', function(){
			var val = $(this).val();
			if(val.length <= 0){
				val = 0;
			}
			if(val == 0){
				$(this).val("");
			}
		});
		
		
		
		$(document).on('change', 'select.update_manage_stock', function(){
			var val = $(this).val();
			var id = $(this).attr("id");								
			var class_id  = id + "_update_stock";
			
			if(val == "yes" || val == "publish"){
				
				$("input.update_stock."+class_id).attr("disabled", false);
				$("input.update_stock."+class_id).removeClass('hide_fields');
				
				
				$("select.manage_backorders."+class_id).attr("disabled", false);
				$("select.manage_backorders."+class_id).removeClass('hide_fields');
			}else if(val == "no" || val == "private"){				
				
				$("input.update_stock."+class_id).attr("disabled", true);
				$("input.update_stock."+class_id).addClass('hide_fields');
				
				
				$("select.manage_backorders."+class_id).attr("disabled", true);
				$("select.manage_backorders."+class_id).addClass('hide_fields');	
			}else{
				$("input.update_stock."+class_id).attr("disabled", true);
				$("input.update_stock."+class_id).addClass('hide_fields');
				
				
				$("select.manage_backorders."+class_id).attr("disabled", true);
				$("select.manage_backorders."+class_id).addClass('hide_fields');
			}
			
			if(val == "yes"){
				var v = $.trim($("input.update_stock"+class_id).val());
				if(v.length <= 0){
					v = 0;
				}
				v = parseInt(v);
				$("input.update_stock."+class_id).val(v + 0);
				
				
				var backorder = $("select.manage_backorders."+class_id).val();
				/*
				if(v == 0){
					if(backorder == "no"){
						//$("select.manage_status."+class_id).val('outofstock');
						//$("select.manage_status."+class_id).addClass('outofstock');	
						//$("select.manage_status."+class_id).removeClass('instock');											 
					}
				}
				*/
			}
		});
	
	
		var ajax_processing = false;
		var please_wait		= wm_ajax_object.please_wait;
		$(document).on('submit', 'form#update_stocks_form, form#frm_search_results', function(){
			
			if(ajax_processing) return false;
			
			var this_submit = this;
			
			ajax_processing = true;
						
			$(".grid_loading").fadeIn()
			$(".onformprocess").attr('disabled',true).addClass('disabled');
			
			$('input[type="text"]').each(function(index, element) {
				var v = $.trim($(element).val());
				$(element).val(v);
			});
			
			$.ajax({
				type:	"POST",
				url: 	wm_ajax_object.ajaxurl,
				data:	$(this_submit).serialize(),
				success:function(data) {
					//alert(JSON.stringify(data))
					ajax_processing = false;
					$('div.searched_content').html(data);
					$(".onformprocess").attr('disabled',false).removeClass('disabled');		
					$(".grid_loading").fadeOut();
				},
				error: function(jqxhr, textStatus, error ){
					ajax_processing = false;
					$(".onformprocess").attr('disabled',false).removeClass('disabled');
					$(".grid_loading").fadeOut();					
				}
			});
			return false;
	});
	
	var ajax_search_results = $.trim($('div.searched_content').html());
	if(ajax_search_results.length == 0){
		$('div.searched_content').html("<p>"+please_wait+"</p>");
		$('form#frm_search_results').submit();
	}
	
	$(".grid_loading").css({"opacity":0.9}).hide();
	
	$("h2.wm_nav_tab_wrapper a").click(function(){
		
		if(ajax_processing) return false;
		
		ajax_processing = true;			
		$(".grid_loading").fadeIn()
		$(".onformprocess").attr('disabled',true).addClass('disabled');
	});
	
});