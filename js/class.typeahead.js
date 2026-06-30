var typeahead_current_value = '';

/*
params:
	'disabled' => false -- use this to enable/disable typeahead based on some other user function
	'fields' => [obj] 'input ID to complete': 'field name returned by server'

*/

class typeahead {

	constructor(input_id, ajax_url, instance_name, params) {

		this.stylesheet_url = '../common/typeahead.css';
		this.ajax_url = ajax_url;
		this.input_id = input_id;
		this.instance_name = instance_name;
		this.disabled = false;
		this.values = [];
		this.auto_select_first = true;
		if (typeof params !== 'undefined') {
			for (const [k, v] of Object.entries(params)) {
				this[k] = v;
			}
		}
		var myself = this;

		//link stylesheet if not already
		var link = document.createElement('link');  
		link.rel = 'stylesheet';  
		link.type = 'text/css'; 
		link.href = myself.stylesheet_url; 
		var found = false; 
		for(var i = 0; i < document.styleSheets.length; i++){
			if(document.styleSheets[i].href==myself.stylesheet_url){
				found=true;
				break;
			}
		}
		if(!found){
			document.getElementsByTagName('HEAD')[0].appendChild(link);
		}
		//create target for resutls
		$('body').append('<div class="typeahead_results" id="typeahead_results_'+this.instance_name+'"></div>');
		
		$("#"+myself.input_id).keyup(function(){
			var v = $("#"+myself.input_id).val()
			if (v.length > 0){
				if (v !== typeahead_current_value){
					myself.check_server();
					typeahead_current_value = v;
				}
			}
		});
	}

	check_server(){
		var myself = this;
		if (myself.disabled){
			return true;
		}
		var e = $("#"+myself.input_id);
		if (typeof myself.width !== 'undefined') {
		    var wd = myself.width;
		}
		else{
			var wd = e.parent().width();
		}
		
		myself.values = e.val();
		var url = myself.ajax_url;
		var pars = {'val' : e.val()};
		var return_div_id = 'typeahead_results_'+this.instance_name;
		$.ajax({
			url: url,
			type: 'POST',
			data: pars,
		success: function(d) {
				if (d !== 'null'){
					var results = JSON.parse(d);
					console.log(results);
					var html = '';
					var hilite = myself.auto_select_first ? 'auto_select_hilite_current' : '';
				
					for (const [field, value] of Object.entries(results)) {
						if (value.length < 1) continue;
						if ($.inArray(value, this.values) == -1){//only show ones that have not been chosen.
							var vals = encodeURIComponent(JSON.stringify(value)).replace(/[\/\(\)\']/g, "\\$&");
							html += '<div class="typeahead_option '+hilite+'" onclick="'+myself.instance_name+'.typeahead_choose(\''+vals+'\')">'+value.label+'</div>';
							hilite = '';
						}
						else if (typeof value.action !== 'undefined') {
							html += '<div class="typeahead_option '+hilite+'" onclick="'+value.action+'">'+value.label+'</div>';
							hilite = '';
						}
					}
					var pos = e.offset();
					$('#'+return_div_id).html(html).show().css({'top' : pos.top + e.outerHeight(), 'left' : pos.left, 'width' : wd});
				}
				else{
					html = '';
					$('#'+return_div_id).hide().html(html);
				}

			}
		});
	}

	
	typeahead_choose(value){
		var vals = JSON.parse(decodeURIComponent(value));
		var myself = this;
		if (typeof myself.fields !== 'undefined') {
		    // multiple values come in one package
		    for (const [k, v] of Object.entries(myself.fields)) {
		    	$("#"+k).val(vals[v]).trigger('change').parent().addClass('mini');
		    }
		}
		else if (typeof vals.response_url !== 'undefined') {
		   	//go directly to this URL upon selection
		   	document.location = vals.response_url;
		}
		else if (typeof vals.action !== 'undefined') {
		   	//perform this action
		   	eval(vals.action);
		}
		else{
			$("#"+this.input_id).val(vals.single_response);
		}
		$('#typeahead_results_'+this.instance_name).html('').hide();
		
	}


}



//this is outside the class

$(document).keydown(function(e) {
	if ($('.typeahead_results').is(":visible")){
		switch (e.keyCode) {
			//up arrow
			case 38:
				e.preventDefault(); // prevent the default action, like horizontal scroll
				scroll_results('up');
			break;
			//down arrow
			case 40:
				e.preventDefault();
				scroll_results('down');
			break;
			//return
			case 13:
				//select the highlighted one (if there is one)
				var n = $('.auto_select_hilite_current');
				if (n.length){
					e.preventDefault();
					$('.auto_select_hilite_current').click();
					$('.typeahead_results').hide();
				}
				else{
					action = $(":focus").data('action_on_enter');
					value = $(":focus").val();
					eval(action + "('" + value + "')");
				}
			break;
		}
	}
});

function scroll_results(dir){
	switch(dir){
		case 'down':
			var cur = $('.auto_select_hilite_current');
			if(cur.length == 0){
				cur = $(".typeahead_option").first();
				cur.addClass('auto_select_hilite_current');
				return true;
			}
			var nxt = $('.auto_select_hilite_current').next();
		break;
		case 'up':
			var cur = $('.auto_select_hilite_current');
			var nxt = $('.auto_select_hilite_current').prev();
		break;
	}
	if (nxt.length){
		nxt.addClass('auto_select_hilite_current');
		cur.removeClass('auto_select_hilite_current');
	}
	else if (dir == 'up'){
		cur.removeClass('auto_select_hilite_current');
	}
}

function toggle_typeahead(elem, typeahead_id, val_to_match){
	val = $(elem).val();
	if (val == val_to_match){
		eval(typeahead_id + ".disabled = false");
	}
	else{
		eval(typeahead_id + ".disabled = true");
	}
}