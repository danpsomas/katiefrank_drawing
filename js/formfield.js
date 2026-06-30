

class formfield{

	constructor(params) {
		this.alerts = [];
		this.validations = {}
		this.track_required = false;
		this.is_dirty_form = false;
		this.disable_on_missing_required = [];
		this.missing_required = [];
		this.form_id = '';//when specifying this, use the hash as well: i.e., '#my_form'
		if (typeof params !== 'undefined') {
			for (const [k, v] of Object.entries(params)) {
				this[k] = v;
			}
		}
		var myself = this;
		//this has to come before plain input.nifty b/c input.nifty swepps this up as well

		//unit_input_ready();// ready the unit_input fields

		$.each($(this.form_id+" input.nifty, "+this.form_id+" textarea.nifty").not('.formfield_indexed'), function(){

			if ($(this).hasClass('disabled')){
				$(this).prop('disabled', true).parent().addClass('mini');
				return true;
			}
			if ($(this).val().length > 0){
				$(this).parent().not(".choice_chips_wrapper").addClass('mini');
			}
			if ($(this).hasClass('nifty_select_label')){
				var $opts = $(this).siblings("ul.options").children('li');
				niftyBindOptionEarlyPick($opts);
				$(this).click(function(){
					myself.show_options($(this));
				});
				$(this).keyup(function(){
					myself.show_options($(this));
				});
			}

			$(this).addClass('formfield_indexed');
			$(this).focus(function(){
				myself.do_click($(this));
			});
			$(this).blur(function(){
				myself.do_blur($(this));
			});
			$(this).keydown(function(e){
				myself.do_keydown($(this), e);
			});
			$(this).keyup(function(e){
				myself.is_dirty_form = true;
				myself.do_keyup($(this), e);
			});
			$(this).change(function(){
				myself.is_dirty_form = true;
				myself.do_change($(this));
			});
			myself.validate($(this));//set the error messages
		});

		$(myself.form_id+" .nifty_wrapper .choice_chips .chip").not('.formfield_indexed').addClass('formfield_indexed').click(function(){
			
			var val = $(this).data("value");
			$(this).parent().siblings("input.nifty_select_value").val(val);
			$(this).parent().siblings("input.nifty_select_value").change();
			$(this).siblings().removeClass('hilite');
			$(this).addClass('hilite');
			myself.do_blur($(this));
		});
		buttons_behave();
		if (myself.track_required){//check required upon load. Also reset dirty form b/c checking required triggers clicks
			setTimeout(function(){myself.check_required_fields(); myself.is_dirty_form = false}, 500);
		}
		//limit for checkbox groups
		$.each($(myself.form_id+" .cbx_group").not('.formfield_indexed'), function(){
			$(this).addClass('formfield_indexed');
			if ($(this).data('limit')){
				$.each($(this).find("input[type='checkbox']"), function(){
					$(this).click(function(){
						limit_cbx_group(this);
					})
				});
			}
		});


		$(".style_chooser_tile_sm, .style_chooser_tile").not('.formfield_indexed').addClass('formfield_indexed').click(function(){
			$(this).find("input[type='checkbox']").prop('checked', !$(this).find("input[type='checkbox']").prop('checked'));
			limit_cbx_group($(this).find("input[type='checkbox']"));
		});
	}

	//this is a straight copy from above where we instantiate the formfield object with select fields.
	//be sure to clear out all <li> elements from <ul class="options"...> before creating and registering new ones
	register_options(select_name){
		niftyBindOptionEarlyPick($("#"+select_name).siblings("ul.options").children('li'));
	}


	//json-encoded vars
	//receive an associative array from URL and output option values
	//{
	//	'235' : {"label":"a label","classnames":"class_one class_two"},
	//	'523' : {"label":"another label","classnames":"class_three"}
	//}

	replace_select_opts(id, url, vars){
		var myself = this;
		var pars = vars;
		$.ajax({
			url: url,
			type: 'POST',
			data: pars,
		success: function(data) {
				var select = JSON.parse(data);
				var str = '<li class="hilite">[none]</li>';
				$.each(select, function(k,v){
					str += '<li class="'+ v.classnames +'" data-value="'+ v.id +'">'+ v.label +'</li>';
				});
				$("#"+id).siblings('ul').html(str);
				setTimeout(function(){myself.register_options(id)}, 500);
			},
	      	error: function (xhr, ajaxOptions, thrownError) {
	       		 alert(xhr.status);
	       		 alert(thrownError);
	      	}
		});
	}


	do_click(input_obj){
		var myself = this;
		input_obj.filter(":checkbox" ).trigger('blur');
		input_obj.parent().addClass('mini').addClass('active');
	}
	show_options(e){
		var myself = this;
		$(e).siblings("ul.options").show(200);
		$(e).css('background-image', 'url("../common/images/caret_up.png")');
	}

	do_blur(input_obj){
		var myself = this;
		//remove class active
		input_obj.parent().removeClass('active');
		//maybe remove class mini
		if (input_obj.val().length < 1){
			input_obj.parent().removeClass('mini');
		}
		else {
			input_obj.parent().addClass('mini');
			//also, any format verification we need to know about? (ignoring empty fields)
				if (input_obj.data("validate")){
				myself.validate(input_obj);
			}
		}
		if (myself.track_required){
			setTimeout(function(){myself.check_required_fields()}, 200);
		}

		if (input_obj.hasClass('nifty_select_label')){
			//we need to clear this out if it does not match the ID value in the actual input field
			//does this exist in the <li>s?
			var val = $.trim(input_obj.val());
			var inpt = input_obj.siblings('.nifty_select_value');
			var is_good = false;
			$.each(input_obj.siblings('ul').children('li'), function(){
				var liHtml = $.trim($(this).html().replace(/&amp;/g, '&'));
				if(liHtml === val){
					inpt.val($(this).data('value'));
					is_good = true;
				}
			});
			if (!is_good){
				inpt.val('').trigger('change').trigger('blur');
				input_obj.val('');
			}
		}
	}
	do_keyup(input_obj, e){
		var myself = this;
		if (input_obj.data("validate")){
			myself.validate(input_obj);
		}
		if (myself.track_required){
			setTimeout(function(){myself.check_required_fields()}, 200);
		}
		if ($('ul.options').is(":visible")){
			var options_ul = $('ul.options').filter(":visible");
			switch (e.keyCode) {
				case 38:
					e.preventDefault(); // prevent the default action, like horizontal scroll
					select_field_scroll('up', options_ul);
				break;
				case 40:
					e.preventDefault();
					select_field_scroll('down', options_ul);
				break;
				
				case 13:
					//e.preventDefault(); it's actually too late for this now (we need to do it on keydown)
					//select the highlighted one
					$(options_ul).children('li.scroll_hilite').click();
				break;
				default:
				options_ul.children('li').show();

				if(!$(input_obj).hasClass('disable_typeahead')){
					var search_str = options_ul.siblings('.nifty_select_label').val().toLowerCase();
					
					$.each(options_ul.children('li'), function(){
						if (-1 == $(this).html().toLowerCase().search(search_str)){
							$(this).hide();
						}
					});
					
				}


				select_field_scroll('first', options_ul);
			}
		}
		else if ($('input.nifty_select_label:focus').length > 0){
			switch (e.keyCode) {
				case 40://arrow down opens the select options
					$('input.nifty_select_label:focus').click();
				break;
			}
		}
		else if ($('button.default_action').is(":visible")){
			if (e.keyCode == 13) {
				$('button.default_action').filter(":visible").first().click();
			}
		}

	}

	do_keydown(input_obj, e){
		var myself = this;
		if ($('ul.options').is(":visible")){
			//var options_ul = $('ul.options').filter(":visible"); -- I don't think this does anything (here)
			if (e.keyCode == 13) {//[enter/return] key
				e.preventDefault();
			}
		}
		if (!input_obj.is("textarea") && (typeof myself.allow_return == 'undefined' || !myself.allow_return)){
			if (e.keyCode == 13) {//[enter/return] key
				e.preventDefault();
			}
		}
	}


	do_change(input_obj){
		var myself = this;
		if (input_obj.closest('.nifty_wrapper').hasClass('checkbox_pill')){
			if (input_obj.is(":checked")){
				input_obj.closest('.nifty_wrapper').addClass('checked');
			}
			else{
				input_obj.closest('.nifty_wrapper').removeClass('checked');
			}
		}
		if (input_obj.data("validate")){
			myself.validate(input_obj);
		}

		//indeterminate?
		if ($(input_obj).closest(".cbx_group.indeterminate").length == 1){
			if ($(input_obj).hasClass('indeterminate')){

				input_obj.prop('checked', true);
				limit_cbx_group(input_obj);
				//then remove this for next time round - just normal checkbox activity
				$(input_obj).removeClass('indeterminate');
			}
			
		}

	}


	check_required_fields(include_hidden){
	    if (typeof include_hidden == 'undefined') {
	        var include_hidden = false;
	    }
		var myself = this;
		var elems = include_hidden ? $(myself.form_id+" .nifty_wrapper.required").find('input, textarea').not(".nifty_select_label") : $(myself.form_id+" .nifty_wrapper.required:visible").find('input, textarea').not(".nifty_select_label");
		
		var cbx_group_elems = $(myself.form_id+" .cbx_group");
	
		myself.missing_required = [];
		$.each(elems, function(){
			if ($(this).is(":checkbox") && !$(this).is(":checked")){
				myself.missing_required.push(this);
			}
			if ($(this).val().length < 1){
				myself.missing_required.push(this);
			}
		});

		//also do checkbox groups
		$.each(cbx_group_elems, function(){
			if (!$(this).is(":visible")){
				return true;
			}
			var req = $(this).data("require");
			var ckd = 0;
			if (typeof req !== 'undefined') {
			   	$.each($(this).find("input[type='checkbox']"), function(){
			   		if ($(this).is(":checked")){
			   			ckd++;
			   		}
			   	});
			   	if (ckd < req){
			   		myself.missing_required.push(this);
			   	}
			}
		});

		if (myself.missing_required.length > 0){
			$.each(myself.disable_on_missing_required, function(){
				$("#" + this).prop('disabled', true);
			});
		}
		else{
			$.each(myself.disable_on_missing_required, function(){
				$("#" + this).prop('disabled', false);
			});
		}
		
		return myself.missing_required;
		
	}



	validate(input_obj){
		var myself = this;
		myself.alerts = [];
		myself.validations = [];
		input_obj.siblings(".helper").html("").hide();
			input_obj.siblings("i").css('top', '50%');
		input_obj.parent().removeClass('alert');

		if (input_obj.data("validatetype")){
			myself.validations.type = input_obj.data("validatetype");
			myself.validatetype(input_obj, input_obj.data("validatetype"));
		}

		//validate min LENGTH not VALUE
		if (typeof input_obj.data("validatemin") !== 'undefined'){
			myself.validations.min = input_obj.data("validatemin");
			myself.validatemin(input_obj, input_obj.data("validatemin"));
		}
		//validate max LENGTH not VALUE
		if (input_obj.data("validatemax")){
			myself.validations.max = input_obj.data("validatemax");
			myself.validatemax(input_obj, input_obj.data("validatemax"));
		}
		//validate min VALUE
		if (typeof input_obj.data("validateminvalue") !== 'undefined'){
			myself.validations.minvalue = input_obj.data("validateminvalue");
			myself.validateminvalue(input_obj, input_obj.data("validateminvalue"));
		}
		//validate max VALUE
		if (input_obj.data("validatemaxvalue")){
			myself.validations.maxvalue = input_obj.data("validatemaxvalue");
			myself.validatemaxvalue(input_obj, input_obj.data("validatemaxvalue"));
		}

		if (myself.alerts.length > 0){
			myself.display_alert(input_obj);
			$(".disable_on_validation_error").attr('disabled', true);
			return false;
		}
		else{
			$(".disable_on_validation_error").attr('disabled', false);
			return true;
		}

	}

	//validate by type (i.e., numeric, date)
	validatetype(input_obj, type){
		var myself = this;
		if (input_obj.val().length > 0){
			switch(type){
				case 'numeric':
					var reg = /^[\d\-\.,\$\%]+$/;//$ and % are allowed here as well.
					if(!input_obj.val().match(reg)){
						myself.alerts.push('type');
					}
				break;
				case 'email':
					var reg = /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i;
					if(!input_obj.val().match(reg)){
						myself.alerts.push('type');
					}
				break;
				case 'phone':
					var reg = /^\D?(\d{3})\D?\D?(\d{3})\D?(\d{4})$/;
					if(!input_obj.val().match(reg)){
						myself.alerts.push('type');
					}
				break;
			}
		}
		
	}

	validatemin(input_obj, min){
		var myself = this;
		//string length
		if (input_obj.val().length < min){
			myself.alerts.push('min');
		}
	}
	validatemax(input_obj, max){
		var myself = this;
		//string length
		if (input_obj.val().length > max){
			myself.alerts.push('max');
		}
	}

	validateminvalue(input_obj, minval){
		var myself = this;
		//value
		if (input_obj.val().length > 0 && Number(input_obj.val().replace(/[^0-9\.]/g, '')) < minval){
			myself.alerts.push('min_value');
		}
		
	}
	validatemaxvalue(input_obj, maxval){
		var myself = this;
		//value
		if (Number(input_obj.val().replace(/[^0-9\.]/g, '')) > maxval){
			myself.alerts.push('max_value');
		}
	}

	display_alert(input_obj){
		var myself = this;
		var alert_msg = '';
		if ($.inArray('type', myself.alerts) !== -1){
			switch(myself.validations.type){
				case 'phone':
					alert_msg += " a valid phone number";
				break;
				case 'email':
					alert_msg += " a valid email address";
				break;

				default:
				alert_msg += " " + myself.validations.type;
			}
			
		}

		//length
		if ($.inArray('max', myself.alerts) !== -1 || $.inArray('min', myself.alerts) !== -1){
			//either the max or min has been violated.
			if ($.inArray('max', Object.keys(myself.validations)) !== -1 && $.inArray('min', Object.keys(myself.validations)) !== -1){
				//we have both min and max values, so teh message should be "between x and y"
				alert_msg += " between "+ myself.validations.min + " and "+ myself.validations.max + " characters";
			}
			else if ($.inArray('max', Object.keys(myself.validations)) !== -1){
				alert_msg += " no more than "+ myself.validations.max + " characters";
			}
			else if ($.inArray('min', Object.keys(myself.validations)) !== -1){
				alert_msg += " at least "+ myself.validations.min + " characters";
			}
		}
		//value
		if ($.inArray('max_value', myself.alerts) !== -1 || $.inArray('min_value', myself.alerts) !== -1){
			
			//either the max or min has been violated.
			if ($.inArray('maxvalue', Object.keys(myself.validations)) !== -1 && $.inArray('minvalue', Object.keys(myself.validations)) !== -1){
				//we have both min and max values, so teh message should be "between x and y"
				alert_msg += " between "+ myself.validations.minvalue + " and "+ myself.validations.maxvalue;
			}
			else if ($.inArray('maxvalue', Object.keys(myself.validations)) !== -1){
				alert_msg += " no greater than "+ myself.validations.maxvalue;
			}
			else if ($.inArray('minvalue', Object.keys(myself.validations)) !== -1){
				alert_msg += " at least "+ myself.validations.minvalue;
			}
		}



		if (alert_msg.length > 0){
			input_obj.siblings(".helper").html("Must be " +alert_msg).show();
			input_obj.parent().addClass('alert');
		}
	}

}



//this is outside the function
$(document).mouseup(function(e){
	if (!$("ul.options").is(e.target) // if the target of the click isn't the container...
		&& $("ul.options").has(e.target).length === 0){ // ... nor a descendant of the container
		hide_options();
	}
});


//where we have a user-editable checkbox field, check the box if the label is not empty
function check_if_not_empty(e){
	if ($(e).val().length > 0){
		$(e).siblings("input:checkbox").prop('checked', true).keyup();
	}
	else{
		$(e).siblings("input:checkbox").prop('checked', false).keyup();
	}
}


function format_number(e, precision){
	var n = $(e).val().replace(/[^0-9\.]/g, '');
	var parts = n.split('.');
	var decimal_part = '';
	var	int_part = '';
	if (!isNaN(parts[0])){
		var int_part = parseInt(parts[0]).toLocaleString('us-EN');
	}
	if (typeof parts[1] !== 'undefined' && precision > 0) {
	    decimal_part = "." + parts[1].substring(0, precision);
	}
	var value = int_part + decimal_part;
	if (value == 'NaN'){
		value = ''
	}
	$(e).val(value);

}


function select_field_scroll(dir, options_ul){
	if ( $(options_ul).children('li.scroll_hilite').length > 0 ){
		var cur = $(options_ul).children('li.scroll_hilite');
		switch(dir){
			case 'up':
				var nxt = $(cur).prev();
			break;
			case 'down':
				var nxt = $(cur).next();
			break;
			case 'first':
				var nxt = $(options_ul).children('li:visible').first();
			break;
		}
		if (nxt.length && nxt.data('value') !== cur.data('value')){
			nxt.addClass('scroll_hilite');
			cur.removeClass('scroll_hilite');
		}
	}
	else{
		$(options_ul).children('li:first').addClass('scroll_hilite');
	}
}

function hide_options(){
	$("ul.options").hide();
	$(".nifty_select_label").css('background-image', 'url("../common/images/caret_dn.png")');
}

// Nifty dropdown: WebKit often blurs the label before the option's click runs, which clears
// the hidden value in do_blur. Commit the selection on pointerdown / mousedown / touchstart
// (before blur) and swallow the follow-up click. Keyboard/programmatic .click() still works.
function niftyApplyLiSelection(li){
	var $li = $(li);
	if ($li.hasClass('disabled')){
		return;
	}
	var val = $li.data("value");
	var lbl = $li.html().replace(/&amp;/g, '&');
	$li.siblings().removeClass('hilite');
	$li.addClass('hilite').parent().siblings("input.nifty_select_label").val(lbl);
	$li.parent().siblings("input.nifty_select_value").val(val);
	$li.parent().siblings("input.nifty_select_value").change();
	hide_options();
	$li.closest(".nifty_wrapper").addClass('mini');
}

function niftyBindOptionEarlyPick($lis){
	$lis = $lis.filter('li').not('.nifty_pick_bound');
	if (!$lis.length){
		return;
	}
	$lis.addClass('nifty_pick_bound');
	var lastEarlyTime = 0;
	var lastEarlyLi = null;
	function allowEarlyPick(e){
		if (e.type === 'mousedown' && e.which && e.which !== 1){
			return false;
		}
		if (e.type === 'pointerdown' && typeof e.button === 'number' && e.button !== 0){
			return false;
		}
		return true;
	}
	function early(e){
		if (!allowEarlyPick(e)){
			return;
		}
		var li = e.currentTarget;
		if ($(li).hasClass('disabled')){
			return;
		}
		var now = Date.now();
		// Same tap can emit touchstart then mousedown; ignore the duplicate for one option.
		if (now - lastEarlyTime < 650 && lastEarlyLi === li){
			if (e.type === 'mousedown' || (e.type === 'pointerdown' && e.pointerType === 'mouse')){
				try { e.preventDefault(); } catch (err) {}
			}
			return;
		}
		// Mouse only: avoid blocking touch scroll on long lists; sync still runs before blur in common cases.
		if (e.type === 'mousedown' || (e.type === 'pointerdown' && e.pointerType === 'mouse')){
			try { e.preventDefault(); } catch (err) {}
		}
		lastEarlyTime = now;
		lastEarlyLi = li;
		niftyApplyLiSelection(li);
	}
	if (window.PointerEvent){
		$lis.on('pointerdown', early);
	}
	else{
		$lis.on('mousedown', early);
		$lis.on('touchstart', early);
	}
	$lis.on('click', function(e){
		if (Date.now() - lastEarlyTime < 700){
			e.preventDefault();
			e.stopImmediatePropagation();
			return false;
		}
		niftyApplyLiSelection(this);
	});
}

function limit_cbx_group(cbx){
	//console.log(cbx);
	if($(cbx).is(":checked")){
		var grp = $(cbx).closest(".cbx_group");
		if (grp.data('limit') && grp.data('limit') == 1){//like a radio button
			$(grp).find("input[type='checkbox']").not($(cbx)).prop('checked', false);
		}
		else if (grp.data('limit')){
			var num = $(grp).find("input[type='checkbox']").filter(":checked").length;
			if (num > grp.data('limit')){
				$(cbx).prop('checked', false);
			}
		}
	}
}

function buttons_behave(){
	$.each($("button").not('.is_behaving'), function(){
		$(this).addClass('is_behaving');
		$(this).click(function(e){
			e.preventDefault();//buttons inside forms (like choice chips will submit the form)
		})
	});
}

function checkboxes_behave(){
	setTimeout(function(){
		var sheet = getStyleSheet("elements");
		sheet.addRule(".nifty_wrapper.checkbox input[type=checkbox]:not(:checked) + .cbx_wrap:before", "animation-duration: 0.3s !important;");
		sheet.addRule(".nifty_wrapper.checkbox .cbx_wrap .check:before", "animation-duration: 700mss !important;");

		sheet.addRule(".nifty_wrapper.checkbox input[type=checkbox]:not(:checked) + .cbx_wrap:before", "-webkit-animation-duration: 0.3s !important;");
		sheet.addRule(".nifty_wrapper.checkbox .cbx_wrap .check:before", "-webkit-animation-duration: 700ms !important;");

	}, 500);

}
function getStyleSheet(unique_title) {
  for(var i=0; i<document.styleSheets.length; i++) {
    var sheet = document.styleSheets[i];
    if(sheet.title == unique_title) {
      return sheet;
    }
  }
}



function unit_input_ready(){//prepare unit_input fields (after they load)
	$(".unit_input input").click(function(){
		if ($(this).siblings('.choice_chips').children('button.hilite').length < 1){
			inform('Please choose a unit first');
		}
		else{
			str = $(this).val();
			start = $(this)[0].selectionStart;
			len = str.length;
			if (str.search(/\$/) != -1){
				if (start == 0){
					$(this)[0].selectionStart = len;
					$(this)[0].selectionEnd = len;
				}
			}
			else{
				if (start == len){
					$(this)[0].selectionStart = len - 1;
					$(this)[0].selectionEnd = len -1;
				}
			}
		}
	}).keyup(function(){
		var units = $(this).siblings('.choice_chips').children('button.hilite').text();
		var str = $(this).val();
		switch(units){
			case '$':
				$(this).val(units + str.replace(units, ''));
				break;
			default:
				$(this).val(str.replace(units, '') + units);
				
		}

		if ($(this).val().length == 1){
			$(this).val('');//set this to zero so it is noted as incomplete
		}
	}).siblings('.choice_chips').children().click(function(){
		//change the symbol
		var str = $(this).parent().siblings('input').val();
		$.each($(this).parent().children(), function(){
			units = $(this).text();
			//first, strip ALL units
			str = str.replace(units, '');
			$(this).parent().siblings('input').val(str);
		});
		//then add back in the units we want
		var units = $(this).parent().children('button.hilite').text();
		$(this).parent().siblings('input').val();
		switch(units){
			case '$':
				$(this).parent().siblings('input').val(units + str.replace(units, ''));
				break;
			default:
				$(this).parent().siblings('input').val(str.replace(units, '') + units);
		}
		if ($(this).parent().siblings('input').val().length == 1){
			$(this).parent().siblings('input').val('');//remove units so this field is noted as incomplete
		}
	});

}







$(document).ready(function(){
	buttons_behave();
	checkboxes_behave();
});