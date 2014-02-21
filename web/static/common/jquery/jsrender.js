/*! JsRender v1.0pre - (jsrender.js version: does not require jQuery): http://github.com/BorisMoore/jsrender */
/*
 * Optimized version of jQuery Templates, fosr rendering to string, using 'codeless' markup.
 *
 * Copyright 2011, Boris Moore
 * Released under the MIT License.
 */
window.JsViews || window.jQuery && jQuery.views || (function( window, undefined ) {

var $, _$, JsViews, viewsNs, tmplEncode, render, rTag, registerTags, registerHelpers, extend,
	FALSE = false, TRUE = true,
	jQuery = window.jQuery, document = window.document,
	htmlExpr = /^[^<]*(<[\w\W]+>)[^>]*$|\{\{\! /,
	rPath = /^(true|false|null|[\d\.]+)|(\w+|\$(view|data|ctx|(\w+)))([\w\.]*)|((['"])(?:\\\1|.)*\7)$/g,
	rParams = /(\$?[\w\.\[\]]+)(?:(\()|\s*(===|!==|==|!=|<|>|<=|>=)\s*|\s*(\=)\s*)?|(\,\s*)|\\?(\')|\\?(\")|(\))|(\s+)/g,
	rNewLine = /\r?\n/g,
	rUnescapeQuotes = /\\(['"])/g,
	rEscapeQuotes = /\\?(['"])/g,
	rBuildHash = /\x08([^\x08]+)\x08/g,
	autoName = 0,
	escapeMapForHtml = {
		"&": "&amp;",
		"<": "&lt;",
		">": "&gt;"
	},
	htmlSpecialChar = /[\x00"&'<>]/g,
	slice = Array.prototype.slice;

if ( jQuery ) {

	////////////////////////////////////////////////////////////////////////////////////////////////
	// jQuery is loaded, so make $ the jQuery object
	$ = jQuery;

	$.fn.extend({
		// Use first wrapped element as template markup.
		// Return string obtained by rendering the template against data.
		render: function( data, context, parentView, path ) {
			return render( data, this[0], context, parentView, path );
		},

		// Consider the first wrapped element as a template declaration, and get the compiled template or store it as a named template.
		template: function( name, context ) {
			return $.template( name, this[0], context );
		}
	});

} else {

	////////////////////////////////////////////////////////////////////////////////////////////////
	// jQuery is not loaded. Make $ the JsViews object

	// Map over the $ in case of overwrite
	_$ = window.$;

	window.JsViews = JsViews = window.$ = $ = {
		extend: function( target, source ) {
			var name;
			for ( name in source ) {
				target[ name ] = source[ name ];
			}
			return target;
		},
		isArray: Array.isArray || function( obj ) {
			return Object.prototype.toString.call( obj ) === "[object Array]";
		},
		noConflict: function() {
			if ( window.$ === JsViews ) {
				window.$ = _$;
			}
			return JsViews;
		}
	};
}

extend = $.extend;

//=================
// View constructor
//=================

function View( context, path, parentView, data, template ) {
	// Returns a view data structure for a new rendered instance of a template.
	// The content field is a hierarchical array of strings and nested views.

	parentView = parentView || { viewsCount:0, ctx: viewsNs.helpers };

	var parentContext = parentView && parentView.ctx;

	return {
		jsViews: "v1.0pre",
		path: path || "",
		// inherit context from parentView, merged with new context.
		itemNumber: ++parentView.viewsCount || 1,
		viewsCount: 0,
		tmpl: template,
		data: data || parentView.data || {},
		// Set additional context on this view (which will modify the context inherited from the parent, and be inherited by child views)
		ctx : context && context === parentContext
			? parentContext
			: (parentContext ? extend( extend( {}, parentContext ), context ) : context||{}), 
			// If no jQuery, extend does not support chained copies - so limit to two parameters
		parent: parentView
	};
}
extend( $, {
	views: viewsNs = {
		templates: {},
		tags: {
			"if": function() {
				var ifTag = this,
					view = ifTag._view;
				view.onElse = function( presenter, args ) {
					var i = 0,
						l = args.length;
					while ( l && !args[ i++ ]) {
						// Only render content if args.length === 0 (i.e. this is an else with no condition) or if a condition argument is truey
						if ( i === l ) {
							return "";
						}
					}
					view.onElse = undefined; // If condition satisfied, so won't run 'else'.
					return render( view.data, presenter.tmpl, view.ctx, view);
				};
				return view.onElse( this, arguments );
			},
			"else": function() {
				var view = this._view;
				return view.onElse ? view.onElse( this, arguments ) : "";
			},
			each: function() {
				var i, 
					self = this,
					result = "",
					args = arguments,
					l = args.length,
					content = self.tmpl,
					view = self._view;
				for ( i = 0; i < l; i++ ) {
					result += args[ i ] ? render( args[ i ], content, self.ctx || view.ctx, view, self._path, self._ctor ) : "";
				}
				return l ? result 
					// If no data parameter, use the current $data from view, and render once
					:  result + render( view.data, content, view.ctx, view, self._path, self.tag );
			},
			"=": function( value ) {
				return value;
			},
			"*": function( value ) {
				return value;
			}
		},
		helpers: {
			not: function( value ) {
				return !value;
			}
		},
		allowCode: FALSE,
		debugMode: TRUE,
		err: function( e ) {
			return viewsNs.debugMode ? ("<br/><b>Error:</b> <em> " + (e.message || e) + ". </em>"): '""';
		},

//===============
// setDelimiters
//===============

		setDelimiters: function( openTag, closeTag ) {
			// Set or modify the delimiter characters for tags: "{{" and "}}"
			var firstCloseChar = closeTag.charAt( 0 ),
				secondCloseChar = closeTag.charAt( 1 );
			openTag = "\\" + openTag.charAt( 0 ) + "\\" + openTag.charAt( 1 );
			closeTag = "\\" + firstCloseChar + "\\" + secondCloseChar;

			// Build regex with new delimiters
			//           {{
			rTag = openTag
				//       #      tag    (followed by space,! or })             or equals or  code
				+ "(?:(?:(\\#)?(\\w+(?=[!\\s\\" + firstCloseChar + "]))" + "|(?:(\\=)|(\\*)))"
				//     params
				+ "\\s*((?:[^\\" + firstCloseChar + "]|\\" + firstCloseChar + "(?!\\" + secondCloseChar + "))*?)"
				//   encoding
				+ "(!(\\w*))?"
				//        closeBlock
				+ "|(?:\\/([\\w\\$\\.\\[\\]]+)))"
			//  }}
			+ closeTag;

			// Default rTag:     #    tag              equals code        params         encoding    closeBlock
			//      /\{\{(?:(?:(\#)?(\w+(?=[\s\}!]))|(?:(\=)|(\*)))((?:[^\}]|\}(?!\}))*?)(!(\w*))?|(?:\/([\w\$\.\[\]]+)))\}\}/g;

			rTag = new RegExp( rTag, "g" );
		},


//===============
// registerTags
//===============

		// Register declarative tag.
		registerTags: registerTags = function( name, tagFn ) {
			var key;
			if ( typeof name === "object" ) {
				for ( key in name ) {
					registerTags( key, name[ key ]);
				}
			} else {
				// Simple single property case.
				viewsNs.tags[ name ] = tagFn;
			}
			return this;
		},

//===============
// registerHelpers
//===============

		// Register helper function for use in markup.
		registerHelpers: registerHelpers = function( name, helper ) {
			if ( typeof name === "object" ) {
				// Object representation where property name is path and property value is value.
				// TODO: We've discussed an "objectchange" event to capture all N property updates here. See TODO note above about propertyChanges.
				var key;
				for ( key in name ) {
					registerHelpers( key, name[ key ]);
				}
			} else {
				// Simple single property case.
				viewsNs.helpers[ name ] = helper;
			}
			return this;
		},

//===============
// tmpl.encode
//===============

		encode: function( encoding, text ) {
			return text
				? ( tmplEncode[ encoding || "html" ] || tmplEncode.html)( text ) // HTML encoding is the default
				: "";
		},

		encoders: tmplEncode = {
			"none": function( text ) {
				return text;
			},
			"html": function( text ) {
				// HTML encoding helper: Replace < > & and ' and " by corresponding entities.
				// Implementation, from Mike Samuel <msamuel@google.com>
				return String( text ).replace( htmlSpecialChar, replacerForHtml );
			}
			//TODO add URL encoding, and perhaps other encoding helpers...
		},

//===============
// renderTag
//===============

		renderTag: function( tag, view, encode, content, tagProperties ) {
			// This is a tag call, with arguments: "tag", view, encode, content, presenter [, params...]
			var ret, ctx, name,
				args = arguments,
				presenters = viewsNs.presenters;
				hash = tagProperties._hash,
				tagFn = viewsNs.tags[ tag ];

			if ( !tagFn ) {
				return "";
			}
			
			content = content && view.tmpl.nested[ content - 1 ];
			tagProperties.tmpl = tagProperties.tmpl || content || undefined;
			// Set the tmpl property to the content of the block tag, unless set as an override property on the tag
		
			if ( presenters && presenters[ tag ]) {
				ctx = extend( extend( {}, tagProperties.ctx ), tagProperties );  
				delete ctx.ctx;  
				delete ctx._path;  
				delete ctx.tmpl;
				tagProperties.ctx = ctx;  
				tagProperties._ctor = tag + (hash ? "=" + hash.slice( 0, -1 ) : "");

				tagProperties = extend( extend( {}, tagFn ), tagProperties );
				tagFn = viewsNs.tags.each; // Use each to render the layout template against the data
			} 

			tagProperties._encode = encode;
			tagProperties._view = view;
			ret = tagFn.apply( tagProperties, args.length > 5 ? slice.call( args, 5 ) : [view.data] );
			return ret || (ret === undefined ? "" : ret.toString()); // (If ret is the value 0 or false or null, will render to string) 
		}
	},

//===============
// render
//===============

	render: render = function( data, tmpl, context, parentView, path, tagName ) {
		// Render template against data as a tree of subviews (nested template), or as a string (top-level template).
		// tagName parameter for internal use only. Used for rendering templates registered as tags (which may have associated presenter objects)
		var i, l, dataItem, arrayView, content, result = "";

		if ( arguments.length === 2 && data.jsViews ) {
			parentView = data;
			context = parentView.ctx;
			data = parentView.data;
		}
		tmpl = $.template( tmpl );
		if ( !tmpl ) {
			return ""; // Could throw...
		}

		if ( $.isArray( data )) {
			// Create a view item for the array, whose child views correspond to each data item.
			arrayView = new View( context, path, parentView, data);
			l = data.length;
			for ( i = 0, l = data.length; i < l; i++ ) {
				dataItem = data[ i ];
				content = dataItem ? tmpl( dataItem, new View( context, path, arrayView, dataItem, tmpl, this )) : "";
				result += viewsNs.activeViews ? "<!--item-->" + content + "<!--/item-->" : content;
			}
		} else {
			result += tmpl( data, new View( context, path, parentView, data, tmpl ));
		}

		return viewsNs.activeViews
			// If in activeView mode, include annotations
			? "<!--tmpl(" + (path || "") + ") " + (tagName ? "tag=" + tagName : tmpl._name) + "-->" + result + "<!--/tmpl-->"
			// else return just the string result
			: result;
	},

//===============
// template
//===============

	template: function( name, tmpl ) {
		// Set:
		// Use $.template( name, tmpl ) to cache a named template,
		// where tmpl is a template string, a script element or a jQuery instance wrapping a script element, etc.
		// Use $( "selector" ).template( name ) to provide access by name to a script block template declaration.

		// Get:
		// Use $.template( name ) to access a cached template.
		// Also $( selectorToScriptBlock ).template(), or $.template( null, templateString )
		// will return the compiled template, without adding a name reference.
		// If templateString is not a selector, $.template( templateString ) is equivalent
		// to $.template( null, templateString ). To ensure a string is treated as a template,
		// include an HTML element, an HTML comment, or a template comment tag.

		if (tmpl) {
			// Compile template and associate with name
			if ( "" + tmpl === tmpl ) { // type string
				// This is an HTML string being passed directly in.
				tmpl = compile( tmpl );
			} else if ( jQuery && tmpl instanceof $ ) {
				tmpl = tmpl[0];
			}
			if ( tmpl ) {
				if ( jQuery && tmpl.nodeType ) {
					// If this is a template block, use cached copy, or generate tmpl function and cache.
					tmpl = $.data( tmpl, "tmpl" ) || $.data( tmpl, "tmpl", compile( tmpl.innerHTML ));
				}
				viewsNs.templates[ tmpl._name = tmpl._name || name || "_" + autoName++ ] = tmpl;
			}
			return tmpl;
		}
		// Return named compiled template
		return name
			? "" + name !== name // not type string
				? (name._name
					? name // already compiled
					: $.template( null, name ))
				: viewsNs.templates[ name ] ||
					// If not in map, treat as a selector. (If integrated with core, use quickExpr.exec)
					$.template( null, htmlExpr.test( name ) ? name : try$( name ))
			: null;
	}
});

viewsNs.setDelimiters( "{{", "}}" );

//=================
// compile template
//=================

// Generate a reusable function that will serve to render a template against data
// (Compile AST then build template function)

function parsePath( all, comp, object, viewDataCtx, viewProperty, path, string, quot ) {
	return object
		? ((viewDataCtx
			? viewProperty
				? ("$view." + viewProperty)
				: object
			:("$data." + object)
		)  + ( path || "" ))
		: string || (comp || "");
}

function compile( markup ) {
	var newNode,
		loc = 0,
		stack = [],
		topNode = [],
		content = topNode,
		current = [,,topNode];

	function pushPreceedingContent( shift ) {
		shift -= loc;
		if ( shift ) {
			content.push( markup.substr( loc, shift ).replace( rNewLine,"\\n"));
		}
	}

	function parseTag( all, isBlock, tagName, equals, code, params, useEncode, encode, closeBlock, index ) {
		// rTag    :    #    tagName          equals code        params         encode      closeBlock
		// /\{\{(?:(?:(\#)?(\w+(?=[\s\}!]))|(?:(\=)|(\*)))((?:[^\}]|\}(?!\}))*?)(!(\w*))?|(?:\/([\w\$\.\[\]]+)))\}\}/g;

		// Build abstract syntax tree: [ tagName, params, content, encode ]
		var named,
			hash = "",
			parenDepth = 0,
			quoted = FALSE, // boolean for string content in double qoutes
			aposed = FALSE; // or in single qoutes

		function parseParams( all, path, paren, comp, eq, comma, apos, quot, rightParen, space, index ) {
			//      path          paren eq      comma   apos   quot  rtPrn  space
			// /(\$?[\w\.\[\]]+)(?:(\()|(===)|(\=))?|(\,\s*)|\\?(\')|\\?(\")|(\))|(\s+)/g

			return aposed
				// within single-quoted string
				? ( aposed = !apos, (aposed ? all : '"'))
				: quoted
					// within double-quoted string
					? ( quoted = !quot, (quoted ? all : '"'))
					: comp
						// comparison
						? ( path.replace( rPath, parsePath ) + comp)
						: eq
							// named param
							? parenDepth ? "" :( named = TRUE, '\b' + path + ':')
							: paren
								// function
								? (parenDepth++, path.replace( rPath, parsePath ) + '(')
								: rightParen
									// function
									? (parenDepth--, ")")
									: path
										// path
										? path.replace( rPath, parsePath )
										: comma
											? ","
											: space
												? (parenDepth
													? ""
													: named
														? ( named = FALSE, "\b")
														: ","
												)
												: (aposed = apos, quoted = quot, '"');
		}

		tagName = tagName || equals;
		pushPreceedingContent( index );
		if ( code ) {
			if ( viewsNs.allowCode ) {
				content.push([ "*", params.replace( rUnescapeQuotes, "$1" )]);
			}
		} else if ( tagName ) {
			if ( tagName === "else" ) {
				current = stack.pop();
				content = current[ 2 ];
				isBlock = TRUE;
			}
			params = (params
				? (params + " ")
					.replace( rParams, parseParams )
					.replace( rBuildHash, function( all, keyValue, index ) {
						hash += keyValue + ",";
						return "";
					})
				: "");
			params = params.slice( 0, -1 );
			newNode = [
				tagName,
				useEncode ? encode || "none" : "",
				isBlock && [],
				"{" + hash + "_hash:'" +  hash + "',_path:'" + params + "'}",
				params
			];

			if ( isBlock ) {
				stack.push( current );
				current = newNode;
			}
			content.push( newNode );
		} else if ( closeBlock ) {
			current = stack.pop();
		}
		loc = index + all.length; // location marker - parsed up to here
		if ( !current ) {
			throw "Expected block tag";
		}
		content = current[ 2 ];
	}
	markup = markup.replace( rEscapeQuotes, "\\$1" );
	markup.replace( rTag, parseTag );
	pushPreceedingContent( markup.length );
	return buildTmplFunction( topNode );
}

// Build javascript compiled template function, from AST
function buildTmplFunction( nodes ) {
	var ret, node, i,
		nested = [],
		l = nodes.length,
		code = "try{var views="
			+ (jQuery ? "jQuery" : "JsViews")
			+ '.views,tag=views.renderTag,enc=views.encode,html=views.encoders.html,$ctx=$view && $view.ctx,result=""+\n\n';

	for ( i = 0; i < l; i++ ) {
		node = nodes[ i ];
		if ( node[ 0 ] === "*" ) {
			code = code.slice( 0, i ? -1 : -3 ) + ";" + node[ 1 ] + ( i + 1 < l ? "result+=" : "" );
		} else if ( "" + node === node ) { // type string
			code += '"' + node + '"+';
		} else {
			var tag = node[ 0 ],
				encode = node[ 1 ],
				content = node[ 2 ],
				obj = node[ 3 ],
				params = node[ 4 ],
				paramsOrEmptyString = params + '||"")+';

			if( content ) {
				nested.push( buildTmplFunction( content ));
			}
			code += tag === "="
				? (!encode || encode === "html"
					? "html(" + paramsOrEmptyString
					: encode === "none"
						? ("(" + paramsOrEmptyString)
						: ('enc("' + encode + '",' + paramsOrEmptyString)
				)
				: 'tag("' + tag + '",$view,"' + ( encode || "" ) + '",'
					+ (content ? nested.length : '""') // For block tags, pass in the key (nested.length) to the nested content template
					+ "," + obj + (params ? "," : "") + params + ")+";
		}
	}
	ret = new Function( "$data, $view", code.slice( 0, -1) + ";return result;\n\n}catch(e){return views.err(e);}" );
	ret.nested = nested;
	return ret;
}

//========================== Private helper functions, used by code above ==========================

function replacerForHtml( ch ) {
	// Original code from Mike Samuel <msamuel@google.com>
	return escapeMapForHtml[ ch ]
		// Intentional assignment that caches the result of encoding ch.
		|| ( escapeMapForHtml[ ch ] = "&#" + ch.charCodeAt( 0 ) + ";" );
}

function try$( selector ) {
	// If selector is valid, return jQuery object, otherwise return (invalid) selector string
	try {
		return $( selector );
	} catch( e) {}
	return selector;
}
})( window );
