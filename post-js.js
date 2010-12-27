/*
javascript:void(eval((function() {var s=document.createElement('script');s.type='text/javascript';s.src="http://ag.mydevstation.com/vm.js";document.body.appendChild(s)})()))
*/

//window.addEventListener('load', init_components, false);
setTimeout('init_components()', 500);

var co, ci, sb;

function init_components() {
	co = document.createElement('textarea');
	document.body.appendChild(co);
	with (co.style) {
		width = "800px";
		height = "300px";
		border = "2px solid black";
	}
	co.setAttribute('id', 'console');
	document.body.appendChild(document.createElement('br'));

	ci = document.createElement('input');
	with (ci.style) { width = "800px"; }
	ci.setAttribute('id', 'console_input');
	ci.setAttribute('type', 'text');
	document.body.appendChild(ci);
	ci.addEventListener('keypress', handle_input, false);
	ci.focus();

	sb = document.createElement('div');
	sb.setAttribute('id', 'sandbox');
	with (sb.style) {
		width = "800px";
		height = "300px";
		border = "1px solid black";
		background = "#ccc";
	}
	sb.innerHTML = 'sandbox';
	document.body.appendChild(document.createElement('br'));
	document.body.appendChild(document.createElement('br'));
	document.body.appendChild(sb);
	
	load_all_words();
}

var s = [];  // the stack
var r = [];  // the reserve stack

var qd = 0;  // how deep into a quotation are we?

function console_write(str) { co.innerHTML += str; co.scrollTop = co.scrollHeight; }
function console_writeln(str) { co.innerHTML += str + "\n"; co.scrollTop = co.scrollHeight; }
function spacer() { console_writeln('---'); co.scrollTop = co.scrollHeight; }
function console_status(str) { console_writeln(str); }

function tokenize_stream(str)
{
	var whitespace = true;
	var is_string = false;
	
	var tokens = [];
	var token = '';
	for (var i=0; i<str.length; i++) {
		if (is_string) {
			if (str[i] == '"') {
				is_string = false;
				tokens.push(token);
				token = '';
				continue;
			} else {
				token += str[i];
				continue;
			}
		} else {
			if (whitespace  && (str[i]==' ' || str[i]=="\n")) continue;
			if (whitespace  && str[i] != ' ') {
				// beginning of a new token
				whitespace = false;
				if (str[i] == '"') {
					is_string = true;
					token += str[i];
					continue;
				} else {
					token += str[i];
					continue;
				}
			}
			if (!whitespace && (str[i] == ' ' || str[i]=="\n") ) {
				if (token.length > 0) {
					tokens.push(token);
					token = '';
				}
				whitespace = true;
				continue;
			}
			if (!whitespace && str[i] != ' ') {
				token += str[i];
				continue;
			}
		}
	}
	
	if (token.length > 0 && token[0] != '"') tokens.push(token);
	
	return tokens;
}

function is_numeric(num) { return !isNaN(num); }
function is_string(tok) { return tok[0] == '"'; }
function is_element(tok) { return tok[0] == '#'; }
		
function verify_stack(str)
{
	var verify_list = str.split(' ');
	if (s.length < verify_list.length) {
		console_status('stack underflow');
		valid_execution = false;
		return false;
	}
	var is_valid = true;
	for (var i=0; i<verify_list.length; i++) {
		var sp = s.length - verify_list.length + i;
		var look_for = verify_list[i];
		var cur_type = s[sp].type;
		
		if (look_for=='numeric' && !(cur_type=='fixnum' || cur_type=='float')) is_valid = false;
		else if (look_for!='any' && look_for!='numeric' && cur_type != look_for) is_valid = false;
	}
	
	if (!is_valid) {
		console_status('invalid stack argument types');
		valid_execution = false;
	}
	return is_valid;
}

// stack object holds the value
function stackItem(type, value) { this.value=value; this.type=type; }

function stack_item_to_string(si)
{
	if (si.type == 'quotation') {
		var items = [];
		for (var i=0; i<si.value.length; ++i) {
			console.log(si.value[i]);
			items.push(stack_item_to_string(si.value[i]));
		}
		return '[ ' + items.join(' ') + ' ]';
	} else if (si.type == 'string') {
		//console.log(si.value);
		return '"' + si.value + '"';
		//alert(si.value);
		 //return si.value + '(' + si.type +')';
		//return si.value.substring(1);
	} else {
		return si.value;
	}
}

var quotations = [];
quotations[0] = [];
var valid_execution = true;

var words = [];  // user defined words
var current_word = [];
var current_word_name = '';
var effects = [];

var word_definition = false;
var the_word = false;
var the_effect = false;
var effect = [];

function parse_token(tok)
{
	valid_execution = true;
	var sp = s.length-1;
	
	//if (words[tok]) console_writeln('word found');
	
	if (qd>0 && tok!='[' && tok !=']') {
		if (token_type(tok)=='string')
			var si = new stackItem('string', tok.substring(1));
		else 
			var si = new stackItem(token_type(tok), tok);
		quotations[qd].push(si);
		return;
	}
	
	if (tok != ';') {
		if (words[tok]) {
			
			s.push(new stackItem('quotation', words[tok]));
			parse_token('call');
			return;
		}

		if (word_definition) {
			if (the_word) {
				the_word = false;
				current_word_name = tok;
				current_word = [];
				return;
			} else if (the_effect && tok != ')') {
				effect.push(tok);
				return;
			}
			
			if (tok == '(') {
				the_effect = true;
				effect = [];
				return;
			}
			
			if (tok == ')') {
				the_effect = false;
				effects[current_word_name] = the_effect;
				return;
			}

			if (token_type(tok)=='string')
				var si = new stackItem('string', tok.substring(1));
			else 
				var si = new stackItem(token_type(tok), tok);
			current_word.push(si);
			return;
		}
	}
	
	switch (tok) {
		case 'append':
			if (verify_stack('string string')) {
				var temp1 = s.pop().value;
				var temp2 = s.pop().value;
				s.push(new stackItem('string', temp2+''+temp1+''));
			}
			break;
			
		case 'appendChild':
			if (verify_stack('element element')) {
				var child = s.pop().value;
				var parent = s.pop().value;
				parent.appendChild(child);
			}
			break;
			
		case 'call':
			if (verify_stack('quotation')) {
				var q = s.pop().value;
				for (var i=0; i<q.length; ++i) {
					if (q[i].type == "string") parse_token('"' + q[i].value);
					else parse_token(q[i].value);
				}
			}
			break;
			
		case 'clear':
			s = [];
			break;
			
		case 'clone':
			if (verify_stack('any')) {
				var temp = s[sp];
				
				if (temp.type=='quotation')
					s.push(new stackItem(temp.type, temp.value.concat()));
				else
					s.push(new stackItem(temp.type, temp.value));
			}
			break;
			
		case 'cls':
			co.innerHTML = '';
			break;
			
		case 'createElement':
			if (verify_stack('string')) {
				s[sp] = new stackItem('element', document.createElement(s[sp].value));
			}
			break;
			
		case 'drop':
			if (verify_stack('any')) s.pop();
			break;
			
		case 'dup':
			if (verify_stack('any')) {
				s.push(new stackItem(s[sp].type, s[sp].value));
			}
			break;
			
		case 'help':
			open('help.html', 'help');
			break;
			
		case 'each':
			if (verify_stack('quotation quotation')) {
				var q = s.pop();
				var arr = s.pop().value;
				for (var i=0; i<arr.length; i++) {
					s.push(arr[i]);
					s.push(q);
					parse_token('call');
				}
			}
			break;
					
		case 'false':
			s.push(new stackItem('boolean', false));
			break;
			
		case 'if':
			if (verify_stack('boolean quotation quotation')) {
				var falseQ = s.pop();
				var trueQ = s.pop();
				var test = s.pop().value;
				var which = test ? trueQ : falseQ;
				s.push(which);
				parse_token('call');
			}
			break;
			
		case 'map':
			if (verify_stack('quotation quotation')) {
				var q = s.pop();
				var arr = s[sp-1].value;
				for (var i=0; i<arr.length; i++) {
					s.push(new stackItem(arr[i].type, arr[i].value));
					s.push(q);
					parse_token('call');
					arr[i] = s.pop();
				}
			}
			break;
			
		case 'num>string':
			if (verify_stack('numeric')) {
				var temp = s.pop();
				temp.type = 'string';
				temp.value += '';
				s.push(temp);
			}
			break;
						
		case 'over':
			if (verify_stack('any any')) {
				var temp = s[sp-1];
				if (temp.type=='float' || temp.type=='fixnum') {
					// copy by value
					s.push(new stackItem(temp.type, temp.value));
				} else {
					// copy by reference
					s.push(s[sp-1]);
					console.log('by ref');
				}
			}
			break;
			
		case 'print':
			if (verify_stack('string')) {
				console_writeln(s.pop().value);
			}
			break;
			
		case 'save-word':
			if (verify_stack('string')) {
				var w = s.pop().value;
				if (words[w]) {
					save_word(w);
				} else {
					console_status('word does not exist');
				}
			}
			break;
			
		case 'sq':
			if (verify_stack('numeric')) {
				var temp = s.pop();
				var type = temp.type;
				s.push(new stackItem(type, temp.value*temp.value));
			}
			break;
			
		case 'string>fixnum':
			if (verify_stack('string')) {
				var temp = s[sp];
				if (is_numeric(temp.value)) {
					temp.type = 'fixnum';
					temp.value = parseInt(temp.value);
					s[sp] = temp;
				} else {
					console_status('invalid stack argument (not a string)');
				}
			}
			break;
			
		case 'string>num':
			if (verify_stack('string')) {
				var temp = s[sp];
				if (is_numeric(temp.value)) {
					temp.value = parseFloat(temp.value);
					temp.type = parseInt(temp.value)==temp.value ? 'fixnum' : 'float';
					s[sp] = temp;
				} else {
					console_status('invalid stack argument (not a string)');
				}
			}
			break;
			
		case 'sum':
			if (verify_stack('quotation')) {
				var arr = s[sp].value;
				var is_valid = true;
				for (var i=0; i<arr.length; i++) {
					if (!is_numeric(arr[i].value)) {
						is_valid = false;
						break;
					}
				}
				if (!is_valid) {
					console_status('all items in quotation must be numeric to perform a sum');
					break;
				}
				var sum = 0;
				for (var i=0; i<arr.length; i++) {
					sum += parseFloat(arr[i].value);
				}
				s[sp] = new stackItem(token_type(sum), sum);
			}
			break;
			
		case 'swap':
			if (verify_stack('any any')) {
				var v1 = s.pop();
				var v2 = s.pop();
				s.push(v1);
				s.push(v2);
			}
			break;
			
		case 'reverse':
			if (verify_stack('quotation')) {
				s[sp].value.reverse();
			}
			break;
			
		case 'times':
			if (verify_stack('fixnum quotation')) {
				var q = s.pop();
				var n = s.pop().value;
				for (var i=0; i<n; i++) {
					s.push(q);
					parse_token('call');
					if (!valid_execution) break;
				}
			}
			break;
			
		case 'true':
			s.push(new stackItem('boolean', true));
			break;
			
		case 'type':
			if (verify_stack('any')) {
				console_status(s.pop().type);
			}
			break;
			
		case 'undefine-word':
			if (verify_stack('string')) {
				var w = s.pop().value;
				if (words[w]) {
					delete words[w];
					console_writeln('word ' + w + ' removed from vocabulary');
				} else {
					console_writeln('word ' + w + ' does not exist');
				}
			}
			break;
		
		case 'while':
			if (verify_stack('quotation quotation quotation')) {
				var i = 0;
				var tailQ = s.pop();
				var loopQ = s.pop();
				var condQ = s.pop();
				var flag = true;
				do {
					s.push(condQ);
					parse_token('call');
					if (s.pop().value) {
						s.push(loopQ);
						parse_token('call');
					} else {
						flag = false;
					}
					if (i++>8) break;
				} while (flag);
				//s.push(tailQ);
				//parse_token('call');
			}
			break;
			
		case '>>@':
			// ( element attr value -- element )
			if (verify_stack('element string string')) {
				s[sp-2].value.setAttribute(s[sp-1].value, s[sp].value);
				s.length-=2;
			}
			break;
		
		case '>>innerHTML':
			if (verify_stack('element string')) {
				var str = s.pop().value;
				var e = s.pop().value;
				e.innerHTML = str;
			}
			break;
			
		case 'innerHTML>>':
			if (verify_stack('element')) {
				var e = s.pop().value;
				s.push(new stackItem('string', e.innerHTML));
			}
			break;
			
		case '>>style':
			// ( element style_attr value -- element )
			if (verify_stack('element string string')) {
				s[sp-2].value.style[s[sp-1].value] =  s[sp].value;
				s.length-=3;
			}
			break;
			
		case '#':
			if (verify_stack('string')) {
				s[sp] = new stackItem('element', document.getElementById(s[sp].value));
			}
			break;
			
		case '=':
			if (verify_stack('any any')) {
				var s1 = s.pop().value;
				var s2 = s.pop().value;
				s.push(new stackItem('boolean', s1==s2));
			}
			break;
			
		case '!=':
			if (verify_stack('any any')) {
				var s1 = s.pop().value;
				var s2 = s.pop().value;
				s.push(new stackItem('boolean', s1!=s2));
			}
			break;
			
		case '>':
			if (verify_stack('any any')) {
				var s1 = s.pop().value;
				var s2 = s.pop().value;
				s.push(new stackItem('boolean', s2>s1));
			}
			break;
			
		case '>=':
			if (verify_stack('any any')) {
				var s1 = s.pop().value;
				var s2 = s.pop().value;
				s.push(new stackItem('boolean', s2>=s1));
			}
			break;
			
		case '<':
			if (verify_stack('any any')) {
				var s1 = s.pop().value;
				var s2 = s.pop().value;
				s.push(new stackItem('boolean', s2<s1));
			}
			break;
			
		case '<=':
			if (verify_stack('any any')) {
				var s1 = s.pop().value;
				var s2 = s.pop().value;
				s.push(new stackItem('boolean', s2<=s1));
			}
			break;
			
		case '+':
			if (verify_stack('numeric numeric')) {
				s[sp-1].type = (s[sp-1].type=='fixnum' && s[sp].type=='fixnum') ? 'fixnum' : 'float';
				s[sp-1].value = s[sp-1].value + s[sp].value;
				s.length = sp;
			}
			break;
			
		case '-':
			if (verify_stack('numeric numeric')) {
				s[sp-1].type = (s[sp-1].type=='fixnum' && s[sp].type=='fixnum') ? 'fixnum' : 'float';
				s[sp-1].value = s[sp-1].value - s[sp].value;
				s.length = sp;
			}
			break;
			
		case '*':
			if (verify_stack('numeric numeric')) {
				s[sp-1].type = (s[sp-1].type=='fixnum' && s[sp].type=='fixnum') ? 'fixnum' : 'float';
				s[sp-1].value = s[sp-1].value * s[sp].value;
				s.length = sp;
			}
			break;
			
		case '/':
			if (verify_stack('numeric numeric')) {
				var temp = s[sp-1].value / s[sp].value;
				s[sp-1].value = temp;
				s[sp-1].type = temp==parseInt(temp) ? 'fixnum' : 'float';
				s.length = sp;
			}
			break;
			
		case 'mod':
			if (verify_stack('numeric numeric')) {
				s[sp-1].value = s[sp-1].value % s[sp].value;
				s.length = sp;
			}
			break;
			
		case '.':
			if (verify_stack('any')) {
				console_writeln(stack_item_to_string(s.pop()));
				//spacer();
			}
			break;
			
		case '.s':
			// print the stack (keeping the stack)
			for (var i=0; i<s.length; i++) {
				console_writeln((i+1)+": " + stack_item_to_string(s[i]));
			}
			//spacer();
			break;
			
		case '>r':
			if (verify_stack('any')) r.push(s.pop());
			break;
			
		case 'r>':
			if (r.length > 0) s.push(r.pop());
			break;
			
		case '[':
			qd++;
			quotations[qd] = [];
			break;
			
		case ']':
			if (qd>0) {
				quotations[qd-1].push(new stackItem('quotation', quotations[qd]));
				qd--;
			} else {
				console_status('] is invalid because there is no quotation to close');
			}
			if (qd==0) {
				s.push(new stackItem('quotation', quotations[1]));
			}
			break;
			
		case ':':
			// begin a word definition
			word_definition = true;
			the_word = true;  // the next word is the name of the word
			break;
		
		case ';':
			// end a word definition
			word_definition = false;
			words[current_word_name] = current_word;
			break;
			
		default:
			var type = token_type(tok);
			switch(type) {
				case 'element':
					s.push(new stackItem('element', document.getElementById(tok.substring(1))));
					break;
				case 'fixnum':
					s.push(new stackItem('fixnum', parseInt(tok)));
					break;
				case 'float':
					s.push(new stackItem('float', parseFloat(tok)));
					break;
				case 'string':
					s.push(new stackItem('string', tok.substring(1)));
					break;
				case 'invalid':
					console_status('invalid: can not push "' + tok + '" to the stack');
					break;
			}	
	}
}

function token_type(tok)
{
	if (is_numeric(tok)) return (parseInt(tok)==tok ? 'fixnum' : 'float');
	if (is_string(tok)) return 'string';
	if (is_element(tok)) return 'element';
	return 'invalid';
}

function parse_input(str)
{
	var show_output = arguments.length==2 ? arguments[1] : true;
	// parse stream
	var tokens = tokenize_stream(str);
	
	if (show_output) console_writeln('>>> ' + str);
	// parse the tokens
	for (var i=0; i<tokens.length; ++i)
		parse_token(tokens[i]);
}

function handle_input(e)
{
	if (e.keyCode == 13) {
		parse_input(ci.value);
		ci.value = '';
	}
}

// ==========
// = Banner =
// ==========
// =======================================
// = NEED TO IMPLEMENT undefined-word-db =
// =======================================
// ==========
// = Banner =
// ==========

function getRequestObject()
{
	var request = false;
	if (window.XMLHttpRequest)
		request = new window.XMLHttpRequest();
	else if(window.ActiveXObject)
		request = new window.ActiveXObject('Microsoft.XMLHTTP');
	return request;
}

function api(cmd, rest)
{
	var result = false;
	var o = getRequestObject();
	var url = 'api.php?r='+Math.random()+'&cmd='+cmd+rest;
	o.open('GET', url, false);
	o.send(null);
	return o.responseText;
}

function save_word(word)
{
	var si = new stackItem('quotation', words[word]);
	var d = stack_item_to_string(si);
	d = d.substring(2);
	d = d.substring(0, d.length-2);
	d = encodeURIComponent(d);
	var w = encodeURIComponent(word);
	var effect = effects[word];
	api('save-word', '&word='+w+'&definition='+d);
	console_writeln('saved word ' + word);
}

function load_all_words()
{
	console_writeln('loading all words...');
	var txt = api('load-all-words', '');
	console_writeln('parsing words');
	parse_input(txt);
	console_writeln('words parsed');
}