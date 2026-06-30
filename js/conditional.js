class conditional {

	constructor (trigger, affected, params) {
		this.triggers = [];
		this.logic = {};
		this.triggers.push(trigger);
		if (typeof params.clear_value == 'undefined') {//set default
		    params.clear_value = true;
		}
		this.logic[trigger] = {[affected]:params};
		var myself = this;
		$("input[name="+trigger+"]").change(function(){
			myself.check_conditional(trigger)
		});
		$("input[name="+trigger+"]").keyup(function(){
			myself.check_conditional(trigger)
		});

		myself.check_conditional(trigger);//also, do this now for setup..
		
	}

	get_trigger_val(trigger){
		var $checkbox = $("input[name="+trigger+"][type=checkbox]");
		if ($checkbox.length){
			if ($checkbox.is(":checked")){
				return $checkbox.val();
			}
			var $hidden = $("input[name="+trigger+"][type=hidden]");
			return $hidden.length ? $hidden.val() : '0';
		}
		return $("input[name="+trigger+"]").val();
	}

	check_conditional(trigger){
		var myself = this;
		//console.log(myself.logic[trigger])
		var val = myself.get_trigger_val(trigger);
		for (const [k, v] of Object.entries(myself.logic[trigger])) {
			var take_action=true;
			//other values ALSO required?
			if (v.other_vals){
				for (const [field, value] of Object.entries(v.other_vals)) {
					if ($("#"+field).val() !== value){
						take_action = false;
					}
				}
			}
			
			if ($.inArray(val, v.vals) !== -1 && take_action){//we found a match! let's do something.
				if (typeof v.remove_option !== 'undefined'){
					$.each(v.remove_option, function(){
						myself.hide_option(k, this);
					});
				}
				else if (v.inverse){//SHOW if match
						myself.show_element(k, v.required);
				}
				else{//unrequire (hide/disable?)
						myself.unrequire(k, v);
				}
			}

			else{//not a match; let's do the opposite of what we do in the case of a match
				if (typeof v.remove_option !== 'undefined'){
					$.each(v.remove_option, function(){
						myself.show_option(k, this);
					})
				}
				else if (v.inverse){//HIDE if match
						this.unrequire(k, v);
				}
				else if (take_action){//SHOW only when other_vals (if any) are satisfied
						this.show_element(k, v.required);
				}
			}
		}
	}


	show_element(id, required){
		var $inputs = $("input[name="+id+"], textarea[name="+id+"]");
		$inputs.prop('disabled', false);
		$inputs.parent().removeClass('disabled').show().siblings('.helper_text').show();
		$inputs.parent().next(".input_adjacent").show();
		// Also show wrapper divs with id matching pattern
		$("#"+id+"_wrapper").show();
		if (required){
			$inputs.parent().addClass('required');
		}
	}
	unrequire(id, params){
		var hide = params.hide;
		var clear_value = params.clear_value;
		var disable = params.disable;
		var $inputs = $("input[name="+id+"], textarea[name="+id+"]");
		$inputs.parent().removeClass('required');

		if (disable){
			if (clear_value !== false){
				$inputs.val('').trigger('blur');
			}
			$inputs.prop('disabled', true);
			$inputs.parent().addClass('disabled');
		}

		if (hide){
			$inputs.parent().hide().siblings('.helper_text').hide();
			$inputs.parent().next(".input_adjacent").hide();
			// Also hide wrapper divs with id matching pattern
			$("#"+id+"_wrapper").hide();
			if (clear_value !== false && !disable){
				$inputs.val('');
			}
		}
	}
	hide_option(id, option_value){
		//choice chips

		//depends.... Is this option selected already?
		var this_button = $("input#"+id).siblings(".choice_chips").children().filter("button[data-value='"+option_value+"']");
		if ($("input#"+id).val() == this_button.data('value')){
			$("input#"+id).val('');
			//select
			$("input#"+id).siblings(".nifty_select_label").val('');
			$("input#"+id).siblings("ul").children("li[data-value='"+option_value+"']").hide();
		}
		else{
		}
		this_button.hide().removeClass('hilite');
	}
	show_option(id, option_value){
		//choice chips
		$("input#"+id).siblings(".choice_chips").children().filter("button[data-value='"+option_value+"']").show();
		//select
		$("input#"+id).siblings("ul").children("li[data-value='"+option_value+"']").show();
	}

}

