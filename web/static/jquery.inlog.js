(function(undefined) {
	var defaults = {
		enabled: false,//Enable logging
		thisValue: false,//Output this-value
		returnValue: true,//Output return-value
		indent: true,//Indent nested calls (makes sense for maxDepth !== 0)
		maxDepth: 0,//Max depth of nested calls
		rawOutput: false//If true, the raw stack trace objects will be printed (thisValue, returnValue and indent are all included for free)
	};

	var settings = jQuery.extend({}, defaults);
	var originaljQuery = jQuery;

	/*
	 * Allows for controlling from outside.
	 */
	jQuery.inlog = function(param) {
		//Is param a boolean?
		if(param === true || param === false) {
			settings.enabled = param;
		}
		//Must be an object
		else {
			settings = jQuery.extend({}, defaults, param);
		}
	};

	jQuery.inlog.VERSION = '1.0.0';

	if(!window.$l) {
		window.$l = jQuery.inlog;
	}


	/*
	 * Since nothing runs parallel,
	 * we can keep track of the stack trace in a global variable.
	 */
	var maintrace, subtrace, tracedepth = 0;

	//Example content of maintrace
	/*
	{
		"function": "jQuery",
		"this": "<some pointer>",
		"arguments": ["<some values or pointers>"],
		"return": "<some value or pointer>",
		"sub"://One or multiple function calls on the same depth
		[
			{
				"function": "parents",
				"this": "<some pointer>",
				"arguments": ["<some values or pointers>"],
				"return": "<some value or pointer>",
				"sub":
				[
					{
						"function": "pushStack",
						"this": "<some pointer>",
						"arguments": ["<some values or pointers>"],
						"return": "<some value or pointer>",
						"sub": []
					}, {
						//Moar...
					}
				]
			}
		]
	}
	*/


	/**
	 * Outputs a function call with all parameters passed.
	 *
	 * @param funcName A human readable name of the function called
	 * @param origArguments The original "arguments" property inside the function
	 * @param origThis The original context the function was running in
	 * @param origReturn The original return value of the function
	 * @returns undefined
	 * */
	function logFunctionCall(funcName, origArguments, origThis, origReturn) {
		var params = [], paramFormatStrings = [], formatString = '';

		for(var i = 0; i < origArguments.length; i++) {
			//You may pass "undefined" explicitely to a function.
			//foo() ist something else than foo(undefined).
			if(origArguments[i] === undefined) {
				break;
			}

			params.push(origArguments[i]);
			paramFormatStrings.push('%o');
		}

		//Print this-value?
		if(settings.thisValue) {
			formatString = '(%o).';
			params.unshift(origThis);
		}

		//First argument of console.log is the format string.
		params.unshift(formatString + funcName + '(' + paramFormatStrings.join(', ') + ')');

		if(settings.returnValue) {
			params[0] += ' ↷ %o';

			params.push(origReturn);
		}

		console.log.apply(console, params);
	}


	/**
	 * Outputs the stack trace to console.
	 * Basically simple tree traversing.
	 *
	 * If rawOutput is enabled, it will simply dump the stack trace.
	 *
	 * @param trace The JSON Object with the trace info
	 * @returns undefined
	 * */
	function logTrace(trace) {
		if(settings.rawOutput) {
			console.log('%o', trace);

			return;
		}

		logFunctionCall(trace["function"], trace["arguments"], trace["this"], trace["return"]);

		//Has sub calls?
		if(trace["sub"].length) {
			if(settings.indent) {
				console.groupCollapsed();
			}

			//Output each sub call.
			for(var i = 0; i < trace["sub"].length; i++) {
				logTrace(trace["sub"][i]);
			}

			if(settings.indent) {
				console.groupEnd();
			}
		}
	}


	/**
	 * Creates a Function which calls the "origFunction"
	 * and logs the call with the function as called "funcName".
	 *
	 * @param funcName The name of the original function. Human readable.
	 * @param origFunction A reference to the original function getting wrapped.
	 * @returns A function, which calls the original function sourended by log calls.
	 * */
	function createReplacementFunction(funcName, origFunction) {
		return function() {
			//Boring. Scroll down for the fun part.
			if(settings.enabled === false) {
				return origFunction.apply(this, arguments);
			}

			//We deep enough
			if(settings.maxDepth !== -1 && tracedepth > settings.maxDepth) {
				return origFunction.apply(this, arguments);
			}

			//Create new trace and keep track of it.
			var _trace = {
				"function": funcName,
				"arguments": arguments,
				"this": this,
				"sub": []
			};

			//Keep track of parent trace, if any.
			var parenttrace;

			//Keep track if this was the first call.
			var isFirst = (tracedepth === 0);

			tracedepth++;

			//Check if this is the first call or if already deeper.
			if(isFirst) {
				//Set everything to the newly created trace.
				maintrace = subtrace = _trace;
			} else {
				parenttrace = subtrace;

				//Push the new trace to the path of the parent trace.
				subtrace["sub"].push(_trace);
				subtrace = _trace;
			}

			//Call the original function.
			var ret = origFunction.apply(this, arguments);

			//Trace the return value (unknown before this point).
			_trace["return"] = ret;

			//Reset tracing if this function call was the top most.
			if(isFirst) {
				tracedepth = 0;

				//Log the shit out of it.
				logTrace(maintrace);
			} else {
				//Reset to parent trace,
				//because there may be calls on the same level as we are.
				subtrace = parenttrace;
			}

			//Return the original return value as if nothing happened.
			return ret;
		};
	}


	/**
	 * Injects log calls inside some functions of "obj"
	 * depending on the "list" and "inverted" parameter.
	 *
	 * If "inverted" is true, only the props inside "list" are considered.
	 * If "inverted" is not true, all props inside "list" are ignored.
	 *
	 * @param obj An object which should get each function replaced.
	 * @param list An optional array of strings with props to consider.
	 * @param inverted An optional boolean indicating how "list" is to be interpreted.
	 * @returns undefined
	 * */
	function inject(obj, list, inverted) {
		//Make a string out of the array because we use String.indexOf
		list = ',' + (list || []).join(',') + ',';

		//Anything but true is considered false
		inverted = inverted === true;

		for(var prop in obj) {
			if(
				//Not inherited
				obj.hasOwnProperty(prop) &&
				//Actually a function
				jQuery.isFunction(obj[prop]) &&
				//Inside our list or not, depending on what we want
				(list.indexOf(',' + prop + ',') !== -1) === inverted
			) {
				//Keep track of the original function
				var tmp = obj[prop];

				//Overwrite that thing
				obj[prop] = createReplacementFunction(prop, tmp);

				//Maybe the function had some props we just removed
				originaljQuery.extend(obj[prop], tmp);
			}
		}
	}


	//Is the dollar actually jQuery?
	if(window.jQuery === window.$) {
		inject(window, ['jQuery', '$'], true);
	} else {
		inject(window, ['jQuery'], true);
	}

	//Usual jQuery stuff like find, children, animate, css, etc.
	inject(jQuery.fn, ['constructor', 'jquery', 'init']);

	//Sizzling hot
	inject(jQuery.find);

	//Sizzle selectors, that actually contain functions
	inject(jQuery.find.selectors.attrHandle);
	inject(jQuery.find.selectors.relative);
	inject(jQuery.find.selectors.find);
	inject(jQuery.find.selectors.preFilter);
	inject(jQuery.find.selectors.filters);
	inject(jQuery.find.selectors.setFilters);
	inject(jQuery.find.selectors.filter);
})();
