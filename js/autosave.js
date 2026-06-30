//holder for a newly generated row ID
var generated_record_id = 0;
class autosave{
	
	constructor(id_var) {
		this.section_has_error = false;
		this.input_elements = [];
		this.pending_input = null;
		this.id_var = id_var;//we will use this in the event of a newly created record.
		this.is_saving = false; //sometimes, this submits twice (which is not really bad unless it's an empty form and we get two IDs)
		var myself = this;
		$.each($("input.autosave, textarea.autosave, select.autosave"), function(){
			myself.input_elements.push(this);
			$(this).change(function(){
				var thisobj = $(this);
				setTimeout(function(){ myself.save(thisobj); }, 100);//give the browser a chance to update the value
			});
		});
	}

	save(input_obj, ignore_is_saving, done_callback){
		var ignore_is_saving = typeof ignore_is_saving == 'undefined' ? false : ignore_is_saving;
		var myself = this;
		if (myself.is_saving && !ignore_is_saving){
			// remember the most recent change so it can be saved
			myself.pending_input = input_obj;
			return true;
		}
		myself.is_saving = true;
		if (input_obj.parent().hasClass('alert')){
			myself.section_has_error = true;
		}
		var url = input_obj.data('autosaveurl') ? input_obj.data('autosaveurl') : '../data/save.php';
		var return_div_id = input_obj.data('returnid') ? input_obj.data('returnid') : 'ajax_results';
		var pars = input_obj.data('pars');
		//is there a col value in the pars? If not, then use the name of the input field
		if (typeof pars.col == 'undefined') {
		  	pars[input_obj.attr('name')] = input_obj.val();
		}
		//this is for newly created records - we need a way to alert subsequent calls to the existence of a new record
		pars.generated_record_id = generated_record_id;//in the global scope
		//is this a checkbox?
		if (input_obj.is(':checkbox')){
			pars.is_checkbox = true;
			pars.colname = input_obj.attr('name');
			//is it checked?
			if (input_obj.is(":checked")){
				pars.checked = true;
			}
			else{
				pars.checked = false;
			}
			pars.value_on_uncheck = input_obj.data('uncheckval');
		}
		$("#status_msg").html('').removeClass('on');
		var finish_save = function(){
			myself.is_saving = false;
			if (myself.pending_input && !ignore_is_saving){
				var next = myself.pending_input;
				myself.pending_input = null;
				myself.save(next, true);
			}
			if (typeof done_callback === 'function'){
				done_callback();
			}
		};
		$.ajax({
			url: url,
			type: 'POST',
			data: pars,
			success: function(d) {
				console.log(pars)
				var data = JSON.parse(d);
				console.log(data);
				if (typeof data.insert_id !== 'undefined') {
				 	generated_record_id = data.insert_id;
				 	eval(myself.id_var + "=" + generated_record_id);
				 	var id_name = myself.id_var;
				 	window.history.pushState('Arbor page', 'Arbor', window.location.href + '?'+id_name+'='+generated_record_id);
				}
				//callback functions in ADDITION to outputting the response
				if (typeof data.callback !== 'undefined') {
					$('#'+return_div_id).html(data.response);
					var args = typeof data.arguments !== 'undefined' ? data.arguments.join(", ") : '';
				    window[data.callback](args);
				}
				//return function operates INSTEAD OF outputting the response
				else if (typeof data.return_function !== 'undefined') {
					var args = typeof data.arguments !== 'undefined' ? data.arguments.join(", ") : '';
				    window[data.return_function](args);
				}

				//default: output response to return div
				else{
					$('#'+return_div_id).html(data.response);
				}
				if (typeof data.status !== 'undefined') {
					$("#status_msg").html(data.status).addClass('on');
				}
				finish_save();
			},
			error: function(){
				finish_save();
			}
		});
	}

	save_all(callback, params){
		var myself = this;
		myself.section_has_error = false;
		var $scope = (params && params.jquery) ? params : null;
		var $to_save = [];
		if (typeof myself.input_elements !== 'undefined') {
			for (const [k, v] of Object.entries(myself.input_elements)) {
				var $el = $(v);
				if ($scope && !$el.closest('.accordion').is($scope)){
					continue;
				}
				if ($el.parent().is(':hidden')){
					continue;
				}
				$to_save.push($el);
			}
		}
		if (!$to_save.length){
			if (typeof callback !== 'undefined') {
				window[callback](params);
			}
			return;
		}
		var remaining = $to_save.length;
		var on_done = function(){
			remaining--;
			if (remaining === 0 && typeof callback !== 'undefined'){
				window[callback](params);
			}
		};
		for (var i = 0; i < $to_save.length; i++){
			myself.save($to_save[i], true, on_done);
		}

	}


}