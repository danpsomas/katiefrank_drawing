<?php
/*

//	INPUT 		//////////////////////////////////////////////////////////////////////////////
$opts = array(
		'required' => TRUE,
		'label' => 'Marketing',
	);
$opts['validate'] = array(
		'type' => 'numeric',
		'min' => 5,
		'max' => 10,
	);

echo $formfield -> input('num_units', $opts);



//	SELECT 		////////////////////////////////////////////////////////////////////////////

$sale_types = array(
	'standard_sale' => 'Standard',
	'new_construction' => 'New construction',
	'lease' => 'Lease',
	'tic' => 'TIC',
	'probate' => 'Probate/trust (of any kind)',
	'short_sale' => 'Short sale',
	'reo' => 'REO/foreclosure',
);

$opts = array(
	'values' => $buy_sell_both_arr,
	'required' => TRUE,
	'label' => 'You are representing',
);

$opts['conditional'] = array(
		'mls_num' => array(//hide this if...
			'vals' => array('Off Market'),//this value = []
			'hide' => FALSE,//actually HIDE this (as opposed to just un-requiring it)
			'inverse' => FALSE,//actually SHOW this if...
			'required' => TRUE, //and then the newly shown/hidden field is required
		),
		'another_input' => array(//hide this if...
			'vals' => array('Off Market'),//this value = []
			'hide' => FALSE,//actually HIDE this (as opposed to just un-requiring it)
			'inverse' => FALSE,//actually SHOW this if...
			'required' => TRUE, //and then the newly shown/hidden field is required
		),
	);

echo $formfield -> select('representing', $opts);


//	CHECKBOX 		/////////////////////////////////////////////////////////////////////////////////


$opts = array(
  'value' => 'Yes',
  'caption' => 'Include a brochure box ($12 charge to agent)',
);
echo $formfield -> checkbox('brochure_box', $opts);



*/


class formfield{
	
	//global options. These can be set here and/or upon instantiation of the class
	var $opts = array(
		'this_classname' => 'formfield', //so we can know if we are outputting a form or a display
		'suppress_id' => FALSE,				//do not output id
		'suppress_placeholder' => FALSE,	//do not output placeholder text
		'placeholder_text' => "",			//placeholder text
		'suppress_wrapper' => FALSE, 		//do not output form field wrapper
		'wrapper_classnames' => array(),	//in addition to "nifty"
		'required' => FALSE,				//whether the item is required
		'nifty' => TRUE,					//by default, all inputs have class: nifty. use this to suppress
		'autosave' => FALSE,				//whether to auto-save the data. This will often be TRUE
		'autosave_url' => NULL,				//default save to /data/save.php (or custom url)
		'validate' => array(),				//where array = ('min' => 3, 'max' => 8, 'type' => numeric)
		'prefix' => NULL,					//an input prefix - like a dollar sign
		'prefix_right' => FALSE,			//put the "prefix" on the right (i.e., a percentage sign)
		'prefix_class' => NULL,				//add this classname to the prefix i
		'format_number' => FALSE,			//whether to format a numeric value using commas (123,456) - dollars, for example
		'format_number_precision' => 0,		//how many places after the decimal
		'long_label' => FALSE,				//if there is a very long label, put it outside the input area (not as a label like we use it normally)
		'no_label' => FALSE,				//do not display a label (handy for disabled inputs)
		'use_radio' => FALSE,				//use radio button style for select field
		'force_choice_chips' => FALSE,		//force the use of choice chips even when there are more than 3 options
		'force_select' => FALSE,			//force the use of select field even for fewer than 3 options
		'disable_typeahead'	 => FALSE,		//disable typeahead feature for SELECT elements
		'is_search' => FALSE,				//we use type=search for input elements that are search or where we want to suppress browser autofill
		'allow_lastpass' => FALSE,			//disable lastpass by default

		'dropzone' => FALSE,				//use dropzone for file uploads
		'croppable' => FALSE,				//show cropping tool after upload
		'dropzone_params' => array(			//standard dz params
			'url' => "'../uploads/upload.php'",	//this is the default upload script. feel free to change it.
			'maxFiles' => 1,
			'autoProcessQueue' => 'true',
			'addRemoveLinks' => 'true',
		),
    'dropzone_oncomplete' => NULL,		//array("console.log(data)", "somethign else()"),
    'dropzone_onremove' => NULL,		//what happens when the file is removed array("console.log(data)", "somethign else()"),
		'id' => NULL,						//input_name will be used if empty unless global 'suppress_id' is TRUE
		'size' => 40,						//default size for input box
		'cols' => 40,						//default cols for textarea
		'rows' => 2,						//default rows for textarea
		'checked' => NULL,					//default checked for radio & cbx
		'caption' => NULL,					//label for radio, cbx
		'label' => NULL,					//What the label says (input name by default)
		'classnames' => array(),			//also accepts single value
		'select_label_classnames' => array(),//also accepts single value
		'attributes' => array(),			//also accepts single value
		'data' => array(),					//data = data-xxxx="yyyy" : array('xxxx' => 'yyyy', 'aaaa' => 'bbbb')
		'pars' => array(),					//will be converted to js object for ajax
		'helper_text' => NULL,				//helper text is shown beneath the input field
		'hide_if_empty' => NULL,			//does nothing here. This is for the form output.

		'value' => '',						//value for checkbox, radio
		'values' => array(),				//values for select element
		'include_blank' => TRUE,			//include blank select elemnt
		'blank_label' => NULL,				//the label for the blank value
		'selected' => NULL,					//the selected value for a select element (superseded by $data['id])
		'multi_select' => FALSE,			//multiple selections in a select field
		'force_value' => FALSE,				//force the value to be the one in variable $value (ignore value from $data)
		'datepicker_opts' => array(			//default datepicker opts add per input field.
			'numberOfMonths' => 1,
			'showButtonPanel' => 'false',
			'dayNamesMin' =>  "[ 'S', 'M', 'T', 'W', 'T', 'F', 'S' ]",
			'onSelect' => 'function() {$(this).trigger(\'keyup\')}',
			'minDate' => +1
			),
		'allow_datepicker_entry' => FALSE,	//allow freehand entry of dates

		'indeterminate' => FALSE, 			//whether the checked checkbox will render the indeterminate marker
		'free_entry' => FALSE,					//instead of a label, produce an input box for the label/value
		'free_entry_keyup' => NULL,			//what to do when the user enters something in the checkbox free entry area

		'admin_editable' => FALSE,			//does nothing here, but for formfield_display, creates an editable formfield if access <= 5\
		'unit_opts' => array('$', '%'),	//default unit options (I don't think we will have others..)
	);
	
	var $cbx_group_open = FALSE;

	//$data = array(fieldname => value)
	var $data = array();

//we want to output js if has_select to disable clicking on headers within the select
	var $select_js_added = FALSE;
	var $unit_input_js_added = FALSE; //we only want to add this once.
	
	//$error = array('fieldname', 'field2');
	//fieldnames in the $error array will receive class="error"
	var $field_has_error = array();
	
	//$opts = array : Same as above; sets GLOBAL options. per-field options are set individually
	function __construct($data=NULL, $opts=NULL, $has_error=NULL){
		$this -> opts = array_merge($this -> opts, (array)$opts);
		$this -> data = (array)$data;
		$this -> field_has_error = (array)$has_error;
	}
	

	//$actions = array('keyup' => 'doSomething()', 'click' => 'somethingElse()');
	public function input($input_name, $opts=NULL, $actions=NULL, $value=NULL){
		$this -> set_type('input');
		$this -> reset_opts($input_name, $opts, $actions, $value);
		$this -> prepare();
		$inpt_type = $this -> input_opts['is_search'] ? 'search' : 'text';
		$inpt = "<input type=\"{$inpt_type}\" {$this -> input_name_str} {$this -> id_str} {$this -> data_str} {$this -> size_str} {$this -> class_str} {$this -> attr_str} {$this -> action_str} {$this -> input_value_str} {$this -> placeholder}  />";
		return $this -> wrapper($inpt);
	}

	//$actions = array('keyup' => 'doSomething()', 'click' => 'somethingElse()');
	public function password($input_name, $opts=NULL, $actions=NULL, $value=NULL){
		$this -> set_type('password');
		$this -> reset_opts($input_name, $opts, $actions, $value);
		$this -> prepare();
		$inpt = "<input type=\"password\" {$this -> input_name_str} {$this -> id_str} {$this -> data_str} {$this -> size_str} {$this -> class_str} {$this -> attr_str} {$this -> action_str} {$this -> input_value_str}  />";
		return $this -> wrapper($inpt);
	}
	public function hidden($input_name, $opts=NULL, $actions=NULL, $value=NULL){
		$this -> set_type('hidden');
		$this -> reset_opts($input_name, $opts, $actions, $value);
		$this -> prepare();
		$inpt = "<input type=\"hidden\" {$this -> input_name_str} {$this -> id_str} {$this -> data_str} {$this -> class_str} {$this -> attr_str} {$this -> action_str} {$this -> input_value_str}  />";
		return $inpt; // no wrapper for this.

	}
	
	public function textarea($input_name, $opts=NULL, $actions=NULL, $value=NULL){
		$this -> set_type('textarea');
		$this -> reset_opts($input_name, $opts, $actions, $value);
		$this -> prepare();
		//print_r($this);
		$inpt = "<textarea {$this -> input_name_str} {$this -> id_str} {$this -> data_str} {$this -> cols_str} {$this -> rows_str} {$this -> id_str} {$this -> class_str} {$this -> attr_str} {$this -> action_str}>{$this -> value}</textarea>";
		return $this -> wrapper($inpt);
	}


	public function unit_input($input_name, $opts=NULL, $actions=NULL, $value=NULL){
		$this -> set_type('unit_input');
		$opts['wrapper_classnames'] = (array)$opts['wrapper_classnames'];
		$opts['wrapper_classnames'][] = 'unit_input';
		$this -> reset_opts($input_name, $opts, $actions, $value);
		$this -> prepare();

		
		$this -> unit_str = '<div class="choice_chips">';
		foreach((array)$this -> input_opts['unit_opts'] AS $u){
			$hilite = FALSE !== strpos($this -> value, $u) ? 'hilite' : '';
			$hilite = count($this -> input_opts['unit_opts']) < 2 ? 'hilite' : $hilite;
			$this -> unit_str .= "<button class=\"chip {$hilite}\">{$u}</button>";
		}
		$this -> unit_str .= '</div>';
		
		if (!$this -> unit_input_js_added){
			$this -> js .= '
			unit_input_ready();
			';
			$this -> input_value_js_added = TRUE; 
		}
		$this -> input_value_js_added = TRUE; 

		$inpt = "<input type=\"text\" {$this -> input_name_str} {$this -> id_str} {$this -> data_str} {$this -> size_str} {$this -> class_str} {$this -> attr_str} {$this -> action_str} {$this -> input_value_str} {$this -> placeholder}  />";
		return $this -> wrapper($inpt);
	}


	public function radio($input_name, $opts=NULL, $actions=NULL, $value=NULL){
		$this -> set_type('radio');
		$this -> reset_opts($input_name, $opts, $actions, $value);
		$this -> prepare();
		$inpt = "<input type=\"radio\" {$this -> input_name_str} {$this -> id_str} {$this -> value_str} {$this -> checked_str} {$this -> class_str} {$this -> attr_str} {$this -> action_str}>{$this -> caption_str}";
		return $this -> wrapper($inpt);
	}


	//use these with standard checkbox function (not cbx_array)
	public function start_checkbox_group($cbx_group_name, $opts){
		if ($opts['require']){
			$opts_str .= " data-require=\"{$opts['require']}\"";
		}
		if ($opts['limit']){
			$opts_str .= " data-limit=\"{$opts['limit']}\"";
		}
		if ($opts['indeterminate']){
			$class = 'indeterminate';
		}
		$ret = "<div class=\"cbx_group {$class}\" {$opts_str}>";
		$ret .= $opts['label'] ? "<div class=\"group_title\">{$opts['label']}</div>" : '';

		$this -> cbx_group_open = TRUE;

		return $ret;
	}

	//we sometimes loop through stuff and call end cbx group at the top of the stack (before there are any cbx groups)
	public function end_checkbox_group(){
		if ($this -> cbx_group_open){
			return '</div><div class="helper group_close" style="display: none"></div>';
		}
		$this -> cbx_group_open = FALSE;
		
	}

	public function checkbox($input_name, $opts=NULL, $actions=NULL, $value=NULL){
		$this -> set_type('checkbox');
		$this -> reset_opts($input_name, $opts, $actions, $value);
		if ($opts['free_entry']){
			$this -> prepare(array('append_name' => '[checked]'));
		}
		else{
			$this -> prepare();
		}
		$this -> wrapper_class_str .= $this -> input_opts['nifty'] ? ' nifty_wrapper checkbox' : '';
		$indeterminate = $this -> input_opts['indeterminate'] ? 'indeterminate' : '';
		$unchecked_hidden = '';
		if (array_key_exists('uncheck_value', (array)$this -> input_opts)){
			$unchecked_val = htmlspecialchars((string)$this -> input_opts['uncheck_value']);
			$unchecked_hidden = "<input type=\"hidden\" {$this -> input_name_str} value=\"{$unchecked_val}\">";
		}
		$inpt = "
		<div class=\"{$this -> wrapper_class_str}\" id=\"formfield_{$this -> id}\">
	      <label>
	        {$unchecked_hidden}<input type=\"checkbox\" {$this -> data_str} {$this -> input_name_str} {$this -> id_str} {$this -> value_str} {$this -> checked_str} {$this -> class_str} {$this -> attr_str} {$this -> action_str}><span class=\"cbx_wrap\"><span class=\"check\"></span></span> {$this -> caption_str}
	      </label>";
	      $inpt .= $this -> input_opts['helper_text'] ? "<div class=\"clear\"></div><div class=\"helper_text\">{$this -> input_opts['helper_text']}</div>\n" : '';
      $inpt .= "</div>";

		//$inpt = "<input type=\"checkbox\" {$this -> input_name_str} {$this -> id_str} {$this -> value_str} {$this -> checked_str} {$this -> class_str} {$this -> attr_str} {$this -> action_str}>{$this -> caption_str}";

		return $this -> input_opts['nifty'] ? $inpt : $this -> wrapper($inpt);
	}

public function checkbox_pill($input_name, $opts=NULL, $actions=NULL, $value=NULL){
		$this -> set_type('checkbox');
		$this -> reset_opts($input_name, $opts, $actions, $value);
		$this -> prepare();
		$this -> wrapper_class_str .= $this -> input_opts['nifty'] ? ' nifty_wrapper checkbox_pill' : '';
		$this -> wrapper_class_str .= $this -> input_opts['checked'] ? ' checked' : NULL;
		$unchecked_hidden = '';
		if (array_key_exists('uncheck_value', (array)$this -> input_opts)){
			$unchecked_val = htmlspecialchars((string)$this -> input_opts['uncheck_value']);
			$unchecked_hidden = "<input type=\"hidden\" {$this -> input_name_str} value=\"{$unchecked_val}\">";
		}
		$inpt = "
		<div class=\"{$this -> wrapper_class_str}\" id=\"formfield_{$this -> id}\">
	      <label>
	        {$unchecked_hidden}<input type=\"checkbox\" {$this -> data_str} {$this -> input_name_str} {$this -> id_str} {$this -> value_str} {$this -> checked_str} {$this -> class_str} {$this -> attr_str} {$this -> action_str}><span class=\"cbx_wrap\"><span class=\"check\"></span></span> {$this -> caption_str}
	      </label>";
	      $inpt .= $this -> input_opts['helper_text'] ? "<div class=\"clear\"></div><div class=\"helper_text\">{$this -> input_opts['helper_text']}</div>\n" : '';
      $inpt .= "</div>";

		//$inpt = "<input type=\"checkbox\" {$this -> input_name_str} {$this -> id_str} {$this -> value_str} {$this -> checked_str} {$this -> class_str} {$this -> attr_str} {$this -> action_str}>{$this -> caption_str}";

		return $this -> input_opts['nifty'] ? $inpt : $this -> wrapper($inpt);
	}

	
	public function file_upload($input_name, $opts=NULL, $actions=NULL){
		if ($opts['dropzone']){
			$this -> set_type('dropzone');
			$this -> reset_opts($input_name, $opts, $actions, $value);
			$this -> prepare();
			$opts['helper_text'] = $opts['helper_text'] ? "<span class=\"fad fa-upload\"></span> {$opts['helper_text']}" : '<span class="fad fa-upload"  style="--fa-primary-color: #e18c5c;  --fa-secondary-color: #7dcade;"></span> Upload here';
			$this -> input_opts['helper_text'] = NULL;
			$this -> input_opts['long_label'] = TRUE;
			$inpt = "<div class=\"dropzone\" id=\"{$input_name}\"><div class=\"dz-message\">{$opts['helper_text']}</div></div><input type=\"hidden\" name=\"{$input_name}\">";
			$this -> dz_output();
			return $this -> wrapper($inpt);
		}
		else{
			$this -> set_type('file_upload');
			if ($this -> opts['nifty'] || $opts['nifty']){
				$opts['wrapper_classnames'] = (array)$opts['wrapper_classnames'];
				array_push($opts['wrapper_classnames'], 'file_upload');
			}
			$this -> reset_opts($input_name, $opts, $actions, $value);
			$this -> prepare();
			$inpt = "<input type=\"file\" {$this -> input_name_str} {$this -> id_str} {$this -> class_str} {$this -> attr_str} {$this -> action_str}>";
		return $this -> wrapper($inpt);

		}
	}

	public function select($input_name, $opts=NULL, $actions=NULL, $selected=NULL){
		$this -> set_type('select');
		$this -> js .= '
			$.each($(".select_header"), function (){
				$(this).parent().addClass("disabled");
				})
		';
		$this -> select_js_added = TRUE;
		$this -> reset_opts($input_name, $opts, $actions, $selected);

		if ($this -> input_opts['nifty']){
			$this -> classnames[] = 'nifty_select_value';
			if ($this -> input_opts['disable_typeahead']){
				$this -> input_opts['select_label_classnames'][] = 'disable_typeahead';
			}
			$this -> input_opts['select_label_classnames'] = (array)$this -> input_opts['select_label_classnames'];
			$this -> input_opts['select_label_classnames'][] = 'nifty';
			$this -> input_opts['select_label_classnames'][] = 'nifty_select_label';
			$this -> prepare();

			//three or fewer options? use choice chips
			if (!$this -> input_opts['force_select'] && (count($this -> input_opts['values']) <= 3 || $this -> input_opts['use_radio'] || $this -> input_opts['force_choice_chips'])){
				$this -> wrapper_class_str .= " choice_chips_wrapper";
				$this -> wrapper_class_str .= $this -> input_opts['use_radio'] ? ' radio' : '';
				$inpt = "<input {$this -> action_str} {$this -> input_name_str} {$this -> id_str} {$this -> value_str} {$this -> data_str} {$this -> class_str} {$this -> attr_str} {$this -> multi_select} type=\"hidden\">
					{$this -> choice_chips_str}
				";
			}
			else{
				$inpt = "<input {$this -> action_str} {$this -> input_name_str} {$this -> id_str} {$this -> value_str} {$this -> data_str} {$this -> class_str} {$this -> attr_str} {$this -> multi_select} type=\"hidden\">
				<input {$this -> select_label_str} {$this -> attr_str} {$this -> select_label_classnames_str} data-lpignore=\"true\" type=\"text\" id=\"{$input_name}_label\" ></input>

				{$this -> select_opts_str}";
			}
		}
		else{//a plain selectbox
			$this -> prepare();
			$inpt = "<select {$this -> input_name_str} {$this -> id_str} {$this -> class_str} {$this -> attr_str} {$this -> action_str} {$this -> multi_select}>
					{$this -> select_opts_str}
			</select>";
		}

		
		return $this -> wrapper($inpt);
	}
//this is for adding variables AFTER the form is submitted. It does not appear as part of the form
	public function additional($input_name, $opts=NULL, $actions=NULL, $value=NULL){
		return '';
	}











/***************************************************


	PRIVATE FUNCTIONS								


***************************************************/

	//reset the input options for each input call
	//reset all input-specific values
	private function reset_opts($input_name, $opts, $actions, $value){
		$this -> input_name_arr_str = str_replace(array(']', '['), '', $input_name);
		$this -> input_name = $input_name;
		$this -> input_opts = array_merge($this -> opts, (array)$opts);
		$this -> classnames = (array)$opts['classnames'];
		$this -> attributes = $opts['attributes'] ? (array)$opts['attributes'] : $this -> opts['attributes'];
		$this -> actions = (array)$actions;
		$this -> id = $opts['id'] ? $opts['id'] : $input_name;

		if ($this -> is_type('checkbox')){
			$this -> value = isset($value) ? $value : $this -> input_opts['value'];
		}
		else if ($this -> is_type('select')){
			$this -> value = isset($value) ? $value : $this -> input_opts['selected'];
		}
		else{
			$this -> value = isset($value) && FALSE !== $opts['force_value'] ? $value : $this -> data[$input_name];
		}
		if ($opts['force_value']){
			$this -> input_value = $value;
		}
		else{
			$this -> input_value = isset($this -> data[$input_name]) ? $this -> data[$input_name] : $value; //input fields have their values set by $data
		}
		$this -> unit_str = '';
		
		$this -> values = (isset($opts['values']) && is_array($opts['values'])) ? $opts['values'] : array();
		$this -> check_error();
	}
	
	//check if the field has an error and add "error" to classnames
	private function check_error(){
		if (in_array($this -> input_name, $this -> field_has_error)){
			$this -> input_opts['wrapper_classnames'][] = 'alert';
		}
	}
	
	//prepare all the various pieces
	private function prepare($special=NULL){
		$this -> data_str = '';
		$this -> input_opts['wrapper_classnames'] = (array)$this -> input_opts['wrapper_classnames'];
		$this -> input_name .= $this -> input_opts['multi_select'] ? "[]" : NULL;
		$this -> label = $this -> input_opts['label'] ? $this -> input_opts['label'] : $this -> input_name;
		$this -> input_name_str = " name=\"{$this -> input_name}{$special['append_name']}\"";
		$this -> id_str = $this -> suppress_id ? '' : " id=\"".str_replace(array('[', ']'), '__', $this -> id)."\"";
		if ($this -> input_opts['validate']){
			$this -> input_opts['data']['validate'] = 1;
			foreach((array)$this -> input_opts['validate'] AS $k => $v){
				$this -> input_opts['data']['validate'.$k] = $v;
			}
		}
		if ($this -> input_opts['required']){
			$this -> input_opts['data']['required'] = 1;
			$this -> input_opts['wrapper_classnames'][] = 'required';
		}
		if (is_array($this -> input_opts['pars']) && count($this -> input_opts['pars']) > 0){
			$this -> input_opts['data']['pars'] = htmlentities(json_encode($this -> input_opts['pars']));
		}
		if ($this -> input_opts['nifty']){
			$this -> classnames[] = 'nifty';
		}
		if  ($this -> input_opts['autosave']){
			$this -> classnames[] = 'autosave';
		}
		if  ($this -> input_opts['autosave_url']){
			$this -> input_opts['data']['autosaveurl'] = $this -> input_opts['autosave_url'];
		}
		if (!$this -> input_opts['allow_lastpass']){
			$this -> input_opts['data']['lpignore'] = 'true';
		}
		if ($this -> input_opts['data']){
			foreach($this -> input_opts['data'] AS $k => $v){
				$this -> data_str.= " data-{$k}=\"{$v}\"";
			}
		}

		if (in_array('datefield', (array)$this -> input_opts['classnames'])){
			$this -> js .= "
    $('#".str_replace(array('[', ']'), '__', $this -> id)."').datepicker({";
		foreach($this -> input_opts['datepicker_opts'] AS $k => $v){
			$this -> js .= "{$k} : {$v},\n\t\t";
		}
$this -> js .= "
	});
";
		if ($this -> input_opts['allow_datepicker_entry']){
			//nothing
		}
		else{
			$this -> attributes['readonly'] = 'readonly';
		}
		}
		$this -> class_str = ' class="'.implode(" ", $this -> classnames).'"';
		$this -> size_str = $this -> input_opts['size'] ?  " size=\"{$this -> input_opts['size']}\"" : NULL;
		$this -> cols_str = $this -> input_opts['cols'] ?  " cols=\"{$this -> input_opts['cols']}\"" : NULL;
		$this -> rows_str = $this -> input_opts['rows'] ?  " rows=\"{$this -> input_opts['rows']}\"" : NULL;


		if (is_array($this -> data[$this -> input_name_arr_str])){
			$this -> checked_str = $this -> input_opts['checked'] || in_array($this -> value, $this -> data[$this -> input_name_arr_str]) ? " checked=\"checked\"" : NULL;
		}
		else{
			$this -> checked_str = $this -> input_opts['checked'] || isset($this -> data[$this -> input_name]) && $this -> value == $this -> data[$this -> input_name] ? " checked=\"checked\"" : NULL;
		}
		if ($this -> is_type('textarea')){
			$this -> value = strlen($this -> value) < 1 && in_array('ckeditor', $this -> classnames) ? ' ' : $this -> value;
		}



		if ($this -> input_opts['free_entry']){
			$fe_action = " onkeyup=\"check_if_not_empty(this); {$this -> input_opts['free_entry_keyup']}\"";

			$this -> caption_str = "<input name=\"{$this -> input_name}[user_label]\" type=\"text\" class=\"cbx_text {$this -> input_opts['free_entry_class']}\" id=\"{$this -> input_opts['free_entry_id']}\" placeholder=\"{$this -> input_opts['placeholder_text']}\" {$fe_action}>";
		}
		else{
			$this -> caption_str = strlen($this -> input_opts['caption']) > 0 ? $this -> input_opts['caption'] : $this -> input_name;
		}
		

		if ($this -> input_opts['no_label']){
			$this -> caption_str = '';
		}
		$this -> value_str = " value=\"{$this -> value}\"";
		//echo $this -> value_str;
		if ($this -> input_opts['format_number'] && strlen($this -> input_value) > 0){
			$this -> input_value_str = ' value="'.htmlspecialchars(number_format((float)str_replace(',', '', $this -> input_value), $this -> input_opts['format_number_precision'])).'"';
			$this -> actions['keyup'] = "format_number(this, {$this -> input_opts['format_number_precision']})";
		}
		else if ($this -> input_opts['format_number']){
			$this -> input_value_str = ' value=""';
			$this -> actions['keyup'] = "format_number(this, {$this -> input_opts['format_number_precision']})";
		}
		else{
			$this -> input_value_str = ' value="'.htmlspecialchars($this -> input_value).'"';
		}
		

		if (strlen($this -> input_opts['placeholder_text']) > 0 && !$this -> suppress_placeholder){
			$this -> placeholder =  " placeholder=\"{$this -> input_opts['placeholder_text']}\"";
			$this -> input_opts['wrapper_classnames'][] = 'perma_mini';
		}
		else{
			$this -> placeholder = '';
		}


		$this -> multi_select = $this -> input_opts['multi_select'] ? ' multiple="multiple"' : NULL;
		$this -> wrapper_class_str = implode(' ',(array)$this -> input_opts['wrapper_classnames'] );
		$this -> select_opts_str = NULL;
		$this -> choice_chips_str = NULL;


		
		//print_r($this -> input_opts);
		




		//conditional display
		if ($this -> input_opts['conditional']){
			foreach($this -> input_opts['conditional'] AS $col => $pars){
				$this -> js .= "new conditional('{$this -> input_name}', '{$col}', ".json_encode((object)$pars).") \n";
			}
		}
	//conditional display -- secondary - because $this -> input_opts['conditional'] can only have one key per field
		if ($this -> input_opts['conditional_remove_opts']){
			foreach($this -> input_opts['conditional_remove_opts'] AS $col => $pars){
				$this -> js .= "new conditional('{$this -> input_name}', '{$col}', ".json_encode((object)$pars).") \n";
			}
		}

		if ($this ->  is_type('select')){
			$this -> value_str = " value=\"{$this -> input_opts['blank_value']}\"";
			$this -> select_label_str = " value=\"{$this -> input_opts['blank_value']}\"";
			$matchval = $this -> data[$this -> input_name] ? $this -> data[$this -> input_name] : $this -> value;

			//we want to create a "nifty" select element.
			if ($this -> input_opts['nifty']){
				$select_values = is_array($this -> values) ? $this -> values : array();

				$this -> select_opts_str = "<ul class=\"options\" style=\"display: none\">";
				if ($this -> input_opts['include_blank']){
					$this -> select_opts_str .= "<li data-value=\"\">{$this -> input_opts['blank_value']}</li>";
				}
				
				foreach($select_values AS $k => $v){
					$hilite = NULL;
					if ($k == $matchval){
						$hilite = 'hilite';
						$this -> value_str = " value=\"{$k}\"";
						$this -> select_label_str = " value=\"{$v}\"";
					}
					$this -> select_opts_str .=  "<li class=\"{$hilite}\" data-value=\"{$k}\">{$v}</li>";
				}
				if (strlen($this -> select_label_str . $this -> value_str) < 1 && !$this -> input_opts['include_blank'] && count($select_values) > 0){
					reset($select_values);
					$x = current($select_values);
					$k = key($select_values);
					$this -> value_str = " value=\"{$k}\"";
					$this -> select_label_str = " value=\"{$x}\"";
				}

				$this -> select_opts_str .= "</ul>";


				//also set up a choice chips version
				reset($select_values);
				$this -> choice_chips_str = "<div class=\"choice_chips\">";
				foreach($select_values AS $k => $v){
					$hilite = $k == $matchval ? 'hilite' : NULL;
					$this -> choice_chips_str .=  "<button class=\"chip {$hilite} btn_ripple\" data-value=\"{$k}\">{$v}</button>";
				}
				$this -> choice_chips_str .= "</div>";



			}

			else{// or a standard select field
				if ($this -> input_opts['include_blank']){
					$this -> select_opts_str = "<option value=\"\">{$this -> input_opts['blank_value']}</option>";
				}
				if ($this -> values && is_array($this -> values)){
				
					if (is_array($matchval)){//multi-select
						foreach($this -> values AS $k => $v){
							$this -> select_opts_str .= in_array($k, $matchval) ? "<option value=\"{$k}\" selected=\"selected\">{$v}</option>" : "<option value=\"{$k}\">{$v}</option>";
						}
					}
					else{
						foreach($this -> values AS $k => $v){
							$this -> select_opts_str .= $k == $matchval ? "<option value=\"{$k}\" selected=\"selected\">{$v}</option>" : "<option value=\"{$k}\">{$v}</option>";
						}
					}
				}
			}


		}

		$this -> select_label_classnames_str = 'class="'.implode(' ', (array)$this -> input_opts['select_label_classnames']).'"';


		
		$this -> action_str = "";
		
		if (is_array($this -> actions) && count($this -> actions) > 0){
			foreach($this -> actions AS $on => $act){
				//we have so much else going on, this way gets obliterated
				//$this -> action_str .= " on{$on}=\"{$act}\"";

				//so attach the handler later
				$this -> js .= "
	\$('#".str_replace(array('[', ']'), '__', $this -> id)."').{$on}(function(){ {$act} });
				";
			}
		}
		


		$this -> attr_str = "";
		if (is_array($this -> attributes) && count($this -> attributes) > 0){
			foreach($this -> attributes AS $n => $v){
				$this -> attr_str .= " {$n}=\"{$v}\"";
			}
		}
	}

	function set_type($t){
		$this -> input_type = $t;
	}
	function is_type($t){
		return $t == $this -> input_type;
	}

	function dz_output(){
		//$proxy_name used to be $this -> input_name but $this -> input_name was sometimes part of an array[][]
		//so it broke the js. 
		$proxy_name = 'rand_'.rand(1000, 100000);

		$this -> js .= "\n\nvar dz_{$proxy_name} = {};\n";
		$this -> js .= "
		$(\"#".str_replace(array('[', ']'), array('\\\[', '\\\]'), $this -> input_name)."\").dropzone({";
		foreach($this -> input_opts['dropzone_params'] AS $k => $v){
			$this -> js .= "\n\t\t{$k}: {$v},";
		}

		$this -> js .="
		  success: function(file, response){
		  	//console.log(response)
		    var data = JSON.parse(response);
		    console.log(data);
		    dz_{$proxy_name}[data.index] = data;
		    $('input[name=\"{$this -> input_name}\"]').val(JSON.stringify(dz_{$proxy_name}));";

		 if ($this -> input_opts['dropzone_oncomplete']){
		 	foreach($this -> input_opts['dropzone_oncomplete'] AS $do_this){
		 		$this -> js .= "\n{$do_this};\n";
		 	}
		 }
		 $this -> js .= $this -> input_opts['croppable'] ? "\n show_cropping_tool(data.index);\n" : NULL;
		$this -> js .= "
		  },
		removedfile: function(file){
		    //this is what this function is replacing (so do it here )
		    if (file.previewElement != null && file.previewElement.parentNode != null) {
		          file.previewElement.parentNode.removeChild(file.previewElement);
		       }
		    var dp_filename = file.name;
		    var i;
		    $.each(dz_{$proxy_name}, function(){
		      if (this.filename == dp_filename){
		        i = this.index;
		      }
		    delete(dz_{$proxy_name}[i]);
		    $('input[name=\"{$this -> input_name}\"]').val(JSON.stringify(dz_{$proxy_name}));";


		 if ($this -> input_opts['dropzone_onremove']){
		 	foreach($this -> input_opts['dropzone_onremove'] AS $do_this){
		 		$this -> js .= "\n{$do_this};\n";
		 	}
		 }
		$this -> js .= "

		    });
		  }
		});";
		//echo $this -> js;
	}

	
	private function wrapper($inpt){
		$prefix = $this -> input_opts['prefix'] ? "<i class=\"{$this -> input_opts['prefix_class']}\">{$this -> input_opts['prefix']}</i>" : '';
		$this -> label = $this -> input_opts['long_label'] && $this -> input_opts['required'] ? "*{$this -> label}" : $this -> label;
		$this -> wrapper_class_str .= $this -> input_opts['prefix_right'] ? ' right' : '';
		$this -> wrapper_class_str .= $this -> input_opts['nifty'] ? ' nifty_wrapper' : '';
		$this -> wrapper_class_str .= $this -> input_opts['no_label'] ? ' no_label' : '';
		$return = $this -> opts['suppress_wrapper'] ? $inpt.$prefix : 
			"<div class=\"{$this -> wrapper_class_str}\" id=\"formfield_".str_replace(array('[', ']'), '__', $this -> id)."\">";
		$return .= $this -> input_opts['long_label'] ? "<div class=\"long_label\">{$this -> label}</div>" : '';
		$return .= $inpt . $prefix;
		$return .= $this -> unit_str;
		$return .= $this -> input_opts['long_label'] || $this -> input_opts['no_label'] ? "" : "<label for=\"{$this -> input_name}\" class=\"input_label\">{$this -> label}</label>";
		$return .= $this -> input_opts['helper_text'] ? "<div class=\"helper_text\">{$this -> input_opts['helper_text']}</div>\n" : '';
		$return .= "<div class=\"helper\" style=\"display: none\"></div>
		</div>";

		return $return;
	}
}
?>