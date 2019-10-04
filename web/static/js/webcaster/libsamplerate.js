// libsamplerate.js - port of libsamplerate to JavaScript using emscripten
// by Romain Beauxis <toots@rastageeks.org>

Samplerate = (function() {
  var Module;
  var context = {};
  return (function() {

// Note: For maximum-speed code, see "Optimizing Code" on the Emscripten wiki, https://github.com/kripken/emscripten/wiki/Optimizing-Code
// Note: Some Emscripten settings may limit the speed of the generated code.
// The Module object: Our interface to the outside world. We import
// and export values on it, and do the work to get that through
// closure compiler if necessary. There are various ways Module can be used:
// 1. Not defined. We create it here
// 2. A function parameter, function(Module) { ..generated code.. }
// 3. pre-run appended it, var Module = {}; ..generated code..
// 4. External script tag defines var Module.
// We need to do an eval in order to handle the closure compiler
// case, where this code here is minified but Module was defined
// elsewhere (e.g. case 4 above). We also need to check if Module
// already exists (e.g. case 3 above).
// Note that if you want to run closure, and also to use Module
// after the generated code, you will need to define   var Module = {};
// before the code. Then that object will be used in the code, and you
// can continue to use Module afterwards as well.
var Module;
if (!Module) Module = eval('(function() { try { return Module || {} } catch(e) { return {} } })()');

// Sometimes an existing Module object exists with properties
// meant to overwrite the default module functionality. Here
// we collect those properties and reapply _after_ we configure
// the current environment's defaults to avoid having to be so
// defensive during initialization.
var moduleOverrides = {};
for (var key in Module) {
  if (Module.hasOwnProperty(key)) {
    moduleOverrides[key] = Module[key];
  }
}

// The environment setup code below is customized to use Module.
// *** Environment setup code ***
var ENVIRONMENT_IS_NODE = typeof process === 'object' && typeof require === 'function';
var ENVIRONMENT_IS_WEB = typeof window === 'object';
var ENVIRONMENT_IS_WORKER = typeof importScripts === 'function';
var ENVIRONMENT_IS_SHELL = !ENVIRONMENT_IS_WEB && !ENVIRONMENT_IS_NODE && !ENVIRONMENT_IS_WORKER;

if (ENVIRONMENT_IS_NODE) {
  // Expose functionality in the same simple way that the shells work
  // Note that we pollute the global namespace here, otherwise we break in node
  if (!Module['print']) Module['print'] = function print(x) {
    process['stdout'].write(x + '\n');
  };
  if (!Module['printErr']) Module['printErr'] = function printErr(x) {
    process['stderr'].write(x + '\n');
  };

  var nodeFS = require('fs');
  var nodePath = require('path');

  Module['read'] = function read(filename, binary) {
    filename = nodePath['normalize'](filename);
    var ret = nodeFS['readFileSync'](filename);
    // The path is absolute if the normalized version is the same as the resolved.
    if (!ret && filename != nodePath['resolve'](filename)) {
      filename = path.join(__dirname, '..', 'src', filename);
      ret = nodeFS['readFileSync'](filename);
    }
    if (ret && !binary) ret = ret.toString();
    return ret;
  };

  Module['readBinary'] = function readBinary(filename) { return Module['read'](filename, true) };

  Module['load'] = function load(f) {
    globalEval(read(f));
  };

  Module['arguments'] = process['argv'].slice(2);

  module['exports'] = Module;
}
else if (ENVIRONMENT_IS_SHELL) {
  if (!Module['print']) Module['print'] = print;
  if (typeof printErr != 'undefined') Module['printErr'] = printErr; // not present in v8 or older sm

  if (typeof read != 'undefined') {
    Module['read'] = read;
  } else {
    Module['read'] = function read() { throw 'no read() available (jsc?)' };
  }

  Module['readBinary'] = function readBinary(f) {
    return read(f, 'binary');
  };

  if (typeof scriptArgs != 'undefined') {
    Module['arguments'] = scriptArgs;
  } else if (typeof arguments != 'undefined') {
    Module['arguments'] = arguments;
  }

  this['Module'] = Module;

  eval("if (typeof gc === 'function' && gc.toString().indexOf('[native code]') > 0) var gc = undefined"); // wipe out the SpiderMonkey shell 'gc' function, which can confuse closure (uses it as a minified name, and it is then initted to a non-falsey value unexpectedly)
}
else if (ENVIRONMENT_IS_WEB || ENVIRONMENT_IS_WORKER) {
  Module['read'] = function read(url) {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, false);
    xhr.send(null);
    return xhr.responseText;
  };

  if (typeof arguments != 'undefined') {
    Module['arguments'] = arguments;
  }

  if (typeof console !== 'undefined') {
    if (!Module['print']) Module['print'] = function print(x) {
      console.log(x);
    };
    if (!Module['printErr']) Module['printErr'] = function printErr(x) {
      console.log(x);
    };
  } else {
    // Probably a worker, and without console.log. We can do very little here...
    var TRY_USE_DUMP = false;
    if (!Module['print']) Module['print'] = (TRY_USE_DUMP && (typeof(dump) !== "undefined") ? (function(x) {
      dump(x);
    }) : (function(x) {
      // self.postMessage(x); // enable this if you want stdout to be sent as messages
    }));
  }

  if (ENVIRONMENT_IS_WEB) {
    this['Module'] = Module;
  } else {
    Module['load'] = importScripts;
  }
}
else {
  // Unreachable because SHELL is dependant on the others
  throw 'Unknown runtime environment. Where are we?';
}

function globalEval(x) {
  eval.call(null, x);
}
if (!Module['load'] == 'undefined' && Module['read']) {
  Module['load'] = function load(f) {
    globalEval(Module['read'](f));
  };
}
if (!Module['print']) {
  Module['print'] = function(){};
}
if (!Module['printErr']) {
  Module['printErr'] = Module['print'];
}
if (!Module['arguments']) {
  Module['arguments'] = [];
}
// *** Environment setup code ***

// Closure helpers
Module.print = Module['print'];
Module.printErr = Module['printErr'];

// Callbacks
Module['preRun'] = [];
Module['postRun'] = [];

// Merge back in the overrides
for (var key in moduleOverrides) {
  if (moduleOverrides.hasOwnProperty(key)) {
    Module[key] = moduleOverrides[key];
  }
}



// === Auto-generated preamble library stuff ===

//========================================
// Runtime code shared with compiler
//========================================

var Runtime = {
  stackSave: function () {
    return STACKTOP;
  },
  stackRestore: function (stackTop) {
    STACKTOP = stackTop;
  },
  forceAlign: function (target, quantum) {
    quantum = quantum || 4;
    if (quantum == 1) return target;
    if (isNumber(target) && isNumber(quantum)) {
      return Math.ceil(target/quantum)*quantum;
    } else if (isNumber(quantum) && isPowerOfTwo(quantum)) {
      return '(((' +target + ')+' + (quantum-1) + ')&' + -quantum + ')';
    }
    return 'Math.ceil((' + target + ')/' + quantum + ')*' + quantum;
  },
  isNumberType: function (type) {
    return type in Runtime.INT_TYPES || type in Runtime.FLOAT_TYPES;
  },
  isPointerType: function isPointerType(type) {
  return type[type.length-1] == '*';
},
  isStructType: function isStructType(type) {
  if (isPointerType(type)) return false;
  if (isArrayType(type)) return true;
  if (/<?\{ ?[^}]* ?\}>?/.test(type)) return true; // { i32, i8 } etc. - anonymous struct types
  // See comment in isStructPointerType()
  return type[0] == '%';
},
  INT_TYPES: {"i1":0,"i8":0,"i16":0,"i32":0,"i64":0},
  FLOAT_TYPES: {"float":0,"double":0},
  or64: function (x, y) {
    var l = (x | 0) | (y | 0);
    var h = (Math.round(x / 4294967296) | Math.round(y / 4294967296)) * 4294967296;
    return l + h;
  },
  and64: function (x, y) {
    var l = (x | 0) & (y | 0);
    var h = (Math.round(x / 4294967296) & Math.round(y / 4294967296)) * 4294967296;
    return l + h;
  },
  xor64: function (x, y) {
    var l = (x | 0) ^ (y | 0);
    var h = (Math.round(x / 4294967296) ^ Math.round(y / 4294967296)) * 4294967296;
    return l + h;
  },
  getNativeTypeSize: function (type) {
    switch (type) {
      case 'i1': case 'i8': return 1;
      case 'i16': return 2;
      case 'i32': return 4;
      case 'i64': return 8;
      case 'float': return 4;
      case 'double': return 8;
      default: {
        if (type[type.length-1] === '*') {
          return Runtime.QUANTUM_SIZE; // A pointer
        } else if (type[0] === 'i') {
          var bits = parseInt(type.substr(1));
          assert(bits % 8 === 0);
          return bits/8;
        } else {
          return 0;
        }
      }
    }
  },
  getNativeFieldSize: function (type) {
    return Math.max(Runtime.getNativeTypeSize(type), Runtime.QUANTUM_SIZE);
  },
  dedup: function dedup(items, ident) {
  var seen = {};
  if (ident) {
    return items.filter(function(item) {
      if (seen[item[ident]]) return false;
      seen[item[ident]] = true;
      return true;
    });
  } else {
    return items.filter(function(item) {
      if (seen[item]) return false;
      seen[item] = true;
      return true;
    });
  }
},
  set: function set() {
  var args = typeof arguments[0] === 'object' ? arguments[0] : arguments;
  var ret = {};
  for (var i = 0; i < args.length; i++) {
    ret[args[i]] = 0;
  }
  return ret;
},
  STACK_ALIGN: 8,
  getAlignSize: function (type, size, vararg) {
    // we align i64s and doubles on 64-bit boundaries, unlike x86
    if (vararg) return 8;
    if (!vararg && (type == 'i64' || type == 'double')) return 8;
    if (!type) return Math.min(size, 8); // align structures internally to 64 bits
    return Math.min(size || (type ? Runtime.getNativeFieldSize(type) : 0), Runtime.QUANTUM_SIZE);
  },
  calculateStructAlignment: function calculateStructAlignment(type) {
    type.flatSize = 0;
    type.alignSize = 0;
    var diffs = [];
    var prev = -1;
    var index = 0;
    type.flatIndexes = type.fields.map(function(field) {
      index++;
      var size, alignSize;
      if (Runtime.isNumberType(field) || Runtime.isPointerType(field)) {
        size = Runtime.getNativeTypeSize(field); // pack char; char; in structs, also char[X]s.
        alignSize = Runtime.getAlignSize(field, size);
      } else if (Runtime.isStructType(field)) {
        if (field[1] === '0') {
          // this is [0 x something]. When inside another structure like here, it must be at the end,
          // and it adds no size
          // XXX this happens in java-nbody for example... assert(index === type.fields.length, 'zero-length in the middle!');
          size = 0;
          if (Types.types[field]) {
            alignSize = Runtime.getAlignSize(null, Types.types[field].alignSize);
          } else {
            alignSize = type.alignSize || QUANTUM_SIZE;
          }
        } else {
          size = Types.types[field].flatSize;
          alignSize = Runtime.getAlignSize(null, Types.types[field].alignSize);
        }
      } else if (field[0] == 'b') {
        // bN, large number field, like a [N x i8]
        size = field.substr(1)|0;
        alignSize = 1;
      } else if (field[0] === '<') {
        // vector type
        size = alignSize = Types.types[field].flatSize; // fully aligned
      } else if (field[0] === 'i') {
        // illegal integer field, that could not be legalized because it is an internal structure field
        // it is ok to have such fields, if we just use them as markers of field size and nothing more complex
        size = alignSize = parseInt(field.substr(1))/8;
        assert(size % 1 === 0, 'cannot handle non-byte-size field ' + field);
      } else {
        assert(false, 'invalid type for calculateStructAlignment');
      }
      if (type.packed) alignSize = 1;
      type.alignSize = Math.max(type.alignSize, alignSize);
      var curr = Runtime.alignMemory(type.flatSize, alignSize); // if necessary, place this on aligned memory
      type.flatSize = curr + size;
      if (prev >= 0) {
        diffs.push(curr-prev);
      }
      prev = curr;
      return curr;
    });
    if (type.name_ && type.name_[0] === '[') {
      // arrays have 2 elements, so we get the proper difference. then we scale here. that way we avoid
      // allocating a potentially huge array for [999999 x i8] etc.
      type.flatSize = parseInt(type.name_.substr(1))*type.flatSize/2;
    }
    type.flatSize = Runtime.alignMemory(type.flatSize, type.alignSize);
    if (diffs.length == 0) {
      type.flatFactor = type.flatSize;
    } else if (Runtime.dedup(diffs).length == 1) {
      type.flatFactor = diffs[0];
    }
    type.needsFlattening = (type.flatFactor != 1);
    return type.flatIndexes;
  },
  generateStructInfo: function (struct, typeName, offset) {
    var type, alignment;
    if (typeName) {
      offset = offset || 0;
      type = (typeof Types === 'undefined' ? Runtime.typeInfo : Types.types)[typeName];
      if (!type) return null;
      if (type.fields.length != struct.length) {
        printErr('Number of named fields must match the type for ' + typeName + ': possibly duplicate struct names. Cannot return structInfo');
        return null;
      }
      alignment = type.flatIndexes;
    } else {
      var type = { fields: struct.map(function(item) { return item[0] }) };
      alignment = Runtime.calculateStructAlignment(type);
    }
    var ret = {
      __size__: type.flatSize
    };
    if (typeName) {
      struct.forEach(function(item, i) {
        if (typeof item === 'string') {
          ret[item] = alignment[i] + offset;
        } else {
          // embedded struct
          var key;
          for (var k in item) key = k;
          ret[key] = Runtime.generateStructInfo(item[key], type.fields[i], alignment[i]);
        }
      });
    } else {
      struct.forEach(function(item, i) {
        ret[item[1]] = alignment[i];
      });
    }
    return ret;
  },
  dynCall: function (sig, ptr, args) {
    if (args && args.length) {
      if (!args.splice) args = Array.prototype.slice.call(args);
      args.splice(0, 0, ptr);
      return Module['dynCall_' + sig].apply(null, args);
    } else {
      return Module['dynCall_' + sig].call(null, ptr);
    }
  },
  functionPointers: [],
  addFunction: function (func) {
    for (var i = 0; i < Runtime.functionPointers.length; i++) {
      if (!Runtime.functionPointers[i]) {
        Runtime.functionPointers[i] = func;
        return 2*(1 + i);
      }
    }
    throw 'Finished up all reserved function pointers. Use a higher value for RESERVED_FUNCTION_POINTERS.';
  },
  removeFunction: function (index) {
    Runtime.functionPointers[(index-2)/2] = null;
  },
  getAsmConst: function (code, numArgs) {
    // code is a constant string on the heap, so we can cache these
    if (!Runtime.asmConstCache) Runtime.asmConstCache = {};
    var func = Runtime.asmConstCache[code];
    if (func) return func;
    var args = [];
    for (var i = 0; i < numArgs; i++) {
      args.push(String.fromCharCode(36) + i); // $0, $1 etc
    }
    code = Pointer_stringify(code);
    if (code[0] === '"') {
      // tolerate EM_ASM("..code..") even though EM_ASM(..code..) is correct
      if (code.indexOf('"', 1) === code.length-1) {
        code = code.substr(1, code.length-2);
      } else {
        // something invalid happened, e.g. EM_ASM("..code($0)..", input)
        abort('invalid EM_ASM input |' + code + '|. Please use EM_ASM(..code..) (no quotes) or EM_ASM({ ..code($0).. }, input) (to input values)');
      }
    }
    return Runtime.asmConstCache[code] = eval('(function(' + args.join(',') + '){ ' + code + ' })'); // new Function does not allow upvars in node
  },
  warnOnce: function (text) {
    if (!Runtime.warnOnce.shown) Runtime.warnOnce.shown = {};
    if (!Runtime.warnOnce.shown[text]) {
      Runtime.warnOnce.shown[text] = 1;
      Module.printErr(text);
    }
  },
  funcWrappers: {},
  getFuncWrapper: function (func, sig) {
    assert(sig);
    if (!Runtime.funcWrappers[func]) {
      Runtime.funcWrappers[func] = function dynCall_wrapper() {
        return Runtime.dynCall(sig, func, arguments);
      };
    }
    return Runtime.funcWrappers[func];
  },
  UTF8Processor: function () {
    var buffer = [];
    var needed = 0;
    this.processCChar = function (code) {
      code = code & 0xFF;

      if (buffer.length == 0) {
        if ((code & 0x80) == 0x00) {        // 0xxxxxxx
          return String.fromCharCode(code);
        }
        buffer.push(code);
        if ((code & 0xE0) == 0xC0) {        // 110xxxxx
          needed = 1;
        } else if ((code & 0xF0) == 0xE0) { // 1110xxxx
          needed = 2;
        } else {                            // 11110xxx
          needed = 3;
        }
        return '';
      }

      if (needed) {
        buffer.push(code);
        needed--;
        if (needed > 0) return '';
      }

      var c1 = buffer[0];
      var c2 = buffer[1];
      var c3 = buffer[2];
      var c4 = buffer[3];
      var ret;
      if (buffer.length == 2) {
        ret = String.fromCharCode(((c1 & 0x1F) << 6)  | (c2 & 0x3F));
      } else if (buffer.length == 3) {
        ret = String.fromCharCode(((c1 & 0x0F) << 12) | ((c2 & 0x3F) << 6)  | (c3 & 0x3F));
      } else {
        // http://mathiasbynens.be/notes/javascript-encoding#surrogate-formulae
        var codePoint = ((c1 & 0x07) << 18) | ((c2 & 0x3F) << 12) |
                        ((c3 & 0x3F) << 6)  | (c4 & 0x3F);
        ret = String.fromCharCode(
          Math.floor((codePoint - 0x10000) / 0x400) + 0xD800,
          (codePoint - 0x10000) % 0x400 + 0xDC00);
      }
      buffer.length = 0;
      return ret;
    }
    this.processJSString = function processJSString(string) {
      string = unescape(encodeURIComponent(string));
      var ret = [];
      for (var i = 0; i < string.length; i++) {
        ret.push(string.charCodeAt(i));
      }
      return ret;
    }
  },
  getCompilerSetting: function (name) {
    throw 'You must build with -s RETAIN_COMPILER_SETTINGS=1 for Runtime.getCompilerSetting or emscripten_get_compiler_setting to work';
  },
  stackAlloc: function (size) { var ret = STACKTOP;STACKTOP = (STACKTOP + size)|0;STACKTOP = (((STACKTOP)+7)&-8); return ret; },
  staticAlloc: function (size) { var ret = STATICTOP;STATICTOP = (STATICTOP + size)|0;STATICTOP = (((STATICTOP)+7)&-8); return ret; },
  dynamicAlloc: function (size) { var ret = DYNAMICTOP;DYNAMICTOP = (DYNAMICTOP + size)|0;DYNAMICTOP = (((DYNAMICTOP)+7)&-8); if (DYNAMICTOP >= TOTAL_MEMORY) enlargeMemory();; return ret; },
  alignMemory: function (size,quantum) { var ret = size = Math.ceil((size)/(quantum ? quantum : 8))*(quantum ? quantum : 8); return ret; },
  makeBigInt: function (low,high,unsigned) { var ret = (unsigned ? ((+((low>>>0)))+((+((high>>>0)))*(+4294967296))) : ((+((low>>>0)))+((+((high|0)))*(+4294967296)))); return ret; },
  GLOBAL_BASE: 8,
  QUANTUM_SIZE: 4,
  __dummy__: 0
}


Module['Runtime'] = Runtime;









//========================================
// Runtime essentials
//========================================

var __THREW__ = 0; // Used in checking for thrown exceptions.

var ABORT = false; // whether we are quitting the application. no code should run after this. set in exit() and abort()
var EXITSTATUS = 0;

var undef = 0;
// tempInt is used for 32-bit signed values or smaller. tempBigInt is used
// for 32-bit unsigned values or more than 32 bits. TODO: audit all uses of tempInt
var tempValue, tempInt, tempBigInt, tempInt2, tempBigInt2, tempPair, tempBigIntI, tempBigIntR, tempBigIntS, tempBigIntP, tempBigIntD, tempDouble, tempFloat;
var tempI64, tempI64b;
var tempRet0, tempRet1, tempRet2, tempRet3, tempRet4, tempRet5, tempRet6, tempRet7, tempRet8, tempRet9;

function assert(condition, text) {
  if (!condition) {
    abort('Assertion failed: ' + text);
  }
}

var globalScope = this;

// C calling interface. A convenient way to call C functions (in C files, or
// defined with extern "C").
//
// Note: LLVM optimizations can inline and remove functions, after which you will not be
//       able to call them. Closure can also do so. To avoid that, add your function to
//       the exports using something like
//
//         -s EXPORTED_FUNCTIONS='["_main", "_myfunc"]'
//
// @param ident      The name of the C function (note that C++ functions will be name-mangled - use extern "C")
// @param returnType The return type of the function, one of the JS types 'number', 'string' or 'array' (use 'number' for any C pointer, and
//                   'array' for JavaScript arrays and typed arrays; note that arrays are 8-bit).
// @param argTypes   An array of the types of arguments for the function (if there are no arguments, this can be ommitted). Types are as in returnType,
//                   except that 'array' is not possible (there is no way for us to know the length of the array)
// @param args       An array of the arguments to the function, as native JS values (as in returnType)
//                   Note that string arguments will be stored on the stack (the JS string will become a C string on the stack).
// @return           The return value, as a native JS value (as in returnType)
function ccall(ident, returnType, argTypes, args) {
  return ccallFunc(getCFunc(ident), returnType, argTypes, args);
}
Module["ccall"] = ccall;

// Returns the C function with a specified identifier (for C++, you need to do manual name mangling)
function getCFunc(ident) {
  try {
    var func = Module['_' + ident]; // closure exported function
    if (!func) func = eval('_' + ident); // explicit lookup
  } catch(e) {
  }
  assert(func, 'Cannot call unknown function ' + ident + ' (perhaps LLVM optimizations or closure removed it?)');
  return func;
}

// Internal function that does a C call using a function, not an identifier
function ccallFunc(func, returnType, argTypes, args) {
  var stack = 0;
  function toC(value, type) {
    if (type == 'string') {
      if (value === null || value === undefined || value === 0) return 0; // null string
      value = intArrayFromString(value);
      type = 'array';
    }
    if (type == 'array') {
      if (!stack) stack = Runtime.stackSave();
      var ret = Runtime.stackAlloc(value.length);
      writeArrayToMemory(value, ret);
      return ret;
    }
    return value;
  }
  function fromC(value, type) {
    if (type == 'string') {
      return Pointer_stringify(value);
    }
    assert(type != 'array');
    return value;
  }
  var i = 0;
  var cArgs = args ? args.map(function(arg) {
    return toC(arg, argTypes[i++]);
  }) : [];
  var ret = fromC(func.apply(null, cArgs), returnType);
  if (stack) Runtime.stackRestore(stack);
  return ret;
}

// Returns a native JS wrapper for a C function. This is similar to ccall, but
// returns a function you can call repeatedly in a normal way. For example:
//
//   var my_function = cwrap('my_c_function', 'number', ['number', 'number']);
//   alert(my_function(5, 22));
//   alert(my_function(99, 12));
//
function cwrap(ident, returnType, argTypes) {
  var func = getCFunc(ident);
  return function() {
    return ccallFunc(func, returnType, argTypes, Array.prototype.slice.call(arguments));
  }
}
Module["cwrap"] = cwrap;

// Sets a value in memory in a dynamic way at run-time. Uses the
// type data. This is the same as makeSetValue, except that
// makeSetValue is done at compile-time and generates the needed
// code then, whereas this function picks the right code at
// run-time.
// Note that setValue and getValue only do *aligned* writes and reads!
// Note that ccall uses JS types as for defining types, while setValue and
// getValue need LLVM types ('i8', 'i32') - this is a lower-level operation
function setValue(ptr, value, type, noSafe) {
  type = type || 'i8';
  if (type.charAt(type.length-1) === '*') type = 'i32'; // pointers are 32-bit
    switch(type) {
      case 'i1': HEAP8[(ptr)]=value; break;
      case 'i8': HEAP8[(ptr)]=value; break;
      case 'i16': HEAP16[((ptr)>>1)]=value; break;
      case 'i32': HEAP32[((ptr)>>2)]=value; break;
      case 'i64': (tempI64 = [value>>>0,(tempDouble=value,(+(Math_abs(tempDouble))) >= (+1) ? (tempDouble > (+0) ? ((Math_min((+(Math_floor((tempDouble)/(+4294967296)))), (+4294967295)))|0)>>>0 : (~~((+(Math_ceil((tempDouble - +(((~~(tempDouble)))>>>0))/(+4294967296))))))>>>0) : 0)],HEAP32[((ptr)>>2)]=tempI64[0],HEAP32[(((ptr)+(4))>>2)]=tempI64[1]); break;
      case 'float': HEAPF32[((ptr)>>2)]=value; break;
      case 'double': HEAPF64[((ptr)>>3)]=value; break;
      default: abort('invalid type for setValue: ' + type);
    }
}
Module['setValue'] = setValue;

// Parallel to setValue.
function getValue(ptr, type, noSafe) {
  type = type || 'i8';
  if (type.charAt(type.length-1) === '*') type = 'i32'; // pointers are 32-bit
    switch(type) {
      case 'i1': return HEAP8[(ptr)];
      case 'i8': return HEAP8[(ptr)];
      case 'i16': return HEAP16[((ptr)>>1)];
      case 'i32': return HEAP32[((ptr)>>2)];
      case 'i64': return HEAP32[((ptr)>>2)];
      case 'float': return HEAPF32[((ptr)>>2)];
      case 'double': return HEAPF64[((ptr)>>3)];
      default: abort('invalid type for setValue: ' + type);
    }
  return null;
}
Module['getValue'] = getValue;

var ALLOC_NORMAL = 0; // Tries to use _malloc()
var ALLOC_STACK = 1; // Lives for the duration of the current function call
var ALLOC_STATIC = 2; // Cannot be freed
var ALLOC_DYNAMIC = 3; // Cannot be freed except through sbrk
var ALLOC_NONE = 4; // Do not allocate
Module['ALLOC_NORMAL'] = ALLOC_NORMAL;
Module['ALLOC_STACK'] = ALLOC_STACK;
Module['ALLOC_STATIC'] = ALLOC_STATIC;
Module['ALLOC_DYNAMIC'] = ALLOC_DYNAMIC;
Module['ALLOC_NONE'] = ALLOC_NONE;

// allocate(): This is for internal use. You can use it yourself as well, but the interface
//             is a little tricky (see docs right below). The reason is that it is optimized
//             for multiple syntaxes to save space in generated code. So you should
//             normally not use allocate(), and instead allocate memory using _malloc(),
//             initialize it with setValue(), and so forth.
// @slab: An array of data, or a number. If a number, then the size of the block to allocate,
//        in *bytes* (note that this is sometimes confusing: the next parameter does not
//        affect this!)
// @types: Either an array of types, one for each byte (or 0 if no type at that position),
//         or a single type which is used for the entire block. This only matters if there
//         is initial data - if @slab is a number, then this does not matter at all and is
//         ignored.
// @allocator: How to allocate memory, see ALLOC_*
function allocate(slab, types, allocator, ptr) {
  var zeroinit, size;
  if (typeof slab === 'number') {
    zeroinit = true;
    size = slab;
  } else {
    zeroinit = false;
    size = slab.length;
  }

  var singleType = typeof types === 'string' ? types : null;

  var ret;
  if (allocator == ALLOC_NONE) {
    ret = ptr;
  } else {
    ret = [_malloc, Runtime.stackAlloc, Runtime.staticAlloc, Runtime.dynamicAlloc][allocator === undefined ? ALLOC_STATIC : allocator](Math.max(size, singleType ? 1 : types.length));
  }

  if (zeroinit) {
    var ptr = ret, stop;
    assert((ret & 3) == 0);
    stop = ret + (size & ~3);
    for (; ptr < stop; ptr += 4) {
      HEAP32[((ptr)>>2)]=0;
    }
    stop = ret + size;
    while (ptr < stop) {
      HEAP8[((ptr++)|0)]=0;
    }
    return ret;
  }

  if (singleType === 'i8') {
    if (slab.subarray || slab.slice) {
      HEAPU8.set(slab, ret);
    } else {
      HEAPU8.set(new Uint8Array(slab), ret);
    }
    return ret;
  }

  var i = 0, type, typeSize, previousType;
  while (i < size) {
    var curr = slab[i];

    if (typeof curr === 'function') {
      curr = Runtime.getFunctionIndex(curr);
    }

    type = singleType || types[i];
    if (type === 0) {
      i++;
      continue;
    }

    if (type == 'i64') type = 'i32'; // special case: we have one i32 here, and one i32 later

    setValue(ret+i, curr, type);

    // no need to look up size unless type changes, so cache it
    if (previousType !== type) {
      typeSize = Runtime.getNativeTypeSize(type);
      previousType = type;
    }
    i += typeSize;
  }

  return ret;
}
Module['allocate'] = allocate;

function Pointer_stringify(ptr, /* optional */ length) {
  // TODO: use TextDecoder
  // Find the length, and check for UTF while doing so
  var hasUtf = false;
  var t;
  var i = 0;
  while (1) {
    t = HEAPU8[(((ptr)+(i))|0)];
    if (t >= 128) hasUtf = true;
    else if (t == 0 && !length) break;
    i++;
    if (length && i == length) break;
  }
  if (!length) length = i;

  var ret = '';

  if (!hasUtf) {
    var MAX_CHUNK = 1024; // split up into chunks, because .apply on a huge string can overflow the stack
    var curr;
    while (length > 0) {
      curr = String.fromCharCode.apply(String, HEAPU8.subarray(ptr, ptr + Math.min(length, MAX_CHUNK)));
      ret = ret ? ret + curr : curr;
      ptr += MAX_CHUNK;
      length -= MAX_CHUNK;
    }
    return ret;
  }

  var utf8 = new Runtime.UTF8Processor();
  for (i = 0; i < length; i++) {
    t = HEAPU8[(((ptr)+(i))|0)];
    ret += utf8.processCChar(t);
  }
  return ret;
}
Module['Pointer_stringify'] = Pointer_stringify;

// Given a pointer 'ptr' to a null-terminated UTF16LE-encoded string in the emscripten HEAP, returns
// a copy of that string as a Javascript String object.
function UTF16ToString(ptr) {
  var i = 0;

  var str = '';
  while (1) {
    var codeUnit = HEAP16[(((ptr)+(i*2))>>1)];
    if (codeUnit == 0)
      return str;
    ++i;
    // fromCharCode constructs a character from a UTF-16 code unit, so we can pass the UTF16 string right through.
    str += String.fromCharCode(codeUnit);
  }
}
Module['UTF16ToString'] = UTF16ToString;

// Copies the given Javascript String object 'str' to the emscripten HEAP at address 'outPtr',
// null-terminated and encoded in UTF16LE form. The copy will require at most (str.length*2+1)*2 bytes of space in the HEAP.
function stringToUTF16(str, outPtr) {
  for(var i = 0; i < str.length; ++i) {
    // charCodeAt returns a UTF-16 encoded code unit, so it can be directly written to the HEAP.
    var codeUnit = str.charCodeAt(i); // possibly a lead surrogate
    HEAP16[(((outPtr)+(i*2))>>1)]=codeUnit;
  }
  // Null-terminate the pointer to the HEAP.
  HEAP16[(((outPtr)+(str.length*2))>>1)]=0;
}
Module['stringToUTF16'] = stringToUTF16;

// Given a pointer 'ptr' to a null-terminated UTF32LE-encoded string in the emscripten HEAP, returns
// a copy of that string as a Javascript String object.
function UTF32ToString(ptr) {
  var i = 0;

  var str = '';
  while (1) {
    var utf32 = HEAP32[(((ptr)+(i*4))>>2)];
    if (utf32 == 0)
      return str;
    ++i;
    // Gotcha: fromCharCode constructs a character from a UTF-16 encoded code (pair), not from a Unicode code point! So encode the code point to UTF-16 for constructing.
    if (utf32 >= 0x10000) {
      var ch = utf32 - 0x10000;
      str += String.fromCharCode(0xD800 | (ch >> 10), 0xDC00 | (ch & 0x3FF));
    } else {
      str += String.fromCharCode(utf32);
    }
  }
}
Module['UTF32ToString'] = UTF32ToString;

// Copies the given Javascript String object 'str' to the emscripten HEAP at address 'outPtr',
// null-terminated and encoded in UTF32LE form. The copy will require at most (str.length+1)*4 bytes of space in the HEAP,
// but can use less, since str.length does not return the number of characters in the string, but the number of UTF-16 code units in the string.
function stringToUTF32(str, outPtr) {
  var iChar = 0;
  for(var iCodeUnit = 0; iCodeUnit < str.length; ++iCodeUnit) {
    // Gotcha: charCodeAt returns a 16-bit word that is a UTF-16 encoded code unit, not a Unicode code point of the character! We must decode the string to UTF-32 to the heap.
    var codeUnit = str.charCodeAt(iCodeUnit); // possibly a lead surrogate
    if (codeUnit >= 0xD800 && codeUnit <= 0xDFFF) {
      var trailSurrogate = str.charCodeAt(++iCodeUnit);
      codeUnit = 0x10000 + ((codeUnit & 0x3FF) << 10) | (trailSurrogate & 0x3FF);
    }
    HEAP32[(((outPtr)+(iChar*4))>>2)]=codeUnit;
    ++iChar;
  }
  // Null-terminate the pointer to the HEAP.
  HEAP32[(((outPtr)+(iChar*4))>>2)]=0;
}
Module['stringToUTF32'] = stringToUTF32;

function demangle(func) {
  var i = 3;
  // params, etc.
  var basicTypes = {
    'v': 'void',
    'b': 'bool',
    'c': 'char',
    's': 'short',
    'i': 'int',
    'l': 'long',
    'f': 'float',
    'd': 'double',
    'w': 'wchar_t',
    'a': 'signed char',
    'h': 'unsigned char',
    't': 'unsigned short',
    'j': 'unsigned int',
    'm': 'unsigned long',
    'x': 'long long',
    'y': 'unsigned long long',
    'z': '...'
  };
  var subs = [];
  var first = true;
  function dump(x) {
    //return;
    if (x) Module.print(x);
    Module.print(func);
    var pre = '';
    for (var a = 0; a < i; a++) pre += ' ';
    Module.print (pre + '^');
  }
  function parseNested() {
    i++;
    if (func[i] === 'K') i++; // ignore const
    var parts = [];
    while (func[i] !== 'E') {
      if (func[i] === 'S') { // substitution
        i++;
        var next = func.indexOf('_', i);
        var num = func.substring(i, next) || 0;
        parts.push(subs[num] || '?');
        i = next+1;
        continue;
      }
      if (func[i] === 'C') { // constructor
        parts.push(parts[parts.length-1]);
        i += 2;
        continue;
      }
      var size = parseInt(func.substr(i));
      var pre = size.toString().length;
      if (!size || !pre) { i--; break; } // counter i++ below us
      var curr = func.substr(i + pre, size);
      parts.push(curr);
      subs.push(curr);
      i += pre + size;
    }
    i++; // skip E
    return parts;
  }
  function parse(rawList, limit, allowVoid) { // main parser
    limit = limit || Infinity;
    var ret = '', list = [];
    function flushList() {
      return '(' + list.join(', ') + ')';
    }
    var name;
    if (func[i] === 'N') {
      // namespaced N-E
      name = parseNested().join('::');
      limit--;
      if (limit === 0) return rawList ? [name] : name;
    } else {
      // not namespaced
      if (func[i] === 'K' || (first && func[i] === 'L')) i++; // ignore const and first 'L'
      var size = parseInt(func.substr(i));
      if (size) {
        var pre = size.toString().length;
        name = func.substr(i + pre, size);
        i += pre + size;
      }
    }
    first = false;
    if (func[i] === 'I') {
      i++;
      var iList = parse(true);
      var iRet = parse(true, 1, true);
      ret += iRet[0] + ' ' + name + '<' + iList.join(', ') + '>';
    } else {
      ret = name;
    }
    paramLoop: while (i < func.length && limit-- > 0) {
      //dump('paramLoop');
      var c = func[i++];
      if (c in basicTypes) {
        list.push(basicTypes[c]);
      } else {
        switch (c) {
          case 'P': list.push(parse(true, 1, true)[0] + '*'); break; // pointer
          case 'R': list.push(parse(true, 1, true)[0] + '&'); break; // reference
          case 'L': { // literal
            i++; // skip basic type
            var end = func.indexOf('E', i);
            var size = end - i;
            list.push(func.substr(i, size));
            i += size + 2; // size + 'EE'
            break;
          }
          case 'A': { // array
            var size = parseInt(func.substr(i));
            i += size.toString().length;
            if (func[i] !== '_') throw '?';
            i++; // skip _
            list.push(parse(true, 1, true)[0] + ' [' + size + ']');
            break;
          }
          case 'E': break paramLoop;
          default: ret += '?' + c; break paramLoop;
        }
      }
    }
    if (!allowVoid && list.length === 1 && list[0] === 'void') list = []; // avoid (void)
    return rawList ? list : ret + flushList();
  }
  try {
    // Special-case the entry point, since its name differs from other name mangling.
    if (func == 'Object._main' || func == '_main') {
      return 'main()';
    }
    if (typeof func === 'number') func = Pointer_stringify(func);
    if (func[0] !== '_') return func;
    if (func[1] !== '_') return func; // C function
    if (func[2] !== 'Z') return func;
    switch (func[3]) {
      case 'n': return 'operator new()';
      case 'd': return 'operator delete()';
    }
    return parse();
  } catch(e) {
    return func;
  }
}

function demangleAll(text) {
  return text.replace(/__Z[\w\d_]+/g, function(x) { var y = demangle(x); return x === y ? x : (x + ' [' + y + ']') });
}

function stackTrace() {
  var stack = new Error().stack;
  return stack ? demangleAll(stack) : '(no stack trace available)'; // Stack trace is not available at least on IE10 and Safari 6.
}

// Memory management

var PAGE_SIZE = 4096;
function alignMemoryPage(x) {
  return (x+4095)&-4096;
}

var HEAP;
var HEAP8, HEAPU8, HEAP16, HEAPU16, HEAP32, HEAPU32, HEAPF32, HEAPF64;

var STATIC_BASE = 0, STATICTOP = 0, staticSealed = false; // static area
var STACK_BASE = 0, STACKTOP = 0, STACK_MAX = 0; // stack area
var DYNAMIC_BASE = 0, DYNAMICTOP = 0; // dynamic area handled by sbrk

function enlargeMemory() {
  abort('Cannot enlarge memory arrays. Either (1) compile with -s TOTAL_MEMORY=X with X higher than the current value ' + TOTAL_MEMORY + ', (2) compile with ALLOW_MEMORY_GROWTH which adjusts the size at runtime but prevents some optimizations, or (3) set Module.TOTAL_MEMORY before the program runs.');
}

var TOTAL_STACK = Module['TOTAL_STACK'] || 5242880;
var TOTAL_MEMORY = Module['TOTAL_MEMORY'] || 16777216;
var FAST_MEMORY = Module['FAST_MEMORY'] || 2097152;

var totalMemory = 4096;
while (totalMemory < TOTAL_MEMORY || totalMemory < 2*TOTAL_STACK) {
  if (totalMemory < 16*1024*1024) {
    totalMemory *= 2;
  } else {
    totalMemory += 16*1024*1024
  }
}
if (totalMemory !== TOTAL_MEMORY) {
  Module.printErr('increasing TOTAL_MEMORY to ' + totalMemory + ' to be more reasonable');
  TOTAL_MEMORY = totalMemory;
}

// Initialize the runtime's memory
// check for full engine support (use string 'subarray' to avoid closure compiler confusion)
assert(typeof Int32Array !== 'undefined' && typeof Float64Array !== 'undefined' && !!(new Int32Array(1)['subarray']) && !!(new Int32Array(1)['set']),
       'JS engine does not provide full typed array support');

var buffer = new ArrayBuffer(TOTAL_MEMORY);
HEAP8 = new Int8Array(buffer);
HEAP16 = new Int16Array(buffer);
HEAP32 = new Int32Array(buffer);
HEAPU8 = new Uint8Array(buffer);
HEAPU16 = new Uint16Array(buffer);
HEAPU32 = new Uint32Array(buffer);
HEAPF32 = new Float32Array(buffer);
HEAPF64 = new Float64Array(buffer);

// Endianness check (note: assumes compiler arch was little-endian)
HEAP32[0] = 255;
assert(HEAPU8[0] === 255 && HEAPU8[3] === 0, 'Typed arrays 2 must be run on a little-endian system');

Module['HEAP'] = HEAP;
Module['HEAP8'] = HEAP8;
Module['HEAP16'] = HEAP16;
Module['HEAP32'] = HEAP32;
Module['HEAPU8'] = HEAPU8;
Module['HEAPU16'] = HEAPU16;
Module['HEAPU32'] = HEAPU32;
Module['HEAPF32'] = HEAPF32;
Module['HEAPF64'] = HEAPF64;

function callRuntimeCallbacks(callbacks) {
  while(callbacks.length > 0) {
    var callback = callbacks.shift();
    if (typeof callback == 'function') {
      callback();
      continue;
    }
    var func = callback.func;
    if (typeof func === 'number') {
      if (callback.arg === undefined) {
        Runtime.dynCall('v', func);
      } else {
        Runtime.dynCall('vi', func, [callback.arg]);
      }
    } else {
      func(callback.arg === undefined ? null : callback.arg);
    }
  }
}

var __ATPRERUN__  = []; // functions called before the runtime is initialized
var __ATINIT__    = []; // functions called during startup
var __ATMAIN__    = []; // functions called when main() is to be run
var __ATEXIT__    = []; // functions called during shutdown
var __ATPOSTRUN__ = []; // functions called after the runtime has exited

var runtimeInitialized = false;

function preRun() {
  // compatibility - merge in anything from Module['preRun'] at this time
  if (Module['preRun']) {
    if (typeof Module['preRun'] == 'function') Module['preRun'] = [Module['preRun']];
    while (Module['preRun'].length) {
      addOnPreRun(Module['preRun'].shift());
    }
  }
  callRuntimeCallbacks(__ATPRERUN__);
}

function ensureInitRuntime() {
  if (runtimeInitialized) return;
  runtimeInitialized = true;
  callRuntimeCallbacks(__ATINIT__);
}

function preMain() {
  callRuntimeCallbacks(__ATMAIN__);
}

function exitRuntime() {
  callRuntimeCallbacks(__ATEXIT__);
}

function postRun() {
  // compatibility - merge in anything from Module['postRun'] at this time
  if (Module['postRun']) {
    if (typeof Module['postRun'] == 'function') Module['postRun'] = [Module['postRun']];
    while (Module['postRun'].length) {
      addOnPostRun(Module['postRun'].shift());
    }
  }
  callRuntimeCallbacks(__ATPOSTRUN__);
}

function addOnPreRun(cb) {
  __ATPRERUN__.unshift(cb);
}
Module['addOnPreRun'] = Module.addOnPreRun = addOnPreRun;

function addOnInit(cb) {
  __ATINIT__.unshift(cb);
}
Module['addOnInit'] = Module.addOnInit = addOnInit;

function addOnPreMain(cb) {
  __ATMAIN__.unshift(cb);
}
Module['addOnPreMain'] = Module.addOnPreMain = addOnPreMain;

function addOnExit(cb) {
  __ATEXIT__.unshift(cb);
}
Module['addOnExit'] = Module.addOnExit = addOnExit;

function addOnPostRun(cb) {
  __ATPOSTRUN__.unshift(cb);
}
Module['addOnPostRun'] = Module.addOnPostRun = addOnPostRun;

// Tools

// This processes a JS string into a C-line array of numbers, 0-terminated.
// For LLVM-originating strings, see parser.js:parseLLVMString function
function intArrayFromString(stringy, dontAddNull, length /* optional */) {
  var ret = (new Runtime.UTF8Processor()).processJSString(stringy);
  if (length) {
    ret.length = length;
  }
  if (!dontAddNull) {
    ret.push(0);
  }
  return ret;
}
Module['intArrayFromString'] = intArrayFromString;

function intArrayToString(array) {
  var ret = [];
  for (var i = 0; i < array.length; i++) {
    var chr = array[i];
    if (chr > 0xFF) {
      chr &= 0xFF;
    }
    ret.push(String.fromCharCode(chr));
  }
  return ret.join('');
}
Module['intArrayToString'] = intArrayToString;

// Write a Javascript array to somewhere in the heap
function writeStringToMemory(string, buffer, dontAddNull) {
  var array = intArrayFromString(string, dontAddNull);
  var i = 0;
  while (i < array.length) {
    var chr = array[i];
    HEAP8[(((buffer)+(i))|0)]=chr;
    i = i + 1;
  }
}
Module['writeStringToMemory'] = writeStringToMemory;

function writeArrayToMemory(array, buffer) {
  for (var i = 0; i < array.length; i++) {
    HEAP8[(((buffer)+(i))|0)]=array[i];
  }
}
Module['writeArrayToMemory'] = writeArrayToMemory;

function writeAsciiToMemory(str, buffer, dontAddNull) {
  for (var i = 0; i < str.length; i++) {
    HEAP8[(((buffer)+(i))|0)]=str.charCodeAt(i);
  }
  if (!dontAddNull) HEAP8[(((buffer)+(str.length))|0)]=0;
}
Module['writeAsciiToMemory'] = writeAsciiToMemory;

function unSign(value, bits, ignore) {
  if (value >= 0) {
    return value;
  }
  return bits <= 32 ? 2*Math.abs(1 << (bits-1)) + value // Need some trickery, since if bits == 32, we are right at the limit of the bits JS uses in bitshifts
                    : Math.pow(2, bits)         + value;
}
function reSign(value, bits, ignore) {
  if (value <= 0) {
    return value;
  }
  var half = bits <= 32 ? Math.abs(1 << (bits-1)) // abs is needed if bits == 32
                        : Math.pow(2, bits-1);
  if (value >= half && (bits <= 32 || value > half)) { // for huge values, we can hit the precision limit and always get true here. so don't do that
                                                       // but, in general there is no perfect solution here. With 64-bit ints, we get rounding and errors
                                                       // TODO: In i64 mode 1, resign the two parts separately and safely
    value = -2*half + value; // Cannot bitshift half, as it may be at the limit of the bits JS uses in bitshifts
  }
  return value;
}

// check for imul support, and also for correctness ( https://bugs.webkit.org/show_bug.cgi?id=126345 )
if (!Math['imul'] || Math['imul'](0xffffffff, 5) !== -5) Math['imul'] = function imul(a, b) {
  var ah  = a >>> 16;
  var al = a & 0xffff;
  var bh  = b >>> 16;
  var bl = b & 0xffff;
  return (al*bl + ((ah*bl + al*bh) << 16))|0;
};
Math.imul = Math['imul'];


var Math_abs = Math.abs;
var Math_cos = Math.cos;
var Math_sin = Math.sin;
var Math_tan = Math.tan;
var Math_acos = Math.acos;
var Math_asin = Math.asin;
var Math_atan = Math.atan;
var Math_atan2 = Math.atan2;
var Math_exp = Math.exp;
var Math_log = Math.log;
var Math_sqrt = Math.sqrt;
var Math_ceil = Math.ceil;
var Math_floor = Math.floor;
var Math_pow = Math.pow;
var Math_imul = Math.imul;
var Math_fround = Math.fround;
var Math_min = Math.min;

// A counter of dependencies for calling run(). If we need to
// do asynchronous work before running, increment this and
// decrement it. Incrementing must happen in a place like
// PRE_RUN_ADDITIONS (used by emcc to add file preloading).
// Note that you can add dependencies in preRun, even though
// it happens right before run - run will be postponed until
// the dependencies are met.
var runDependencies = 0;
var runDependencyWatcher = null;
var dependenciesFulfilled = null; // overridden to take different actions when all run dependencies are fulfilled

function addRunDependency(id) {
  runDependencies++;
  if (Module['monitorRunDependencies']) {
    Module['monitorRunDependencies'](runDependencies);
  }
}
Module['addRunDependency'] = addRunDependency;
function removeRunDependency(id) {
  runDependencies--;
  if (Module['monitorRunDependencies']) {
    Module['monitorRunDependencies'](runDependencies);
  }
  if (runDependencies == 0) {
    if (runDependencyWatcher !== null) {
      clearInterval(runDependencyWatcher);
      runDependencyWatcher = null;
    }
    if (dependenciesFulfilled) {
      var callback = dependenciesFulfilled;
      dependenciesFulfilled = null;
      callback(); // can add another dependenciesFulfilled
    }
  }
}
Module['removeRunDependency'] = removeRunDependency;

Module["preloadedImages"] = {}; // maps url to image data
Module["preloadedAudios"] = {}; // maps url to audio data


var memoryInitializer = null;

// === Body ===



STATIC_BASE = 8;

STATICTOP = STATIC_BASE + 101160;


/* global initializers */ __ATINIT__.push({ func: function() { runPostSets() } });





















































/* memory initializer */ allocate([235,1,0,0,186,71,107,63,97,71,107,63,86,70,107,63,153,68,107,63,42,66,107,63,8,63,107,63,53,59,107,63,175,54,107,63,120,49,107,63,143,43,107,63,243,36,107,63,166,29,107,63,167,21,107,63,246,12,107,63,147,3,107,63,127,249,106,63,185,238,106,63,65,227,106,63,23,215,106,63,61,202,106,63,176,188,106,63,115,174,106,63,132,159,106,63,228,143,106,63,147,127,106,63,144,110,106,63,221,92,106,63,121,74,106,63,101,55,106,63,159,35,106,63,41,15,106,63,3,250,105,63,44,228,105,63,166,205,105,63,111,182,105,63,136,158,105,63,241,133,105,63,171,108,105,63,181,82,105,63,15,56,105,63,187,28,105,63,183,0,105,63,4,228,104,63,163,198,104,63,146,168,104,63,212,137,104,63,102,106,104,63,75,74,104,63,130,41,104,63,11,8,104,63,230,229,103,63,20,195,103,63,148,159,103,63,103,123,103,63,142,86,103,63,7,49,103,63,212,10,103,63,245,227,102,63,106,188,102,63,51,148,102,63,80,107,102,63,194,65,102,63,136,23,102,63,164,236,101,63,21,193,101,63,219,148,101,63,246,103,101,63,104,58,101,63,48,12,101,63,78,221,100,63,195,173,100,63,143,125,100,63,178,76,100,63,45,27,100,63,255,232,99,63,41,182,99,63,171,130,99,63,134,78,99,63,186,25,99,63,70,228,98,63,44,174,98,63,108,119,98,63,6,64,98,63,249,7,98,63,72,207,97,63,241,149,97,63,245,91,97,63,85,33,97,63,16,230,96,63,40,170,96,63,155,109,96,63,108,48,96,63,153,242,95,63,36,180,95,63,13,117,95,63,83,53,95,63,248,244,94,63,252,179,94,63,94,114,94,63,32,48,94,63,66,237,93,63,196,169,93,63,166,101,93,63,233,32,93,63,141,219,92,63,147,149,92,63,251,78,92,63,197,7,92,63,242,191,91,63,129,119,91,63,116,46,91,63,203,228,90,63,134,154,90,63,166,79,90,63,43,4,90,63,21,184,89,63,101,107,89,63,27,30,89,63,55,208,88,63,187,129,88,63,166,50,88,63,249,226,87,63,180,146,87,63,216,65,87,63,101,240,86,63,92,158,86,63,188,75,86,63,135,248,85,63,189,164,85,63,94,80,85,63,107,251,84,63,228,165,84,63,202,79,84,63,29,249,83,63,221,161,83,63,12,74,83,63,169,241,82,63,181,152,82,63,48,63,82,63,27,229,81,63,119,138,81,63,67,47,81,63,129,211,80,63,48,119,80,63,82,26,80,63,230,188,79,63,238,94,79,63,106,0,79,63,89,161,78,63,190,65,78,63,152,225,77,63,231,128,77,63,173,31,77,63,234,189,76,63,158,91,76,63,201,248,75,63,109,149,75,63,138,49,75,63,33,205,74,63,49,104,74,63,187,2,74,63,193,156,73,63,66,54,73,63,63,207,72,63,185,103,72,63,176,255,71,63,36,151,71,63,23,46,71,63,136,196,70,63,121,90,70,63,233,239,69,63,218,132,69,63,76,25,69,63,64,173,68,63,182,64,68,63,174,211,67,63,41,102,67,63,41,248,66,63,173,137,66,63,181,26,66,63,68,171,65,63,88,59,65,63,243,202,64,63,21,90,64,63,192,232,63,63,242,118,63,63,174,4,63,63,244,145,62,63,196,30,62,63,30,171,61,63,4,55,61,63,118,194,60,63,117,77,60,63,1,216,59,63,27,98,59,63,196,235,58,63,251,116,58,63,194,253,57,63,26,134,57,63,3,14,57,63,125,149,56,63,137,28,56,63,40,163,55,63,91,41,55,63,34,175,54,63,126,52,54,63,111,185,53,63,246,61,53,63,19,194,52,63,200,69,52,63,21,201,51,63,250,75,51,63,121,206,50,63,145,80,50,63,68,210,49,63,146,83,49,63,124,212,48,63,3,85,48,63,39,213,47,63,232,84,47,63,72,212,46,63,71,83,46,63,230,209,45,63,37,80,45,63,5,206,44,63,135,75,44,63,172,200,43,63,115,69,43,63,223,193,42,63,239,61,42,63,164,185,41,63,255,52,41,63,0,176,40,63,169,42,40,63,250,164,39,63,243,30,39,63,149,152,38,63,225,17,38,63,216,138,37,63,123,3,37,63,201,123,36,63,196,243,35,63,108,107,35,63,195,226,34,63,200,89,34,63,125,208,33,63,226,70,33,63,247,188,32,63,191,50,32,63,57,168,31,63,101,29,31,63,70,146,30,63,219,6,30,63,37,123,29,63,36,239,28,63,219,98,28,63,72,214,27,63,110,73,27,63,76,188,26,63,228,46,26,63,54,161,25,63,67,19,25,63,11,133,24,63,143,246,23,63,209,103,23,63,208,216,22,63,142,73,22,63,11,186,21,63,71,42,21,63,69,154,20,63,3,10,20,63,132,121,19,63,200,232,18,63,207,87,18,63,154,198,17,63,43,53,17,63,129,163,16,63,157,17,16,63,129,127,15,63,45,237,14,63,161,90,14,63,223,199,13,63,231,52,13,63,185,161,12,63,88,14,12,63,194,122,11,63,250,230,10,63,255,82,10,63,212,190,9,63,119,42,9,63,234,149,8,63,47,1,8,63,68,108,7,63,44,215,6,63,231,65,6,63,118,172,5,63,217,22,5,63,18,129,4,63,32,235,3,63,6,85,3,63,194,190,2,63,87,40,2,63,197,145,1,63,13,251,0,63,47,100,0,63,88,154,255,62,10,108,254,62,118,61,253,62,157,14,252,62,128,223,250,62,33,176,249,62,129,128,248,62,163,80,247,62,134,32,246,62,46,240,244,62,155,191,243,62,207,142,242,62,203,93,241,62,145,44,240,62,34,251,238,62,128,201,237,62,173,151,236,62,169,101,235,62,119,51,234,62,24,1,233,62,141,206,231,62,216,155,230,62,250,104,229,62,245,53,228,62,202,2,227,62,123,207,225,62,10,156,224,62,119,104,223,62,197,52,222,62,244,0,221,62,7,205,219,62,255,152,218,62,221,100,217,62,163,48,216,62,82,252,214,62,235,199,213,62,113,147,212,62,229,94,211,62,72,42,210,62,156,245,208,62,226,192,207,62,28,140,206,62,74,87,205,62,112,34,204,62,142,237,202,62,165,184,201,62,183,131,200,62,198,78,199,62,212,25,198,62,224,228,196,62,238,175,195,62,254,122,194,62,18,70,193,62,44,17,192,62,77,220,190,62,118,167,189,62,169,114,188,62,231,61,187,62,50,9,186,62,139,212,184,62,244,159,183,62,110,107,182,62,251,54,181,62,156,2,180,62,82,206,178,62,32,154,177,62,6,102,176,62,6,50,175,62,33,254,173,62,89,202,172,62,175,150,171,62,37,99,170,62,189,47,169,62,118,252,167,62,84,201,166,62,88,150,165,62,130,99,164,62,212,48,163,62,80,254,161,62,248,203,160,62,204,153,159,62,206,103,158,62,255,53,157,62,98,4,156,62,246,210,154,62,191,161,153,62,188,112,152,62,240,63,151,62,92,15,150,62,1,223,148,62,225,174,147,62,253,126,146,62,87,79,145,62,240,31,144,62,200,240,142,62,227,193,141,62,65,147,140,62,227,100,139,62,203,54,138,62,250,8,137,62,113,219,135,62,51,174,134,62,64,129,133,62,154,84,132,62,65,40,131,62,57,252,129,62,129,208,128,62,53,74,127,62,16,244,124,62,149,158,122,62,198,73,120,62,166,245,117,62,56,162,115,62,126,79,113,62,124,253,110,62,50,172,108,62,166,91,106,62,216,11,104,62,203,188,101,62,131,110,99,62,2,33,97,62,75,212,94,62,95,136,92,62,66,61,90,62,246,242,87,62,126,169,85,62,221,96,83,62,21,25,81,62,40,210,78,62,25,140,76,62,235,70,74,62,160,2,72,62,59,191,69,62,190,124,67,62,44,59,65,62,135,250,62,62,210,186,60,62,16,124,58,62,66,62,56,62,107,1,54,62,143,197,51,62,174,138,49,62,204,80,47,62,235,23,45,62,14,224,42,62,54,169,40,62,103,115,38,62,163,62,36,62,236,10,34,62,69,216,31,62,175,166,29,62,47,118,27,62,196,70,25,62,115,24,23,62,62,235,20,62,38,191,18,62,46,148,16,62,89,106,14,62,168,65,12,62,31,26,10,62,191,243,7,62,139,206,5,62,132,170,3,62,174,135,1,62,22,204,254,61,56,139,250,61,201,76,246,61,204,16,242,61,69,215,237,61,59,160,233,61,176,107,229,61,169,57,225,61,42,10,221,61,57,221,216,61,217,178,212,61,14,139,208,61,221,101,204,61,75,67,200,61,90,35,196,61,17,6,192,61,114,235,187,61,130,211,183,61,69,190,179,61,192,171,175,61,246,155,171,61,236,142,167,61,166,132,163,61,39,125,159,61,117,120,155,61,146,118,151,61,131,119,147,61,76,123,143,61,241,129,139,61,117,139,135,61,221,151,131,61,91,78,127,61,210,114,119,61,40,157,111,61,100,205,103,61,143,3,96,61,175,63,88,61,204,129,80,61,239,201,72,61,29,24,65,61,96,108,57,61,189,198,49,61,61,39,42,61,231,141,34,61,193,250,26,61,212,109,19,61,38,231,11,61,191,102,4,61,73,217,249,60,191,241,234,60,235,22,220,60,221,72,205,60,161,135,190,60,70,211,175,60,216,43,161,60,103,145,146,60,254,3,132,60,87,7,107,60,249,32,78,60,253,84,49,60,124,163,20,60,33,25,240,59,168,32,183,59,130,187,124,59,58,161,11,59,250,150,215,57,86,158,170,186,130,36,69,187,98,70,154,187,198,195,209,187,31,133,4,188,206,12,32,188,214,120,59,188,33,201,86,188,150,253,113,188,16,139,134,188,83,9,148,188,137,121,161,188,166,219,174,188,160,47,188,188,107,117,201,188,252,172,214,188,72,214,227,188,69,241,240,188,231,253,253,188,18,126,5,189,249,245,11,189,162,102,18,189,10,208,24,189,42,50,31,189,253,140,37,189,128,224,43,189,171,44,50,189,124,113,56,189,236,174,62,189,247,228,68,189,152,19,75,189,202,58,81,189,138,90,87,189,209,114,93,189,156,131,99,189,229,140,105,189,170,142,111,189,228,136,117,189,144,123,123,189,85,179,128,189,22,165,131,189,9,147,134,189,45,125,137,189,127,99,140,189,254,69,143,189,166,36,146,189,119,255,148,189,110,214,151,189,138,169,154,189,200,120,157,189,38,68,160,189,163,11,163,189,61,207,165,189,242,142,168,189,192,74,171,189,166,2,174,189,161,182,176,189,177,102,179,189,211,18,182,189,5,187,184,189,71,95,187,189,150,255,189,189,241,155,192,189,87,52,195,189,197,200,197,189,59,89,200,189,182,229,202,189,54,110,205,189,185,242,207,189,62,115,210,189,195,239,212,189,70,104,215,189,200,220,217,189,69,77,220,189,190,185,222,189,48,34,225,189,155,134,227,189,253,230,229,189,86,67,232,189,163,155,234,189,229,239,236,189,25,64,239,189,63,140,241,189,86,212,243,189,93,24,246,189,83,88,248,189,54,148,250,189,6,204,252,189,195,255,254,189,181,151,0,190,126,173,1,190,60,193,2,190,238,210,3,190,148,226,4,190,45,240,5,190,186,251,6,190,57,5,8,190,171,12,9,190,16,18,10,190,102,21,11,190,174,22,12,190,231,21,13,190,17,19,14,190,44,14,15,190,56,7,16,190,51,254,16,190,31,243,17,190,251,229,18,190,199,214,19,190,130,197,20,190,44,178,21,190,198,156,22,190,79,133,23,190,198,107,24,190,45,80,25,190,130,50,26,190,198,18,27,190,248,240,27,190,25,205,28,190,40,167,29,190,38,127,30,190,18,85,31,190,236,40,32,190,181,250,32,190,108,202,33,190,18,152,34,190,166,99,35,190,40,45,36,190,153,244,36,190,249,185,37,190,71,125,38,190,132,62,39,190,176,253,39,190,203,186,40,190,213,117,41,190,207,46,42,190,184,229,42,190,145,154,43,190,90,77,44,190,18,254,44,190,187,172,45,190,85,89,46,190,223,3,47,190,90,172,47,190,199,82,48,190,37,247,48,190,117,153,49,190,183,57,50,190,236,215,50,190,19,116,51,190,45,14,52,190,59,166,52,190,61,60,53,190,50,208,53,190,29,98,54,190,252,241,54,190,209,127,55,190,155,11,56,190,92,149,56,190,20,29,57,190,194,162,57,190,104,38,58,190,7,168,58,190,158,39,59,190,46,165,59,190,183,32,60,190,59,154,60,190,186,17,61,190,51,135,61,190,169,250,61,190,27,108,62,190,137,219,62,190,246,72,63,190,97,180,63,190,202,29,64,190,51,133,64,190,156,234,64,190,6,78,65,190,113,175,65,190,222,14,66,190,78,108,66,190,194,199,66,190,58,33,67,190,182,120,67,190,57,206,67,190,194,33,68,190,82,115,68,190,234,194,68,190,139,16,69,190,53,92,69,190,234,165,69,190,170,237,69,190,118,51,70,190,79,119,70,190,53,185,70,190,42,249,70,190,47,55,71,190,68,115,71,190,106,173,71,190,162,229,71,190,237,27,72,190,76,80,72,190,192,130,72,190,74,179,72,190,235,225,72,190,163,14,73,190,117,57,73,190,96,98,73,190,101,137,73,190,135,174,73,190,197,209,73,190,33,243,73,190,156,18,74,190,55,48,74,190,243,75,74,190,209,101,74,190,210,125,74,190,247,147,74,190,66,168,74,190,179,186,74,190,76,203,74,190,13,218,74,190,248,230,74,190,15,242,74,190,81,251,74,190,193,2,75,190,95,8,75,190,45,12,75,190,44,14,75,190,94,14,75,190,194,12,75,190,91,9,75,190,42,4,75,190,48,253,74,190,111,244,74,190,231,233,74,190,154,221,74,190,137,207,74,190,181,191,74,190,33,174,74,190,204,154,74,190,185,133,74,190,233,110,74,190,93,86,74,190,23,60,74,190,23,32,74,190,95,2,74,190,241,226,73,190,207,193,73,190,248,158,73,190,111,122,73,190,54,84,73,190,77,44,73,190,182,2,73,190,114,215,72,190,132,170,72,190,236,123,72,190,172,75,72,190,197,25,72,190,57,230,71,190,9,177,71,190,56,122,71,190,197,65,71,190,179,7,71,190,4,204,70,190,185,142,70,190,211,79,70,190,84,15,70,190,61,205,69,190,145,137,69,190,81,68,69,190,125,253,68,190,25,181,68,190,37,107,68,190,162,31,68,190,148,210,67,190,251,131,67,190,217,51,67,190,47,226,66,190,255,142,66,190,76,58,66,190,21,228,65,190,94,140,65,190,40,51,65,190,116,216,64,190,68,124,64,190,154,30,64,190,119,191,63,190,222,94,63,190,208,252,62,190,78,153,62,190,91,52,62,190,247,205,61,190,38,102,61,190,232,252,60,190,64,146,60,190,46,38,60,190,182,184,59,190,216,73,59,190,150,217,58,190,243,103,58,190,239,244,57,190,141,128,57,190,207,10,57,190,182,147,56,190,69,27,56,190,124,161,55,190,94,38,55,190,236,169,54,190,41,44,54,190,23,173,53,190,182,44,53,190,9,171,52,190,19,40,52,190,211,163,51,190,78,30,51,190,132,151,50,190,119,15,50,190,41,134,49,190,157,251,48,190,211,111,48,190,206,226,47,190,144,84,47,190,27,197,46,190,112,52,46,190,145,162,45,190,129,15,45,190,66,123,44,190,212,229,43,190,59,79,43,190,120,183,42,190,141,30,42,190,124,132,41,190,71,233,40,190,240,76,40,190,121,175,39,190,228,16,39,190,50,113,38,190,103,208,37,190,131,46,37,190,136,139,36,190,122,231,35,190,89,66,35,190,40,156,34,190,233,244,33,190,157,76,33,190,71,163,32,190,233,248,31,190,133,77,31,190,28,161,30,190,177,243,29,190,70,69,29,190,220,149,28,190,119,229,27,190,23,52,27,190,191,129,26,190,113,206,25,190,47,26,25,190,251,100,24,190,215,174,23,190,197,247,22,190,199,63,22,190,224,134,21,190,16,205,20,190,91,18,20,190,194,86,19,190,72,154,18,190,238,220,17,190,183,30,17,190,164,95,16,190,184,159,15,190,245,222,14,190,92,29,14,190,241,90,13,190,180,151,12,190,168,211,11,190,208,14,11,190,45,73,10,190,193,130,9,190,142,187,8,190,151,243,7,190,222,42,7,190,100,97,6,190,44,151,5,190,56,204,4,190,137,0,4,190,35,52,3,190,7,103,2,190,55,153,1,190,182,202,0,190,9,247,255,189,76,87,254,189,56,182,252,189,210,19,251,189,29,112,249,189,30,203,247,189,218,36,246,189,83,125,244,189,143,212,242,189,146,42,241,189,96,127,239,189,253,210,237,189,110,37,236,189,182,118,234,189,217,198,232,189,221,21,231,189,197,99,229,189,150,176,227,189,83,252,225,189,1,71,224,189,164,144,222,189,65,217,220,189,219,32,219,189,119,103,217,189,25,173,215,189,197,241,213,189,127,53,212,189,76,120,210,189,48,186,208,189,47,251,206,189,77,59,205,189,142,122,203,189,247,184,201,189,140,246,199,189,81,51,198,189,74,111,196,189,123,170,194,189,233,228,192,189,152,30,191,189,140,87,189,189,201,143,187,189,83,199,185,189,47,254,183,189,96,52,182,189,235,105,180,189,213,158,178,189,32,211,176,189,210,6,175,189,239,57,173,189,122,108,171,189,119,158,169,189,236,207,167,189,220,0,166,189,75,49,164,189,62,97,162,189,184,144,160,189,190,191,158,189,83,238,156,189,124,28,155,189,61,74,153,189,155,119,151,189,152,164,149,189,58,209,147,189,132,253,145,189,123,41,144,189,34,85,142,189,126,128,140,189,146,171,138,189,100,214,136,189,246,0,135,189,76,43,133,189,108,85,131,189,89,127,129,189,45,82,127,189,83,165,123,189,42,248,119,189,188,74,116,189,16,157,112,189,46,239,108,189,29,65,105,189,231,146,101,189,147,228,97,189,41,54,94,189,177,135,90,189,51,217,86,189,182,42,83,189,67,124,79,189,226,205,75,189,154,31,72,189,115,113,68,189,117,195,64,189,167,21,61,189,19,104,57,189,191,186,53,189,179,13,50,189,246,96,46,189,146,180,42,189,140,8,39,189,238,92,35,189,190,177,31,189,5,7,28,189,202,92,24,189,20,179,20,189,235,9,17,189,88,97,13,189,96,185,9,189,12,18,6,189,100,107,2,189,221,138,253,188,103,64,246,188,117,247,238,188,21,176,231,188,87,106,224,188,73,38,217,188,250,227,209,188,120,163,202,188,211,100,195,188,25,40,188,188,89,237,180,188,161,180,173,188,0,126,166,188,131,73,159,188,59,23,152,188,52,231,144,188,126,185,137,188,39,142,130,188,120,202,118,188,153,125,104,188,205,53,90,188,47,243,75,188,220,181,61,188,240,125,47,188,135,75,33,188,189,30,19,188,173,247,4,188,230,172,237,187,85,118,209,187,223,75,181,187,186,45,153,187,57,56,122,187,122,46,66,187,163,62,10,187,62,210,164,186,215,114,213,185,248,137,231,57,136,234,168,58,74,221,11,59,231,40,67,59,177,87,122,59,160,180,152,59,150,46,180,59,133,153,207,59,59,245,234,59,194,32,3,60,21,191,16,60,127,85,30,60,229,227,43,60,46,106,57,60,66,232,70,60,5,94,84,60,97,203,97,60,59,48,111,60,122,140,124,60,4,240,132,60,101,149,139,60,83,54,146,60,196,210,152,60,171,106,159,60,251,253,165,60,168,140,172,60,168,22,179,60,237,155,185,60,108,28,192,60,26,152,198,60,234,14,205,60,208,128,211,60,194,237,217,60,180,85,224,60,154,184,230,60,105,22,237,60,21,111,243,60,148,194,249,60,108,8,0,61,237,44,3,61,198,78,6,61,242,109,9,61,107,138,12,61,44,164,15,61,47,187,18,61,111,207,21,61,232,224,24,61,146,239,27,61,105,251,30,61,105,4,34,61,138,10,37,61,202,13,40,61,33,14,43,61,139,11,46,61,3,6,49,61,132,253,51,61,9,242,54,61,141,227,57,61,10,210,60,61,125,189,63,61,223,165,66,61,44,139,69,61,96,109,72,61,117,76,75,61,102,40,78,61,48,1,81,61,204,214,83,61,56,169,86,61,109,120,89,61,103,68,92,61,34,13,95,61,153,210,97,61,200,148,100,61,170,83,103,61,59,15,106,61,118,199,108,61,87,124,111,61,217,45,114,61,249,219,116,61,178,134,119,61,0,46,122,61,222,209,124,61,73,114,127,61,158,7,129,61,90,84,130,61,86,159,131,61,144,232,132,61,6,48,134,61,182,117,135,61,158,185,136,61,189,251,137,61,16,60,139,61,149,122,140,61,75,183,141,61,47,242,142,61,64,43,144,61,124,98,145,61,226,151,146,61,110,203,147,61,33,253,148,61,247,44,150,61,239,90,151,61,7,135,152,61,62,177,153,61,146,217,154,61,2,0,156,61,139,36,157,61,44,71,158,61,227,103,159,61,175,134,160,61,142,163,161,61,127,190,162,61,128,215,163,61,144,238,164,61,173,3,166,61,213,22,167,61,7,40,168,61,67,55,169,61,133,68,170,61,205,79,171,61,26,89,172,61,105,96,173,61,186,101,174,61,12,105,175,61,93,106,176,61,171,105,177,61,246,102,178,61,60,98,179,61,124,91,180,61,181,82,181,61,229,71,182,61,11,59,183,61,39,44,184,61,54,27,185,61,56,8,186,61,45,243,186,61,17,220,187,61,229,194,188,61,168,167,189,61,88,138,190,61,245,106,191,61,125,73,192,61,239,37,193,61,75,0,194,61,143,216,194,61,187,174,195,61,205,130,196,61,197,84,197,61,163,36,198,61,100,242,198,61,8,190,199,61,143,135,200,61,247,78,201,61,64,20,202,61,105,215,202,61,113,152,203,61,88,87,204,61,29,20,205,61,191,206,205,61,61,135,206,61,151,61,207,61,205,241,207,61,221,163,208,61,199,83,209,61,138,1,210,61,38,173,210,61,155,86,211,61,231,253,211,61,11,163,212,61,5,70,213,61,214,230,213,61,124,133,214,61,248,33,215,61,73,188,215,61,111,84,216,61,105,234,216,61,55,126,217,61,216,15,218,61,77,159,218,61,148,44,219,61,175,183,219,61,156,64,220,61,90,199,220,61,235,75,221,61,78,206,221,61,130,78,222,61,135,204,222,61,94,72,223,61,6,194,223,61,126,57,224,61,200,174,224,61,226,33,225,61,205,146,225,61,137,1,226,61,21,110,226,61,114,216,226,61,160,64,227,61,158,166,227,61,109,10,228,61,12,108,228,61,125,203,228,61,190,40,229,61,209,131,229,61,180,220,229,61,105,51,230,61,240,135,230,61,72,218,230,61,114,42,231,61,110,120,231,61,61,196,231,61,222,13,232,61,82,85,232,61,153,154,232,61,179,221,232,61,162,30,233,61,100,93,233,61,252,153,233,61,104,212,233,61,169,12,234,61,192,66,234,61,173,118,234,61,113,168,234,61,11,216,234,61,126,5,235,61,200,48,235,61,235,89,235,61,231,128,235,61,188,165,235,61,108,200,235,61,246,232,235,61,92,7,236,61,158,35,236,61,189,61,236,61,184,85,236,61,146,107,236,61,74,127,236,61,226,144,236,61,89,160,236,61,178,173,236,61,236,184,236,61,8,194,236,61,7,201,236,61,235,205,236,61,178,208,236,61,96,209,236,61,244,207,236,61,110,204,236,61,210,198,236,61,30,191,236,61,84,181,236,61,116,169,236,61,129,155,236,61,122,139,236,61,97,121,236,61,55,101,236,61,253,78,236,61,179,54,236,61,91,28,236,61,245,255,235,61,132,225,235,61,8,193,235,61,129,158,235,61,242,121,235,61,91,83,235,61,190,42,235,61,27,0,235,61,116,211,234,61,202,164,234,61,30,116,234,61,114,65,234,61,198,12,234,61,28,214,233,61,117,157,233,61,211,98,233,61,54,38,233,61,161,231,232,61,19,167,232,61,144,100,232,61,23,32,232,61,171,217,231,61,76,145,231,61,253,70,231,61,190,250,230,61,145,172,230,61,120,92,230,61,116,10,230,61,133,182,229,61,175,96,229,61,242,8,229,61,79,175,228,61,201,83,228,61,97,246,227,61,24,151,227,61,240,53,227,61,235,210,226,61,9,110,226,61,78,7,226,61,185,158,225,61,78,52,225,61,13,200,224,61,248,89,224,61,17,234,223,61,90,120,223,61,212,4,223,61,128,143,222,61,98,24,222,61,121,159,221,61,201,36,221,61,83,168,220,61,24,42,220,61,26,170,219,61,92,40,219,61,222,164,218,61,164,31,218,61,173,152,217,61,254,15,217,61,150,133,216,61,121,249,215,61,167,107,215,61,35,220,214,61,239,74,214,61,13,184,213,61,126,35,213,61,69,141,212,61,99,245,211,61,218,91,211,61,173,192,210,61,221,35,210,61,108,133,209,61,92,229,208,61,176,67,208,61,105,160,207,61,137,251,206,61,18,85,206,61,7,173,205,61,105,3,205,61,59,88,204,61,126,171,203,61,52,253,202,61,96,77,202,61,4,156,201,61,34,233,200,61,188,52,200,61,212,126,199,61,109,199,198,61,136,14,198,61,39,84,197,61,77,152,196,61,252,218,195,61,54,28,195,61,254,91,194,61,85,154,193,61,62,215,192,61,186,18,192,61,205,76,191,61,120,133,190,61,190,188,189,61,161,242,188,61,35,39,188,61,71,90,187,61,14,140,186,61,124,188,185,61,145,235,184,61,82,25,184,61,191,69,183,61,219,112,182,61,170,154,181,61,44,195,180,61,100,234,179,61,85,16,179,61,1,53,178,61,106,88,177,61,147,122,176,61,126,155,175,61,46,187,174,61,165,217,173,61,228,246,172,61,240,18,172,61,202,45,171,61,116,71,170,61,241,95,169,61,67,119,168,61,110,141,167,61,115,162,166,61,84,182,165,61,20,201,164,61,183,218,163,61,61,235,162,61,170,250,161,61,0,9,161,61,66,22,160,61,114,34,159,61,146,45,158,61,165,55,157,61,174,64,156,61,175,72,155,61,171,79,154,61,164,85,153,61,157,90,152,61,151,94,151,61,151,97,150,61,158,99,149,61,174,100,148,61,203,100,147,61,247,99,146,61,53,98,145,61,135,95,144,61,239,91,143,61,113,87,142,61,14,82,141,61,202,75,140,61,166,68,139,61,167,60,138,61,205,51,137,61,29,42,136,61,151,31,135,61,64,20,134,61,25,8,133,61,38,251,131,61,104,237,130,61,227,222,129,61,152,207,128,61,23,127,127,61,127,93,125,61,108,58,123,61,229,21,121,61,237,239,118,61,140,200,116,61,197,159,114,61,159,117,112,61,30,74,110,61,72,29,108,61,34,239,105,61,177,191,103,61,252,142,101,61,6,93,99,61,213,41,97,61,111,245,94,61,217,191,92,61,25,137,90,61,51,81,88,61,44,24,86,61,11,222,83,61,213,162,81,61,142,102,79,61,61,41,77,61,230,234,74,61,143,171,72,61,61,107,70,61,246,41,68,61,191,231,65,61,157,164,63,61,149,96,61,61,174,27,59,61,235,213,56,61,84,143,54,61,236,71,52,61,185,255,49,61,193,182,47,61,9,109,45,61,150,34,43,61,110,215,40,61,150,139,38,61,19,63,36,61,234,241,33,61,34,164,31,61,190,85,29,61,197,6,27,61,61,183,24,61,41,103,22,61,144,22,20,61,119,197,17,61,227,115,15,61,217,33,13,61,95,207,10,61,123,124,8,61,48,41,6,61,134,213,3,61,128,129,1,61,73,90,254,60,241,176,249,60,3,7,245,60,137,92,240,60,142,177,235,60,27,6,231,60,61,90,226,60,253,173,221,60,102,1,217,60,130,84,212,60,92,167,207,60,254,249,202,60,115,76,198,60,196,158,193,60,254,240,188,60,41,67,184,60,80,149,179,60,127,231,174,60,190,57,170,60,25,140,165,60,153,222,160,60,74,49,156,60,53,132,151,60,101,215,146,60,228,42,142,60,189,126,137,60,248,210,132,60,162,39,128,60,135,249,118,60,207,164,109,60,47,81,100,60,189,254,90,60,139,173,81,60,176,93,72,60,62,15,63,60,74,194,53,60,233,118,44,60,46,45,35,60,45,229,25,60,250,158,16,60,170,90,7,60,159,48,252,59,0,176,233,59,156,51,215,59,155,187,196,59,38,72,178,59,99,217,159,59,123,111,141,59,38,21,118,59,167,85,81,59,199,160,44,59,211,246,7,59,52,176,198,58,161,19,123,58,97,236,209,57,221,218,35,185,226,177,58,186,14,29,166,186,26,199,238,186,62,171,27,187,79,229,63,187,114,17,100,187,175,23,132,187,98,31,150,187,174,31,168,187,109,24,186,187,121,9,204,187,173,242,221,187,229,211,239,187,125,214,0,188,229,190,9,188,22,163,18,188,255,130,27,188,142,94,36,188,176,53,45,188,83,8,54,188,101,214,62,188,212,159,71,188,142,100,80,188,129,36,89,188,155,223,97,188,202,149,106,188,254,70,115,188,35,243,123,188,20,77,130,188,255,157,134,188,72,236,138,188,230,55,143,188,211,128,147,188,4,199,151,188,113,10,156,188,18,75,160,188,221,136,164,188,204,195,168,188,212,251,172,188,238,48,177,188,17,99,181,188,53,146,185,188,82,190,189,188,95,231,193,188,84,13,198,188,41,48,202,188,214,79,206,188,83,108,210,188,150,133,214,188,154,155,218,188,84,174,222,188,190,189,226,188,208,201,230,188,129,210,234,188,202,215,238,188,163,217,242,188,4,216,246,188,229,210,250,188,63,202,254,188,5,95,1,189,31,87,3,189,106,77,5,189,226,65,7,189,131,52,9,189,74,37,11,189,51,20,13,189,58,1,15,189,92,236,16,189,149,213,18,189,225,188,20,189,60,162,22,189,164,133,24,189,20,103,26,189,137,70,28,189,255,35,30,189,116,255,31,189,226,216,33,189,72,176,35,189,162,133,37,189,235,88,39,189,33,42,41,189,65,249,42,189,70,198,44,189,47,145,46,189,246,89,48,189,154,32,50,189,22,229,51,189,104,167,53,189,141,103,55,189,128,37,57,189,64,225,58,189,200,154,60,189,22,82,62,189,39,7,64,189,247,185,65,189,132,106,67,189,202,24,69,189,199,196,70,189,119,110,72,189,216,21,74,189,230,186,75,189,158,93,77,189,255,253,78,189,4,156,80,189,171,55,82,189,242,208,83,189,212,103,85,189,81,252,86,189,100,142,88,189,11,30,90,189,68,171,91,189,11,54,93,189,95,190,94,189,60,68,96,189,160,199,97,189,136,72,99,189,241,198,100,189,218,66,102,189,64,188,103,189,31,51,105,189,119,167,106,189,67,25,108,189,130,136,109,189,50,245,110,189,80,95,112,189,218,198,113,189,205,43,115,189,39,142,116,189,231,237,117,189,8,75,119,189,139,165,120,189,107,253,121,189,168,82,123,189,62,165,124,189,44,245,125,189,112,66,127,189,132,70,128,189,121,234,128,189,21,141,129,189,89,46,130,189,66,206,130,189,208,108,131,189,2,10,132,189,215,165,132,189,78,64,133,189,102,217,133,189,30,113,134,189,118,7,135,189,108,156,135,189,0,48,136,189,48,194,136,189,253,82,137,189,100,226,137,189,102,112,138,189,1,253,138,189,53,136,139,189,0,18,140,189,99,154,140,189,93,33,141,189,236,166,141,189,15,43,142,189,199,173,142,189,19,47,143,189,241,174,143,189,98,45,144,189,100,170,144,189,247,37,145,189,26,160,145,189,205,24,146,189,15,144,146,189,223,5,147,189,61,122,147,189,41,237,147,189,161,94,148,189,165,206,148,189,53,61,149,189,80,170,149,189,245,21,150,189,37,128,150,189,223,232,150,189,33,80,151,189,237,181,151,189,64,26,152,189,28,125,152,189,127,222,152,189,105,62,153,189,218,156,153,189,210,249,153,189,79,85,154,189,82,175,154,189,218,7,155,189,231,94,155,189,121,180,155,189,143,8,156,189,42,91,156,189,72,172,156,189,234,251,156,189,15,74,157,189,183,150,157,189,226,225,157,189,144,43,158,189,193,115,158,189,116,186,158,189,169,255,158,189,96,67,159,189,153,133,159,189,83,198,159,189,144,5,160,189,78,67,160,189,141,127,160,189,78,186,160,189,144,243,160,189,84,43,161,189,153,97,161,189,95,150,161,189,166,201,161,189,111,251,161,189,185,43,162,189,132,90,162,189,208,135,162,189,158,179,162,189,238,221,162,189,191,6,163,189,18,46,163,189,230,83,163,189,60,120,163,189,21,155,163,189,111,188,163,189,76,220,163,189,172,250,163,189,142,23,164,189,243,50,164,189,219,76,164,189,70,101,164,189,53,124,164,189,167,145,164,189,158,165,164,189,25,184,164,189,25,201,164,189,157,216,164,189,167,230,164,189,54,243,164,189,75,254,164,189,231,7,165,189,9,16,165,189,177,22,165,189,225,27,165,189,153,31,165,189,217,33,165,189,162,34,165,189,243,33,165,189,206,31,165,189,51,28,165,189,34,23,165,189,156,16,165,189,161,8,165,189,50,255,164,189,79,244,164,189,250,231,164,189,49,218,164,189,247,202,164,189,75,186,164,189,46,168,164,189,160,148,164,189,163,127,164,189,55,105,164,189,92,81,164,189,20,56,164,189,94,29,164,189,60,1,164,189,173,227,163,189,180,196,163,189,79,164,163,189,130,130,163,189,74,95,163,189,171,58,163,189,164,20,163,189,54,237,162,189,97,196,162,189,40,154,162,189,137,110,162,189,135,65,162,189,34,19,162,189,90,227,161,189,50,178,161,189,168,127,161,189,191,75,161,189,119,22,161,189,209,223,160,189,206,167,160,189,111,110,160,189,180,51,160,189,159,247,159,189,48,186,159,189,105,123,159,189,75,59,159,189,213,249,158,189,10,183,158,189,234,114,158,189,119,45,158,189,176,230,157,189,152,158,157,189,47,85,157,189,119,10,157,189,112,190,156,189,27,113,156,189,121,34,156,189,140,210,155,189,85,129,155,189,212,46,155,189,11,219,154,189,251,133,154,189,165,47,154,189,10,216,153,189,43,127,153,189,9,37,153,189,166,201,152,189,3,109,152,189,32,15,152,189,0,176,151,189,163,79,151,189,10,238,150,189,54,139,150,189,42,39,150,189,230,193,149,189,107,91,149,189,186,243,148,189,214,138,148,189,190,32,148,189,117,181,147,189,251,72,147,189,83,219,146,189,124,108,146,189,121,252,145,189,75,139,145,189,243,24,145,189,115,165,144,189,203,48,144,189,254,186,143,189,12,68,143,189,247,203,142,189,193,82,142,189,106,216,141,189,244,92,141,189,97,224,140,189,177,98,140,189,231,227,139,189,3,100,139,189,8,227,138,189,246,96,138,189,208,221,137,189,150,89,137,189,73,212,136,189,236,77,136,189,129,198,135,189,7,62,135,189,129,180,134,189,241,41,134,189,88,158,133,189,183,17,133,189,16,132,132,189,100,245,131,189,182,101,131,189,6,213,130,189,86,67,130,189,168,176,129,189,253,28,129,189,87,136,128,189,109,229,127,189,62,184,126,189,33,137,125,189,26,88,124,189,45,37,123,189,93,240,121,189,173,185,120,189,32,129,119,189,185,70,118,189,124,10,117,189,108,204,115,189,141,140,114,189,225,74,113,189,108,7,112,189,49,194,110,189,52,123,109,189,119,50,108,189,0,232,106,189,207,155,105,189,234,77,104,189,84,254,102,189,15,173,101,189,32,90,100,189,137,5,99,189,79,175,97,189,116,87,96,189,253,253,94,189,236,162,93,189,69,70,92,189,12,232,90,189,68,136,89,189,241,38,88,189,21,196,86,189,181,95,85,189,213,249,83,189,119,146,82,189,160,41,81,189,82,191,79,189,146,83,78,189,99,230,76,189,201,119,75,189,199,7,74,189,96,150,72,189,154,35,71,189,118,175,69,189,249,57,68,189,38,195,66,189,2,75,65,189,143,209,63,189,210,86,62,189,205,218,60,189,134,93,59,189,254,222,57,189,59,95,56,189,64,222,54,189,16,92,53,189,175,216,51,189,34,84,50,189,107,206,48,189,142,71,47,189,144,191,45,189,115,54,44,189,60,172,42,189,239,32,41,189,142,148,39,189,31,7,38,189,164,120,36,189,34,233,34,189,156,88,33,189,22,199,31,189,148,52,30,189,25,161,28,189,170,12,27,189,74,119,25,189,253,224,23,189,199,73,22,189,172,177,20,189,175,24,19,189,212,126,17,189,32,228,15,189,149,72,14,189,57,172,12,189,14,15,11,189,24,113,9,189,92,210,7,189,221,50,6,189,159,146,4,189,165,241,2,189,245,79,1,189,33,91,255,188,250,20,252,188,123,205,248,188,173,132,245,188,150,58,242,188,64,239,238,188,176,162,235,188,240,84,232,188,7,6,229,188,252,181,225,188,216,100,222,188,162,18,219,188,97,191,215,188,30,107,212,188,225,21,209,188,176,191,205,188,149,104,202,188,150,16,199,188,187,183,195,188,12,94,192,188,145,3,189,188,81,168,185,188,85,76,182,188,163,239,178,188,68,146,175,188,64,52,172,188,158,213,168,188,102,118,165,188,159,22,162,188,81,182,158,188,133,85,155,188,65,244,151,188,142,146,148,188,115,48,145,188,247,205,141,188,36,107,138,188,255,7,135,188,145,164,131,188,226,64,128,188,243,185,121,188,189,241,114,188,51,41,108,188,98,96,101,188,92,151,94,188,46,206,87,188,233,4,81,188,156,59,74,188,85,114,67,188,36,169,60,188,24,224,53,188,65,23,47,188,173,78,40,188,108,134,33,188,141,190,26,188,30,247,19,188,48,48,13,188,209,105,6,188,32,72,255,187,248,189,241,187,73,53,228,187,48,174,214,187,203,40,201,187,56,165,187,187,149,35,174,187,1,164,160,187,151,38,147,187,119,171,133,187,124,101,112,187,18,121,85,187,237,145,58,187,70,176,31,187,89,212,4,187,194,252,211,186,47,93,158,186,226,148,81,186,235,19,205,185,176,175,140,55,27,113,222,57,83,238,89,58,243,66,162,58,48,127,215,58,182,85,6,59,155,227,32,59,12,105,59,59,209,229,85,59,177,89,112,59,57,98,133,59,238,146,146,59,218,190,159,59,227,229,172,59,235,7,186,59,213,36,199,59,135,60,212,59,229,78,225,59,209,91,238,59,49,99,251,59,116,50,4,60,109,176,10,60,119,43,17,60,131,163,23,60,131,24,30,60,107,138,36,60,44,249,42,60,184,100,49,60,2,205,55,60,253,49,62,60,155,147,68,60,206,241,74,60,138,76,81,60,192,163,87,60,100,247,93,60,105,71,100,60,192,147,106,60,93,220,112,60,51,33,119,60,52,98,125,60,170,207,129,60,67,236,132,60,222,6,136,60,117,31,139,60,1,54,142,60,125,74,145,60,225,92,148,60,39,109,151,60,74,123,154,60,66,135,157,60,9,145,160,60,154,152,163,60,238,157,166,60,254,160,169,60,197,161,172,60,60,160,175,60,94,156,178,60,35,150,181,60,135,141,184,60,131,130,187,60,17,117,190,60,43,101,193,60,203,82,196,60,235,61,199,60,133,38,202,60,148,12,205,60,18,240,207,60,248,208,210,60,65,175,213,60,231,138,216,60,228,99,219,60,51,58,222,60,207,13,225,60,176,222,227,60,210,172,230,60,48,120,233,60,195,64,236,60,134,6,239,60,116,201,241,60,134,137,244,60,184,70,247,60,5,1,250,60,102,184,252,60,215,108,255,60,41,15,1,61,105,102,2,61,41,188,3,61,102,16,5,61,29,99,6,61,77,180,7,61,242,3,9,61,10,82,10,61,146,158,11,61,136,233,12,61,234,50,14,61,180,122,15,61,229,192,16,61,121,5,18,61,111,72,19,61,197,137,20,61,118,201,21,61,131,7,23,61,231,67,24,61,160,126,25,61,173,183,26,61,11,239,27,61,184,36,29,61,176,88,30,61,243,138,31,61,126,187,32,61,78,234,33,61,97,23,35,61,182,66,36,61,73,108,37,61,26,148,38,61,37,186,39,61,104,222,40,61,226,0,42,61,144,33,43,61,113,64,44,61,130,93,45,61,192,120,46,61,43,146,47,61,192,169,48,61,125,191,49,61,96,211,50,61,104,229,51,61,145,245,52,61,219,3,54,61,68,16,55,61,201,26,56,61,104,35,57,61,33,42,58,61,240,46,59,61,213,49,60,61,205,50,61,61,215,49,62,61,240,46,63,61,24,42,64,61,76,35,65,61,140,26,66,61,212,15,67,61,35,3,68,61,120,244,68,61,210,227,69,61,46,209,70,61,139,188,71,61,231,165,72,61,66,141,73,61,152,114,74,61,234,85,75,61,53,55,76,61,119,22,77,61,176,243,77,61,222,206,78,61,0,168,79,61,20,127,80,61,24,84,81,61,13,39,82,61,239,247,82,61,190,198,83,61,120,147,84,61,29,94,85,61,171,38,86,61,32,237,86,61,124,177,87,61,190,115,88,61,227,51,89,61,235,241,89,61,214,173,90,61,161,103,91,61,75,31,92,61,212,212,92,61,59,136,93,61,126,57,94,61,156,232,94,61,149,149,95,61,103,64,96,61,18,233,96,61,148,143,97,61,236,51,98,61,27,214,98,61,30,118,99,61,245,19,100,61,159,175,100,61,27,73,101,61,105,224,101,61,135,117,102,61,118,8,103,61,51,153,103,61,191,39,104,61,24,180,104,61,63,62,105,61,49,198,105,61,240,75,106,61,121,207,106,61,205,80,107,61,235,207,107,61,210,76,108,61,130,199,108,61,250,63,109,61,58,182,109,61,65,42,110,61,15,156,110,61,163,11,111,61,253,120,111,61,28,228,111,61,0,77,112,61,169,179,112,61,22,24,113,61,72,122,113,61,61,218,113,61,245,55,114,61,112,147,114,61,175,236,114,61,176,67,115,61,115,152,115,61,249,234,115,61,64,59,116,61,74,137,116,61,21,213,116,61,162,30,117,61,240,101,117,61,0,171,117,61,210,237,117,61,100,46,118,61,184,108,118,61,206,168,118,61,164,226,118,61,61,26,119,61,150,79,119,61,177,130,119,61,142,179,119,61,45,226,119,61,142,14,120,61,176,56,120,61,149,96,120,61,60,134,120,61,166,169,120,61,211,202,120,61,195,233,120,61,119,6,121,61,238,32,121,61,41,57,121,61,41,79,121,61,237,98,121,61,119,116,121,61,198,131,121,61,218,144,121,61,181,155,121,61,87,164,121,61,192,170,121,61,241,174,121,61,234,176,121,61,172,176,121,61,54,174,121,61,139,169,121,61,170,162,121,61,148,153,121,61,74,142,121,61,204,128,121,61,27,113,121,61,56,95,121,61,34,75,121,61,220,52,121,61,101,28,121,61,191,1,121,61,234,228,120,61,231,197,120,61,183,164,120,61,90,129,120,61,210,91,120,61,31,52,120,61,66,10,120,61,60,222,119,61,15,176,119,61,186,127,119,61,63,77,119,61,158,24,119,61,218,225,118,61,242,168,118,61,232,109,118,61,189,48,118,61,114,241,117,61,7,176,117,61,127,108,117,61,218,38,117,61,25,223,116,61,61,149,116,61,72,73,116,61,59,251,115,61,22,171,115,61,220,88,115,61,141,4,115,61,42,174,114,61,181,85,114,61,48,251,113,61,154,158,113,61,247,63,113,61,70,223,112,61,138,124,112,61,196,23,112,61,244,176,111,61,29,72,111,61,64,221,110,61,95,112,110,61,122,1,110,61,147,144,109,61,172,29,109,61,198,168,108,61,226,49,108,61,3,185,107,61,42,62,107,61,88,193,106,61,143,66,106,61,208,193,105,61,29,63,105,61,120,186,104,61,226,51,104,61,93,171,103,61,235,32,103,61,140,148,102,61,68,6,102,61,20,118,101,61,252,227,100,61,0,80,100,61,33,186,99,61,97,34,99,61,192,136,98,61,66,237,97,61,232,79,97,61,180,176,96,61,168,15,96,61,197,108,95,61,13,200,94,61,131,33,94,61,40,121,93,61,254,206,92,61,7,35,92,61,69,117,91,61,185,197,90,61,103,20,90,61,80,97,89,61,117,172,88,61,217,245,87,61,126,61,87,61,102,131,86,61,147,199,85,61,7,10,85,61,196,74,84,61,204,137,83,61,34,199,82,61,198,2,82,61,189,60,81,61,7,117,80,61,166,171,79,61,158,224,78,61,240,19,78,61,158,69,77,61,171,117,76,61,24,164,75,61,233,208,74,61,30,252,73,61,187,37,73,61,194,77,72,61,53,116,71,61,22,153,70,61,103,188,69,61,43,222,68,61,101,254,67,61,22,29,67,61,65,58,66,61,232,85,65,61,14,112,64,61,180,136,63,61,222,159,62,61,142,181,61,61,198,201,60,61,136,220,59,61,216,237,58,61,183,253,57,61,39,12,57,61,44,25,56,61,200,36,55,61,253,46,54,61,206,55,53,61,61,63,52,61,77,69,51,61,0,74,50,61,89,77,49,61,90,79,48,61,7,80,47,61,96,79,46,61,106,77,45,61,39,74,44,61,152,69,43,61,194,63,42,61,165,56,41,61,70,48,40,61,166,38,39,61,201,27,38,61,176,15,37,61,95,2,36,61,217,243,34,61,31,228,33,61,52,211,32,61,28,193,31,61,217,173,30,61,109,153,29,61,219,131,28,61,39,109,27,61,82,85,26,61,95,60,25,61,82,34,24,61,44,7,23,61,242,234,21,61,164,205,20,61,71,175,19,61,221,143,18,61,104,111,17,61,236,77,16,61,107,43,15,61], "i8", ALLOC_NONE, Runtime.GLOBAL_BASE);
/* memory initializer */ allocate([232,7,14,61,102,227,12,61,231,189,11,61,111,151,10,61,0,112,9,61,158,71,8,61,74,30,7,61,8,244,5,61,218,200,4,61,196,156,3,61,201,111,2,61,234,65,1,61,44,19,0,61,32,199,253,60,52,102,251,60,153,3,249,60,85,159,246,60,111,57,244,60,234,209,241,60,206,104,239,60,32,254,236,60,230,145,234,60,38,36,232,60,229,180,229,60,42,68,227,60,250,209,224,60,91,94,222,60,83,233,219,60,232,114,217,60,31,251,214,60,255,129,212,60,142,7,210,60,208,139,207,60,205,14,205,60,138,144,202,60,13,17,200,60,92,144,197,60,125,14,195,60,118,139,192,60,76,7,190,60,6,130,187,60,169,251,184,60,60,116,182,60,196,235,179,60,72,98,177,60,205,215,174,60,89,76,172,60,242,191,169,60,159,50,167,60,101,164,164,60,74,21,162,60,84,133,159,60,137,244,156,60,239,98,154,60,140,208,151,60,102,61,149,60,131,169,146,60,233,20,144,60,158,127,141,60,168,233,138,60,12,83,136,60,210,187,133,60,254,35,131,60,151,139,128,60,70,229,123,60,79,178,118,60,86,126,113,60,103,73,108,60,140,19,103,60,211,220,97,60,72,165,92,60,246,108,87,60,232,51,82,60,45,250,76,60,206,191,71,60,216,132,66,60,87,73,61,60,87,13,56,60,228,208,50,60,9,148,45,60,211,86,40,60,77,25,35,60,132,219,29,60,131,157,24,60,86,95,19,60,9,33,14,60,168,226,8,60,63,164,3,60,177,203,252,59,4,79,242,59,140,210,231,59,99,86,221,59,159,218,210,59,87,95,200,59,164,228,189,59,157,106,179,59,90,241,168,59,241,120,158,59,123,1,148,59,14,139,137,59,133,43,126,59,94,67,105,59,214,93,84,59,28,123,63,59,94,155,42,59,203,190,21,59,145,229,0,59,188,31,216,58,193,123,174,58,142,223,132,58,249,150,54,58,172,255,198,57,64,211,131,56,50,241,132,185,118,88,21,186,121,36,104,186,245,109,157,186,11,191,198,186,35,5,240,186,241,159,12,187,119,55,33,187,246,200,53,187,65,84,74,187,44,217,94,187,137,87,115,187,150,231,131,187,244,31,142,187,200,84,152,187,253,133,162,187,123,179,172,187,45,221,182,187,252,2,193,187,211,36,203,187,156,66,213,187,64,92,223,187,171,113,233,187,197,130,243,187,122,143,253,187,218,203,3,188,174,205,8,188,47,205,13,188,82,202,18,188,12,197,23,188,83,189,28,188,28,179,33,188,92,166,38,188,8,151,43,188,23,133,48,188,125,112,53,188,48,89,58,188,38,63,63,188,84,34,68,188,176,2,73,188,46,224,77,188,198,186,82,188,109,146,87,188,24,103,92,188,189,56,97,188,83,7,102,188,206,210,106,188,37,155,111,188,77,96,116,188,61,34,121,188,234,224,125,188,38,78,129,188,43,170,131,188,128,4,134,188,32,93,136,188,6,180,138,188,45,9,141,188,144,92,143,188,43,174,145,188,248,253,147,188,243,75,150,188,23,152,152,188,95,226,154,188,198,42,157,188,72,113,159,188,224,181,161,188,137,248,163,188,63,57,166,188,253,119,168,188,190,180,170,188,125,239,172,188,55,40,175,188,230,94,177,188,134,147,179,188,18,198,181,188,135,246,183,188,222,36,186,188,21,81,188,188,39,123,190,188,14,163,192,188,200,200,194,188,78,236,196,188,158,13,199,188,178,44,201,188,135,73,203,188,24,100,205,188,97,124,207,188,94,146,209,188,10,166,211,188,97,183,213,188,96,198,215,188,1,211,217,188,66,221,219,188,29,229,221,188,143,234,223,188,148,237,225,188,39,238,227,188,69,236,229,188,234,231,231,188,18,225,233,188,185,215,235,188,218,203,237,188,115,189,239,188,127,172,241,188,251,152,243,188,227,130,245,188,50,106,247,188,230,78,249,188,250,48,251,188,108,16,253,188,54,237,254,188,171,99,0,189,100,79,1,189,196,57,2,189,202,34,3,189,116,10,4,189,191,240,4,189,170,213,5,189,52,185,6,189,91,155,7,189,29,124,8,189,120,91,9,189,108,57,10,189,245,21,11,189,18,241,11,189,195,202,12,189,4,163,13,189,214,121,14,189,53,79,15,189,33,35,16,189,151,245,16,189,152,198,17,189,31,150,18,189,46,100,19,189,193,48,20,189,215,251,20,189,112,197,21,189,137,141,22,189,32,84,23,189,54,25,24,189,199,220,24,189,212,158,25,189,89,95,26,189,87,30,27,189,204,219,27,189,181,151,28,189,19,82,29,189,227,10,30,189,37,194,30,189,215,119,31,189,248,43,32,189,134,222,32,189,129,143,33,189,231,62,34,189,182,236,34,189,239,152,35,189,143,67,36,189,149,236,36,189,1,148,37,189,209,57,38,189,4,222,38,189,153,128,39,189,142,33,40,189,228,192,40,189,152,94,41,189,169,250,41,189,24,149,42,189,226,45,43,189,6,197,43,189,132,90,44,189,91,238,44,189,138,128,45,189,15,17,46,189,235,159,46,189,27,45,47,189,159,184,47,189,119,66,48,189,161,202,48,189,28,81,49,189,233,213,49,189,5,89,50,189,112,218,50,189,41,90,51,189,48,216,51,189,132,84,52,189,36,207,52,189,15,72,53,189,69,191,53,189,197,52,54,189,142,168,54,189,159,26,55,189,249,138,55,189,154,249,55,189,130,102,56,189,176,209,56,189,35,59,57,189,220,162,57,189,217,8,58,189,26,109,58,189,158,207,58,189,101,48,59,189,111,143,59,189,187,236,59,189,72,72,60,189,22,162,60,189,37,250,60,189,117,80,61,189,4,165,61,189,211,247,61,189,224,72,62,189,45,152,62,189,184,229,62,189,129,49,63,189,136,123,63,189,205,195,63,189,79,10,64,189,14,79,64,189,10,146,64,189,67,211,64,189,184,18,65,189,105,80,65,189,86,140,65,189,127,198,65,189,228,254,65,189,133,53,66,189,97,106,66,189,121,157,66,189,204,206,66,189,90,254,66,189,35,44,67,189,40,88,67,189,104,130,67,189,227,170,67,189,154,209,67,189,139,246,67,189,184,25,68,189,33,59,68,189,197,90,68,189,164,120,68,189,191,148,68,189,22,175,68,189,169,199,68,189,120,222,68,189,131,243,68,189,202,6,69,189,78,24,69,189,15,40,69,189,14,54,69,189,73,66,69,189,194,76,69,189,121,85,69,189,110,92,69,189,162,97,69,189,20,101,69,189,198,102,69,189,183,102,69,189,231,100,69,189,89,97,69,189,10,92,69,189,253,84,69,189,49,76,69,189,168,65,69,189,96,53,69,189,92,39,69,189,154,23,69,189,29,6,69,189,228,242,68,189,240,221,68,189,65,199,68,189,217,174,68,189,183,148,68,189,220,120,68,189,73,91,68,189,254,59,68,189,252,26,68,189,68,248,67,189,215,211,67,189,180,173,67,189,221,133,67,189,83,92,67,189,22,49,67,189,38,4,67,189,134,213,66,189,52,165,66,189,51,115,66,189,131,63,66,189,37,10,66,189,25,211,65,189,97,154,65,189,253,95,65,189,238,35,65,189,53,230,64,189,211,166,64,189,201,101,64,189,24,35,64,189,193,222,63,189,196,152,63,189,34,81,63,189,221,7,63,189,246,188,62,189,109,112,62,189,68,34,62,189,124,210,61,189,21,129,61,189,16,46,61,189,112,217,60,189,52,131,60,189,95,43,60,189,240,209,59,189,233,118,59,189,76,26,59,189,25,188,58,189,82,92,58,189,248,250,57,189,11,152,57,189,142,51,57,189,129,205,56,189,230,101,56,189,189,252,55,189,9,146,55,189,202,37,55,189,1,184,54,189,177,72,54,189,217,215,53,189,125,101,53,189,156,241,52,189,56,124,52,189,82,5,52,189,237,140,51,189,9,19,51,189,167,151,50,189,202,26,50,189,114,156,49,189,160,28,49,189,87,155,48,189,152,24,48,189,100,148,47,189,189,14,47,189,164,135,46,189,26,255,45,189,34,117,45,189,188,233,44,189,234,92,44,189,174,206,43,189,10,63,43,189,254,173,42,189,141,27,42,189,183,135,41,189,127,242,40,189,230,91,40,189,238,195,39,189,153,42,39,189,231,143,38,189,219,243,37,189,118,86,37,189,187,183,36,189,170,23,36,189,69,118,35,189,142,211,34,189,135,47,34,189,50,138,33,189,144,227,32,189,163,59,32,189,108,146,31,189,238,231,30,189,42,60,30,189,34,143,29,189,216,224,28,189,77,49,28,189,132,128,27,189,126,206,26,189,61,27,26,189,195,102,25,189,17,177,24,189,42,250,23,189,15,66,23,189,195,136,22,189,71,206,21,189,157,18,21,189,199,85,20,189,198,151,19,189,158,216,18,189,79,24,18,189,220,86,17,189,70,148,16,189,144,208,15,189,187,11,15,189,202,69,14,189,190,126,13,189,153,182,12,189,94,237,11,189,14,35,11,189,172,87,10,189,57,139,9,189,183,189,8,189,41,239,7,189,144,31,7,189,239,78,6,189,72,125,5,189,156,170,4,189,238,214,3,189,63,2,3,189,147,44,2,189,234,85,1,189,71,126,0,189,90,75,255,188,57,152,253,188,49,227,251,188,71,44,250,188,125,115,248,188,217,184,246,188,95,252,244,188,19,62,243,188,249,125,241,188,22,188,239,188,110,248,237,188,6,51,236,188,225,107,234,188,5,163,232,188,116,216,230,188,53,12,229,188,75,62,227,188,187,110,225,188,137,157,223,188,185,202,221,188,81,246,219,188,84,32,218,188,199,72,216,188,174,111,214,188,15,149,212,188,237,184,210,188,76,219,208,188,50,252,206,188,164,27,205,188,164,57,203,188,57,86,201,188,102,113,199,188,49,139,197,188,157,163,195,188,176,186,193,188,109,208,191,188,218,228,189,188,251,247,187,188,213,9,186,188,109,26,184,188,198,41,182,188,230,55,180,188,209,68,178,188,140,80,176,188,27,91,174,188,132,100,172,188,203,108,170,188,244,115,168,188,5,122,166,188,1,127,164,188,238,130,162,188,209,133,160,188,173,135,158,188,137,136,156,188,103,136,154,188,78,135,152,188,66,133,150,188,72,130,148,188,99,126,146,188,154,121,144,188,241,115,142,188,109,109,140,188,17,102,138,188,228,93,136,188,234,84,134,188,39,75,132,188,161,64,130,188,92,53,128,188,185,82,124,188,80,57,120,188,134,30,116,188,100,2,112,188,245,228,107,188,65,198,103,188,82,166,99,188,50,133,95,188,234,98,91,188,132,63,87,188,10,27,83,188,133,245,78,188,254,206,74,188,128,167,70,188,19,127,66,188,194,85,62,188,149,43,58,188,152,0,54,188,210,212,49,188,78,168,45,188,22,123,41,188,50,77,37,188,173,30,33,188,143,239,28,188,228,191,24,188,180,143,20,188,8,95,16,188,235,45,12,188,101,252,7,188,129,202,3,188,145,48,255,187,137,203,246,187,252,101,238,187,255,255,229,187,164,153,221,187,255,50,213,187,35,204,204,187,33,101,196,187,15,254,187,187,254,150,179,187,1,48,171,187,44,201,162,187,146,98,154,187,69,252,145,187,88,150,137,187,223,48,129,187,215,151,113,187,35,207,96,187,198,7,80,187,231,65,63,187,171,125,46,187,56,187,29,187,179,250,12,187,132,120,248,186,19,0,215,186,96,140,181,186,180,29,148,186,182,104,101,186,61,161,34,186,36,203,191,185,81,178,233,184,26,176,21,57,152,1,208,57,115,135,42,58,102,255,108,58,10,180,151,58,116,224,184,58,168,4,218,58,93,32,251,58,165,25,14,59,146,158,30,59,211,30,47,59,65,154,63,59,186,16,80,59,25,130,96,59,57,238,112,59,124,170,128,59,23,219,136,59,222,8,145,59,189,51,153,59,164,91,161,59,127,128,169,59,62,162,177,59,207,192,185,59,31,220,193,59,30,244,201,59,185,8,210,59,223,25,218,59,126,39,226,59,133,49,234,59,226,55,242,59,133,58,250,59,173,28,1,60,41,26,5,60,174,21,9,60,50,15,13,60,174,6,17,60,24,252,20,60,105,239,24,60,151,224,28,60,154,207,32,60,106,188,36,60,255,166,40,60,79,143,44,60,82,117,48,60,1,89,52,60,82,58,56,60,61,25,60,60,187,245,63,60,195,207,67,60,76,167,71,60,79,124,75,60,195,78,79,60,160,30,83,60,223,235,86,60,118,182,90,60,94,126,94,60,143,67,98,60,1,6,102,60,172,197,105,60,136,130,109,60,141,60,113,60,179,243,116,60,242,167,120,60,67,89,124,60,207,3,128,60,126,217,129,60,169,173,131,60,78,128,133,60,105,81,135,60,245,32,137,60,239,238,138,60,83,187,140,60,30,134,142,60,75,79,144,60,216,22,146,60,191,220,147,60,254,160,149,60,145,99,151,60,116,36,153,60,163,227,154,60,28,161,156,60,218,92,158,60,218,22,160,60,23,207,161,60,144,133,163,60,64,58,165,60,35,237,166,60,54,158,168,60,118,77,170,60,223,250,171,60,111,166,173,60,32,80,175,60,240,247,176,60,220,157,178,60,224,65,180,60,249,227,181,60,36,132,183,60,93,34,185,60,161,190,186,60,236,88,188,60,60,241,189,60,141,135,191,60,221,27,193,60,39,174,194,60,105,62,196,60,159,204,197,60,199,88,199,60,221,226,200,60,223,106,202,60,201,240,203,60,152,116,205,60,73,246,206,60,217,117,208,60,70,243,209,60,139,110,211,60,167,231,212,60,151,94,214,60,87,211,215,60,228,69,217,60,60,182,218,60,93,36,220,60,66,144,221,60,234,249,222,60,81,97,224,60,118,198,225,60,84,41,227,60,234,137,228,60,52,232,229,60,49,68,231,60,222,157,232,60,55,245,233,60,58,74,235,60,230,156,236,60,54,237,237,60,41,59,239,60,189,134,240,60,238,207,241,60,186,22,243,60,31,91,244,60,27,157,245,60,171,220,246,60,205,25,248,60,126,84,249,60,188,140,250,60,133,194,251,60,214,245,252,60,174,38,254,60,10,85,255,60,116,64,0,61,34,213,0,61,144,104,1,61,187,250,1,61,163,139,2,61,71,27,3,61,165,169,3,61,189,54,4,61,141,194,4,61,21,77,5,61,84,214,5,61,72,94,6,61,241,228,6,61,78,106,7,61,94,238,7,61,31,113,8,61,146,242,8,61,181,114,9,61,135,241,9,61,7,111,10,61,53,235,10,61,16,102,11,61,150,223,11,61,200,87,12,61,164,206,12,61,41,68,13,61,87,184,13,61,45,43,14,61,170,156,14,61,206,12,15,61,151,123,15,61,5,233,15,61,23,85,16,61,205,191,16,61,37,41,17,61,32,145,17,61,189,247,17,61,250,92,18,61,216,192,18,61,85,35,19,61,113,132,19,61,44,228,19,61,132,66,20,61,122,159,20,61,13,251,20,61,60,85,21,61,6,174,21,61,108,5,22,61,108,91,22,61,6,176,22,61,58,3,23,61,8,85,23,61,110,165,23,61,108,244,23,61,2,66,24,61,48,142,24,61,244,216,24,61,80,34,25,61,65,106,25,61,201,176,25,61,230,245,25,61,152,57,26,61,224,123,26,61,187,188,26,61,44,252,26,61,48,58,27,61,199,118,27,61,243,177,27,61,177,235,27,61,3,36,28,61,231,90,28,61,93,144,28,61,102,196,28,61,2,247,28,61,47,40,29,61,238,87,29,61,62,134,29,61,32,179,29,61,148,222,29,61,152,8,30,61,46,49,30,61,85,88,30,61,13,126,30,61,86,162,30,61,48,197,30,61,155,230,30,61,151,6,31,61,35,37,31,61,65,66,31,61,239,93,31,61,46,120,31,61,254,144,31,61,95,168,31,61,81,190,31,61,212,210,31,61,233,229,31,61,142,247,31,61,198,7,32,61,142,22,32,61,233,35,32,61,213,47,32,61,83,58,32,61,100,67,32,61,7,75,32,61,61,81,32,61,5,86,32,61,96,89,32,61,79,91,32,61,209,91,32,61,231,90,32,61,146,88,32,61,208,84,32,61,163,79,32,61,12,73,32,61,9,65,32,61,156,55,32,61,198,44,32,61,133,32,32,61,219,18,32,61,201,3,32,61,78,243,31,61,107,225,31,61,32,206,31,61,110,185,31,61,86,163,31,61,215,139,31,61,242,114,31,61,168,88,31,61,249,60,31,61,230,31,31,61,111,1,31,61,149,225,30,61,88,192,30,61,185,157,30,61,184,121,30,61,86,84,30,61,148,45,30,61,114,5,30,61,241,219,29,61,17,177,29,61,211,132,29,61,56,87,29,61,64,40,29,61,237,247,28,61,62,198,28,61,52,147,28,61,208,94,28,61,20,41,28,61,255,241,27,61,146,185,27,61,206,127,27,61,180,68,27,61,69,8,27,61,129,202,26,61,105,139,26,61,255,74,26,61,66,9,26,61,51,198,25,61,212,129,25,61,38,60,25,61,40,245,24,61,221,172,24,61,69,99,24,61,96,24,24,61,48,204,23,61,182,126,23,61,243,47,23,61,231,223,22,61,147,142,22,61,249,59,22,61,25,232,21,61,245,146,21,61,141,60,21,61,226,228,20,61,246,139,20,61,201,49,20,61,93,214,19,61,178,121,19,61,202,27,19,61,165,188,18,61,69,92,18,61,170,250,17,61,215,151,17,61,203,51,17,61,137,206,16,61,17,104,16,61,100,0,16,61,131,151,15,61,112,45,15,61,44,194,14,61,184,85,14,61,21,232,13,61,68,121,13,61,71,9,13,61,31,152,12,61,204,37,12,61,81,178,11,61,174,61,11,61,228,199,10,61,246,80,10,61,228,216,9,61,175,95,9,61,89,229,8,61,227,105,8,61,79,237,7,61,157,111,7,61,207,240,6,61,230,112,6,61,228,239,5,61,202,109,5,61,153,234,4,61,83,102,4,61,249,224,3,61,140,90,3,61,15,211,2,61,129,74,2,61,229,192,1,61,60,54,1,61,136,170,0,61,202,29,0,61,5,32,255,60,104,2,254,60,192,226,252,60,16,193,251,60,89,157,250,60,160,119,249,60,231,79,248,60,50,38,247,60,130,250,245,60,220,204,244,60,67,157,243,60,184,107,242,60,64,56,241,60,222,2,240,60,148,203,238,60,102,146,237,60,87,87,236,60,105,26,235,60,161,219,233,60,1,155,232,60,141,88,231,60,71,20,230,60,51,206,228,60,84,134,227,60,173,60,226,60,66,241,224,60,22,164,223,60,43,85,222,60,134,4,221,60,42,178,219,60,26,94,218,60,89,8,217,60,234,176,215,60,210,87,214,60,19,253,212,60,176,160,211,60,174,66,210,60,15,227,208,60,215,129,207,60,9,31,206,60,169,186,204,60,187,84,203,60,64,237,201,60,62,132,200,60,184,25,199,60,177,173,197,60,44,64,196,60,45,209,194,60,184,96,193,60,208,238,191,60,120,123,190,60,181,6,189,60,138,144,187,60,250,24,186,60,8,160,184,60,186,37,183,60,17,170,181,60,18,45,180,60,193,174,178,60,32,47,177,60,52,174,175,60,0,44,174,60,136,168,172,60,208,35,171,60,218,157,169,60,172,22,168,60,72,142,166,60,179,4,165,60,239,121,163,60,1,238,161,60,237,96,160,60,181,210,158,60,95,67,157,60,237,178,155,60,99,33,154,60,198,142,152,60,24,251,150,60,94,102,149,60,155,208,147,60,211,57,146,60,11,162,144,60,69,9,143,60,133,111,141,60,208,212,139,60,41,57,138,60,148,156,136,60,21,255,134,60,175,96,133,60,103,193,131,60,64,33,130,60,62,128,128,60,202,188,125,60,114,119,122,60,123,48,119,60,237,231,115,60,208,157,112,60,43,82,109,60,6,5,106,60,106,182,102,60,93,102,99,60,232,20,96,60,18,194,92,60,227,109,89,60,99,24,86,60,153,193,82,60,142,105,79,60,73,16,76,60,210,181,72,60,49,90,69,60,110,253,65,60,144,159,62,60,159,64,59,60,164,224,55,60,165,127,52,60,171,29,49,60,190,186,45,60,230,86,42,60,41,242,38,60,145,140,35,60,37,38,32,60,237,190,28,60,240,86,25,60,55,238,21,60,202,132,18,60,176,26,15,60,240,175,11,60,148,68,8,60,163,216,4,60,36,108,1,60,65,254,251,59,61,35,245,59,78,71,238,59,132,106,231,59,237,140,224,59,154,174,217,59,154,207,210,59,253,239,203,59,211,15,197,59,43,47,190,59,21,78,183,59,160,108,176,59,221,138,169,59,219,168,162,59,169,198,155,59,87,228,148,59,246,1,142,59,147,31,135,59,64,61,128,59,22,182,114,59,8,242,100,59,118,46,87,59,125,107,73,59,63,169,59,59,217,231,45,59,106,39,32,59,18,104,18,59,239,169,4,59,64,218,237,58,137,99,210,58,245,239,182,58,195,127,155,58,48,19,128,58,243,84,73,58,185,139,18,58,91,150,183,57,41,79,20,57,178,207,140,184,52,123,144,185,78,173,253,185,164,100,53,186,22,231,107,186,195,46,145,186,188,99,172,186,59,146,199,186,3,186,226,186,216,218,253,186,62,122,12,187,91,3,26,187,164,136,39,187,251,9,53,187,67,135,66,187,94,0,80,187,45,117,93,187,148,229,106,187,116,81,120,187,88,220,130,187,149,141,137,187,99,60,144,187,179,232,150,187,118,146,157,187,157,57,164,187,26,222,170,187,223,127,177,187,221,30,184,187,5,187,190,187,73,84,197,187,154,234,203,187,235,125,210,187,44,14,217,187,80,155,223,187,72,37,230,187,6,172,236,187,124,47,243,187,155,175,249,187,43,22,0,188,207,82,3,188,179,141,6,188,208,198,9,188,30,254,12,188,151,51,16,188,52,103,19,188,238,152,22,188,189,200,25,188,156,246,28,188,132,34,32,188,108,76,35,188,80,116,38,188,39,154,41,188,235,189,44,188,150,223,47,188,33,255,50,188,132,28,54,188,186,55,57,188,187,80,60,188,130,103,63,188,7,124,66,188,68,142,69,188,50,158,72,188,203,171,75,188,9,183,78,188,228,191,81,188,87,198,84,188,91,202,87,188,233,203,90,188,252,202,93,188,141,199,96,188,149,193,99,188,15,185,102,188,244,173,105,188,61,160,108,188,229,143,111,188,230,124,114,188,57,103,117,188,216,78,120,188,190,51,123,188,227,21,126,188,161,122,128,188,235,232,129,188,204,85,131,188,64,193,132,188,70,43,134,188,218,147,135,188,249,250,136,188,161,96,138,188,206,196,139,188,125,39,141,188,172,136,142,188,88,232,143,188,126,70,145,188,27,163,146,188,44,254,147,188,174,87,149,188,160,175,150,188,253,5,152,188,196,90,153,188,241,173,154,188,130,255,155,188,116,79,157,188,196,157,158,188,113,234,159,188,118,53,161,188,210,126,162,188,129,198,163,188,130,12,165,188,210,80,166,188,109,147,167,188,82,212,168,188,127,19,170,188,239,80,171,188,162,140,172,188,148,198,173,188,196,254,174,188,46,53,176,188,208,105,177,188,168,156,178,188,179,205,179,188,240,252,180,188,91,42,182,188,242,85,183,188,179,127,184,188,156,167,185,188,171,205,186,188,220,241,187,188,46,20,189,188,160,52,190,188,45,83,191,188,213,111,192,188,148,138,193,188,106,163,194,188,83,186,195,188,78,207,196,188,89,226,197,188,113,243,198,188,148,2,200,188,193,15,201,188,245,26,202,188,46,36,203,188,106,43,204,188,168,48,205,188,229,51,206,188,32,53,207,188,86,52,208,188,134,49,209,188,173,44,210,188,202,37,211,188,220,28,212,188,223,17,213,188,211,4,214,188,181,245,214,188,133,228,215,188,63,209,216,188,227,187,217,188,111,164,218,188,224,138,219,188,54,111,220,188,111,81,221,188,136,49,222,188,130,15,223,188,89,235,223,188,12,197,224,188,154,156,225,188,2,114,226,188,65,69,227,188,86,22,228,188,64,229,228,188,254,177,229,188,141,124,230,188,237,68,231,188,28,11,232,188,25,207,232,188,227,144,233,188,119,80,234,188,213,13,235,188,252,200,235,188,234,129,236,188,158,56,237,188,23,237,237,188,84,159,238,188,83,79,239,188,19,253,239,188,147,168,240,188,211,81,241,188,208,248,241,188,138,157,242,188,0,64,243,188,48,224,243,188,27,126,244,188,190,25,245,188,25,179,245,188,42,74,246,188,242,222,246,188,111,113,247,188,159,1,248,188,131,143,248,188,25,27,249,188,97,164,249,188,90,43,250,188,2,176,250,188,90,50,251,188,96,178,251,188,20,48,252,188,117,171,252,188,130,36,253,188,59,155,253,188,159,15,254,188,173,129,254,188,102,241,254,188,199,94,255,188,210,201,255,188,66,25,0,189,111,76,0,189,112,126,0,189,68,175,0,189,235,222,0,189,101,13,1,189,178,58,1,189,209,102,1,189,195,145,1,189,135,187,1,189,29,228,1,189,132,11,2,189,190,49,2,189,201,86,2,189,165,122,2,189,83,157,2,189,210,190,2,189,35,223,2,189,68,254,2,189,54,28,3,189,250,56,3,189,142,84,3,189,244,110,3,189,42,136,3,189,49,160,3,189,9,183,3,189,177,204,3,189,43,225,3,189,118,244,3,189,145,6,4,189,126,23,4,189,59,39,4,189,202,53,4,189,42,67,4,189,91,79,4,189,94,90,4,189,50,100,4,189,216,108,4,189,79,116,4,189,153,122,4,189,180,127,4,189,162,131,4,189,98,134,4,189,245,135,4,189,90,136,4,189,147,135,4,189,159,133,4,189,126,130,4,189,48,126,4,189,183,120,4,189,18,114,4,189,65,106,4,189,69,97,4,189,30,87,4,189,204,75,4,189,80,63,4,189,170,49,4,189,218,34,4,189,224,18,4,189,189,1,4,189,114,239,3,189,254,219,3,189,99,199,3,189,159,177,3,189,181,154,3,189,163,130,3,189,108,105,3,189,14,79,3,189,139,51,3,189,226,22,3,189,22,249,2,189,36,218,2,189,16,186,2,189,216,152,2,189,125,118,2,189,0,83,2,189,97,46,2,189,162,8,2,189,193,225,1,189,193,185,1,189,161,144,1,189,98,102,1,189,4,59,1,189,137,14,1,189,240,224,0,189,59,178,0,189,106,130,0,189,125,81,0,189,118,31,0,189,169,216,255,188,51,112,255,188,139,5,255,188,179,152,254,188,172,41,254,188,119,184,253,188,22,69,253,188,138,207,252,188,213,87,252,188,247,221,251,188,243,97,251,188,202,227,250,188,126,99,250,188,15,225,249,188,128,92,249,188,209,213,248,188,6,77,248,188,30,194,247,188,28,53,247,188,2,166,246,188,209,20,246,188,138,129,245,188,47,236,244,188,195,84,244,188,70,187,243,188,187,31,243,188,35,130,242,188,128,226,241,188,211,64,241,188,31,157,240,188,101,247,239,188,168,79,239,188,232,165,238,188,40,250,237,188,106,76,237,188,175,156,236,188,250,234,235,188,76,55,235,188,167,129,234,188,14,202,233,188,130,16,233,188,5,85,232,188,153,151,231,188,65,216,230,188,254,22,230,188,210,83,229,188,191,142,228,188,200,199,227,188,239,254,226,188,53,52,226,188,157,103,225,188,40,153,224,188,218,200,223,188,180,246,222,188,184,34,222,188,233,76,221,188,73,117,220,188,218,155,219,188,158,192,218,188,152,227,217,188,201,4,217,188,53,36,216,188,221,65,215,188,195,93,214,188,235,119,213,188,86,144,212,188,7,167,211,188,255,187,210,188,66,207,209,188,211,224,208,188,178,240,207,188,227,254,206,188,104,11,206,188,68,22,205,188,120,31,204,188,8,39,203,188,246,44,202,188,69,49,201,188,247,51,200,188,14,53,199,188,141,52,198,188,119,50,197,188,205,46,196,188,148,41,195,188,205,34,194,188,123,26,193,188,160,16,192,188,64,5,191,188,92,248,189,188,247,233,188,188,21,218,187,188,184,200,186,188,225,181,185,188,149,161,184,188,214,139,183,188,166,116,182,188,9,92,181,188,0,66,180,188,143,38,179,188,185,9,178,188,128,235,176,188,231,203,175,188,240,170,174,188,160,136,173,188,247,100,172,188,250,63,171,188,171,25,170,188,12,242,168,188,34,201,167,188,238,158,166,188,115,115,165,188,181,70,164,188,182,24,163,188,121,233,161,188,1,185,160,188,80,135,159,188,107,84,158,188,83,32,157,188,12,235,155,188,153,180,154,188,252,124,153,188,56,68,152,188,82,10,151,188,74,207,149,188,37,147,148,188,230,85,147,188,142,23,146,188,35,216,144,188,166,151,143,188,26,86,142,188,131,19,141,188,227,207,139,188,62,139,138,188,151,69,137,188,240,254,135,188,77,183,134,188,177,110,133,188,31,37,132,188,153,218,130,188,36,143,129,188,194,66,128,188,237,234,125,188,137,78,123,188,94,176,120,188,114,16,118,188,203,110,115,188,112,203,112,188,103,38,110,188,183,127,107,188,101,215,104,188,120,45,102,188,247,129,99,188,231,212,96,188,79,38,94,188,53,118,91,188,160,196,88,188,150,17,86,188,29,93,83,188,60,167,80,188,249,239,77,188,91,55,75,188,103,125,72,188,37,194,69,188,154,5,67,188,206,71,64,188,198,136,61,188,138,200,58,188,31,7,56,188,139,68,53,188,214,128,50,188,6,188,47,188,34,246,44,188,47,47,42,188,52,103,39,188,56,158,36,188,65,212,33,188,86,9,31,188,126,61,28,188,190,112,25,188,29,163,22,188,162,212,19,188,83,5,17,188,55,53,14,188,84,100,11,188,177,146,8,188,84,192,5,188,68,237,2,188,135,25,0,188,73,138,250,187,68,224,244,187,13,53,239,187,177,136,233,187,60,219,227,187,189,44,222,187,64,125,216,187,210,204,210,187,128,27,205,187,88,105,199,187,101,182,193,187,182,2,188,187,88,78,182,187,86,153,176,187,192,227,170,187,161,45,165,187,6,119,159,187,253,191,153,187,147,8,148,187,212,80,142,187,206,152,136,187,141,224,130,187,64,80,122,187,36,223,110,187,225,109,99,187,146,252,87,187,80,139,76,187,53,26,65,187,91,169,53,187,220,56,42,187,211,200,30,187,87,89,19,187,133,234,7,187,233,248,248,186,129,30,226,186,4,70,203,186,167,111,180,186,156,155,157,186,23,202,134,186,152,246,95,186,218,94,50,186,91,205,4,186,4,133,174,185,211,250,38,185,189,105,111,55,173,200,68,57,229,60,189,57,23,2,12,58,179,92,57,58,226,173,102,58,159,250,137,58,49,153,160,58,118,50,183,58,57,198,205,58,74,84,228,58,119,220,250,58,70,175,8,59,44,237,19,59,213,39,31,59,40,95,42,59,11,147,53,59,102,195,64,59,33,240,75,59,34,25,87,59,81,62,98,59,150,95,109,59,215,124,120,59,254,202,129,59,119,85,135,59,201,221,140,59,233,99,146,59,202,231,151,59,96,105,157,59,160,232,162,59,125,101,168,59,234,223,173,59,221,87,179,59,73,205,184,59,34,64,190,59,92,176,195,59,236,29,201,59,197,136,206,59,219,240,211,59,36,86,217,59,147,184,222,59,28,24,228,59,180,116,233,59,79,206,238,59,226,36,244,59,96,120,249,59,191,200,254,59,249,10,2,60,248,175,4,60,85,83,7,60,12,245,9,60,22,149,12,60,110,51,15,60,14,208,17,60,241,106,20,60,17,4,23,60,105,155,25,60,242,48,28,60,168,196,30,60,132,86,33,60,130,230,35,60,155,116,38,60,203,0,41,60,11,139,43,60,87,19,46,60,169,153,48,60,252,29,51,60,74,160,53,60,142,32,56,60,194,158,58,60,226,26,61,60,232,148,63,60,206,12,66,60,145,130,68,60,41,246,70,60,147,103,73,60,200,214,75,60,196,67,78,60,130,174,80,60,253,22,83,60,47,125,85,60,20,225,87,60,166,66,90,60,224,161,92,60,190,254,94,60,59,89,97,60,81,177,99,60,252,6,102,60,54,90,104,60,252,170,106,60,72,249,108,60,21,69,111,60,94,142,113,60,31,213,115,60,84,25,118,60,246,90,120,60,2,154,122,60,116,214,124,60,69,16,127,60,185,163,128,60,252,189,129,60,231,214,130,60,122,238,131,60,178,4,133,60,140,25,134,60,6,45,135,60,31,63,136,60,211,79,137,60,34,95,138,60,8,109,139,60,131,121,140,60,146,132,141,60,50,142,142,60,97,150,143,60,30,157,144,60,101,162,145,60,53,166,146,60,139,168,147,60,103,169,148,60,197,168,149,60,164,166,150,60,2,163,151,60,220,157,152,60,50,151,153,60,0,143,154,60,69,133,155,60,255,121,156,60,44,109,157,60,203,94,158,60,217,78,159,60,84,61,160,60,59,42,161,60,139,21,162,60,68,255,162,60,98,231,163,60,230,205,164,60,203,178,165,60,18,150,166,60,184,119,167,60,187,87,168,60,26,54,169,60,210,18,170,60,228,237,170,60,76,199,171,60,8,159,172,60,25,117,173,60,123,73,174,60,45,28,175,60,46,237,175,60,124,188,176,60,21,138,177,60,249,85,178,60,37,32,179,60,151,232,179,60,80,175,180,60,76,116,181,60,139,55,182,60,11,249,182,60,202,184,183,60,200,118,184,60,3,51,185,60,122,237,185,60,42,166,186,60,20,93,187,60,53,18,188,60,140,197,188,60,24,119,189,60,216,38,190,60,202,212,190,60,238,128,191,60,65,43,192,60,195,211,192,60,115,122,193,60,79,31,194,60,87,194,194,60,136,99,195,60,227,2,196,60,101,160,196,60,15,60,197,60,222,213,197,60,210,109,198,60,233,3,199,60,35,152,199,60,127,42,200,60,251,186,200,60,152,73,201,60,83,214,201,60,44,97,202,60,33,234,202,60,51,113,203,60,96,246,203,60,167,121,204,60,8,251,204,60,130,122,205,60,19,248,205,60,187,115,206,60,122,237,206,60,78,101,207,60,55,219,207,60,52,79,208,60,68,193,208,60,103,49,209,60,156,159,209,60,226,11,210,60,57,118,210,60,160,222,210,60,22,69,211,60,156,169,211,60,48,12,212,60,209,108,212,60,128,203,212,60,60,40,213,60,4,131,213,60,216,219,213,60,183,50,214,60,161,135,214,60,150,218,214,60,148,43,215,60,157,122,215,60,174,199,215,60,201,18,216,60,236,91,216,60,24,163,216,60,75,232,216,60,135,43,217,60,201,108,217,60,20,172,217,60,101,233,217,60,189,36,218,60,27,94,218,60,128,149,218,60,235,202,218,60,92,254,218,60,212,47,219,60,81,95,219,60,212,140,219,60,93,184,219,60,235,225,219,60,127,9,220,60,25,47,220,60,184,82,220,60,93,116,220,60,8,148,220,60,184,177,220,60,111,205,220,60,43,231,220,60,237,254,220,60,181,20,221,60,132,40,221,60,89,58,221,60,52,74,221,60,23,88,221,60,0,100,221,60,241,109,221,60,233,117,221,60,232,123,221,60,240,127,221,60,0,130,221,60,25,130,221,60,58,128,221,60,101,124,221,60,154,118,221,60,216,110,221,60,33,101,221,60,117,89,221,60,212,75,221,60,63,60,221,60,182,42,221,60,58,23,221,60,203,1,221,60,105,234,220,60,22,209,220,60,210,181,220,60,156,152,220,60,119,121,220,60,99,88,220,60,95,53,220,60,110,16,220,60,142,233,219,60,194,192,219,60,10,150,219,60,102,105,219,60,215,58,219,60,95,10,219,60,253,215,218,60,178,163,218,60,128,109,218,60,102,53,218,60,103,251,217,60,130,191,217,60,185,129,217,60,12,66,217,60,125,0,217,60,11,189,216,60,185,119,216,60,135,48,216,60,118,231,215,60,134,156,215,60,186,79,215,60,17,1,215,60,142,176,214,60,48,94,214,60,249,9,214,60,235,179,213,60,5,92,213,60,74,2,213,60,185,166,212,60,86,73,212,60,32,234,211,60,24,137,211,60,64,38,211,60,154,193,210,60,37,91,210,60,229,242,209,60,216,136,209,60,2,29,209,60,99,175,208,60,252,63,208,60,207,206,207,60,221,91,207,60,39,231,206,60,175,112,206,60,118,248,205,60,125,126,205,60,198,2,205,60,82,133,204,60,34,6,204,60,57,133,203,60,151,2,203,60,62,126,202,60,47,248,201,60,107,112,201,60,245,230,200,60,206,91,200,60,247,206,199,60,114,64,199,60,64,176,198,60,99,30,198,60,220,138,197,60,173,245,196,60,216,94,196,60,95,198,195,60,66,44,195,60,131,144,194,60,37,243,193,60,41,84,193,60,144,179,192,60,92,17,192,60,144,109,191,60,44,200,190,60,50,33,190,60,164,120,189,60,132,206,188,60,212,34,188,60,149,117,187,60,202,198,186,60,115,22,186,60,148,100,185,60,44,177,184,60,64,252,183,60,208,69,183,60,222,141,182,60,108,212,181,60,124,25,181,60,16,93,180,60,42,159,179,60,204,223,178,60,247,30,178,60,174,92,177,60,243,152,176,60,199,211,175,60,45,13,175,60,38,69,174,60,181,123,173,60,219,176,172,60,156,228,171,60,247,22,171,60,241,71,170,60,138,119,169,60,198,165,168,60,165,210,167,60,42,254,166,60,88,40,166,60,47,81,165,60,180,120,164,60,230,158,163,60,202,195,162,60,96,231,161,60,171,9,161,60,174,42,160,60,106,74,159,60,225,104,158,60,23,134,157,60,12,162,156,60,196,188,155,60,64,214,154,60,131,238,153,60,143,5,153,60,102,27,152,60,11,48,151,60,127,67,150,60,198,85,149,60,225,102,148,60,211,118,147,60,157,133,146,60,68,147,145,60,200,159,144,60,44,171,143,60,114,181,142,60,157,190,141,60,176,198,140,60,172,205,139,60,148,211,138,60,106,216,137,60,49,220,136,60,235,222,135,60,154,224,134,60,66,225,133,60,228,224,132,60,131,223,131,60,33,221,130,60,193,217,129,60,101,213,128,60,32,160,127,60,136,147,125,60,7,133,123,60,163,116,121,60,96,98,119,60,67,78,117,60,81,56,115,60,144,32,113,60,4,7,111,60,179,235,108,60,161,206,106,60,212,175,104,60,81,143,102,60,28,109,100,60,60,73,98,60,180,35,96,60,139,252,93,60,197,211,91,60,104,169,89,60,120,125,87,60,252,79,85,60,247,32,83,60,112,240,80,60,107,190,78,60,238,138,76,60,254,85,74,60,160,31,72,60,218,231,69,60,177,174,67,60,41,116,65,60,73,56,63,60,22,251,60,60,148,188,58,60,202,124,56,60,188,59,54,60,112,249,51,60,235,181,49,60,51,113,47,60,76,43,45,60,61,228,42,60,11,156,40,60,186,82,38,60,81,8,36,60,212,188,33,60,74,112,31,60,183,34,29,60,32,212,26,60,141,132,24,60,0,52,22,60,129,226,19,60,21,144,17,60,192,60,15,60,136,232,12,60,116,147,10,60,135,61,8,60,201,230,5,60,61,143,3,60,234,54,1,60,170,187,253,59,7,8,249,59,245,82,244,59,128,156,239,59,179,228,234,59,153,43,230,59,59,113,225,59,167,181,220,59,229,248,215,59,2,59,211,59,9,124,206,59,3,188,201,59,252,250,196,59,0,57,192,59,25,118,187,59,81,178,182,59,181,237,177,59,78,40,173,59,41,98,168,59,79,155,163,59,204,211,158,59,171,11,154,59,246,66,149,59,185,121,144,59,254,175,139,59,209,229,134,59,61,27,130,59,152,160,122,59,18,10,113,59,255,114,103,59,116,219,93,59,135,67,84,59,79,171,74,59,224,18,65,59,81,122,55,59,183,225,45,59,40,73,36,59,187,176,26,59,132,24,17,59,153,128,7,59,34,210,251,58,1,164,232,58,251,118,213,58,59,75,194,58,237,32,175,58,60,248,155,58,83,209,136,58,187,88,107,58,12,19,69,58,240,209,30,58,122,43,241,57,146,189,164,57,166,181,48,57,142,62,192,55,3,141,0,185,193,131,140,185,42,179,216,185,10,106,18,186,233,114,56,186,222,115,94,186,73,54,130,186,90,46,149,186,247,33,168,186,244,16,187,186,41,251,205,186,107,224,224,186,144,192,243,186,183,77,3,187,109,184,12,187,87,32,22,187,94,133,31,187,109,231,40,187,113,70,50,187,85,162,59,187,3,251,68,187,103,80,78,187,108,162,87,187,254,240,96,187,8,60,106,187,118,131,115,187,51,199,124,187,150,3,131,187,165,161,135,187,189,61,140,187,213,215,144,187,225,111,149,187,216,5,154,187,175,153,158,187,93,43,163,187,216,186,167,187,21,72,172,187,12,211,176,187,176,91,181,187,250,225,185,187,223,101,190,187,85,231,194,187,82,102,199,187,205,226,203,187,187,92,208,187,20,212,212,187,204,72,217,187,220,186,221,187,56,42,226,187,216,150,230,187,177,0,235,187,187,103,239,187,236,203,243,187,57,45,248,187,155,139,252,187,131,115,0,188,185,159,2,188,107,202,4,188,149,243,6,188,48,27,9,188,58,65,11,188,172,101,13,188,131,136,15,188,187,169,17,188,77,201,19,188,55,231,21,188,115,3,24,188,253,29,26,188,208,54,28,188,232,77,30,188,65,99,32,188,213,118,34,188,161,136,36,188,161,152,38,188,207,166,40,188,39,179,42,188,166,189,44,188,70,198,46,188,4,205,48,188,219,209,50,188,199,212,52,188,196,213,54,188,205,212,56,188,222,209,58,188,243,204,60,188,8,198,62,188,25,189,64,188,33,178,66,188,29,165,68,188,8,150,70,188,222,132,72,188,156,113,74,188,61,92,76,188,189,68,78,188,25,43,80,188,75,15,82,188,82,241,83,188,39,209,85,188,200,174,87,188,49,138,89,188,94,99,91,188,75,58,93,188,244,14,95,188,85,225,96,188,107,177,98,188,50,127,100,188,166,74,102,188,196,19,104,188,136,218,105,188,237,158,107,188,242,96,109,188,145,32,111,188,200,221,112,188,147,152,114,188,238,80,116,188,214,6,118,188,71,186,119,188,62,107,121,188,184,25,123,188,176,197,124,188,37,111,126,188,9,11,128,188,57,221,128,188,35,174,129,188,196,125,130,188,27,76,131,188,37,25,132,188,226,228,132,188,79,175,133,188,108,120,134,188,54,64,135,188,173,6,136,188,205,203,136,188,151,143,137,188,9,82,138,188,32,19,139,188,220,210,139,188,58,145,140,188,59,78,141,188,219,9,142,188,26,196,142,188,246,124,143,188,110,52,144,188,129,234,144,188,44,159,145,188,112,82,146,188,73,4,147,188,183,180,147,188,185,99,148,188,78,17,149,188,115,189,149,188,40,104,150,188,108,17,151,188,60,185,151,188,153,95,152,188,128,4,153,188,241,167,153,188,234,73,154,188,106,234,154,188,111,137,155,188,250,38,156,188,8,195,156,188,153,93,157,188,170,246,157,188,60,142,158,188,77,36,159,188,220,184,159,188,232,75,160,188,112,221,160,188,115,109,161,188,239,251,161,188,228,136,162,188,81,20,163,188,53,158,163,188,143,38,164,188,93,173,164,188,159,50,165,188,85,182,165,188,124,56,166,188,21,185,166,188,30,56,167,188,150,181,167,188,125,49,168,188,210,171,168,188,147,36,169,188,193,155,169,188,90,17,170,188,93,133,170,188,203,247,170,188,161,104,171,188,223,215,171,188,133,69,172,188,146,177,172,188,5,28,173,188,221,132,173,188,26,236,173,188,187,81,174,188,192,181,174,188,39,24,175,188,241,120,175,188,28,216,175,188,168,53,176,188,149,145,176,188,226,235,176,188,142,68,177,188,153,155,177,188,3,241,177,188,202,68,178,188,239,150,178,188,113,231,178,188,79,54,179,188,137,131,179,188,32,207,179,188,17,25,180,188,93,97,180,188,4,168,180,188,5,237,180,188,95,48,181,188,19,114,181,188,33,178,181,188,135,240,181,188,70,45,182,188,93,104,182,188,204,161,182,188,147,217,182,188,177,15,183,188,39,68,183,188,244,118,183,188,24,168,183,188,147,215,183,188,101,5,184,188,141,49,184,188,12,92,184,188,225,132,184,188,12,172,184,188,142,209,184,188,101,245,184,188,147,23,185,188,22,56,185,188,240,86,185,188,32,116,185,188,166,143,185,188,129,169,185,188,179,193,185,188,59,216,185,188,25,237,185,188,78,0,186,188,217,17,186,188,186,33,186,188,243,47,186,188,129,60,186,188,103,71,186,188,164,80,186,188,57,88,186,188,37,94,186,188,104,98,186,188,4,101,186,188,248,101,186,188,68,101,186,188,233,98,186,188,231,94,186,188,63,89,186,188,240,81,186,188,251,72,186,188,97,62,186,188,33,50,186,188,60,36,186,188,179,20,186,188,134,3,186,188,181,240,185,188,65,220,185,188,42,198,185,188,112,174,185,188,21,149,185,188,25,122,185,188,123,93,185,188,61,63,185,188,96,31,185,188,227,253,184,188,199,218,184,188,13,182,184,188,182,143,184,188,194,103,184,188,49,62,184,188,5,19,184,188,62,230,183,188,220,183,183,188,224,135,183,188,76,86,183,188,31,35,183,188,91,238,182,188,255,183,182,188,14,128,182,188,135,70,182,188,107,11,182,188,188,206,181,188,122,144,181,188,165,80,181,188,63,15,181,188,72,204,180,188,194,135,180,188,172,65,180,188,9,250,179,188,216,176,179,188,28,102,179,188,212,25,179,188,1,204,178,188,165,124,178,188,193,43,178,188,85,217,177,188], "i8", ALLOC_NONE, Runtime.GLOBAL_BASE+10240);
/* memory initializer */ allocate([99,133,177,188,235,47,177,188,239,216,176,188,111,128,176,188,108,38,176,188,232,202,175,188,228,109,175,188,96,15,175,188,94,175,174,188,223,77,174,188,228,234,173,188,110,134,173,188,126,32,173,188,22,185,172,188,54,80,172,188,224,229,171,188,20,122,171,188,213,12,171,188,35,158,170,188,255,45,170,188,107,188,169,188,104,73,169,188,248,212,168,188,26,95,168,188,210,231,167,188,31,111,167,188,4,245,166,188,129,121,166,188,152,252,165,188,75,126,165,188,154,254,164,188,136,125,164,188,20,251,163,188,65,119,163,188,17,242,162,188,132,107,162,188,156,227,161,188,90,90,161,188,192,207,160,188,208,67,160,188,138,182,159,188,240,39,159,188,4,152,158,188,199,6,158,188,59,116,157,188,97,224,156,188,59,75,156,188,201,180,155,188,15,29,155,188,13,132,154,188,197,233,153,188,56,78,153,188,105,177,152,188,88,19,152,188,7,116,151,188,120,211,150,188,172,49,150,188,166,142,149,188,103,234,148,188,239,68,148,188,66,158,147,188,97,246,146,188,77,77,146,188,9,163,145,188,149,247,144,188,244,74,144,188,39,157,143,188,47,238,142,188,16,62,142,188,202,140,141,188,96,218,140,188,210,38,140,188,36,114,139,188,86,188,138,188,106,5,138,188,98,77,137,188,65,148,136,188,7,218,135,188,183,30,135,188,82,98,134,188,219,164,133,188,82,230,132,188,187,38,132,188,23,102,131,188,104,164,130,188,175,225,129,188,239,29,129,188,41,89,128,188,191,38,127,188,41,153,125,188,147,9,124,188,2,120,122,188,120,228,120,188,251,78,119,188,142,183,117,188,53,30,116,188,244,130,114,188,206,229,112,188,200,70,111,188,230,165,109,188,44,3,108,188,158,94,106,188,63,184,104,188,21,16,103,188,34,102,101,188,107,186,99,188,244,12,98,188,193,93,96,188,215,172,94,188,57,250,92,188,235,69,91,188,242,143,89,188,82,216,87,188,15,31,86,188,45,100,84,188,176,167,82,188,156,233,80,188,247,41,79,188,195,104,77,188,6,166,75,188,195,225,73,188,254,27,72,188,189,84,70,188,3,140,68,188,212,193,66,188,53,246,64,188,43,41,63,188,184,90,61,188,227,138,59,188,174,185,57,188,32,231,55,188,58,19,54,188,4,62,52,188,127,103,50,188,178,143,48,188,160,182,46,188,77,220,44,188,191,0,43,188,249,35,41,188,0,70,39,188,217,102,37,188,135,134,35,188,16,165,33,188,120,194,31,188,194,222,29,188,245,249,27,188,19,20,26,188,35,45,24,188,39,69,22,188,37,92,20,188,33,114,18,188,32,135,16,188,37,155,14,188,55,174,12,188,88,192,10,188,143,209,8,188,222,225,6,188,76,241,4,188,220,255,2,188,146,13,1,188,233,52,254,187,13,77,250,187,155,99,246,187,154,120,242,187,21,140,238,187,21,158,234,187,162,174,230,187,199,189,226,187,139,203,222,187,249,215,218,187,25,227,214,187,244,236,210,187,149,245,206,187,4,253,202,187,73,3,199,187,112,8,195,187,128,12,191,187,130,15,187,187,129,17,183,187,134,18,179,187,152,18,175,187,195,17,171,187,15,16,167,187,133,13,163,187,46,10,159,187,20,6,155,187,64,1,151,187,188,251,146,187,144,245,142,187,197,238,138,187,102,231,134,187,123,223,130,187,25,174,125,187,75,156,117,187,156,137,109,187,32,118,101,187,232,97,93,187,8,77,85,187,145,55,77,187,150,33,69,187,42,11,61,187,95,244,52,187,71,221,44,187,245,197,36,187,123,174,28,187,236,150,20,187,89,127,12,187,214,103,4,187,234,160,248,186,144,114,232,186,194,68,216,186,166,23,200,186,95,235,183,186,20,192,167,186,231,149,151,186,254,108,135,186,251,138,110,186,18,63,78,186,139,246,45,186,176,177,13,186,142,225,218,185,53,104,154,185,203,239,51,185,119,137,76,184,116,45,155,56,92,58,78,57,180,99,167,57,95,158,231,57,79,230,19,58,242,246,51,58,207,0,84,58,159,3,116,58,142,255,137,58,126,249,153,58,125,239,169,58,102,225,185,58,23,207,201,58,108,184,217,58,65,157,233,58,115,125,249,58,112,172,4,59,177,151,12,59,108,128,20,59,143,102,28,59,9,74,36,59,200,42,44,59,186,8,52,59,207,227,59,59,245,187,67,59,27,145,75,59,46,99,83,59,31,50,91,59,220,253,98,59,83,198,106,59,116,139,114,59,45,77,122,59,183,5,129,59,18,227,132,59,160,190,136,59,88,152,140,59,51,112,144,59,38,70,148,59,42,26,152,59,55,236,155,59,68,188,159,59,73,138,163,59,61,86,167,59,25,32,171,59,212,231,174,59,101,173,178,59,197,112,182,59,236,49,186,59,208,240,189,59,107,173,193,59,179,103,197,59,162,31,201,59,46,213,204,59,80,136,208,59,0,57,212,59,53,231,215,59,232,146,219,59,17,60,223,59,168,226,226,59,165,134,230,59,1,40,234,59,179,198,237,59,179,98,241,59,250,251,244,59,129,146,248,59,63,38,252,59,45,183,255,59,161,162,1,60,60,104,3,60,100,44,5,60,20,239,6,60,74,176,8,60,0,112,10,60,52,46,12,60,226,234,13,60,5,166,15,60,155,95,17,60,160,23,19,60,16,206,20,60,231,130,22,60,34,54,24,60,189,231,25,60,181,151,27,60,6,70,29,60,172,242,30,60,164,157,32,60,235,70,34,60,124,238,35,60,86,148,37,60,115,56,39,60,208,218,40,60,107,123,42,60,64,26,44,60,74,183,45,60,136,82,47,60,245,235,48,60,143,131,50,60,81,25,52,60,57,173,53,60,68,63,55,60,109,207,56,60,179,93,58,60,17,234,59,60,132,116,61,60,9,253,62,60,158,131,64,60,62,8,66,60,231,138,67,60,150,11,69,60,71,138,70,60,248,6,72,60,165,129,73,60,76,250,74,60,233,112,76,60,121,229,77,60,250,87,79,60,104,200,80,60,192,54,82,60,1,163,83,60,37,13,85,60,44,117,86,60,18,219,87,60,211,62,89,60,111,160,90,60,224,255,91,60,38,93,93,60,60,184,94,60,33,17,96,60,209,103,97,60,74,188,98,60,138,14,100,60,141,94,101,60,81,172,102,60,211,247,103,60,17,65,105,60,8,136,106,60,183,204,107,60,25,15,109,60,45,79,110,60,240,140,111,60,96,200,112,60,122,1,114,60,61,56,115,60,165,108,116,60,176,158,117,60,92,206,118,60,166,251,119,60,141,38,121,60,14,79,122,60,39,117,123,60,213,152,124,60,23,186,125,60,233,216,126,60,75,245,127,60,157,135,128,60,90,19,129,60,219,157,129,60,31,39,130,60,38,175,130,60,238,53,131,60,118,187,131,60,190,63,132,60,196,194,132,60,135,68,133,60,7,197,133,60,66,68,134,60,56,194,134,60,232,62,135,60,80,186,135,60,112,52,136,60,72,173,136,60,213,36,137,60,23,155,137,60,14,16,138,60,185,131,138,60,22,246,138,60,38,103,139,60,230,214,139,60,87,69,140,60,120,178,140,60,72,30,141,60,197,136,141,60,240,241,141,60,200,89,142,60,76,192,142,60,123,37,143,60,84,137,143,60,216,235,143,60,5,77,144,60,218,172,144,60,87,11,145,60,124,104,145,60,72,196,145,60,185,30,146,60,208,119,146,60,141,207,146,60,237,37,147,60,242,122,147,60,154,206,147,60,228,32,148,60,209,113,148,60,96,193,148,60,144,15,149,60,97,92,149,60,211,167,149,60,228,241,149,60,149,58,150,60,229,129,150,60,212,199,150,60,96,12,151,60,139,79,151,60,84,145,151,60,185,209,151,60,187,16,152,60,90,78,152,60,149,138,152,60,108,197,152,60,222,254,152,60,235,54,153,60,148,109,153,60,215,162,153,60,181,214,153,60,45,9,154,60,62,58,154,60,234,105,154,60,47,152,154,60,14,197,154,60,134,240,154,60,151,26,155,60,65,67,155,60,132,106,155,60,95,144,155,60,211,180,155,60,224,215,155,60,132,249,155,60,194,25,156,60,151,56,156,60,4,86,156,60,10,114,156,60,168,140,156,60,222,165,156,60,172,189,156,60,18,212,156,60,17,233,156,60,167,252,156,60,214,14,157,60,157,31,157,60,253,46,157,60,245,60,157,60,134,73,157,60,175,84,157,60,113,94,157,60,204,102,157,60,193,109,157,60,78,115,157,60,117,119,157,60,54,122,157,60,144,123,157,60,133,123,157,60,20,122,157,60,61,119,157,60,1,115,157,60,96,109,157,60,91,102,157,60,241,93,157,60,34,84,157,60,240,72,157,60,91,60,157,60,98,46,157,60,7,31,157,60,73,14,157,60,41,252,156,60,168,232,156,60,197,211,156,60,129,189,156,60,221,165,156,60,217,140,156,60,117,114,156,60,178,86,156,60,144,57,156,60,16,27,156,60,50,251,155,60,247,217,155,60,96,183,155,60,108,147,155,60,29,110,155,60,114,71,155,60,109,31,155,60,14,246,154,60,86,203,154,60,69,159,154,60,219,113,154,60,27,67,154,60,3,19,154,60,149,225,153,60,209,174,153,60,184,122,153,60,75,69,153,60,139,14,153,60,119,214,152,60,18,157,152,60,90,98,152,60,83,38,152,60,251,232,151,60,84,170,151,60,94,106,151,60,27,41,151,60,138,230,150,60,174,162,150,60,134,93,150,60,20,23,150,60,88,207,149,60,84,134,149,60,7,60,149,60,115,240,148,60,153,163,148,60,122,85,148,60,22,6,148,60,111,181,147,60,133,99,147,60,90,16,147,60,237,187,146,60,65,102,146,60,86,15,146,60,45,183,145,60,200,93,145,60,38,3,145,60,74,167,144,60,51,74,144,60,228,235,143,60,93,140,143,60,159,43,143,60,171,201,142,60,131,102,142,60,39,2,142,60,153,156,141,60,217,53,141,60,233,205,140,60,201,100,140,60,124,250,139,60,2,143,139,60,91,34,139,60,138,180,138,60,144,69,138,60,109,213,137,60,35,100,137,60,180,241,136,60,31,126,136,60,103,9,136,60,140,147,135,60,145,28,135,60,117,164,134,60,59,43,134,60,227,176,133,60,112,53,133,60,225,184,132,60,57,59,132,60,121,188,131,60,162,60,131,60,181,187,130,60,180,57,130,60,159,182,129,60,121,50,129,60,67,173,128,60,254,38,128,60,86,63,127,60,152,46,126,60,195,27,125,60,220,6,124,60,229,239,122,60,224,214,121,60,209,187,120,60,186,158,119,60,158,127,118,60,128,94,117,60,99,59,116,60,74,22,115,60,55,239,113,60,46,198,112,60,50,155,111,60,69,110,110,60,107,63,109,60,166,14,108,60,250,219,106,60,105,167,105,60,247,112,104,60,166,56,103,60,122,254,101,60,118,194,100,60,157,132,99,60,242,68,98,60,120,3,97,60,50,192,95,60,36,123,94,60,80,52,93,60,186,235,91,60,101,161,90,60,84,85,89,60,139,7,88,60,12,184,86,60,220,102,85,60,252,19,84,60,113,191,82,60,61,105,81,60,101,17,80,60,234,183,78,60,210,92,77,60,30,0,76,60,211,161,74,60,244,65,73,60,131,224,71,60,133,125,70,60,253,24,69,60,238,178,67,60,92,75,66,60,74,226,64,60,188,119,63,60,180,11,62,60,55,158,60,60,72,47,59,60,235,190,57,60,34,77,56,60,242,217,54,60,94,101,53,60,105,239,51,60,24,120,50,60,109,255,48,60,108,133,47,60,25,10,46,60,119,141,44,60,138,15,43,60,85,144,41,60,221,15,40,60,36,142,38,60,46,11,37,60,0,135,35,60,156,1,34,60,6,123,32,60,66,243,30,60,83,106,29,60,62,224,27,60,6,85,26,60,174,200,24,60,59,59,23,60,175,172,21,60,15,29,20,60,95,140,18,60,162,250,16,60,219,103,15,60,16,212,13,60,66,63,12,60,119,169,10,60,177,18,9,60,245,122,7,60,71,226,5,60,170,72,4,60,33,174,2,60,178,18,1,60,189,236,254,59,88,178,251,59,58,118,248,59,109,56,245,59,247,248,241,59,225,183,238,59,49,117,235,59,239,48,232,59,36,235,228,59,215,163,225,59,15,91,222,59,213,16,219,59,47,197,215,59,38,120,212,59,193,41,209,59,8,218,205,59,4,137,202,59,186,54,199,59,52,227,195,59,121,142,192,59,145,56,189,59,132,225,185,59,88,137,182,59,23,48,179,59,200,213,175,59,114,122,172,59,30,30,169,59,211,192,165,59,154,98,162,59,121,3,159,59,120,163,155,59,160,66,152,59,249,224,148,59,137,126,145,59,89,27,142,59,113,183,138,59,217,82,135,59,152,237,131,59,182,135,128,59,117,66,122,59,92,116,115,59,49,165,108,59,2,213,101,59,224,3,95,59,218,49,88,59,255,94,81,59,96,139,74,59,12,183,67,59,19,226,60,59,132,12,54,59,110,54,47,59,226,95,40,59,240,136,33,59,165,177,26,59,19,218,19,59,73,2,13,59,87,42,6,59,150,164,254,58,107,244,240,58,77,68,227,58,90,148,213,58,178,228,199,58,114,53,186,58,187,134,172,58,172,216,158,58,99,43,145,58,255,126,131,58,62,167,107,58,197,82,80,58,207,0,53,58,155,177,25,58,204,202,252,57,219,56,198,57,223,173,143,57,163,84,50,57,181,186,138,56,152,36,30,184,234,93,20,185,254,143,128,185,53,231,182,185,31,52,237,185,34,187,17,186,148,214,44,186,42,236,71,186,167,251,98,186,206,4,126,186,178,131,140,186,149,1,154,186,244,123,167,186,175,242,180,186,168,101,194,186,195,212,207,186,224,63,221,186,227,166,234,186,173,9,248,186,16,180,2,187,16,97,9,187,198,11,16,187,37,180,22,187,30,90,29,187,162,253,35,187,161,158,42,187,14,61,49,187,219,216,55,187,247,113,62,187,85,8,69,187,231,155,75,187,157,44,82,187,106,186,88,187,62,69,95,187,12,205,101,187,197,81,108,187,91,211,114,187,191,81,121,187,228,204,127,187,93,34,131,187,155,92,134,187,35,149,137,187,239,203,140,187,248,0,144,187,55,52,147,187,164,101,150,187,58,149,153,187,241,194,156,187,193,238,159,187,165,24,163,187,149,64,166,187,138,102,169,187,126,138,172,187,105,172,175,187,70,204,178,187,12,234,181,187,183,5,185,187,61,31,188,187,154,54,191,187,199,75,194,187,187,94,197,187,114,111,200,187,229,125,203,187,12,138,206,187,226,147,209,187,95,155,212,187,126,160,215,187,55,163,218,187,133,163,221,187,96,161,224,187,196,156,227,187,168,149,230,187,7,140,233,187,219,127,236,187,28,113,239,187,198,95,242,187,209,75,245,187,55,53,248,187,243,27,251,187,254,255,253,187,169,112,0,188,244,223,1,188,222,77,3,188,99,186,4,188,128,37,6,188,50,143,7,188,119,247,8,188,76,94,10,188,172,195,11,188,151,39,13,188,7,138,14,188,252,234,15,188,113,74,17,188,100,168,18,188,210,4,20,188,184,95,21,188,19,185,22,188,225,16,24,188,31,103,25,188,202,187,26,188,222,14,28,188,90,96,29,188,59,176,30,188,126,254,31,188,32,75,33,188,30,150,34,188,118,223,35,188,38,39,37,188,42,109,38,188,128,177,39,188,37,244,40,188,23,53,42,188,83,116,43,188,214,177,44,188,159,237,45,188,170,39,47,188,245,95,48,188,126,150,49,188,66,203,50,188,62,254,51,188,112,47,53,188,214,94,54,188,110,140,55,188,52,184,56,188,39,226,57,188,68,10,59,188,137,48,60,188,243,84,61,188,129,119,62,188,48,152,63,188,253,182,64,188,231,211,65,188,235,238,66,188,7,8,68,188,57,31,69,188,126,52,70,188,213,71,71,188,60,89,72,188,176,104,73,188,46,118,74,188,182,129,75,188,69,139,76,188,217,146,77,188,112,152,78,188,7,156,79,188,158,157,80,188,50,157,81,188,193,154,82,188,72,150,83,188,199,143,84,188,60,135,85,188,164,124,86,188,253,111,87,188,70,97,88,188,125,80,89,188,161,61,90,188,174,40,91,188,164,17,92,188,130,248,92,188,68,221,93,188,234,191,94,188,114,160,95,188,217,126,96,188,32,91,97,188,67,53,98,188,66,13,99,188,26,227,99,188,203,182,100,188,82,136,101,188,174,87,102,188,222,36,103,188,224,239,103,188,179,184,104,188,85,127,105,188,197,67,106,188,1,6,107,188,9,198,107,188,218,131,108,188,116,63,109,188,213,248,109,188,252,175,110,188,231,100,111,188,149,23,112,188,6,200,112,188,55,118,113,188,40,34,114,188,216,203,114,188,69,115,115,188,110,24,116,188,82,187,116,188,240,91,117,188,72,250,117,188,87,150,118,188,29,48,119,188,153,199,119,188,202,92,120,188,175,239,120,188,71,128,121,188,145,14,122,188,141,154,122,188,57,36,123,188,148,171,123,188,158,48,124,188,85,179,124,188,186,51,125,188,203,177,125,188,136,45,126,188,240,166,126,188,1,30,127,188,188,146,127,188,144,2,128,188,150,58,128,188,112,113,128,188,29,167,128,188,157,219,128,188,241,14,129,188,22,65,129,188,15,114,129,188,217,161,129,188,117,208,129,188,227,253,129,188,34,42,130,188,51,85,130,188,20,127,130,188,199,167,130,188,74,207,130,188,158,245,130,188,194,26,131,188,182,62,131,188,122,97,131,188,15,131,131,188,115,163,131,188,167,194,131,188,170,224,131,188,126,253,131,188,32,25,132,188,147,51,132,188,212,76,132,188,230,100,132,188,198,123,132,188,118,145,132,188,245,165,132,188,68,185,132,188,98,203,132,188,79,220,132,188,12,236,132,188,153,250,132,188,245,7,133,188,33,20,133,188,28,31,133,188,231,40,133,188,130,49,133,188,237,56,133,188,41,63,133,188,52,68,133,188,16,72,133,188,189,74,133,188,58,76,133,188,137,76,133,188,168,75,133,188,153,73,133,188,91,70,133,188,239,65,133,188,85,60,133,188,141,53,133,188,152,45,133,188,117,36,133,188,37,26,133,188,169,14,133,188,0,2,133,188,43,244,132,188,42,229,132,188,253,212,132,188,166,195,132,188,35,177,132,188,118,157,132,188,159,136,132,188,158,114,132,188,116,91,132,188,32,67,132,188,164,41,132,188,0,15,132,188,52,243,131,188,65,214,131,188,39,184,131,188,230,152,131,188,127,120,131,188,243,86,131,188,66,52,131,188,108,16,131,188,115,235,130,188,85,197,130,188,21,158,130,188,178,117,130,188,45,76,130,188,135,33,130,188,192,245,129,188,217,200,129,188,210,154,129,188,172,107,129,188,103,59,129,188,5,10,129,188,133,215,128,188,232,163,128,188,48,111,128,188,92,57,128,188,109,2,128,188,202,148,127,188,133,34,127,188,16,174,126,188,106,55,126,188,150,190,125,188,148,67,125,188,103,198,124,188,16,71,124,188,143,197,123,188,231,65,123,188,26,188,122,188,40,52,122,188,19,170,121,188,221,29,121,188,136,143,120,188,20,255,119,188,132,108,119,188,218,215,118,188,22,65,118,188,59,168,117,188,74,13,117,188,69,112,116,188,45,209,115,188,6,48,115,188,207,140,114,188,139,231,113,188,60,64,113,188,228,150,112,188,132,235,111,188,30,62,111,188,181,142,110,188,73,221,109,188,221,41,109,188,115,116,108,188,13,189,107,188,172,3,107,188,83,72,106,188,4,139,105,188,192,203,104,188,138,10,104,188,99,71,103,188,78,130,102,188,77,187,101,188,98,242,100,188,142,39,100,188,213,90,99,188,55,140,98,188,184,187,97,188,89,233,96,188,29,21,96,188,6,63,95,188,21,103,94,188,78,141,93,188,178,177,92,188,67,212,91,188,4,245,90,188,248,19,90,188,32,49,89,188,127,76,88,188,22,102,87,188,233,125,86,188,250,147,85,188,75,168,84,188,222,186,83,188,182,203,82,188,214,218,81,188,63,232,80,188,244,243,79,188,247,253,78,188,75,6,78,188,243,12,77,188,241,17,76,188,71,21,75,188,248,22,74,188,6,23,73,188,117,21,72,188,70,18,71,188,123,13,70,188,25,7,69,188,33,255,67,188,149,245,66,188,121,234,65,188,207,221,64,188,153,207,63,188,219,191,62,188,151,174,61,188,207,155,60,188,135,135,59,188,192,113,58,188,127,90,57,188,197,65,56,188,149,39,55,188,241,11,54,188,222,238,52,188,92,208,51,188,112,176,50,188,28,143,49,188,99,108,48,188,71,72,47,188,203,34,46,188,243,251,44,188,193,211,43,188,55,170,42,188,89,127,41,188,42,83,40,188,172,37,39,188,226,246,37,188,208,198,36,188,120,149,35,188,221,98,34,188,1,47,33,188,233,249,31,188,150,195,30,188,13,140,29,188,79,83,28,188,95,25,27,188,65,222,25,188,248,161,24,188,135,100,23,188,240,37,22,188,55,230,20,188,95,165,19,188,106,99,18,188,92,32,17,188,56,220,15,188,1,151,14,188,186,80,13,188,101,9,12,188,7,193,10,188,161,119,9,188,56,45,8,188,206,225,6,188,103,149,5,188,5,72,4,188,172,249,2,188,94,170,1,188,32,90,0,188,230,17,254,187,183,109,251,187,184,199,248,187,241,31,246,187,102,118,243,187,31,203,240,187,34,30,238,187,118,111,235,187,32,191,232,187,38,13,230,187,144,89,227,187,100,164,224,187,168,237,221,187,99,53,219,187,154,123,216,187,85,192,213,187,154,3,211,187,111,69,208,187,219,133,205,187,229,196,202,187,146,2,200,187,233,62,197,187,241,121,194,187,176,179,191,187,44,236,188,187,109,35,186,187,121,89,183,187,86,142,180,187,11,194,177,187,158,244,174,187,22,38,172,187,122,86,169,187,208,133,166,187,30,180,163,187,107,225,160,187,190,13,158,187,30,57,155,187,144,99,152,187,28,141,149,187,200,181,146,187,154,221,143,187,154,4,141,187,206,42,138,187,60,80,135,187,235,116,132,187,226,152,129,187,79,120,125,187,131,189,119,187,110,1,114,187,30,68,108,187,159,133,102,187,255,197,96,187,75,5,91,187,144,67,85,187,220,128,79,187,59,189,73,187,188,248,67,187,106,51,62,187,85,109,56,187,136,166,50,187,16,223,44,187,253,22,39,187,89,78,33,187,51,133,27,187,152,187,21,187,150,241,15,187,56,39,10,187,141,92,4,187,68,35,253,186,8,141,241,186,128,246,229,186,198,95,218,186,246,200,206,186,41,50,195,186,122,155,183,186,3,5,172,186,222,110,160,186,38,217,148,186,245,67,137,186,202,94,123,186,32,55,100,186,34,17,77,186,3,237,53,186,247,202,30,186,52,171,7,186,216,27,225,185,169,230,178,185,67,183,132,185,27,28,45,185,194,173,161,184,144,5,54,55,134,17,207,56,142,161,67,57,246,212,143,57,135,208,189,57,18,195,235,57,25,214,12,58,191,197,35,58,73,176,58,58,130,149,81,58,56,117,104,58,56,79,127,58,167,17,139,58,164,120,150,58,122,220,161,58,14,61,173,58,73,154,184,58,15,244,195,58,73,74,207,58,221,156,218,58,178,235,229,58,174,54,241,58,185,125,252,58,93,224,3,59,204,127,9,59,29,29,15,59,67,184,20,59,50,81,26,59,223,231,31,59,59,124,37,59,60,14,43,59,212,157,48,59,248,42,54,59,156,181,59,59,178,61,65,59,48,195,70,59,8,70,76,59,48,198,81,59,153,67,87,59,58,190,92,59,5,54,98,59,239,170,103,59,236,28,109,59,239,139,114,59,237,247,119,59,219,96,125,59,86,99,129,59,170,20,132,59,100,196,134,59,126,114,137,59,242,30,140,59,187,201,142,59,211,114,145,59,51,26,148,59,214,191,150,59,183,99,153,59,207,5,156,59,25,166,158,59,144,68,161,59,45,225,163,59,235,123,166,59,196,20,169,59,179,171,171,59,178,64,174,59,188,211,176,59,203,100,179,59,217,243,181,59,225,128,184,59,222,11,187,59,202,148,189,59,160,27,192,59,90,160,194,59,243,34,197,59,102,163,199,59,172,33,202,59,194,157,204,59,161,23,207,59,68,143,209,59,167,4,212,59,195,119,214,59,148,232,216,59,20,87,219,59,63,195,221,59,14,45,224,59,126,148,226,59,137,249,228,59,41,92,231,59,91,188,233,59,24,26,236,59,92,117,238,59,34,206,240,59,101,36,243,59,32,120,245,59,79,201,247,59,235,23,250,59,241,99,252,59,92,173,254,59,19,122,0,60,38,156,1,60,228,188,2,60,75,220,3,60,88,250,4,60,10,23,6,60,94,50,7,60,81,76,8,60,225,100,9,60,13,124,10,60,209,145,11,60,44,166,12,60,27,185,13,60,156,202,14,60,173,218,15,60,76,233,16,60,118,246,17,60,41,2,19,60,100,12,20,60,36,21,21,60,103,28,22,60,43,34,23,60,110,38,24,60,45,41,25,60,104,42,26,60,26,42,27,60,68,40,28,60,226,36,29,60,242,31,30,60,116,25,31,60,100,17,32,60,192,7,33,60,136,252,33,60,184,239,34,60,79,225,35,60,76,209,36,60,171,191,37,60,108,172,38,60,140,151,39,60,10,129,40,60,228,104,41,60,24,79,42,60,164,51,43,60,135,22,44,60,190,247,44,60,72,215,45,60,36,181,46,60,79,145,47,60,201,107,48,60,142,68,49,60,158,27,50,60,247,240,50,60,152,196,51,60,126,150,52,60,168,102,53,60,21,53,54,60,195,1,55,60,177,204,55,60,220,149,56,60,68,93,57,60,231,34,58,60,195,230,58,60,216,168,59,60,35,105,60,60,163,39,61,60,87,228,61,60,61,159,62,60,85,88,63,60,156,15,64,60,17,197,64,60,179,120,65,60,130,42,66,60,122,218,66,60,156,136,67,60,229,52,68,60,86,223,68,60,235,135,69,60,165,46,70,60,130,211,70,60,129,118,71,60,161,23,72,60,224,182,72,60,62,84,73,60,185,239,73,60,81,137,74,60,3,33,75,60,208,182,75,60,183,74,76,60,181,220,76,60,203,108,77,60,246,250,77,60,55,135,78,60,141,17,79,60,246,153,79,60,113,32,80,60,254,164,80,60,155,39,81,60,73,168,81,60,6,39,82,60,208,163,82,60,169,30,83,60,142,151,83,60,127,14,84,60,123,131,84,60,129,246,84,60,145,103,85,60,171,214,85,60,204,67,86,60,246,174,86,60,38,24,87,60,93,127,87,60,154,228,87,60,220,71,88,60,34,169,88,60,109,8,89,60,188,101,89,60,13,193,89,60,98,26,90,60,184,113,90,60,16,199,90,60,105,26,91,60,196,107,91,60,30,187,91,60,121,8,92,60,211,83,92,60,45,157,92,60,134,228,92,60,221,41,93,60,51,109,93,60,135,174,93,60,217,237,93,60,40,43,94,60,117,102,94,60,191,159,94,60,6,215,94,60,73,12,95,60,138,63,95,60,199,112,95,60,0,160,95,60,54,205,95,60,104,248,95,60,150,33,96,60,192,72,96,60,231,109,96,60,10,145,96,60,40,178,96,60,67,209,96,60,90,238,96,60,109,9,97,60,125,34,97,60,137,57,97,60,146,78,97,60,151,97,97,60,153,114,97,60,153,129,97,60,149,142,97,60,143,153,97,60,134,162,97,60,124,169,97,60,111,174,97,60,97,177,97,60,82,178,97,60,66,177,97,60,49,174,97,60,31,169,97,60,14,162,97,60,253,152,97,60,237,141,97,60,223,128,97,60,210,113,97,60,199,96,97,60,191,77,97,60,185,56,97,60,184,33,97,60,187,8,97,60,194,237,96,60,206,208,96,60,225,177,96,60,250,144,96,60,25,110,96,60,65,73,96,60,112,34,96,60,169,249,95,60,235,206,95,60,55,162,95,60,143,115,95,60,242,66,95,60,98,16,95,60,223,219,94,60,106,165,94,60,3,109,94,60,173,50,94,60,103,246,93,60,50,184,93,60,15,120,93,60,255,53,93,60,4,242,92,60,29,172,92,60,76,100,92,60,145,26,92,60,238,206,91,60,100,129,91,60,244,49,91,60,158,224,90,60,100,141,90,60,70,56,90,60,71,225,89,60,102,136,89,60,165,45,89,60,5,209,88,60,136,114,88,60,45,18,88,60,247,175,87,60,231,75,87,60,254,229,86,60,60,126,86,60,164,20,86,60,54,169,85,60,244,59,85,60,223,204,84,60,248,91,84,60,65,233,83,60,187,116,83,60,102,254,82,60,69,134,82,60,90,12,82,60,164,144,81,60,38,19,81,60,225,147,80,60,214,18,80,60,8,144,79,60,118,11,79,60,36,133,78,60,17,253,77,60,64,115,77,60,179,231,76,60,106,90,76,60,104,203,75,60,173,58,75,60,60,168,74,60,22,20,74,60,60,126,73,60,176,230,72,60,117,77,72,60,138,178,71,60,243,21,71,60,176,119,70,60,196,215,69,60,48,54,69,60,245,146,68,60,22,238,67,60,148,71,67,60,114,159,66,60,175,245,65,60,80,74,65,60,84,157,64,60,191,238,63,60,145,62,63,60,205,140,62,60,117,217,61,60,138,36,61,60,14,110,60,60,3,182,59,60,107,252,58,60,72,65,58,60,155,132,57,60,104,198,56,60,175,6,56,60,114,69,55,60,180,130,54,60,119,190,53,60,188,248,52,60,133,49,52,60,213,104,51,60,173,158,50,60,16,211,49,60,0,6,49,60,126,55,48,60,140,103,47,60,46,150,46,60,100,195,45,60,49,239,44,60,152,25,44,60,153,66,43,60,56,106,42,60,118,144,41,60,86,181,40,60,217,216,39,60,3,251,38,60,212,27,38,60,80,59,37,60,120,89,36,60,79,118,35,60,216,145,34,60,19,172,33,60,4,197,32,60,172,220,31,60,14,243,30,60,45,8,30,60,10,28,29,60,169,46,28,60,10,64,27,60,48,80,26,60,31,95,25,60,216,108,24,60,93,121,23,60,177,132,22,60,214,142,21,60,207,151,20,60,157,159,19,60,68,166,18,60,198,171,17,60,37,176,16,60,99,179,15,60,132,181,14,60,136,182,13,60,116,182,12,60,73,181,11,60,10,179,10,60,185,175,9,60,89,171,8,60,236,165,7,60,116,159,6,60,245,151,5,60,112,143,4,60,233,133,3,60,97,123,2,60,220,111,1,60,91,99,0,60,196,171,254,59,229,142,252,59,30,112,250,59,118,79,248,59,241,44,246,59,148,8,244,59,101,226,241,59,104,186,239,59,163,144,237,59,27,101,235,59,213,55,233,59,215,8,231,59,37,216,228,59,197,165,226,59,188,113,224,59,16,60,222,59,198,4,220,59,226,203,217,59,107,145,215,59,102,85,213,59,215,23,211,59,197,216,208,59,52,152,206,59,43,86,204,59,174,18,202,59,195,205,199,59,111,135,197,59,183,63,195,59,161,246,192,59,51,172,190,59,114,96,188,59,99,19,186,59,11,197,183,59,113,117,181,59,153,36,179,59,137,210,176,59,71,127,174,59,216,42,172,59,65,213,169,59,136,126,167,59,179,38,165,59,199,205,162,59,201,115,160,59,191,24,158,59,175,188,155,59,157,95,153,59,145,1,151,59,142,162,148,59,155,66,146,59,189,225,143,59,250,127,141,59,88,29,139,59,219,185,136,59,138,85,134,59,105,240,131,59,128,138,129,59,164,71,126,59,204,120,121,59,132,168,116,59,212,214,111,59,202,3,107,59,113,47,102,59,210,89,97,59,250,130,92,59,244,170,87,59,203,209,82,59,138,247,77,59,61,28,73,59,238,63,68,59,169,98,63,59,121,132,58,59,105,165,53,59,132,197,48,59,215,228,43,59,107,3,39,59,76,33,34,59,134,62,29,59,35,91,24,59,46,119,19,59,180,146,14,59,191,173,9,59,90,200,4,59,34,197,255,58,221,248,245,58,252,43,236,58,149,94,226,58,190,144,216,58,143,194,206,58,29,244,196,58,126,37,187,58,202,86,177,58,21,136,167,58,120,185,157,58,7,235,147,58,217,28,138,58,4,79,128,58,63,3,109,58,129,105,89,58,250,208,69,58,216,57,50,58,71,164,30,58,114,16,11,58,14,253,238,57,98,221,199,57,56,194,160,57,212,87,115,57,158,53,37,57,251,60,174,56,43,50,17,55,133,215,137,184,131,221,18,185,112,193,96,185,109,75,151,185,138,46,190,185,183,9,229,185,78,238,5,186,114,83,25,186,28,180,44,186,32,16,64,186,84,103,83,186,139,185,102,186,155,6,122,186,45,167,134,186,77,72,144,186,153,230,153,186,252,129,163,186,96,26,173,186,176,175,182,186,215,65,192,186,190,208,201,186,82,92,211,186,125,228,220,186,42,105,230,186,67,234,239,186,180,103,249,186,180,112,1,187,165,43,6,187,162,228,10,187,161,155,15,187,152,80,20,187,124,3,25,187,67,180,29,187,227,98,34,187,81,15,39,187,130,185,43,187,110,97,48,187,9,7,53,187,74,170,57,187,38,75,62,187,146,233,66,187,134,133,71,187,247,30,76,187,218,181,80,187,38,74,85,187,209,219,89,187,209,106,94,187,28,247,98,187,168,128,103,187,107,7,108,187,91,139,112,187,110,12,117,187,156,138,121,187,217,5,126,187,14,63,129,187,174,121,131,187,200,178,133,187,86,234,135,187,84,32,138,187,189,84,140,187,140,135,142,187,189,184,144,187,75,232,146,187,49,22,149,187,106,66,151,187,242,108,153,187,196,149,155,187,220,188,157,187,53,226,159,187,202,5,162,187,151,39,164,187,150,71,166,187,197,101,168,187,29,130,170,187,155,156,172,187,59,181,174,187,246,203,176,187,202,224,178,187,178,243,180,187,169,4,183,187,171,19,185,187,180,32,187,187,191,43,189,187,200,52,191,187,203,59,193,187,195,64,195,187,172,67,197,187,129,68,199,187,64,67,201,187,227,63,203,187,102,58,205,187,197,50,207,187,252,40,209,187,8,29,211,187,226,14,213,187,137,254,214,187,247,235,216,187,41,215,218,187,26,192,220,187,199,166,222,187,44,139,224,187,68,109,226,187,13,77,228,187,129,42,230,187,157,5,232,187,94,222,233,187,191,180,235,187,188,136,237,187,83,90,239,187,126,41,241,187,59,246,242,187,134,192,244,187,90,136,246,187,181,77,248,187,147,16,250,187,240,208,251,187,200,142,253,187,24,74,255,187,111,129,0,188,137,92,1,188,91,54,2,188,226,14,3,188,28,230,3,188,7,188,4,188,162,144,5,188,236,99,6,188,226,53,7,188,131,6,8,188,206,213,8,188,192,163,9,188,88,112,10,188,148,59,11,188,116,5,12,188,244,205,12,188,21,149,13,188,211,90,14,188,46,31,15,188,37,226,15,188,181,163,16,188,221,99,17,188,155,34,18,188,239,223,18,188,215,155,19,188,80,86,20,188,91,15,21,188,245,198,21,188,29,125,22,188,210,49,23,188,18,229,23,188,220,150,24,188,47,71,25,188,9,246,25,188,104,163,26,188,77,79,27,188,181,249,27,188,159,162,28,188,9,74,29,188,243,239,29,188,92,148,30,188,66,55,31,188,163,216,31,188,128,120,32,188,214,22,33,188,164,179,33,188,234,78,34,188,165,232,34,188,214,128,35,188,122,23,36,188,146,172,36,188,27,64,37,188,21,210,37,188,126,98,38,188,86,241,38,188,156,126,39,188,78,10,40,188,108,148,40,188,244,28,41,188,230,163,41,188,65,41,42,188,4,173,42,188,45,47,43,188,189,175,43,188,177,46,44,188,10,172,44,188,198,39,45,188,229,161,45,188,101,26,46,188,71,145,46,188,136,6,47,188,41,122,47,188,40,236,47,188,133,92,48,188,64,203,48,188,86,56,49,188,200,163,49,188,150,13,50,188,189,117,50,188,62,220,50,188,25,65,51,188,75,164,51,188,213,5,52,188,183,101,52,188,239,195,52,188,125,32,53,188,96,123,53,188,153,212,53,188,37,44,54,188,6,130,54,188,58,214,54,188,193,40,55,188,154,121,55,188,198,200,55,188,67,22,56,188,17,98,56,188,47,172,56,188,159,244,56,188,94,59,57,188,109,128,57,188,203,195,57,188,120,5,58,188,115,69,58,188,189,131,58,188,85,192,58,188,59,251,58,188,111,52,59,188,240,107,59,188,190,161,59,188,216,213,59,188,64,8,60,188,244,56,60,188,245,103,60,188,65,149,60,188,218,192,60,188,191,234,60,188,240,18,61,188,109,57,61,188,53,94,61,188,73,129,61,188,169,162,61,188,84,194,61,188,75,224,61,188,142,252,61,188,28,23,62,188,246,47,62,188,28,71,62,188,142,92,62,188,75,112,62,188,85,130,62,188,171,146,62,188,77,161,62,188,59,174,62,188,119,185,62,188,254,194,62,188,211,202,62,188,245,208,62,188,100,213,62,188,33,216,62,188,44,217,62,188,133,216,62,188,44,214,62,188,34,210,62,188,103,204,62,188,251,196,62,188,223,187,62,188,19,177,62,188,151,164,62,188,108,150,62,188,146,134,62,188,10,117,62,188,212,97,62,188,240,76,62,188,95,54,62,188,33,30,62,188,56,4,62,188,163,232,61,188,98,203,61,188,119,172,61,188,226,139,61,188,164,105,61,188,188,69,61,188,44,32,61,188,245,248,60,188,22,208,60,188,145,165,60,188,102,121,60,188,150,75,60,188,33,28,60,188,9,235,59,188,77,184,59,188,239,131,59,188,239,77,59,188,79,22,59,188,14,221,58,188,46,162,58,188,175,101,58,188,146,39,58,188,216,231,57,188,130,166,57,188,145,99,57,188,6,31,57,188,224,216,56,188,34,145,56,188,204,71,56,188,223,252,55,188,93,176,55,188,69,98,55,188,153,18,55,188,89,193,54,188,136,110,54,188,38,26,54,188,51,196,53,188,177,108,53,188,161,19,53,188,3,185,52,188,218,92,52,188,38,255,51,188,232,159,51,188,33,63,51,188,210,220,50,188,253,120,50,188,162,19,50,188,195,172,49,188,97,68,49,188,125,218,48,188,25,111,48,188,52,2,48,188,209,147,47,188,241,35,47,188,150,178,46,188,191,63,46,188,111,203,45,188,167,85,45,188,104,222,44,188,180,101,44,188,139,235,43,188,239,111,43,188,226,242,42,188,100,116,42,188,120,244,41,188,30,115,41,188,88,240,40,188,39,108,40,188,141,230,39,188,139,95,39,188,34,215,38,188,84,77,38,188,35,194,37,188,143,53,37,188,155,167,36,188,72,24,36,188,150,135,35,188,137,245,34,188,33,98,34,188,96,205,33,188,71,55,33,188,216,159,32,188,21,7,32,188,254,108,31,188,151,209,30,188,223,52,30,188,218,150,29,188,135,247,28,188,234,86,28,188,4,181,27,188,214,17,27,188,98,109,26,188,170,199,25,188,175,32,25,188,115,120,24,188,248,206,23,188,63,36,23,188,75,120,22,188,28,203,21,188,182,28,21,188,24,109,20,188,70,188,19,188,64,10,19,188,9,87,18,188,163,162,17,188,15,237,16,188,79,54,16,188,101,126,15,188,83,197,14,188,26,11,14,188,188,79,13,188,60,147,12,188,155,213,11,188,219,22,11,188,253,86,10,188,5,150,9,188,243,211,8,188,201,16,8,188,138,76,7,188,55,135,6,188,211,192,5,188,94,249,4,188,220,48,4,188,78,103,3,188,182,156,2,188,22,209,1,188,112,4,1,188,198,54,0,188,52,208,254,187,220,48,253,187,135,143,251,187,59,236,249,187,251,70,248,187,203,159,246,187,175,246,244,187,171,75,243,187,196,158,241,187,253,239,239,187,91,63,238,187,225,140,236,187,148,216,234,187,121,34,233,187,146,106,231,187,230,176,229,187,118,245,227,187,73,56,226,187,98,121,224,187,197,184,222,187,119,246,220,187,124,50,219,187,215,108,217,187,143,165,215,187,166,220,213,187,33,18,212,187,5,70,210,187,86,120,208,187,24,169,206,187,79,216,204,187,0,6,203,187,48,50,201,187,226,92,199,187,27,134,197,187,224,173,195,187,53,212,193,187,30,249,191,187,160,28,190,187,192,62,188,187,129,95,186,187,232,126,184,187,250,156,182,187,188,185,180,187,49,213,178,187,95,239,176,187,73,8,175,187,244,31,173,187,102,54,171,187,162,75,169,187,172,95,167,187,139,114,165,187,65,132,163,187,212,148,161,187,72,164,159,187,163,178,157,187,231,191,155,187,27,204,153,187,66,215,151,187,98,225,149,187,127,234,147,187,157,242,145,187,193,249,143,187,240,255,141,187,47,5,140,187,130,9,138,187,237,12,136,187,118,15,134,187,33,17,132,187,243,17,130,187,241,17,128,187,60,34,124,187,1,31,120,187,57,26,116,187,237,19,112,187,40,12,108,187,241,2,104,187,83,248,99,187,87,236,95,187,7,223,91,187,108,208,87,187,143,192,83,187,122,175,79,187,54,157,75,187,205,137,71,187,72,117,67,187,177,95,63,187,16,73,59,187,113,49,55,187,219,24,51,187,89,255,46,187,244,228,42,187,181,201,38,187,166,173,34,187,209,144,30,187,62,115,26,187,248,84,22,187,8,54,18,187,119,22,14,187,79,246,9,187,153,213,5,187,95,180,1,187,84,37,251,186,7,225,242,186,235,155,234,186,18,86,226,186,143,15,218,186,118,200,209,186,216,128,201,186,202,56,193,186,93,240,184,186,165,167,176,186,181,94,168,186,159,21,160,186,119,204,151,186,79,131,143,186,58,58,135,186,150,226,125,186,41,81,109,186,83,192,92,186,58,48,76,186,3,161,59,186,212,18,43,186,210,133,26,186,35,250,9,186,215,223,242,185,163,206,209,185,245,192,176,185,23,183,143,185,167,98,93,185,233,95,27,185,22,205,178,184,253,184,187,183,17,183,41,56,24,143,216,56,165,21,46,57,122,215,111,57,60,198,152,57,6,154,185,57,209,102,218,57,85,44,251,57,35,245,13,58,47,80,30,58,40,167,46,58,236,249,62,58,84,72,79,58,61,146,95,58,131,215,111,58,0,12,128,58,202,41,136,58,11,69,144,58,177,93,152,58,172,115,160,58,233,134,168,58,86,151,176,58,226,164,184,58,121,175,192,58,11,183,200,58,134,187,208,58,216,188,216,58,240,186,224,58,187,181,232,58,40,173,240,58,38,161,248,58,210,72,0,59,71,63,4,59,234,51,8,59,179,38,12,59,152,23,16,59,145,6,20,59,150,243,23,59,157,222,27,59,159,199,31,59,146,174,35,59,111,147,39,59,44,118,43,59,193,86,47,59,37,53,51,59,81,17,55,59,60,235,58,59,221,194,62,59,44,152,66,59,32,107,70,59,178,59,74,59,218,9,78,59,141,213,81,59,198,158,85,59,123,101,89,59,164,41,93,59,57,235,96,59,50,170,100,59,135,102,104,59,47,32,108,59,36,215,111,59,92,139,115,59,207,60,119,59,119,235,122,59,74,151,126,59,33,32,129,59,43,243,130,59,191,196,132,59,217,148,134,59,117,99,136,59,145,48,138,59,38,252,139,59,51,198,141,59,178,142,143,59,161,85,145,59,251,26,147,59,189,222,148,59,227,160,150,59,105,97,152,59,75,32,154,59,134,221,155,59,22,153,157,59,248,82,159,59,39,11,161,59,160,193,162,59,96,118,164,59,99,41,166,59,165,218,167,59,35,138,169,59,217,55,171,59,195,227,172,59,223,141,174,59,40,54,176,59,156,220,177,59,54,129,179,59,244,35,181,59,209,196,182,59,203,99,184,59,223,0,186,59,8,156,187,59,67,53,189,59,141,204,190,59,228,97,192,59,66,245,193,59,166,134,195,59,12,22,197,59,113,163,198,59,210,46,200,59,42,184,201,59,120,63,203,59,185,196,204,59,232,71,206,59,3,201,207,59,6,72,209,59,240,196,210,59,188,63,212,59,104,184,213,59,240,46,215,59,83,163,216,59,140,21,218,59,152,133,219,59,118,243,220,59,34,95,222,59,152,200,223,59,215,47,225,59,220,148,226,59,163,247,227,59,42,88,229,59,110,182,230,59,108,18,232,59,34,108,233,59,141,195,234,59,170,24,236,59,119,107,237,59,240,187,238,59,20,10,240,59,224,85,241,59,81,159,242,59,101,230,243,59,25,43,245,59,106,109,246,59,87,173,247,59,220,234,248,59,248,37,250,59,167,94,251,59,232,148,252,59,184,200,253,59,21,250,254,59,126,20,0,60,182,170,0,60,177,63,1,60,110,211,1,60,235,101,2,60,40,247,2,60,36,135,3,60,221,21,4,60,82,163,4,60,131,47,5,60,110,186,5,60,19,68,6,60,112,204,6,60,132,83,7,60,78,217,7,60,206,93,8,60,2,225,8,60,233,98,9,60,131,227,9,60,207,98,10,60,203,224,10,60,119,93,11,60,209,216,11,60,218,82,12,60,144,203,12,60,241,66,13,60,255,184,13,60,182,45,14,60,24,161,14,60,34,19,15,60,213,131,15,60,47,243,15,60,47,97,16,60,213,205,16,60,33,57,17,60,16,163,17,60,164,11,18,60,218,114,18,60,178,216,18,60,44,61,19,60,71,160,19,60,2,2,20,60,93,98,20,60,86,193,20,60,238,30,21,60,36,123,21,60,247,213,21,60,102,47,22,60,113,135,22,60,24,222,22,60,90,51,23,60], "i8", ALLOC_NONE, Runtime.GLOBAL_BASE+20480);
/* memory initializer */ allocate([54,135,23,60,171,217,23,60,187,42,24,60,98,122,24,60,163,200,24,60,123,21,25,60,235,96,25,60,241,170,25,60,143,243,25,60,194,58,26,60,139,128,26,60,234,196,26,60,222,7,27,60,102,73,27,60,130,137,27,60,51,200,27,60,119,5,28,60,78,65,28,60,185,123,28,60,182,180,28,60,70,236,28,60,104,34,29,60,28,87,29,60,97,138,29,60,56,188,29,60,161,236,29,60,154,27,30,60,36,73,30,60,64,117,30,60,235,159,30,60,39,201,30,60,244,240,30,60,81,23,31,60,61,60,31,60,186,95,31,60,199,129,31,60,100,162,31,60,144,193,31,60,76,223,31,60,152,251,31,60,116,22,32,60,223,47,32,60,218,71,32,60,101,94,32,60,127,115,32,60,42,135,32,60,100,153,32,60,46,170,32,60,137,185,32,60,115,199,32,60,238,211,32,60,249,222,32,60,149,232,32,60,193,240,32,60,126,247,32,60,204,252,32,60,172,0,33,60,29,3,33,60,31,4,33,60,179,3,33,60,218,1,33,60,146,254,32,60,222,249,32,60,188,243,32,60,45,236,32,60,50,227,32,60,202,216,32,60,247,204,32,60,184,191,32,60,14,177,32,60,249,160,32,60,122,143,32,60,144,124,32,60,61,104,32,60,129,82,32,60,91,59,32,60,206,34,32,60,216,8,32,60,123,237,31,60,183,208,31,60,140,178,31,60,251,146,31,60,5,114,31,60,170,79,31,60,234,43,31,60,199,6,31,60,64,224,30,60,86,184,30,60,10,143,30,60,92,100,30,60,78,56,30,60,222,10,30,60,16,220,29,60,226,171,29,60,85,122,29,60,107,71,29,60,36,19,29,60,128,221,28,60,129,166,28,60,38,110,28,60,113,52,28,60,99,249,27,60,252,188,27,60,60,127,27,60,38,64,27,60,185,255,26,60,246,189,26,60,222,122,26,60,115,54,26,60,179,240,25,60,162,169,25,60,63,97,25,60,139,23,25,60,135,204,24,60,53,128,24,60,148,50,24,60,166,227,23,60,108,147,23,60,230,65,23,60,22,239,22,60,253,154,22,60,155,69,22,60,242,238,21,60,2,151,21,60,204,61,21,60,82,227,20,60,149,135,20,60,149,42,20,60,83,204,19,60,209,108,19,60,16,12,19,60,16,170,18,60,211,70,18,60,90,226,17,60,166,124,17,60,184,21,17,60,145,173,16,60,51,68,16,60,157,217,15,60,211,109,15,60,212,0,15,60,162,146,14,60,62,35,14,60,169,178,13,60,229,64,13,60,243,205,12,60,211,89,12,60,136,228,11,60,17,110,11,60,114,246,10,60,170,125,10,60,187,3,10,60,167,136,9,60,110,12,9,60,19,143,8,60,149,16,8,60,247,144,7,60,58,16,7,60,96,142,6,60,104,11,6,60,86,135,5,60,42,2,5,60,229,123,4,60,138,244,3,60,25,108,3,60,147,226,2,60,251,87,2,60,82,204,1,60,152,63,1,60,208,177,0,60,250,34,0,60,51,38,255,59,92,4,254,59,115,224,252,59,124,186,251,59,122,146,250,59,110,104,249,59,93,60,248,59,73,14,247,59,53,222,245,59,37,172,244,59,27,120,243,59,26,66,242,59,37,10,241,59,65,208,239,59,111,148,238,59,178,86,237,59,15,23,236,59,136,213,234,59,32,146,233,59,218,76,232,59,186,5,231,59,195,188,229,59,248,113,228,59,92,37,227,59,243,214,225,59,192,134,224,59,197,52,223,59,8,225,221,59,138,139,220,59,79,52,219,59,90,219,217,59,175,128,216,59,81,36,215,59,68,198,213,59,138,102,212,59,40,5,211,59,33,162,209,59,119,61,208,59,47,215,206,59,77,111,205,59,210,5,204,59,196,154,202,59,37,46,201,59,248,191,199,59,67,80,198,59,7,223,196,59,73,108,195,59,11,248,193,59,83,130,192,59,34,11,191,59,125,146,189,59,104,24,188,59,229,156,186,59,249,31,185,59,167,161,183,59,243,33,182,59,224,160,180,59,114,30,179,59,173,154,177,59,148,21,176,59,43,143,174,59,118,7,173,59,121,126,171,59,54,244,169,59,179,104,168,59,242,219,166,59,247,77,165,59,198,190,163,59,99,46,162,59,210,156,160,59,22,10,159,59,51,118,157,59,44,225,155,59,7,75,154,59,198,179,152,59,109,27,151,59,0,130,149,59,131,231,147,59,250,75,146,59,104,175,144,59,209,17,143,59,58,115,141,59,166,211,139,59,24,51,138,59,150,145,136,59,34,239,134,59,192,75,133,59,117,167,131,59,68,2,130,59,50,92,128,59,130,106,125,59,237,26,122,59,171,201,118,59,197,118,115,59,66,34,112,59,43,204,108,59,134,116,105,59,93,27,102,59,182,192,98,59,154,100,95,59,17,7,92,59,34,168,88,59,213,71,85,59,51,230,81,59,66,131,78,59,12,31,75,59,152,185,71,59,238,82,68,59,22,235,64,59,23,130,61,59,250,23,58,59,199,172,54,59,134,64,51,59,62,211,47,59,248,100,44,59,188,245,40,59,145,133,37,59,127,20,34,59,143,162,30,59,201,47,27,59,52,188,23,59,216,71,20,59,190,210,16,59,237,92,13,59,110,230,9,59,72,111,6,59,132,247,2,59,81,254,254,58,126,12,248,58,157,25,241,58,191,37,234,58,244,48,227,58,75,59,220,58,213,68,213,58,162,77,206,58,193,85,199,58,67,93,192,58,55,100,185,58,174,106,178,58,183,112,171,58,99,118,164,58,192,123,157,58,224,128,150,58,211,133,143,58,167,138,136,58,109,143,129,58,106,40,117,58,30,50,103,58,20,60,89,58,110,70,75,58,74,81,61,58,200,92,47,58,8,105,33,58,42,118,19,58,77,132,5,58,33,39,239,57,40,72,211,57,239,107,183,57,179,146,155,57,107,121,127,57,103,212,71,57,215,54,16,57,118,66,177,56,55,80,4,56,148,129,179,183,19,214,155,184,250,91,5,185,137,194,60,185,56,30,116,185,70,183,149,185,133,89,177,185,155,245,204,185,73,139,232,185,41,13,2,186,61,209,15,186,192,145,29,186,149,78,43,186,157,7,57,186,185,188,70,186,202,109,84,186,178,26,98,186,84,195,111,186,143,103,125,186,163,131,133,186,46,81,140,186,88,28,147,186,20,229,153,186,81,171,160,186,0,111,167,186,20,48,174,186,124,238,180,186,42,170,187,186,15,99,194,186,28,25,201,186,67,204,207,186,117,124,214,186,162,41,221,186,189,211,227,186,182,122,234,186,127,30,241,186,10,191,247,186,72,92,254,186,21,123,2,187,81,198,5,187,208,15,9,187,141,87,12,187,126,157,15,187,158,225,18,187,229,35,22,187,76,100,25,187,204,162,28,187,94,223,31,187,250,25,35,187,155,82,38,187,56,137,41,187,203,189,44,187,77,240,47,187,183,32,51,187,2,79,54,187,39,123,57,187,31,165,60,187,228,204,63,187,110,242,66,187,183,21,70,187,184,54,73,187,107,85,76,187,200,113,79,187,202,139,82,187,104,163,85,187,157,184,88,187,98,203,91,187,177,219,94,187,130,233,97,187,208,244,100,187,148,253,103,187,199,3,107,187,99,7,110,187,97,8,113,187,188,6,116,187,108,2,119,187,108,251,121,187,180,241,124,187,64,229,127,187,4,107,129,187,3,226,130,187,154,87,132,187,198,203,133,187,132,62,135,187,209,175,136,187,169,31,138,187,11,142,139,187,242,250,140,187,91,102,142,187,69,208,143,187,171,56,145,187,140,159,146,187,227,4,148,187,174,104,149,187,234,202,150,187,148,43,152,187,170,138,153,187,40,232,154,187,12,68,156,187,83,158,157,187,249,246,158,187,253,77,160,187,91,163,161,187,17,247,162,187,28,73,164,187,121,153,165,187,37,232,166,187,31,53,168,187,98,128,169,187,237,201,170,187,189,17,172,187,206,87,173,187,32,156,174,187,174,222,175,187,119,31,177,187,120,94,178,187,174,155,179,187,23,215,180,187,176,16,182,187,119,72,183,187,105,126,184,187,133,178,185,187,198,228,186,187,44,21,188,187,180,67,189,187,90,112,190,187,30,155,191,187,252,195,192,187,243,234,193,187,255,15,195,187,31,51,196,187,81,84,197,187,145,115,198,187,223,144,199,187,55,172,200,187,152,197,201,187,255,220,202,187,107,242,203,187,216,5,205,187,70,23,206,187,177,38,207,187,24,52,208,187,121,63,209,187,209,72,210,187,31,80,211,187,97,85,212,187,149,88,213,187,184,89,214,187,201,88,215,187,197,85,216,187,172,80,217,187,123,73,218,187,48,64,219,187,202,52,220,187,70,39,221,187,162,23,222,187,222,5,223,187,247,241,223,187,235,219,224,187,185,195,225,187,96,169,226,187,220,140,227,187,45,110,228,187,81,77,229,187,71,42,230,187,13,5,231,187,160,221,231,187,1,180,232,187,45,136,233,187,34,90,234,187,223,41,235,187,99,247,235,187,173,194,236,187,186,139,237,187,137,82,238,187,26,23,239,187,106,217,239,187,120,153,240,187,67,87,241,187,202,18,242,187,11,204,242,187,6,131,243,187,184,55,244,187,32,234,244,187,62,154,245,187,17,72,246,187,150,243,246,187,205,156,247,187,181,67,248,187,77,232,248,187,147,138,249,187,135,42,250,187,39,200,250,187,115,99,251,187,105,252,251,187,9,147,252,187,82,39,253,187,66,185,253,187,217,72,254,187,22,214,254,187,248,96,255,187,126,233,255,187,212,55,0,188,186,121,0,188,113,186,0,188,248,249,0,188,80,56,1,188,119,117,1,188,110,177,1,188,52,236,1,188,200,37,2,188,44,94,2,188,93,149,2,188,92,203,2,188,41,0,3,188,195,51,3,188,43,102,3,188,95,151,3,188,96,199,3,188,45,246,3,188,198,35,4,188,43,80,4,188,92,123,4,188,89,165,4,188,33,206,4,188,180,245,4,188,18,28,5,188,60,65,5,188,48,101,5,188,239,135,5,188,120,169,5,188,204,201,5,188,235,232,5,188,211,6,6,188,134,35,6,188,4,63,6,188,75,89,6,188,93,114,6,188,56,138,6,188,222,160,6,188,78,182,6,188,136,202,6,188,141,221,6,188,91,239,6,188,244,255,6,188,87,15,7,188,133,29,7,188,125,42,7,188,63,54,7,188,204,64,7,188,36,74,7,188,71,82,7,188,52,89,7,188,237,94,7,188,113,99,7,188,193,102,7,188,220,104,7,188,195,105,7,188,118,105,7,188,245,103,7,188,64,101,7,188,89,97,7,188,62,92,7,188,240,85,7,188,111,78,7,188,188,69,7,188,216,59,7,188,193,48,7,188,121,36,7,188,255,22,7,188,85,8,7,188,122,248,6,188,111,231,6,188,53,213,6,188,202,193,6,188,49,173,6,188,105,151,6,188,115,128,6,188,78,104,6,188,252,78,6,188,125,52,6,188,210,24,6,188,250,251,5,188,246,221,5,188,199,190,5,188,110,158,5,188,233,124,5,188,59,90,5,188,100,54,5,188,100,17,5,188,60,235,4,188,235,195,4,188,116,155,4,188,214,113,4,188,17,71,4,188,40,27,4,188,25,238,3,188,230,191,3,188,143,144,3,188,20,96,3,188,120,46,3,188,185,251,2,188,217,199,2,188,217,146,2,188,185,92,2,188,121,37,2,188,27,237,1,188,159,179,1,188,5,121,1,188,79,61,1,188,126,0,1,188,145,194,0,188,138,131,0,188,105,67,0,188,48,2,0,188,188,127,255,187,235,248,254,187,236,111,254,187,194,228,253,187,110,87,253,187,242,199,252,187,79,54,252,187,135,162,251,187,156,12,251,187,144,116,250,187,100,218,249,187,25,62,249,187,178,159,248,187,49,255,247,187,151,92,247,187,230,183,246,187,31,17,246,187,70,104,245,187,90,189,244,187,95,16,244,187,86,97,243,187,66,176,242,187,35,253,241,187,253,71,241,187,208,144,240,187,159,215,239,187,108,28,239,187,57,95,238,187,8,160,237,187,219,222,236,187,180,27,236,187,148,86,235,187,127,143,234,187,119,198,233,187,124,251,232,187,146,46,232,187,187,95,231,187,248,142,230,187,77,188,229,187,186,231,228,187,67,17,228,187,234,56,227,187,176,94,226,187,153,130,225,187,166,164,224,187,217,196,223,187,54,227,222,187,190,255,221,187,115,26,221,187,88,51,220,187,112,74,219,187,188,95,218,187,64,115,217,187,252,132,216,187,245,148,215,187,44,163,214,187,164,175,213,187,95,186,212,187,96,195,211,187,168,202,210,187,60,208,209,187,29,212,208,187,77,214,207,187,208,214,206,187,167,213,205,187,214,210,204,187,95,206,203,187,69,200,202,187,137,192,201,187,48,183,200,187,58,172,199,187,172,159,198,187,136,145,197,187,208,129,196,187,135,112,195,187,175,93,194,187,77,73,193,187,97,51,192,187,239,27,191,187,250,2,190,187,132,232,188,187,145,204,187,187,34,175,186,187,59,144,185,187,223,111,184,187,16,78,183,187,209,42,182,187,37,6,181,187,15,224,179,187,146,184,178,187,176,143,177,187,109,101,176,187,202,57,175,187,205,12,174,187,118,222,172,187,201,174,171,187,201,125,170,187,121,75,169,187,220,23,168,187,244,226,166,187,198,172,165,187,83,117,164,187,158,60,163,187,172,2,162,187,126,199,160,187,23,139,159,187,124,77,158,187,174,14,157,187,177,206,155,187,136,141,154,187,54,75,153,187,189,7,152,187,34,195,150,187,102,125,149,187,142,54,148,187,156,238,146,187,148,165,145,187,119,91,144,187,75,16,143,187,17,196,141,187,205,118,140,187,130,40,139,187,51,217,137,187,228,136,136,187,150,55,135,187,79,229,133,187,16,146,132,187,221,61,131,187,185,232,129,187,168,146,128,187,88,119,126,187,145,199,123,187,3,22,121,187,178,98,118,187,167,173,115,187,230,246,112,187,120,62,110,187,97,132,107,187,169,200,104,187,86,11,102,187,111,76,99,187,250,139,96,187,254,201,93,187,129,6,91,187,137,65,88,187,30,123,85,187,69,179,82,187,6,234,79,187,104,31,77,187,111,83,74,187,36,134,71,187,140,183,68,187,175,231,65,187,147,22,63,187,63,68,60,187,185,112,57,187,7,156,54,187,49,198,51,187,62,239,48,187,51,23,46,187,23,62,43,187,242,99,40,187,201,136,37,187,164,172,34,187,137,207,31,187,127,241,28,187,141,18,26,187,184,50,23,187,9,82,20,187,133,112,17,187,51,142,14,187,26,171,11,187,65,199,8,187,175,226,5,187,105,253,2,187,119,23,0,187,192,97,250,186,84,147,244,186,183,195,238,186,248,242,232,186,36,33,227,186,71,78,221,186,112,122,215,186,173,165,209,186,9,208,203,186,147,249,197,186,89,34,192,186,103,74,186,186,204,113,180,186,148,152,174,186,204,190,168,186,131,228,162,186,198,9,157,186,162,46,151,186,37,83,145,186,91,119,139,186,83,155,133,186,50,126,127,186,119,197,115,186,142,12,104,186,147,83,92,186,161,154,80,186,209,225,68,186,62,41,57,186,4,113,45,186,61,185,33,186,2,2,22,186,112,75,10,186,65,43,253,185,92,193,229,185,101,89,206,185,147,243,182,185,25,144,159,185,45,47,136,185,5,162,97,185,160,235,50,185,146,59,4,185,137,36,171,184,131,192,27,184,10,78,245,54,24,244,88,56,111,142,201,56,157,72,19,57,205,192,65,57,97,47,112,57,247,73,143,57,8,119,166,57,174,158,189,57,183,192,212,57,237,220,235,57,143,121,1,58,139,1,13,58,81,134,24,58,199,7,36,58,212,133,47,58,93,0,59,58,74,119,70,58,129,234,81,58,233,89,93,58,103,197,104,58,228,44,116,58,69,144,127,58,185,119,133,58,40,37,139,58,100,208,144,58,96,121,150,58,15,32,156,58,101,196,161,58,86,102,167,58,213,5,173,58,214,162,178,58,77,61,184,58,44,213,189,58,105,106,195,58,246,252,200,58,199,140,206,58,208,25,212,58,6,164,217,58,91,43,223,58,196,175,228,58,53,49,234,58,162,175,239,58,254,42,245,58,62,163,250,58,43,12,0,59,29,197,2,59,111,124,5,59,27,50,8,59,28,230,10,59,106,152,13,59,2,73,16,59,220,247,18,59,243,164,21,59,65,80,24,59,192,249,26,59,107,161,29,59,60,71,32,59,45,235,34,59,57,141,37,59,89,45,40,59,137,203,42,59,194,103,45,59,254,1,48,59,58,154,50,59,110,48,53,59,149,196,55,59,170,86,58,59,167,230,60,59,135,116,63,59,68,0,66,59,217,137,68,59,64,17,71,59,117,150,73,59,113,25,76,59,47,154,78,59,171,24,81,59,222,148,83,59,196,14,86,59,87,134,88,59,146,251,90,59,112,110,93,59,235,222,95,59,255,76,98,59,167,184,100,59,220,33,103,59,155,136,105,59,222,236,107,59,160,78,110,59,221,173,112,59,142,10,115,59,176,100,117,59,61,188,119,59,49,17,122,59,134,99,124,59,56,179,126,59,33,128,128,59,80,165,129,59,37,201,130,59,160,235,131,59,189,12,133,59,122,44,134,59,213,74,135,59,203,103,136,59,91,131,137,59,130,157,138,59,61,182,139,59,139,205,140,59,105,227,141,59,212,247,142,59,204,10,144,59,77,28,145,59,85,44,146,59,227,58,147,59,244,71,148,59,133,83,149,59,150,93,150,59,35,102,151,59,42,109,152,59,170,114,153,59,161,118,154,59,12,121,155,59,233,121,156,59,54,121,157,59,242,118,158,59,27,115,159,59,173,109,160,59,168,102,161,59,9,94,162,59,207,83,163,59,248,71,164,59,129,58,165,59,106,43,166,59,175,26,167,59,79,8,168,59,73,244,168,59,154,222,169,59,65,199,170,59,59,174,171,59,136,147,172,59,38,119,173,59,17,89,174,59,74,57,175,59,206,23,176,59,156,244,176,59,178,207,177,59,13,169,178,59,174,128,179,59,145,86,180,59,182,42,181,59,26,253,181,59,189,205,182,59,156,156,183,59,182,105,184,59,10,53,185,59,150,254,185,59,89,198,186,59,81,140,187,59,124,80,188,59,218,18,189,59,104,211,189,59,38,146,190,59,18,79,191,59,43,10,192,59,110,195,192,59,220,122,193,59,115,48,194,59,49,228,194,59,21,150,195,59,29,70,196,59,74,244,196,59,152,160,197,59,8,75,198,59,152,243,198,59,70,154,199,59,18,63,200,59,251,225,200,59,254,130,201,59,28,34,202,59,84,191,202,59,163,90,203,59,9,244,203,59,133,139,204,59,22,33,205,59,187,180,205,59,115,70,206,59,61,214,206,59,24,100,207,59,3,240,207,59,253,121,208,59,6,2,209,59,28,136,209,59,62,12,210,59,109,142,210,59,166,14,211,59,233,140,211,59,54,9,212,59,139,131,212,59,232,251,212,59,76,114,213,59,182,230,213,59,38,89,214,59,155,201,214,59,20,56,215,59,145,164,215,59,17,15,216,59,148,119,216,59,24,222,216,59,157,66,217,59,36,165,217,59,170,5,218,59,48,100,218,59,182,192,218,59,58,27,219,59,188,115,219,59,60,202,219,59,185,30,220,59,52,113,220,59,171,193,220,59,30,16,221,59,141,92,221,59,248,166,221,59,93,239,221,59,190,53,222,59,25,122,222,59,111,188,222,59,191,252,222,59,9,59,223,59,76,119,223,59,137,177,223,59,191,233,223,59,239,31,224,59,23,84,224,59,56,134,224,59,83,182,224,59,102,228,224,59,113,16,225,59,118,58,225,59,115,98,225,59,104,136,225,59,87,172,225,59,61,206,225,59,29,238,225,59,245,11,226,59,199,39,226,59,145,65,226,59,84,89,226,59,16,111,226,59,198,130,226,59,118,148,226,59,31,164,226,59,194,177,226,59,95,189,226,59,246,198,226,59,137,206,226,59,22,212,226,59,158,215,226,59,34,217,226,59,162,216,226,59,30,214,226,59,151,209,226,59,13,203,226,59,128,194,226,59,241,183,226,59,96,171,226,59,206,156,226,59,59,140,226,59,168,121,226,59,21,101,226,59,131,78,226,59,242,53,226,59,99,27,226,59,214,254,225,59,76,224,225,59,198,191,225,59,68,157,225,59,199,120,225,59,80,82,225,59,222,41,225,59,116,255,224,59,18,211,224,59,183,164,224,59,102,116,224,59,31,66,224,59,227,13,224,59,178,215,223,59,141,159,223,59,118,101,223,59,108,41,223,59,114,235,222,59,135,171,222,59,172,105,222,59,228,37,222,59,46,224,221,59,139,152,221,59,253,78,221,59,132,3,221,59,33,182,220,59,214,102,220,59,164,21,220,59,139,194,219,59,140,109,219,59,170,22,219,59,228,189,218,59,60,99,218,59,178,6,218,59,74,168,217,59,2,72,217,59,221,229,216,59,219,129,216,59,255,27,216,59,72,180,215,59,185,74,215,59,83,223,214,59,22,114,214,59,5,3,214,59,32,146,213,59,105,31,213,59,225,170,212,59,137,52,212,59,100,188,211,59,113,66,211,59,179,198,210,59,44,73,210,59,219,201,209,59,196,72,209,59,231,197,208,59,70,65,208,59,226,186,207,59,189,50,207,59,216,168,206,59,53,29,206,59,213,143,205,59,187,0,205,59,230,111,204,59,90,221,203,59,24,73,203,59,33,179,202,59,119,27,202,59,27,130,201,59,16,231,200,59,87,74,200,59,241,171,199,59,224,11,199,59,39,106,198,59,198,198,197,59,192,33,197,59,22,123,196,59,202,210,195,59,221,40,195,59,82,125,194,59,43,208,193,59,105,33,193,59,13,113,192,59,27,191,191,59,147,11,191,59,120,86,190,59,203,159,189,59,143,231,188,59,197,45,188,59,111,114,187,59,143,181,186,59,39,247,185,59,57,55,185,59,199,117,184,59,211,178,183,59,96,238,182,59,110,40,182,59,0,97,181,59,24,152,180,59,184,205,179,59,226,1,179,59,152,52,178,59,221,101,177,59,178,149,176,59,25,196,175,59,21,241,174,59,168,28,174,59,211,70,173,59,154,111,172,59,253,150,171,59,0,189,170,59,164,225,169,59,236,4,169,59,217,38,168,59,111,71,167,59,175,102,166,59,155,132,165,59,54,161,164,59,130,188,163,59,130,214,162,59,54,239,161,59,163,6,161,59,202,28,160,59,173,49,159,59,78,69,158,59,177,87,157,59,215,104,156,59,195,120,155,59,118,135,154,59,244,148,153,59,63,161,152,59,89,172,151,59,68,182,150,59,4,191,149,59,153,198,148,59,8,205,147,59,81,210,146,59,120,214,145,59,127,217,144,59,104,219,143,59,54,220,142,59,236,219,141,59,139,218,140,59,23,216,139,59,145,212,138,59,253,207,137,59,92,202,136,59,178,195,135,59,0,188,134,59,74,179,133,59,146,169,132,59,218,158,131,59,37,147,130,59,117,134,129,59,206,120,128,59,97,212,126,59,65,181,124,59,65,148,122,59,102,113,120,59,180,76,118,59,49,38,116,59,227,253,113,59,206,211,111,59,248,167,109,59,102,122,107,59,29,75,105,59,35,26,103,59,124,231,100,59,46,179,98,59,63,125,96,59,179,69,94,59,145,12,92,59,221,209,89,59,156,149,87,59,213,87,85,59,140,24,83,59,198,215,80,59,138,149,78,59,221,81,76,59,195,12,74,59,67,198,71,59,98,126,69,59,37,53,67,59,146,234,64,59,174,158,62,59,126,81,60,59,9,3,58,59,83,179,55,59,98,98,53,59,59,16,51,59,229,188,48,59,100,104,46,59,190,18,44,59,248,187,41,59,25,100,39,59,37,11,37,59,34,177,34,59,22,86,32,59,6,250,29,59,248,156,27,59,241,62,25,59,247,223,22,59,15,128,20,59,63,31,18,59,141,189,15,59,253,90,13,59,151,247,10,59,95,147,8,59,90,46,6,59,143,200,3,59,3,98,1,59,120,245,253,58,126,37,249,58,35,84,244,58,116,129,239,58,122,173,234,58,66,216,229,58,214,1,225,58,67,42,220,58,146,81,215,58,207,119,210,58,5,157,205,58,65,193,200,58,140,228,195,58,242,6,191,58,126,40,186,58,61,73,181,58,56,105,176,58,123,136,171,58,17,167,166,58,6,197,161,58,100,226,156,58,55,255,151,58,139,27,147,58,106,55,142,58,224,82,137,58,247,109,132,58,119,17,127,58,112,70,117,58,240,122,107,58,13,175,97,58,222,226,87,58,120,22,78,58,243,73,68,58,101,125,58,58,227,176,48,58,133,228,38,58,96,24,29,58,139,76,19,58,28,129,9,58,82,108,255,57,145,215,235,57,34,68,216,57,48,178,196,57,232,33,177,57,118,147,157,57,7,7,138,57,139,249,108,57,190,233,69,57,255,222,30,57,73,179,239,56,15,180,161,56,254,129,39,56,12,89,59,54,204,251,15,184,93,200,149,184,238,131,227,184,245,151,24,185,210,101,63,185,55,43,102,185,231,115,134,185,159,205,153,185,153,34,173,185,170,114,192,185,167,189,211,185,100,3,231,185,183,67,250,185,58,191,6,186,185,89,16,186,67,241,25,186,193,133,35,186,32,23,45,186,74,165,54,186,41,48,64,186,169,183,73,186,180,59,83,186,53,188,92,186,23,57,102,186,70,178,111,186,172,39,121,186,154,76,129,186,102,3,134,186,45,184,138,186,231,106,143,186,136,27,148,186,7,202,152,186,89,118,157,186,116,32,162,186,78,200,166,186,220,109,171,186,20,17,176,186,237,177,180,186,91,80,185,186,85,236,189,186,210,133,194,186,198,28,199,186,39,177,203,186,237,66,208,186,13,210,212,186,124,94,217,186,50,232,221,186,35,111,226,186,71,243,230,186,148,116,235,186,0,243,239,186,129,110,244,186,13,231,248,186,155,92,253,186,144,231,0,187,75,31,3,187,119,85,5,187,18,138,7,187,21,189,9,187,125,238,11,187,68,30,14,187,102,76,16,187,222,120,18,187,168,163,20,187,191,204,22,187,30,244,24,187,192,25,27,187,162,61,29,187,190,95,31,187,16,128,33,187,147,158,35,187,67,187,37,187,28,214,39,187,25,239,41,187,53,6,44,187,108,27,46,187,186,46,48,187,26,64,50,187,136,79,52,187,0,93,54,187,125,104,56,187,251,113,58,187,117,121,60,187,232,126,62,187,78,130,64,187,165,131,66,187,231,130,68,187,17,128,70,187,30,123,72,187,11,116,74,187,210,106,76,187,113,95,78,187,226,81,80,187,34,66,82,187,45,48,84,187,255,27,86,187,148,5,88,187,231,236,89,187,246,209,91,187,187,180,93,187,52,149,95,187,91,115,97,187,47,79,99,187,169,40,101,187,200,255,102,187,134,212,104,187,225,166,106,187,213,118,108,187,93,68,110,187,118,15,112,187,29,216,113,187,78,158,115,187,4,98,117,187,62,35,119,187,247,225,120,187,43,158,122,187,215,87,124,187,248,14,126,187,138,195,127,187,197,186,128,187,122,146,129,187,226,104,130,187,252,61,131,187,198,17,132,187,63,228,132,187,100,181,133,187,53,133,134,187,175,83,135,187,209,32,136,187,154,236,136,187,7,183,137,187,24,128,138,187,202,71,139,187,28,14,140,187,13,211,140,187,156,150,141,187,197,88,142,187,137,25,143,187,230,216,143,187,218,150,144,187,99,83,145,187,129,14,146,187,50,200,146,187,116,128,147,187,71,55,148,187,168,236,148,187,150,160,149,187,17,83,150,187,23,4,151,187,165,179,151,187,188,97,152,187,90,14,153,187,125,185,153,187,37,99,154,187,80,11,155,187,252,177,155,187,41,87,156,187,213,250,156,187,0,157,157,187,167,61,158,187,203,220,158,187,105,122,159,187,128,22,160,187,16,177,160,187,24,74,161,187,149,225,161,187,136,119,162,187,239,11,163,187,200,158,163,187,20,48,164,187,209,191,164,187,254,77,165,187,154,218,165,187,164,101,166,187,27,239,166,187,254,118,167,187,76,253,167,187,5,130,168,187,39,5,169,187,177,134,169,187,163,6,170,187,252,132,170,187,186,1,171,187,222,124,171,187,102,246,171,187,82,110,172,187,160,228,172,187,80,89,173,187,97,204,173,187,211,61,174,187,164,173,174,187,213,27,175,187,99,136,175,187,80,243,175,187,153,92,176,187,63,196,176,187,64,42,177,187,156,142,177,187,83,241,177,187,100,82,178,187,206,177,178,187,145,15,179,187,172,107,179,187,30,198,179,187,232,30,180,187,8,118,180,187,127,203,180,187,75,31,181,187,109,113,181,187,227,193,181,187,174,16,182,187,205,93,182,187,64,169,182,187,5,243,182,187,30,59,183,187,137,129,183,187,70,198,183,187,85,9,184,187,182,74,184,187,104,138,184,187,107,200,184,187,191,4,185,187,99,63,185,187,88,120,185,187,157,175,185,187,49,229,185,187,22,25,186,187,74,75,186,187,205,123,186,187,160,170,186,187,193,215,186,187,50,3,187,187,242,44,187,187,1,85,187,187,94,123,187,187,11,160,187,187,6,195,187,187,79,228,187,187,232,3,188,187,207,33,188,187,6,62,188,187,139,88,188,187,95,113,188,187,130,136,188,187,244,157,188,187,181,177,188,187,197,195,188,187,37,212,188,187,213,226,188,187,213,239,188,187,36,251,188,187,195,4,189,187,179,12,189,187,244,18,189,187,133,23,189,187,104,26,189,187,155,27,189,187,33,27,189,187,248,24,189,187,34,21,189,187,158,15,189,187,109,8,189,187,144,255,188,187,6,245,188,187,209,232,188,187,239,218,188,187,99,203,188,187,44,186,188,187,75,167,188,187,192,146,188,187,140,124,188,187,175,100,188,187,41,75,188,187,252,47,188,187,40,19,188,187,173,244,187,187,140,212,187,187,197,178,187,187,89,143,187,187,73,106,187,187,150,67,187,187,63,27,187,187,70,241,186,187,171,197,186,187,111,152,186,187,146,105,186,187,22,57,186,187,251,6,186,187,65,211,185,187,234,157,185,187,246,102,185,187,103,46,185,187,60,244,184,187,119,184,184,187,24,123,184,187,32,60,184,187,145,251,183,187,106,185,183,187,173,117,183,187,91,48,183,187,117,233,182,187,251,160,182,187,239,86,182,187,81,11,182,187,34,190,181,187,100,111,181,187,23,31,181,187,60,205,180,187,212,121,180,187,225,36,180,187,99,206,179,187,91,118,179,187,203,28,179,187,179,193,178,187,21,101,178,187,241,6,178,187,74,167,177,187,31,70,177,187,114,227,176,187,68,127,176,187,151,25,176,187,107,178,175,187,193,73,175,187,156,223,174,187,252,115,174,187,226,6,174,187,79,152,173,187,69,40,173,187,198,182,172,187,209,67,172,187,105,207,171,187,143,89,171,187,69,226,170,187,138,105,170,187,98,239,169,187,205,115,169,187,204,246,168,187,97,120,168,187,141,248,167,187,82,119,167,187,177,244,166,187,171,112,166,187,67,235,165,187,120,100,165,187,78,220,164,187,196,82,164,187,221,199,163,187,155,59,163,187,254,173,162,187,8,31,162,187,186,142,161,187,23,253,160,187,32,106,160,187,214,213,159,187,58,64,159,187,80,169,158,187,22,17,158,187,145,119,157,187,193,220,156,187,167,64,156,187,70,163,155,187,158,4,155,187,178,100,154,187,132,195,153,187,20,33,153,187,101,125,152,187,121,216,151,187,80,50,151,187,237,138,150,187,81,226,149,187,126,56,149,187,118,141,148,187,59,225,147,187,206,51,147,187,49,133,146,187,101,213,145,187,110,36,145,187,75,114,144,187,0,191,143,187,142,10,143,187,246,84,142,187,59,158,141,187,95,230,140,187,99,45,140,187,72,115,139,187,18,184,138,187,194,251,137,187,89,62,137,187,218,127,136,187,70,192,135,187,160,255,134,187,232,61,134,187,34,123,133,187,79,183,132,187,113,242,131,187,138,44,131,187,156,101,130,187,169,157,129,187,179,212,128,187,187,10,128,187,136,127,126,187,160,231,124,187,193,77,123,187,239,177,121,187,47,20,120,187,132,116,118,187,243,210,116,187,128,47,115,187,46,138,113,187,1,227,111,187,255,57,110,187,42,143,108,187,136,226,106,187,28,52,105,187,234,131,103,187,246,209,101,187,69,30,100,187,219,104,98,187,188,177,96,187,237,248,94,187,112,62,93,187,76,130,91,187,131,196,89,187,27,5,88,187,23,68,86,187,124,129,84,187,77,189,82,187,145,247,80,187,74,48,79,187,125,103,77,187,46,157,75,187,98,209,73,187,29,4,72,187,99,53,70,187,57,101,68,187,163,147,66,187,166,192,64,187,70,236,62,187,135,22,61,187,110,63,59,187,255,102,57,187,63,141,55,187,50,178,53,187,220,213,51,187,67,248,49,187,106,25,48,187,86,57,46,187,12,88,44,187,143,117,42,187,229,145,40,187,19,173,38,187,27,199,36,187,4,224,34,187,209,247,32,187,135,14,31,187,44,36,29,187,194,56,27,187,79,76,25,187,215,94,23,187,96,112,21,187,237,128,19,187,131,144,17,187,38,159,15,187,220,172,13,187,169,185,11,187,145,197,9,187,154,208,7,187,199,218,5,187,29,228,3,187,161,236,1,187,176,232,255,186,141,246,251,186,224,2,248,186,181,13,244,186,20,23,240,186,6,31,236,186,149,37,232,186,203,42,228,186,175,46,224,186,77,49,220,186,172,50,216,186,215,50,212,186,214,49,208,186,179,47,204,186,120,44,200,186,45,40,196,186,220,34,192,186,143,28,188,186,78,21,184,186,36,13,180,186,24,4,176,186,54,250,171,186,133,239,167,186,16,228,163,186,223,215,159,186,253,202,155,186,114,189,151,186,72,175,147,186,136,160,143,186,60,145,139,186,108,129,135,186,35,113,131,186,210,192,126,186,144,158,118,186,146,123,110,186,235,87,102,186,174,51,94,186,238,14,86,186,188,233,77,186,44,196,69,186,80,158,61,186,60,120,53,186,0,82,45,186,177,43,37,186,96,5,29,186,32,223,20,186,4,185,12,186,31,147,4,186,3,219,248,185,128,144,232,185,216,70,216,185,49,254,199,185,175,182,183,185,119,112,167,185,174,43,151,185,121,232,134,185,250,77,109,185,189,206,76,185,131,83,44,185,150,220,11,185,129,212,214,184,147,249,149,184,238,81,42,184,235,25,35,183,32,92,177,55,143,10,90,56,241,166,173,56,114,59,238,56,29,97,23,57,93,157,55,57,48,210,87,57,79,255,119,57,57,18,140,57,168,32,156,57,209,42,172,57,144,48,188,57,194,49,204,57,66,46,220,57,238,37,236,57,162,24,252,57,29,3,6,58,74,247,13,58,198,232,21,58,127,215,29,58,99,195,37,58,98,172,45,58,106,146,53,58,104,117,61,58,77,85,69,58,6,50,77,58,130,11,85,58,176,225,92,58,127,180,100,58,220,131,108,58,184,79,116,58,1,24,124,58,83,238,129,58,203,206,133,58,96,173,137,58,10,138,141,58,191,100,145,58,120,61,149,58,44,20,153,58,212,232,156,58,101,187,160,58,217,139,164,58,39,90,168,58,70,38,172,58,46,240,175,58,216,183,179,58,58,125,183,58,77,64,187,58,9,1,191,58,101,191,194,58,89,123,198,58,222,52,202,58,235,235,205,58,120,160,209,58,125,82,213,58,242,1,217,58,208,174,220,58,14,89,224,58,164,0,228,58,140,165,231,58,188,71,235,58,45,231,238,58,215,131,242,58,179,29,246,58,185,180,249,58,226,72,253,58,18,109,0,59,61,52,2,59,238,249,3,59,32,190,5,59,209,128,7,59,252,65,9,59,158,1,11,59,179,191,12,59,55,124,14,59,39,55,16,59,127,240,17,59,59,168,19,59,88,94,21,59,210,18,23,59,166,197,24,59,207,118,26,59,75,38,28,59,22,212,29,59,44,128,31,59,138,42,33,59,45,211,34,59,16,122,36,59,49,31,38,59,140,194,39,59,30,100,41,59,227,3,43,59,215,161,44,59,248,61,46,59,67,216,47,59,179,112,49,59,69,7,51,59,247,155,52,59,197,46,54,59,172,191,55,59,168,78,57,59,183,219,58,59,213,102,60,59,255,239,61,59,49,119,63,59,105,252,64,59,164,127,66,59,223,0,68,59,22,128,69,59,70,253,70,59,109,120,72,59,135,241,73,59,145,104,75,59,137,221,76,59,108,80,78,59,54,193,79,59,228,47,81,59,117,156,82,59,228,6,84,59,47,111,85,59,84,213,86,59,79,57,88,59,30,155,89,59,190,250,90,59,44,88,92,59,101,179,93,59,103,12,95,59,48,99,96,59,188,183,97,59,8,10,99,59,19,90,100,59,218,167,101,59,90,243,102,59,144,60,104,59,122,131,105,59,22,200,106,59,97,10,108,59,89,74,109,59,251,135,110,59,68,195,111,59,51,252,112,59,198,50,114,59,249,102,115,59,202,152,116,59,55,200,117,59,63,245,118,59,222,31,120,59,18,72,121,59,218,109,122,59,50,145,123,59,26,178,124,59,142,208,125,59,141,236,126,59,10,3,128,59,145,142,128,59,218,24,129,59,229,161,129,59,176,41,130,59,57,176,130,59,130,53,131,59,135,185,131,59,73,60,132,59,199,189,132,59,254,61,133,59,240,188,133,59,154,58,134,59,252,182,134,59,21,50,135,59,228,171,135,59,105,36,136,59,161,155,136,59,142,17,137,59,45,134,137,59,126,249,137,59,128,107,138,59,51,220,138,59,149,75,139,59,166,185,139,59,101,38,140,59,210,145,140,59,235,251,140,59,177,100,141,59,33,204,141,59,61,50,142,59,2,151,142,59,112,250,142,59,135,92,143,59,71,189,143,59,173,28,144,59,187,122,144,59,110,215,144,59,200,50,145,59,198,140,145,59,105,229,145,59,176,60,146,59,154,146,146,59,39,231,146,59,87,58,147,59,40,140,147,59,155,220,147,59,175,43,148,59,99,121,148,59,184,197,148,59,172,16,149,59,64,90,149,59,114,162,149,59,67,233,149,59,177,46,150,59,190,114,150,59,104,181,150,59,174,246,150,59,146,54,151,59,18,117,151,59,46,178,151,59,229,237,151,59,56,40,152,59,39,97,152,59,176,152,152,59,212,206,152,59,146,3,153,59,235,54,153,59,222,104,153,59,107,153,153,59,145,200,153,59,81,246,153,59,171,34,154,59,157,77,154,59,41,119,154,59,78,159,154,59,11,198,154,59,98,235,154,59,81,15,155,59,217,49,155,59,249,82,155,59,179,114,155,59,4,145,155,59,238,173,155,59,113,201,155,59,140,227,155,59,64,252,155,59,141,19,156,59,114,41,156,59,240,61,156,59,6,81,156,59,182,98,156,59,254,114,156,59,224,129,156,59,91,143,156,59,111,155,156,59,29,166,156,59,100,175,156,59,69,183,156,59,192,189,156,59,214,194,156,59,133,198,156,59,208,200,156,59,181,201,156,59,53,201,156,59,81,199,156,59,9,196,156,59,92,191,156,59,75,185,156,59,216,177,156,59,1,169,156,59,199,158,156,59,42,147,156,59,44,134,156,59,204,119,156,59,10,104,156,59,232,86,156,59,101,68,156,59,129,48,156,59,62,27,156,59,156,4,156,59,155,236,155,59,59,211,155,59,126,184,155,59,99,156,155,59,236,126,155,59,23,96,155,59,231,63,155,59,92,30,155,59,118,251,154,59,54,215,154,59,156,177,154,59,168,138,154,59,93,98,154,59,185,56,154,59,191,13,154,59,109,225,153,59,198,179,153,59,201,132,153,59,119,84,153,59,210,34,153,59,217,239,152,59,141,187,152,59,240,133,152,59,1,79,152,59,194,22,152,59,51,221,151,59,85,162,151,59,41,102,151,59,176,40,151,59,233,233,150,59,215,169,150,59,122,104,150,59,210,37,150,59,226,225,149,59,168,156,149,59,39,86,149,59,95,14,149,59,81,197,148,59,253,122,148,59,102,47,148,59,139,226,147,59,110,148,147,59,15,69,147,59,112,244,146,59,145,162,146,59,116,79,146,59,25,251,145,59,129,165,145,59,173,78,145,59,159,246,144,59,87,157,144,59,215,66,144,59,30,231,143,59,47,138,143,59,11,44,143,59,178,204,142,59,38,108,142,59,103,10,142,59,119,167,141,59,87,67,141,59,7,222,140,59,138,119,140,59,224,15,140,59,10,167,139,59,10,61,139,59,225,209,138,59,143,101,138,59,22,248,137,59,119,137,137,59,180,25,137,59,205,168,136,59,196,54,136,59,154,195,135,59,80,79,135,59,232,217,134,59,98,99,134,59,192,235,133,59,4,115,133,59,46,249,132,59,64,126,132,59,59,2,132,59,32,133,131,59,241,6,131,59,175,135,130,59,92,7,130,59,248,133,129,59,133,3,129,59,5,128,128,59,240,246,127,59,193,235,126,59,127,222,125,59,45,207,124,59,205,189,123,59,99,170,122,59,241,148,121,59,122,125,120,59,1,100,119,59,137,72,118,59,21,43,117,59,167,11,116,59,67,234,114,59,235,198,113,59,162,161,112,59,108,122,111,59,75,81,110,59,67,38,109,59,86,249,107,59,135,202,106,59,218,153,105,59,81,103,104,59,240,50,103,59,186,252,101,59,177,196,100,59,217,138,99,59,53,79,98,59,200,17,97,59,149,210,95,59,160,145,94,59,235,78,93,59,122,10,92,59,80,196,90,59,113,124,89,59,223,50,88,59,157,231,86,59,176,154,85,59,25,76,84,59,221,251,82,59,255,169,81,59,131,86,80,59,106,1,79,59,185,170,77,59,116,82,76,59,157,248,74,59,55,157,73,59,71,64,72,59,208,225,70,59,212,129,69,59,88,32,68,59,95,189,66,59,236,88,65,59,2,243,63,59,166,139,62,59,219,34,61,59,163,184,59,59,3,77,58,59,255,223,56,59,152,113,55,59,212,1,54,59,182,144,52,59,64,30,51,59,120,170,49,59,95,53,48,59,251,190,46,59,77,71,45,59,91,206,43,59,39,84,42,59,182,216,40,59,10,92,39,59,40,222,37,59,18,95,36,59,205,222,34,59,93,93,33,59,196,218,31,59,7,87,30,59,41,210,28,59,46,76,27,59,26,197,25,59,239,60,24,59,179,179,22,59,104,41,21,59,19,158,19,59,183,17,18,59,87,132,16,59,248,245,14,59,158,102,13,59,75,214,11,59,4,69,10,59,204,178,8,59,167,31,7,59,154,139,5,59,167,246,3,59,211,96,2,59,32,202,0,59,41,101,254,58,100,52,251,58,251,1,248,58,244,205,244,58,88,152,241,58,46,97,238,58,126,40,235,58,79,238,231,58,168,178,228,58,146,117,225,58,21,55,222,58,55,247,218,58,0,182,215,58,121,115,212,58,169,47,209,58,151,234,205,58,75,164,202,58,205,92,199,58,36,20,196,58,89,202,192,58,114,127,189,58,121,51,186,58,115,230,182,58,106,152,179,58,100,73,176,58,106,249,172,58,131,168,169,58,183,86,166,58,14,4,163,58,143,176,159,58,66,92,156,58,48,7,153,58,94,177,149,58,214,90,146,58,160,3,143,58,194,171,139,58,68,83,136,58,47,250,132,58,138,160,129,58,185,140,124,58,93,215,117,58,16,33,111,58,224,105,104,58,222,177,97,58,25,249,90,58,160,63,84,58,131,133,77,58,210,202,70,58,155,15,64,58,237,83,57,58,218,151,50,58,111,219,43,58,189,30,37,58,210,97,30,58,191,164,23,58,146,231,16,58,91,42,10,58,42,109,3,58,25,96,249,57,38,230,235,57,155,108,222,57,148,243,208,57,49,123,195,57,145,3,182,57,209,140,168,57,17,23,155,57,110,162,141,57,8,47,128,57,250,121,101,57,213,152,74,57,224,186,47,57,86,224,20,57,233,18,244,56,238,108,190,56,54,207,136,56,113,116,38,56,113,115,109,55,136,157,62,183,89,151,26,184,13,185,130,184,89,27,184,184,27,114,237,184,110,94,17,185,145,253,43,185,60,150,70,185,51,40,97,185,60,179,123,185,141,27,139,185,202,89,152,185,54,148,165,185,181,202,178,185,41,253,191,185,117,43,205,185,124,85,218,185,32,123,231,185,68,156,244,185,101,220,0,186,76,104,7,186,199,241,13,186,200,120,20,186,65,253,26,186,36,127,33,186,97,254,39,186,235,122,46,186,180,244,52,186,172,107,59,186,199,223,65,186,245,80,72,186,41,191,78,186,84,42,85,186,105,146,91,186,89,247,97,186,23,89,104,186,149,183,110,186,196,18,117,186,150,106,123,186,127,223,128,186,248,7,132,186,173,46,135,186,153,83,138,186,181,118,141,186,249,151,144,186,95,183,147,186,224,212,150,186,117,240,153,186,25,10,157,186,195,33,160,186,109,55,163,186,17,75,166,186,168,92,169,186,44,108,172,186,149,121,175,186,221,132,178,186,255,141,181,186,242,148,184,186,177,153,187,186,53,156,190,186,120,156,193,186,116,154,196,186,33,150,199,186,122,143,202,186,120,134,205,186,21,123,208,186,75,109,211,186,19,93,214,186,103,74,217,186,65,53,220,186,155,29,223,186,111,3,226,186,182,230,228,186,106,199,231,186,133,165,234,186,2,129,237,186,218,89,240,186,7,48,243,186,131,3,246,186,73,212,248,186,82,162,251,186,152,109,254,186,11,155,0,187,227,253,1,187,81,95,3,187,82,191,4,187,228,29,6,187,3,123,7,187], "i8", ALLOC_NONE, Runtime.GLOBAL_BASE+30720);
/* memory initializer */ allocate([172,214,8,187,221,48,10,187,147,137,11,187,203,224,12,187,131,54,14,187,183,138,15,187,101,221,16,187,138,46,18,187,36,126,19,187,47,204,20,187,170,24,22,187,144,99,23,187,225,172,24,187,152,244,25,187,180,58,27,187,50,127,28,187,15,194,29,187,73,3,31,187,221,66,32,187,201,128,33,187,10,189,34,187,157,247,35,187,128,48,37,187,177,103,38,187,46,157,39,187,243,208,40,187,254,2,42,187,77,51,43,187,222,97,44,187,174,142,45,187,187,185,46,187,2,227,47,187,129,10,49,187,55,48,50,187,32,84,51,187,58,118,52,187,131,150,53,187,249,180,54,187,154,209,55,187,99,236,56,187,83,5,58,187,103,28,59,187,157,49,60,187,242,68,61,187,102,86,62,187,245,101,63,187,157,115,64,187,94,127,65,187,51,137,66,187,29,145,67,187,24,151,68,187,34,155,69,187,59,157,70,187,95,157,71,187,140,155,72,187,194,151,73,187,254,145,74,187,61,138,75,187,128,128,76,187,194,116,77,187,3,103,78,187,66,87,79,187,123,69,80,187,174,49,81,187,217,27,82,187,249,3,83,187,14,234,83,187,21,206,84,187,14,176,85,187,246,143,86,187,203,109,87,187,141,73,88,187,57,35,89,187,207,250,89,187,76,208,90,187,175,163,91,187,246,116,92,187,33,68,93,187,45,17,94,187,26,220,94,187,229,164,95,187,142,107,96,187,18,48,97,187,114,242,97,187,170,178,98,187,187,112,99,187,162,44,100,187,95,230,100,187,241,157,101,187,85,83,102,187,139,6,103,187,146,183,103,187,104,102,104,187,12,19,105,187,126,189,105,187,187,101,106,187,196,11,107,187,151,175,107,187,50,81,108,187,149,240,108,187,192,141,109,187,176,40,110,187,101,193,110,187,222,87,111,187,26,236,111,187,24,126,112,187,215,13,113,187,87,155,113,187,151,38,114,187,149,175,114,187,81,54,115,187,203,186,115,187,0,61,116,187,242,188,116,187,158,58,117,187,5,182,117,187,38,47,118,187,255,165,118,187,145,26,119,187,218,140,119,187,219,252,119,187,146,106,120,187,0,214,120,187,34,63,121,187,250,165,121,187,134,10,122,187,199,108,122,187,187,204,122,187,98,42,123,187,188,133,123,187,200,222,123,187,134,53,124,187,246,137,124,187,24,220,124,187,234,43,125,187,110,121,125,187,162,196,125,187,134,13,126,187,26,84,126,187,94,152,126,187,82,218,126,187,246,25,127,187,73,87,127,187,75,146,127,187,253,202,127,187,175,0,128,187,183,26,128,187,150,51,128,187,77,75,128,187,220,97,128,187,66,119,128,187,128,139,128,187,150,158,128,187,131,176,128,187,72,193,128,187,228,208,128,187,89,223,128,187,166,236,128,187,202,248,128,187,199,3,129,187,156,13,129,187,74,22,129,187,208,29,129,187,47,36,129,187,103,41,129,187,120,45,129,187,98,48,129,187,38,50,129,187,195,50,129,187,58,50,129,187,139,48,129,187,182,45,129,187,188,41,129,187,157,36,129,187,89,30,129,187,240,22,129,187,99,14,129,187,178,4,129,187,220,249,128,187,228,237,128,187,200,224,128,187,137,210,128,187,40,195,128,187,165,178,128,187,0,161,128,187,57,142,128,187,82,122,128,187,74,101,128,187,33,79,128,187,217,55,128,187,113,31,128,187,235,5,128,187,139,214,127,187,5,159,127,187,67,101,127,187,71,41,127,187,17,235,126,187,163,170,126,187,254,103,126,187,34,35,126,187,17,220,125,187,203,146,125,187,83,71,125,187,168,249,124,187,204,169,124,187,192,87,124,187,133,3,124,187,29,173,123,187,136,84,123,187,200,249,122,187,222,156,122,187,203,61,122,187,144,220,121,187,47,121,121,187,170,19,121,187,0,172,120,187,52,66,120,187,72,214,119,187,59,104,119,187,16,248,118,187,201,133,118,187,102,17,118,187,233,154,117,187,83,34,117,187,167,167,116,187,228,42,116,187,14,172,115,187,37,43,115,187,43,168,114,187,34,35,114,187,10,156,113,187,231,18,113,187,184,135,112,187,129,250,111,187,66,107,111,187,253,217,110,187,180,70,110,187,105,177,109,187,29,26,109,187,210,128,108,187,138,229,107,187,71,72,107,187,10,169,106,187,212,7,106,187,169,100,105,187,138,191,104,187,120,24,104,187,117,111,103,187,132,196,102,187,166,23,102,187,221,104,101,187,43,184,100,187,146,5,100,187,19,81,99,187,178,154,98,187,112,226,97,187,78,40,97,187,79,108,96,187,117,174,95,187,194,238,94,187,56,45,94,187,217,105,93,187,167,164,92,187,165,221,91,187,211,20,91,187,54,74,90,187,205,125,89,187,157,175,88,187,167,223,87,187,236,13,87,187,112,58,86,187,53,101,85,187,60,142,84,187,137,181,83,187,28,219,82,187,250,254,81,187,35,33,81,187,154,65,80,187,98,96,79,187,124,125,78,187,236,152,77,187,179,178,76,187,212,202,75,187,81,225,74,187,45,246,73,187,105,9,73,187,10,27,72,187,16,43,71,187,126,57,70,187,87,70,69,187,157,81,68,187,83,91,67,187,123,99,66,187,24,106,65,187,44,111,64,187,186,114,63,187,196,116,62,187,76,117,61,187,86,116,60,187,228,113,59,187,249,109,58,187,151,104,57,187,192,97,56,187,120,89,55,187,193,79,54,187,157,68,53,187,15,56,52,187,27,42,51,187,194,26,50,187,7,10,49,187,237,247,47,187,119,228,46,187,167,207,45,187,129,185,44,187,6,162,43,187,58,137,42,187,31,111,41,187,184,83,40,187,8,55,39,187,17,25,38,187,215,249,36,187,92,217,35,187,163,183,34,187,175,148,33,187,131,112,32,187,33,75,31,187,140,36,30,187,200,252,28,187,214,211,27,187,187,169,26,187,120,126,25,187,17,82,24,187,137,36,23,187,226,245,21,187,31,198,20,187,68,149,19,187,82,99,18,187,78,48,17,187,58,252,15,187,25,199,14,187,238,144,13,187,188,89,12,187,134,33,11,187,79,232,9,187,25,174,8,187,233,114,7,187,192,54,6,187,162,249,4,187,146,187,3,187,146,124,2,187,167,60,1,187,164,247,255,186,47,116,253,186,244,238,250,186,248,103,248,186,67,223,245,186,218,84,243,186,195,200,240,186,4,59,238,186,164,171,235,186,169,26,233,186,25,136,230,186,250,243,227,186,81,94,225,186,39,199,222,186,128,46,220,186,98,148,217,186,213,248,214,186,222,91,212,186,131,189,209,186,203,29,207,186,188,124,204,186,92,218,201,186,178,54,199,186,195,145,196,186,150,235,193,186,49,68,191,186,155,155,188,186,218,241,185,186,243,70,183,186,239,154,180,186,209,237,177,186,162,63,175,186,103,144,172,186,39,224,169,186,232,46,167,186,176,124,164,186,133,201,161,186,111,21,159,186,115,96,156,186,151,170,153,186,226,243,150,186,91,60,148,186,8,132,145,186,238,202,142,186,21,17,140,186,130,86,137,186,61,155,134,186,75,223,131,186,179,34,129,186,246,202,124,186,83,79,119,186,138,210,113,186,168,84,108,186,185,213,102,186,202,85,97,186,231,212,91,186,30,83,86,186,123,208,80,186,11,77,75,186,219,200,69,186,247,67,64,186,108,190,58,186,71,56,53,186,148,177,47,186,96,42,42,186,185,162,36,186,169,26,31,186,63,146,25,186,135,9,20,186,142,128,14,186,96,247,8,186,10,110,3,186,48,201,251,185,47,182,240,185,41,163,229,185,57,144,218,185,119,125,207,185,252,106,196,185,225,88,185,185,65,71,174,185,51,54,163,185,209,37,152,185,52,22,141,185,117,7,130,185,92,243,109,185,237,217,87,185,208,194,65,185,56,174,43,185,86,156,21,185,183,26,255,184,246,2,211,184,203,241,166,184,51,207,117,184,137,201,29,184,190,166,139,183,51,148,144,54,196,206,211,55,110,170,65,56,116,173,140,56,4,124,184,56,135,64,228,56,77,253,7,57,238,212,29,57,245,166,51,57,50,115,73,57,116,57,95,57,139,249,116,57,163,89,133,57,59,51,144,57,117,9,155,57,57,220,165,57,110,171,176,57,254,118,187,57,207,62,198,57,203,2,209,57,217,194,219,57,225,126,230,57,205,54,241,57,132,234,251,57,247,76,3,58,122,162,8,58,192,245,13,58,188,70,19,58,99,149,24,58,170,225,29,58,132,43,35,58,230,114,40,58,197,183,45,58,21,250,50,58,202,57,56,58,218,118,61,58,56,177,66,58,217,232,71,58,178,29,77,58,184,79,82,58,222,126,87,58,27,171,92,58,98,212,97,58,169,250,102,58,229,29,108,58,9,62,113,58,12,91,118,58,226,116,123,58,192,69,128,58,109,207,130,58,116,87,133,58,206,221,135,58,118,98,138,58,103,229,140,58,156,102,143,58,14,230,145,58,185,99,148,58,152,223,150,58,164,89,153,58,218,209,155,58,50,72,158,58,169,188,160,58,57,47,163,58,220,159,165,58,142,14,168,58,73,123,170,58,9,230,172,58,199,78,175,58,128,181,177,58,46,26,180,58,203,124,182,58,83,221,184,58,193,59,187,58,16,152,189,58,58,242,191,58,60,74,194,58,15,160,196,58,175,243,198,58,24,69,201,58,67,148,203,58,46,225,205,58,210,43,208,58,42,116,210,58,51,186,212,58,232,253,214,58,66,63,217,58,63,126,219,58,218,186,221,58,12,245,223,58,211,44,226,58,42,98,228,58,11,149,230,58,115,197,232,58,92,243,234,58,195,30,237,58,163,71,239,58,247,109,241,58,187,145,243,58,235,178,245,58,130,209,247,58,125,237,249,58,214,6,252,58,137,29,254,58,201,24,0,59,119,33,1,59,204,40,2,59,197,46,3,59,97,51,4,59,158,54,5,59,121,56,6,59,241,56,7,59,4,56,8,59,175,53,9,59,240,49,10,59,199,44,11,59,48,38,12,59,41,30,13,59,178,20,14,59,199,9,15,59,103,253,15,59,144,239,16,59,65,224,17,59,119,207,18,59,48,189,19,59,107,169,20,59,37,148,21,59,94,125,22,59,19,101,23,59,67,75,24,59,235,47,25,59,10,19,26,59,159,244,26,59,167,212,27,59,33,179,28,59,11,144,29,59,100,107,30,59,41,69,31,59,90,29,32,59,245,243,32,59,247,200,33,59,96,156,34,59,45,110,35,59,94,62,36,59,241,12,37,59,228,217,37,59,53,165,38,59,228,110,39,59,239,54,40,59,84,253,40,59,17,194,41,59,38,133,42,59,145,70,43,59,81,6,44,59,100,196,44,59,201,128,45,59,126,59,46,59,130,244,46,59,212,171,47,59,115,97,48,59,92,21,49,59,144,199,49,59,13,120,50,59,209,38,51,59,219,211,51,59,42,127,52,59,189,40,53,59,147,208,53,59,170,118,54,59,2,27,55,59,152,189,55,59,109,94,56,59,127,253,56,59,205,154,57,59,86,54,58,59,25,208,58,59,21,104,59,59,72,254,59,59,179,146,60,59,83,37,61,59,40,182,61,59,49,69,62,59,109,210,62,59,220,93,63,59,123,231,63,59,75,111,64,59,74,245,64,59,121,121,65,59,212,251,65,59,93,124,66,59,19,251,66,59,243,119,67,59,255,242,67,59,52,108,68,59,147,227,68,59,26,89,69,59,201,204,69,59,160,62,70,59,157,174,70,59,191,28,71,59,7,137,71,59,116,243,71,59,5,92,72,59,185,194,72,59,145,39,73,59,138,138,73,59,166,235,73,59,227,74,74,59,65,168,74,59,192,3,75,59,94,93,75,59,28,181,75,59,249,10,76,59,245,94,76,59,16,177,76,59,72,1,77,59,158,79,77,59,17,156,77,59,162,230,77,59,79,47,78,59,24,118,78,59,254,186,78,59,0,254,78,59,29,63,79,59,86,126,79,59,170,187,79,59,26,247,79,59,164,48,80,59,73,104,80,59,9,158,80,59,227,209,80,59,216,3,81,59,231,51,81,59,17,98,81,59,85,142,81,59,179,184,81,59,44,225,81,59,190,7,82,59,108,44,82,59,51,79,82,59,21,112,82,59,17,143,82,59,40,172,82,59,90,199,82,59,167,224,82,59,14,248,82,59,145,13,83,59,47,33,83,59,232,50,83,59,190,66,83,59,175,80,83,59,188,92,83,59,230,102,83,59,45,111,83,59,144,117,83,59,17,122,83,59,176,124,83,59,108,125,83,59,71,124,83,59,65,121,83,59,90,116,83,59,146,109,83,59,235,100,83,59,100,90,83,59,253,77,83,59,184,63,83,59,149,47,83,59,149,29,83,59,183,9,83,59,252,243,82,59,102,220,82,59,244,194,82,59,167,167,82,59,128,138,82,59,128,107,82,59,166,74,82,59,244,39,82,59,107,3,82,59,10,221,81,59,211,180,81,59,199,138,81,59,230,94,81,59,48,49,81,59,168,1,81,59,77,208,80,59,32,157,80,59,34,104,80,59,84,49,80,59,183,248,79,59,75,190,79,59,18,130,79,59,12,68,79,59,59,4,79,59,159,194,78,59,56,127,78,59,9,58,78,59,18,243,77,59,84,170,77,59,208,95,77,59,135,19,77,59,122,197,76,59,170,117,76,59,25,36,76,59,198,208,75,59,180,123,75,59,227,36,75,59,85,204,74,59,10,114,74,59,4,22,74,59,68,184,73,59,204,88,73,59,155,247,72,59,180,148,72,59,24,48,72,59,200,201,71,59,197,97,71,59,16,248,70,59,171,140,70,59,152,31,70,59,214,176,69,59,104,64,69,59,79,206,68,59,141,90,68,59,34,229,67,59,16,110,67,59,89,245,66,59,253,122,66,59,255,254,65,59,95,129,65,59,31,2,65,59,65,129,64,59,198,254,63,59,176,122,63,59,255,244,62,59,182,109,62,59,214,228,61,59,97,90,61,59,87,206,60,59,187,64,60,59,143,177,59,59,211,32,59,59,138,142,58,59,180,250,57,59,85,101,57,59,108,206,56,59,253,53,56,59,8,156,55,59,143,0,55,59,148,99,54,59,25,197,53,59,31,37,53,59,168,131,52,59,181,224,51,59,74,60,51,59,102,150,50,59,13,239,49,59,63,70,49,59,255,155,48,59,78,240,47,59,47,67,47,59,162,148,46,59,171,228,45,59,74,51,45,59,130,128,44,59,84,204,43,59,194,22,43,59,207,95,42,59,124,167,41,59,202,237,40,59,189,50,40,59,86,118,39,59,150,184,38,59,128,249,37,59,22,57,37,59,89,119,36,59,77,180,35,59,242,239,34,59,74,42,34,59,89,99,33,59,31,155,32,59,159,209,31,59,218,6,31,59,212,58,30,59,142,109,29,59,9,159,28,59,73,207,27,59,79,254,26,59,29,44,26,59,181,88,25,59,26,132,24,59,78,174,23,59,82,215,22,59,42,255,21,59,214,37,21,59,90,75,20,59,184,111,19,59,241,146,18,59,7,181,17,59,254,213,16,59,215,245,15,59,148,20,15,59,56,50,14,59,197,78,13,59,62,106,12,59,163,132,11,59,248,157,10,59,63,182,9,59,123,205,8,59,172,227,7,59,215,248,6,59,252,12,6,59,31,32,5,59,66,50,4,59,102,67,3,59,143,83,2,59,190,98,1,59,247,112,0,59,117,252,254,58,23,21,253,58,218,43,251,58,194,64,249,58,211,83,247,58,18,101,245,58,132,116,243,58,46,130,241,58,21,142,239,58,61,152,237,58,171,160,235,58,101,167,233,58,110,172,231,58,204,175,229,58,131,177,227,58,153,177,225,58,18,176,223,58,244,172,221,58,66,168,219,58,3,162,217,58,59,154,215,58,238,144,213,58,35,134,211,58,221,121,209,58,34,108,207,58,246,92,205,58,96,76,203,58,99,58,201,58,5,39,199,58,74,18,197,58,56,252,194,58,212,228,192,58,35,204,190,58,41,178,188,58,237,150,186,58,114,122,184,58,190,92,182,58,215,61,180,58,192,29,178,58,128,252,175,58,26,218,173,58,150,182,171,58,246,145,169,58,66,108,167,58,125,69,165,58,172,29,163,58,214,244,160,58,255,202,158,58,44,160,156,58,98,116,154,58,167,71,152,58,255,25,150,58,112,235,147,58,255,187,145,58,178,139,143,58,140,90,141,58,148,40,139,58,206,245,136,58,64,194,134,58,239,141,132,58,224,88,130,58,25,35,128,58,59,217,123,58,232,106,119,58,66,251,114,58,85,138,110,58,42,24,106,58,204,164,101,58,69,48,97,58,160,186,92,58,230,67,88,58,36,204,83,58,97,83,79,58,170,217,74,58,9,95,70,58,135,227,65,58,48,103,61,58,14,234,56,58,43,108,52,58,145,237,47,58,75,110,43,58,100,238,38,58,229,109,34,58,217,236,29,58,75,107,25,58,68,233,20,58,208,102,16,58,249,227,11,58,200,96,7,58,73,221,2,58,10,179,252,57,15,171,243,57,181,162,234,57,15,154,225,57,52,145,216,57,56,136,207,57,47,127,198,57,46,118,189,57,73,109,180,57,150,100,171,57,40,92,162,57,20,84,153,57,112,76,144,57,78,69,135,57,136,125,124,57,205,113,106,57,148,103,88,57,4,95,70,57,71,88,52,57,134,83,34,57,234,80,16,57,53,161,252,56,129,165,216,56,10,175,180,56,32,190,144,56,41,166,89,56,111,220,17,56,106,63,148,55,210,38,28,53,123,96,138,183,53,194,12,184,171,68,84,184,127,219,141,184,72,140,177,184,97,52,213,184,122,211,248,184,161,52,14,185,183,250,31,185,213,187,49,185,212,119,67,185,142,46,85,185,219,223,102,185,147,139,120,185,199,24,133,185,212,232,141,185,220,181,150,185,203,127,159,185,143,70,168,185,19,10,177,185,69,202,185,185,17,135,194,185,100,64,203,185,42,246,211,185,81,168,220,185,198,86,229,185,117,1,238,185,75,168,246,185,54,75,255,185,17,245,3,186,126,66,8,186,218,141,12,186,25,215,16,186,52,30,21,186,33,99,25,186,214,165,29,186,75,230,33,186,118,36,38,186,77,96,42,186,200,153,46,186,221,208,50,186,132,5,55,186,179,55,59,186,97,103,63,186,133,148,67,186,22,191,71,186,11,231,75,186,92,12,80,186,254,46,84,186,234,78,88,186,22,108,92,186,122,134,96,186,12,158,100,186,197,178,104,186,155,196,108,186,133,211,112,186,123,223,116,186,117,232,120,186,105,238,124,186,168,120,128,186,143,120,130,186,232,118,132,186,172,115,134,186,217,110,136,186,106,104,138,186,90,96,140,186,166,86,142,186,73,75,144,186,63,62,146,186,133,47,148,186,22,31,150,186,237,12,152,186,8,249,153,186,97,227,155,186,245,203,157,186,192,178,159,186,190,151,161,186,235,122,163,186,67,92,165,186,193,59,167,186,99,25,169,186,37,245,170,186,1,207,172,186,246,166,174,186,253,124,176,186,21,81,178,186,57,35,180,186,102,243,181,186,151,193,183,186,201,141,185,186,248,87,187,186,33,32,189,186,64,230,190,186,81,170,192,186,80,108,194,186,59,44,196,186,13,234,197,186,195,165,199,186,89,95,201,186,205,22,203,186,25,204,204,186,60,127,206,186,49,48,208,186,245,222,209,186,133,139,211,186,221,53,213,186,250,221,214,186,216,131,216,186,117,39,218,186,205,200,219,186,221,103,221,186,161,4,223,186,22,159,224,186,58,55,226,186,8,205,227,186,126,96,229,186,153,241,230,186,85,128,232,186,176,12,234,186,166,150,235,186,53,30,237,186,89,163,238,186,15,38,240,186,85,166,241,186,39,36,243,186,131,159,244,186,102,24,246,186,204,142,247,186,180,2,249,186,26,116,250,186,251,226,251,186,84,79,253,186,36,185,254,186,51,16,0,187,141,194,0,187,158,115,1,187,100,35,2,187,222,209,2,187,12,127,3,187,235,42,4,187,123,213,4,187,185,126,5,187,166,38,6,187,64,205,6,187,133,114,7,187,116,22,8,187,12,185,8,187,77,90,9,187,52,250,9,187,193,152,10,187,242,53,11,187,198,209,11,187,61,108,12,187,85,5,13,187,13,157,13,187,99,51,14,187,88,200,14,187,234,91,15,187,23,238,15,187,223,126,16,187,65,14,17,187,60,156,17,187,206,40,18,187,248,179,18,187,183,61,19,187,11,198,19,187,243,76,20,187,110,210,20,187,124,86,21,187,26,217,21,187,73,90,22,187,8,218,22,187,85,88,23,187,49,213,23,187,153,80,24,187,142,202,24,187,14,67,25,187,24,186,25,187,173,47,26,187,203,163,26,187,113,22,27,187,158,135,27,187,83,247,27,187,142,101,28,187,79,210,28,187,149,61,29,187,94,167,29,187,172,15,30,187,124,118,30,187,207,219,30,187,164,63,31,187,249,161,31,187,208,2,32,187,38,98,32,187,252,191,32,187,81,28,33,187,36,119,33,187,117,208,33,187,68,40,34,187,144,126,34,187,88,211,34,187,156,38,35,187,92,120,35,187,151,200,35,187,77,23,36,187,125,100,36,187,39,176,36,187,75,250,36,187,232,66,37,187,255,137,37,187,141,207,37,187,148,19,38,187,20,86,38,187,10,151,38,187,121,214,38,187,94,20,39,187,187,80,39,187,142,139,39,187,216,196,39,187,152,252,39,187,206,50,40,187,123,103,40,187,157,154,40,187,52,204,40,187,66,252,40,187,196,42,41,187,188,87,41,187,42,131,41,187,12,173,41,187,100,213,41,187,48,252,41,187,114,33,42,187,40,69,42,187,84,103,42,187,244,135,42,187,10,167,42,187,149,196,42,187,148,224,42,187,9,251,42,187,243,19,43,187,83,43,43,187,40,65,43,187,114,85,43,187,50,104,43,187,104,121,43,187,20,137,43,187,54,151,43,187,207,163,43,187,222,174,43,187,99,184,43,187,96,192,43,187,212,198,43,187,192,203,43,187,35,207,43,187,254,208,43,187,81,209,43,187,29,208,43,187,99,205,43,187,33,201,43,187,89,195,43,187,11,188,43,187,55,179,43,187,222,168,43,187,0,157,43,187,158,143,43,187,184,128,43,187,78,112,43,187,97,94,43,187,241,74,43,187,255,53,43,187,140,31,43,187,151,7,43,187,34,238,42,187,45,211,42,187,183,182,42,187,195,152,42,187,81,121,42,187,97,88,42,187,243,53,42,187,9,18,42,187,162,236,41,187,192,197,41,187,100,157,41,187,141,115,41,187,61,72,41,187,116,27,41,187,51,237,40,187,123,189,40,187,76,140,40,187,168,89,40,187,142,37,40,187,0,240,39,187,254,184,39,187,137,128,39,187,163,70,39,187,75,11,39,187,131,206,38,187,75,144,38,187,164,80,38,187,144,15,38,187,15,205,37,187,33,137,37,187,201,67,37,187,6,253,36,187,218,180,36,187,69,107,36,187,73,32,36,187,230,211,35,187,30,134,35,187,241,54,35,187,96,230,34,187,109,148,34,187,25,65,34,187,99,236,33,187,78,150,33,187,219,62,33,187,10,230,32,187,221,139,32,187,84,48,32,187,114,211,31,187,54,117,31,187,162,21,31,187,184,180,30,187,119,82,30,187,226,238,29,187,250,137,29,187,192,35,29,187,52,188,28,187,88,83,28,187,46,233,27,187,183,125,27,187,243,16,27,187,228,162,26,187,139,51,26,187,234,194,25,187,2,81,25,187,211,221,24,187,96,105,24,187,170,243,23,187,177,124,23,187,120,4,23,187,255,138,22,187,73,16,22,187,85,148,21,187,38,23,21,187,189,152,20,187,28,25,20,187,67,152,19,187,52,22,19,187,241,146,18,187,122,14,18,187,211,136,17,187,251,1,17,187,244,121,16,187,192,240,15,187,96,102,15,187,214,218,14,187,34,78,14,187,72,192,13,187,72,49,13,187,35,161,12,187,219,15,12,187,114,125,11,187,234,233,10,187,67,85,10,187,127,191,9,187,160,40,9,187,168,144,8,187,151,247,7,187,113,93,7,187,53,194,6,187,230,37,6,187,134,136,5,187,22,234,4,187,151,74,4,187,12,170,3,187,117,8,3,187,213,101,2,187,46,194,1,187,128,29,1,187,206,119,0,187,51,162,255,186,200,82,254,186,94,1,253,186,249,173,251,186,156,88,250,186,74,1,249,186,8,168,247,186,216,76,246,186,191,239,244,186,191,144,243,186,220,47,242,186,26,205,240,186,124,104,239,186,5,2,238,186,186,153,236,186,158,47,235,186,180,195,233,186,1,86,232,186,135,230,230,186,74,117,229,186,79,2,228,186,152,141,226,186,42,23,225,186,7,159,223,186,53,37,222,186,182,169,220,186,142,44,219,186,192,173,217,186,82,45,216,186,70,171,214,186,160,39,213,186,101,162,211,186,151,27,210,186,58,147,208,186,83,9,207,186,229,125,205,186,245,240,203,186,133,98,202,186,154,210,200,186,55,65,199,186,97,174,197,186,28,26,196,186,107,132,194,186,82,237,192,186,213,84,191,186,248,186,189,186,191,31,188,186,46,131,186,186,73,229,184,186,20,70,183,186,147,165,181,186,201,3,180,186,187,96,178,186,109,188,176,186,227,22,175,186,32,112,173,186,42,200,171,186,3,31,170,186,176,116,168,186,53,201,166,186,149,28,165,186,214,110,163,186,251,191,161,186,8,16,160,186,1,95,158,186,235,172,156,186,201,249,154,186,159,69,153,186,114,144,151,186,69,218,149,186,30,35,148,186,255,106,146,186,237,177,144,186,237,247,142,186,1,61,141,186,47,129,139,186,123,196,137,186,232,6,136,186,124,72,134,186,57,137,132,186,36,201,130,186,66,8,129,186,44,141,126,186,73,8,123,186,228,129,119,186,5,250,115,186,181,112,112,186,251,229,108,186,225,89,105,186,110,204,101,186,170,61,98,186,159,173,94,186,85,28,91,186,212,137,87,186,36,246,83,186,78,97,80,186,90,203,76,186,81,52,73,186,59,156,69,186,32,3,66,186,10,105,62,186,255,205,58,186,10,50,55,186,49,149,51,186,127,247,47,186,250,88,44,186,172,185,40,186,156,25,37,186,212,120,33,186,92,215,29,186,60,53,26,186,124,146,22,186,38,239,18,186,65,75,15,186,214,166,11,186,237,1,8,186,142,92,4,186,195,182,0,186,40,33,250,185,16,212,242,185,82,134,235,185,252,55,228,185,33,233,220,185,210,153,213,185,29,74,206,185,22,250,198,185,204,169,191,185,80,89,184,185,178,8,177,185,5,184,169,185,87,103,162,185,186,22,155,185,63,198,147,185,246,117,140,185,240,37,133,185,124,172,123,185,224,13,109,185,45,112,94,185,132,211,79,185,8,56,65,185,216,157,50,185,23,5,36,185,228,109,21,185,97,216,6,185,93,137,240,184,221,101,211,184,129,70,182,184,140,43,153,184,127,42,120,184,185,7,62,184,73,239,3,184,98,195,147,183,63,247,125,182,179,91,40,55,222,1,200,55,18,222,29,56,211,173,87,56,217,183,136,56,150,145,165,56,226,99,194,56,123,46,223,56,34,241,251,56,203,85,12,57,204,174,26,57,117,3,41,57,164,83,55,57,59,159,69,57,26,230,83,57,33,40,98,57,48,101,112,57,41,157,126,57,246,103,134,57,172,126,141,57,169,146,148,57,219,163,155,57,52,178,162,57,164,189,169,57,27,198,176,57,138,203,183,57,226,205,190,57,20,205,197,57,15,201,204,57,197,193,211,57,39,183,218,57,37,169,225,57,176,151,232,57,185,130,239,57,49,106,246,57,10,78,253,57,26,23,2,58,79,133,5,58,159,241,8,58,0,92,12,58,108,196,15,58,220,42,19,58,72,143,22,58,169,241,25,58,247,81,29,58,43,176,32,58,62,12,36,58,41,102,39,58,228,189,42,58,105,19,46,58,176,102,49,58,178,183,52,58,104,6,56,58,203,82,59,58,212,156,62,58,123,228,65,58,187,41,69,58,139,108,72,58,229,172,75,58,194,234,78,58,28,38,82,58,235,94,85,58,40,149,88,58,205,200,91,58,210,249,94,58,50,40,98,58,230,83,101,58,230,124,104,58,45,163,107,58,178,198,110,58,113,231,113,58,98,5,117,58,127,32,120,58,194,56,123,58,35,78,126,58,78,176,128,58,20,56,130,58,96,190,131,58,46,67,133,58,124,198,134,58,70,72,136,58,138,200,137,58,67,71,139,58,112,196,140,58,12,64,142,58,22,186,143,58,137,50,145,58,99,169,146,58,161,30,148,58,63,146,149,58,60,4,151,58,148,116,152,58,67,227,153,58,71,80,155,58,158,187,156,58,68,37,158,58,54,141,159,58,114,243,160,58,244,87,162,58,186,186,163,58,193,27,165,58,7,123,166,58,136,216,167,58,66,52,169,58,49,142,170,58,85,230,171,58,168,60,173,58,42,145,174,58,215,227,175,58,172,52,177,58,168,131,178,58,199,208,179,58,7,28,181,58,101,101,182,58,223,172,183,58,114,242,184,58,28,54,186,58,218,119,187,58,170,183,188,58,137,245,189,58,117,49,191,58,108,107,192,58,107,163,193,58,111,217,194,58,119,13,196,58,127,63,197,58,135,111,198,58,138,157,199,58,136,201,200,58,126,243,201,58,106,27,203,58,73,65,204,58,25,101,205,58,216,134,206,58,133,166,207,58,28,196,208,58,156,223,209,58,2,249,210,58,77,16,212,58,122,37,213,58,136,56,214,58,117,73,215,58,61,88,216,58,225,100,217,58,93,111,218,58,175,119,219,58,214,125,220,58,208,129,221,58,155,131,222,58,53,131,223,58,157,128,224,58,208,123,225,58,204,116,226,58,145,107,227,58,28,96,228,58,107,82,229,58,125,66,230,58,81,48,231,58,227,27,232,58,52,5,233,58,65,236,233,58,9,209,234,58,138,179,235,58,194,147,236,58,176,113,237,58,83,77,238,58,170,38,239,58,177,253,239,58,105,210,240,58,208,164,241,58,228,116,242,58,164,66,243,58,14,14,244,58,34,215,244,58,223,157,245,58,65,98,246,58,74,36,247,58,246,227,247,58,70,161,248,58,55,92,249,58,202,20,250,58,251,202,250,58,203,126,251,58,57,48,252,58,66,223,252,58,231,139,253,58,38,54,254,58,254,221,254,58,110,131,255,58,59,19,0,59,138,99,0,59,163,178,0,59,135,0,1,59,52,77,1,59,170,152,1,59,234,226,1,59,242,43,2,59,194,115,2,59,90,186,2,59,185,255,2,59,223,67,3,59,204,134,3,59,127,200,3,59,248,8,4,59,54,72,4,59,58,134,4,59,3,195,4,59,145,254,4,59,227,56,5,59,250,113,5,59,212,169,5,59,114,224,5,59,212,21,6,59,249,73,6,59,225,124,6,59,139,174,6,59,249,222,6,59,41,14,7,59,27,60,7,59,208,104,7,59,71,148,7,59,128,190,7,59,122,231,7,59,54,15,8,59,180,53,8,59,244,90,8,59,245,126,8,59,183,161,8,59,59,195,8,59,128,227,8,59,135,2,9,59,79,32,9,59,216,60,9,59,34,88,9,59,46,114,9,59,251,138,9,59,137,162,9,59,217,184,9,59,235,205,9,59,190,225,9,59,82,244,9,59,169,5,10,59,193,21,10,59,156,36,10,59,56,50,10,59,151,62,10,59,184,73,10,59,156,83,10,59,66,92,10,59,172,99,10,59,216,105,10,59,200,110,10,59,123,114,10,59,242,116,10,59,46,118,10,59,45,118,10,59,241,116,10,59,122,114,10,59,200,110,10,59,219,105,10,59,180,99,10,59,83,92,10,59,184,83,10,59,228,73,10,59,214,62,10,59,145,50,10,59,18,37,10,59,92,22,10,59,111,6,10,59,74,245,9,59,239,226,9,59,93,207,9,59,149,186,9,59,152,164,9,59,102,141,9,59,0,117,9,59,101,91,9,59,151,64,9,59,150,36,9,59,98,7,9,59,253,232,8,59,101,201,8,59,157,168,8,59,164,134,8,59,124,99,8,59,36,63,8,59,157,25,8,59,232,242,7,59,6,203,7,59,246,161,7,59,186,119,7,59,83,76,7,59,192,31,7,59,3,242,6,59,28,195,6,59,12,147,6,59,211,97,6,59,115,47,6,59,236,251,5,59,62,199,5,59,107,145,5,59,114,90,5,59,86,34,5,59,21,233,4,59,179,174,4,59,46,115,4,59,136,54,4,59,193,248,3,59,219,185,3,59,215,121,3,59,180,56,3,59,116,246,2,59,23,179,2,59,159,110,2,59,12,41,2,59,96,226,1,59,154,154,1,59,189,81,1,59,200,7,1,59,189,188,0,59,156,112,0,59,103,35,0,59,61,170,255,58,134,11,255,58,171,106,254,58,175,199,253,58,148,34,253,58,90,123,252,58,5,210,251,58,150,38,251,58,14,121,250,58,113,201,249,58,192,23,249,58,253,99,248,58,43,174,247,58,74,246,246,58,94,60,246,58,104,128,245,58,107,194,244,58,104,2,244,58,98,64,243,58,91,124,242,58,85,182,241,58,82,238,240,58,84,36,240,58,95,88,239,58,115,138,238,58,147,186,237,58,194,232,236,58,2,21,236,58,85,63,235,58,189,103,234,58,60,142,233,58,214,178,232,58,140,213,231,58,97,246,230,58,87,21,230,58,112,50,229,58,175,77,228,58,23,103,227,58,170,126,226,58,106,148,225,58,89,168,224,58,123,186,223,58,210,202,222,58,96,217,221,58,40,230,220,58,44,241,219,58,111,250,218,58,244,1,218,58,189,7,217,58,204,11,216,58,37,14,215,58,201,14,214,58,188,13,213,58,1,11,212,58,153,6,211,58,136,0,210,58,208,248,208,58,116,239,207,58,118,228,206,58,218,215,205,58,163,201,204,58,210,185,203,58,106,168,202,58,111,149,201,58,228,128,200,58,202,106,199,58,37,83,198,58,248,57,197,58,69,31,196,58,16,3,195,58,91,229,193,58,40,198,192,58,124,165,191,58,89,131,190,58,193,95,189,58,184,58,188,58,64,20,187,58,93,236,185,58,17,195,184,58,96,152,183,58,75,108,182,58,216,62,181,58,7,16,180,58,221,223,178,58,92,174,177,58,135,123,176,58,98,71,175,58,238,17,174,58,49,219,172,58,43,163,171,58,225,105,170,58,86,47,169,58,140,243,167,58,135,182,166,58,73,120,165,58,215,56,164,58,50,248,162,58,95,182,161,58,96,115,160,58,56,47,159,58,234,233,157,58,122,163,156,58,235,91,155,58,64,19,154,58,124,201,152,58,162,126,151,58,182,50,150,58,186,229,148,58,178,151,147,58,162,72,146,58,139,248,144,58,114,167,143,58,89,85,142,58,69,2,141,58,56,174,139,58,53,89,138,58,63,3,137,58,91,172,135,58,139,84,134,58,210,251,132,58,52,162,131,58,179,71,130,58,84,236,128,58,51,32,127,58,14,102,124,58,63,170,121,58,204,236,118,58,189,45,116,58,24,109,113,58,227,170,110,58,38,231,107,58,231,33,105,58,44,91,102,58,252,146,99,58,95,201,96,58,90,254,93,58,244,49,91,58,51,100,88,58,32,149,85,58,192,196,82,58,25,243,79,58,52,32,77,58,22,76,74,58,197,118,71,58,74,160,68,58,171,200,65,58,237,239,62,58,25,22,60,58,52,59,57,58,70,95,54,58,86,130,51,58,105,164,48,58,135,197,45,58,183,229,42,58,255,4,40,58,102,35,37,58,242,64,34,58,172,93,31,58,153,121,28,58,192,148,25,58,40,175,22,58,215,200,19,58,213,225,16,58,40,250,13,58,216,17,11,58,234,40,8,58,102,63,5,58,82,85,2,58,105,213,254,57,43,255,248,57,247,39,243,57,217,79,237,57,224,118,231,57,24,157,225,57,144,194,219,57,85,231,213,57,117,11,208,57,252,46,202,57,249,81,196,57,121,116,190,57,138,150,184,57,57,184,178,57,147,217,172,57,166,250,166,57,128,27,161,57,45,60,155,57,188,92,149,57,58,125,143,57,179,157,137,57,55,190,131,57,162,189,123,57,32,255,111,57,1,65,100,57,96,131,88,57,89,198,76,57,5,10,65,57,127,78,53,57,226,147,41,57,72,218,29,57,205,33,18,57,138,106,6,57,53,105,245,56,48,0,222,56,59,154,198,56,139,55,175,56,84,216,151,56,203,124,128,56,73,74,82,56,41,163,35,56,63,9,234,55,43,222,140,55,152,23,191,54,255,250,180,182,246,46,138,183,236,9,231,183,106,231,33,184,111,62,80,184,157,137,126,184,71,100,150,184,109,125,173,184,14,144,196,184,246,155,219,184,242,160,242,184,103,207,4,185,172,74,16,185,47,194,27,185,214,53,39,185,136,165,50,185,44,17,62,185,167,120,73,185,226,219,84,185,196,58,96,185,50,149,107,185,21,235,118,185,41,30,129,185,105,196,134,185,62,104,140,185,156,9,146,185,117,168,151,185,191,68,157,185,108,222,162,185,112,117,168,185,192,9,174,185,79,155,179,185,16,42,185,185,249,181,190,185,252,62,196,185,14,197,201,185,34,72,207,185,46,200,212,185,36,69,218,185,250,190,223,185,163,53,229,185,19,169,234,185,63,25,240,185,27,134,245,185,155,239,250,185,218,42,0,186,45,220,2,186,193,139,5,186,144,57,8,186,148,229,10,186,200,143,13,186,37,56,16,186,167,222,18,186,71,131,21,186,0,38,24,186,205,198,26,186,167,101,29,186,138,2,32,186,111,157,34,186,81,54,37,186,43,205,39,186,247,97,42,186,176,244,44,186,79,133,47,186,209,19,50,186,48,160,52,186,101,42,55,186,108,178,57,186,64,56,60,186,219,187,62,186,55,61,65,186,81,188,67,186,34,57,70,186,165,179,72,186,214,43,75,186,174,161,77,186,42,21,80,186,67,134,82,186,245,244,84,186,59,97,87,186,15,203,89,186,109,50,92,186,80,151,94,186,179,249,96,186,144,89,99,186,228,182,101,186,169,17,104,186,219,105,106,186,116,191,108,186,111,18,111,186,201,98,113,186,125,176,115,186,133,251,117,186,221,67,120,186,128,137,122,186,106,204,124,186,151,12,127,186,0,165,128,186,82,194,129,186,62,222,130,186,194,248,131,186,220,17,133,186,138,41,134,186,201,63,135,186,152,84,136,186,243,103,137,186,218,121,138,186,74,138,139,186,64,153,140,186,187,166,141,186,185,178,142,186,55,189,143,186,52,198,144,186,174,205,145,186,161,211,146,186,13,216,147,186,240,218,148,186,71,220,149,186,17,220,150,186,75,218,151,186,244,214,152,186,9,210,153,186,138,203,154,186,115,195,155,186,195,185,156,186,121,174,157,186,146,161,158,186,12,147,159,186,231,130,160,186,31,113,161,186,179,93,162,186,162,72,163,186,234,49,164,186,137,25,165,186,125,255,165,186,196,227,166,186,94,198,167,186,72,167,168,186,128,134,169,186,6,100,170,186,215,63,171,186,241,25,172,186,84,242,172,186,254,200,173,186,237,157,174,186,31,113,175,186,148,66,176,186,73,18,177,186,61,224,177,186,111,172,178,186,221,118,179,186,135,63,180,186,105,6,181,186,132,203,181,186,213,142,182,186,92,80,183,186,22,16,184,186,4,206,184,186,34,138,185,186,113,68,186,186,238,252,186,186,154,179,187,186,113,104,188,186,116,27,189,186,160,204,189,186,245,123,190,186,114,41,191,186,21,213,191,186,222,126,192,186,203,38,193,186,219,204,193,186,12,113,194,186,95,19,195,186,210,179,195,186,100,82,196,186,19,239,196,186,224,137,197,186,200,34,198,186,204,185,198,186,233,78,199,186,32,226,199,186,111,115,200,186,213,2,201,186,82,144,201,186,228,27,202,186,139,165,202,186,70,45,203,186,21,179,203,186,246,54,204,186,232,184,204,186,236,56,205,186,0,183,205,186,35,51,206,186,85,173,206,186,150,37,207,186,228,155,207,186,63,16,208,186,166,130,208,186,25,243,208,186,151,97,209,186,31,206,209,186,178,56,210,186,78,161,210,186,243,7,211,186,161,108,211,186,86,207,211,186,19,48,212,186,216,142,212,186,163,235,212,186,117,70,213,186,76,159,213,186,41,246,213,186,12,75,214,186,243,157,214,186,223,238,214,186,207,61,215,186,196,138,215,186,188,213,215,186,184,30,216,186,183,101,216,186,185,170,216,186,191,237,216,186,199,46,217,186,210,109,217,186,224,170,217,186,240,229,217,186,2,31,218,186,23,86,218,186,45,139,218,186,71,190,218,186,98,239,218,186,127,30,219,186,159,75,219,186,193,118,219,186,229,159,219,186,11,199,219,186,52,236,219,186,96,15,220,186,142,48,220,186,191,79,220,186,242,108,220,186,41,136,220,186,100,161,220,186,162,184,220,186,227,205,220,186,41,225,220,186,115,242,220,186,193,1,221,186,21,15,221,186,109,26,221,186,203,35,221,186,47,43,221,186,153,48,221,186,10,52,221,186,129,53,221,186,0,53,221,186,135,50,221,186,23,46,221,186,175,39,221,186,80,31,221,186,251,20,221,186,177,8,221,186,113,250,220,186,61,234,220,186,20,216,220,186,248,195,220,186,234,173,220,186,233,149,220,186,247,123,220,186,19,96,220,186,64,66,220,186,125,34,220,186,203,0,220,186,43,221,219,186,158,183,219,186,36,144,219,186,190,102,219,186,109,59,219,186,50,14,219,186,14,223,218,186,1,174,218,186,12,123,218,186,49,70,218,186,111,15,218,186,201,214,217,186,62,156,217,186,208,95,217,186,128,33,217,186,79,225,216,186,62,159,216,186,77,91,216,186,126,21,216,186,210,205,215,186,73,132,215,186,230,56,215,186,168,235,214,186,146,156,214,186,164,75,214,186,223,248,213,186,69,164,213,186,214,77,213,186,148,245,212,186,128,155,212,186,155,63,212,186,231,225,211,186,100,130,211,186,20,33,211,186,248,189,210,186,18,89,210,186,99,242,209,186,235,137,209,186,173,31,209,186,170,179,208,186,227,69,208,186,89,214,207,186,14,101,207,186,3,242,206,186,57,125,206,186,179,6,206,186,114,142,205,186,118,20,205,186,194,152,204,186,86,27,204,186,53,156,203,186,96,27,203,186,216,152,202,186,160,20,202,186,184,142,201,186,33,7,201,186,223,125,200,186,242,242,199,186,92,102,199,186,30,216,198,186,58,72,198,186,178,182,197,186,136,35,197,186,189,142,196,186,82,248,195,186,74,96,195,186,166,198,194,186,104,43,194,186,146,142,193,186,37,240,192,186,36,80,192,186,143,174,191,186,105,11,191,186,180,102,190,186,113,192,189,186,162,24,189,186,74,111,188,186,105,196,187,186,2,24,187,186,22,106,186,186,168,186,185,186,186,9,185,186,77,87,184,186,99,163,183,186,254,237,182,186,33,55,182,186,204,126,181,186,3,197,180,186,198,9,180,186,25,77,179,186,253,142,178,186,115,207,177,186,127,14,177,186,34,76,176,186,94,136,175,186,53,195,174,186,170,252,173,186,189,52,173,186,115,107,172,186,204,160,171,186,202,212,170,186,112,7,170,186,192,56,169,186,189,104,168,186,103,151,167,186,194,196,166,186,207,240,165,186,145,27,165,186,10,69,164,186,59,109,163,186,41,148,162,186,211,185,161,186,62,222,160,186,106,1,160,186,91,35,159,186,18,68,158,186,146,99,157,186,221,129,156,186,245,158,155,186,221,186,154,186,150,213,153,186,36,239,152,186,137,7,152,186,198,30,151,186,222,52,150,186,212,73,149,186,170,93,148,186,98,112,147,186,254,129,146,186,130,146,145,186,238,161,144,186,71,176,143,186,141,189,142,186,197,201,141,186,239,212,140,186,14,223,139,186,37,232,138,186,55,240,137,186,69,247,136,186,82,253,135,186,97,2,135,186,115,6,134,186,140,9,133,186,174,11,132,186,220,12,131,186,23,13,130,186,99,12,129,186,194,10,128,186,108,16,126,186,132,9,124,186,209,0,122,186,88,246,119,186,30,234,117,186,40,220,115,186,123,204,113,186,28,187,111,186,17,168,109,186,93,147,107,186,8,125,105,186,21,101,103,186,138,75,101,186,108,48,99,186,192,19,97,186,139,245,94,186,211,213,92,186,156,180,90,186,237,145,88,186,201,109,86,186,55,72,84,186,59,33,82,186,219,248,79,186,28,207,77,186,3,164,75,186,149,119,73,186,216,73,71,186,210,26,69,186,134,234,66,186,251,184,64,186,54,134,62,186,60,82,60,186,19,29,58,186,191,230,55,186,71,175,53,186,174,118,51,186,252,60,49,186,52,2,47,186,93,198,44,186,124,137,42,186,150,75,40,186,176,12,38,186,207,204,35,186,250,139,33,186,54,74,31,186,135,7,29,186,243,195,26,186,129,127,24,186,52,58,22,186,18,244,19,186,33,173,17,186,102,101,15,186,231,28,13,186,168,211,10,186,176,137,8,186,3,63,6,186,168,243,3,186,163,167,1,186,243,181,254,185,99,27,250,185,160,127,245,185,181,226,240,185,173,68,236,185,147,165,231,185,113,5,227,185,82,100,222,185,65,194,217,185,73,31,213,185,117,123,208,185,207,214,203,185,99,49,199,185,58,139,194,185,96,228,189,185,224,60,185,185,196,148,180,185,23,236,175,185,228,66,171,185,54,153,166,185,23,239,161,185,146,68,157,185,178,153,152,185,130,238,147,185,12,67,143,185,91,151,138,185,122,235,133,185,116,63,129,185,166,38,121,185,68,206,111,185,215,117,102,185,116,29,93,185], "i8", ALLOC_NONE, Runtime.GLOBAL_BASE+40960);
/* memory initializer */ allocate([50,197,83,185,37,109,74,185,98,21,65,185,0,190,55,185,19,103,46,185,176,16,37,185,236,186,27,185,222,101,18,185,153,17,9,185,102,124,255,184,132,215,236,184,179,52,218,184,32,148,199,184,243,245,180,184,87,90,162,184,117,193,143,184,240,86,122,184,19,49,85,184,166,17,48,184,252,248,10,184,211,206,203,183,129,186,129,183,162,214,222,182,130,248,17,54,9,70,56,55,143,245,165,55,11,182,239,55,234,177,28,56,35,127,65,56,94,66,102,56,165,125,133,56,203,212,151,56,120,38,170,56,131,114,188,56,195,184,206,56,17,249,224,56,68,51,243,56,154,179,2,57,92,202,11,57,212,221,20,57,239,237,29,57,151,250,38,57,186,3,48,57,68,9,57,57,32,11,66,57,59,9,75,57,129,3,84,57,223,249,92,57,65,236,101,57,147,218,110,57,194,196,119,57,93,85,128,57,53,198,132,57,222,52,137,57,78,161,141,57,125,11,146,57,96,115,150,57,239,216,154,57,30,60,159,57,230,156,163,57,61,251,167,57,24,87,172,57,111,176,176,57,57,7,181,57,107,91,185,57,254,172,189,57,230,251,193,57,28,72,198,57,150,145,202,57,74,216,206,57,49,28,211,57,63,93,215,57,110,155,219,57,178,214,223,57,4,15,228,57,91,68,232,57,172,118,236,57,241,165,240,57,31,210,244,57,46,251,248,57,21,33,253,57,229,161,0,58,164,177,2,58,193,191,4,58,57,204,6,58,7,215,8,58,40,224,10,58,149,231,12,58,77,237,14,58,73,241,16,58,135,243,18,58,2,244,20,58,181,242,22,58,156,239,24,58,180,234,26,58,248,227,28,58,101,219,30,58,245,208,32,58,165,196,34,58,114,182,36,58,86,166,38,58,78,148,40,58,87,128,42,58,107,106,44,58,135,82,46,58,168,56,48,58,200,28,50,58,230,254,51,58,252,222,53,58,6,189,55,58,2,153,57,58,234,114,59,58,188,74,61,58,116,32,63,58,13,244,64,58,133,197,66,58,216,148,68,58,1,98,70,58,254,44,72,58,202,245,73,58,98,188,75,58,195,128,77,58,233,66,79,58,209,2,81,58,118,192,82,58,214,123,84,58,237,52,86,58,184,235,87,58,51,160,89,58,91,82,91,58,44,2,93,58,164,175,94,58,190,90,96,58,120,3,98,58,207,169,99,58,191,77,101,58,69,239,102,58,93,142,104,58,5,43,106,58,58,197,107,58,248,92,109,58,60,242,110,58,4,133,112,58,75,21,114,58,16,163,115,58,79,46,117,58,6,183,118,58,49,61,120,58,205,192,121,58,216,65,123,58,78,192,124,58,45,60,126,58,115,181,127,58,14,150,128,58,18,80,129,58,198,8,130,58,39,192,130,58,52,118,131,58,236,42,132,58,78,222,132,58,88,144,133,58,9,65,134,58,96,240,134,58,91,158,135,58,249,74,136,58,58,246,136,58,27,160,137,58,156,72,138,58,187,239,138,58,119,149,139,58,207,57,140,58,194,220,140,58,79,126,141,58,117,30,142,58,50,189,142,58,134,90,143,58,110,246,143,58,235,144,144,58,252,41,145,58,158,193,145,58,210,87,146,58,149,236,146,58,232,127,147,58,201,17,148,58,55,162,148,58,49,49,149,58,183,190,149,58,198,74,150,58,96,213,150,58,129,94,151,58,43,230,151,58,91,108,152,58,16,241,152,58,75,116,153,58,10,246,153,58,76,118,154,58,17,245,154,58,88,114,155,58,32,238,155,58,103,104,156,58,47,225,156,58,117,88,157,58,57,206,157,58,122,66,158,58,56,181,158,58,114,38,159,58,39,150,159,58,86,4,160,58,0,113,160,58,35,220,160,58,191,69,161,58,210,173,161,58,94,20,162,58,96,121,162,58,217,220,162,58,200,62,163,58,44,159,163,58,5,254,163,58,82,91,164,58,19,183,164,58,72,17,165,58,239,105,165,58,9,193,165,58,150,22,166,58,148,106,166,58,3,189,166,58,227,13,167,58,52,93,167,58,244,170,167,58,37,247,167,58,198,65,168,58,213,138,168,58,84,210,168,58,65,24,169,58,156,92,169,58,102,159,169,58,157,224,169,58,67,32,170,58,85,94,170,58,213,154,170,58,194,213,170,58,28,15,171,58,227,70,171,58,22,125,171,58,182,177,171,58,194,228,171,58,59,22,172,58,32,70,172,58,113,116,172,58,46,161,172,58,87,204,172,58,236,245,172,58,237,29,173,58,90,68,173,58,52,105,173,58,121,140,173,58,42,174,173,58,72,206,173,58,210,236,173,58,200,9,174,58,43,37,174,58,250,62,174,58,54,87,174,58,222,109,174,58,244,130,174,58,119,150,174,58,103,168,174,58,197,184,174,58,144,199,174,58,202,212,174,58,114,224,174,58,136,234,174,58,13,243,174,58,1,250,174,58,100,255,174,58,55,3,175,58,122,5,175,58,46,6,175,58,82,5,175,58,231,2,175,58,237,254,174,58,101,249,174,58,79,242,174,58,172,233,174,58,124,223,174,58,192,211,174,58,119,198,174,58,163,183,174,58,68,167,174,58,90,149,174,58,230,129,174,58,233,108,174,58,98,86,174,58,83,62,174,58,189,36,174,58,158,9,174,58,250,236,173,58,207,206,173,58,30,175,173,58,233,141,173,58,47,107,173,58,242,70,173,58,50,33,173,58,240,249,172,58,45,209,172,58,232,166,172,58,35,123,172,58,224,77,172,58,29,31,172,58,221,238,171,58,31,189,171,58,230,137,171,58,49,85,171,58,1,31,171,58,87,231,170,58,52,174,170,58,154,115,170,58,135,55,170,58,255,249,169,58,1,187,169,58,142,122,169,58,168,56,169,58,78,245,168,58,131,176,168,58,71,106,168,58,155,34,168,58,128,217,167,58,247,142,167,58,1,67,167,58,159,245,166,58,209,166,166,58,154,86,166,58,250,4,166,58,242,177,165,58,132,93,165,58,175,7,165,58,118,176,164,58,217,87,164,58,218,253,163,58,122,162,163,58,185,69,163,58,153,231,162,58,28,136,162,58,65,39,162,58,12,197,161,58,123,97,161,58,146,252,160,58,81,150,160,58,185,46,160,58,203,197,159,58,138,91,159,58,245,239,158,58,15,131,158,58,216,20,158,58,82,165,157,58,126,52,157,58,93,194,156,58,242,78,156,58,60,218,155,58,62,100,155,58,249,236,154,58,109,116,154,58,158,250,153,58,139,127,153,58,54,3,153,58,161,133,152,58,206,6,152,58,188,134,151,58,111,5,151,58,231,130,150,58,38,255,149,58,45,122,149,58,254,243,148,58,154,108,148,58,3,228,147,58,58,90,147,58,65,207,146,58,25,67,146,58,196,181,145,58,67,39,145,58,152,151,144,58,196,6,144,58,201,116,143,58,169,225,142,58,101,77,142,58,254,183,141,58,119,33,141,58,209,137,140,58,13,241,139,58,46,87,139,58,52,188,138,58,33,32,138,58,248,130,137,58,185,228,136,58,102,69,136,58,2,165,135,58,141,3,135,58,10,97,134,58,121,189,133,58,222,24,133,58,57,115,132,58,140,204,131,58,217,36,131,58,34,124,130,58,105,210,129,58,174,39,129,58,244,123,128,58,123,158,127,58,22,67,126,58,189,229,124,58,117,134,123,58,64,37,122,58,35,194,120,58,32,93,119,58,60,246,117,58,122,141,116,58,221,34,115,58,106,182,113,58,36,72,112,58,15,216,110,58,47,102,109,58,135,242,107,58,26,125,106,58,238,5,105,58,4,141,103,58,98,18,102,58,12,150,100,58,3,24,99,58,78,152,97,58,239,22,96,58,234,147,94,58,67,15,93,58,254,136,91,58,31,1,90,58,170,119,88,58,162,236,86,58,11,96,85,58,234,209,83,58,66,66,82,58,23,177,80,58,110,30,79,58,73,138,77,58,174,244,75,58,159,93,74,58,33,197,72,58,57,43,71,58,233,143,69,58,54,243,67,58,36,85,66,58,182,181,64,58,242,20,63,58,219,114,61,58,117,207,59,58,196,42,58,58,204,132,56,58,145,221,54,58,23,53,53,58,99,139,51,58,120,224,49,58,91,52,48,58,15,135,46,58,153,216,44,58,253,40,43,58,63,120,41,58,99,198,39,58,109,19,38,58,98,95,36,58,69,170,34,58,26,244,32,58,231,60,31,58,174,132,29,58,117,203,27,58,63,17,26,58,17,86,24,58,238,153,22,58,219,220,20,58,220,30,19,58,245,95,17,58,43,160,15,58,130,223,13,58,253,29,12,58,162,91,10,58,116,152,8,58,119,212,6,58,176,15,5,58,35,74,3,58,213,131,1,58,145,121,255,57,6,234,251,57,16,89,248,57,186,198,244,57,9,51,241,57,9,158,237,57,192,7,234,57,55,112,230,57,119,215,226,57,136,61,223,57,116,162,219,57,65,6,216,57,250,104,212,57,165,202,208,57,77,43,205,57,249,138,201,57,178,233,197,57,128,71,194,57,109,164,190,57,128,0,187,57,193,91,183,57,59,182,179,57,244,15,176,57,246,104,172,57,73,193,168,57,245,24,165,57,4,112,161,57,125,198,157,57,106,28,154,57,210,113,150,57,190,198,146,57,54,27,143,57,68,111,139,57,240,194,135,57,65,22,132,57,65,105,128,57,241,119,121,57,223,28,114,57,92,193,106,57,123,101,99,57,75,9,92,57,222,172,84,57,69,80,77,57,144,243,69,57,208,150,62,57,23,58,55,57,116,221,47,57,250,128,40,57,184,36,33,57,192,200,25,57,33,109,18,57,238,17,11,57,54,183,3,57,21,186,248,56,247,6,234,56,54,85,219,56,240,164,204,56,73,246,189,56,97,73,175,56,88,158,160,56,82,245,145,56,109,78,131,56,151,83,105,56,29,15,76,56,173,207,46,56,137,149,17,56,230,193,232,55,87,100,174,55,212,37,104,55,126,56,231,54,201,10,83,179,10,78,234,182,173,94,105,183,127,188,174,183,124,186,232,183,102,84,17,184,118,67,46,184,48,42,75,184,82,8,104,184,206,110,130,184,232,212,144,184,86,54,159,184,249,146,173,184,177,234,187,184,94,61,202,184,226,138,216,184,27,211,230,184,236,21,245,184,154,169,1,185,106,197,8,185,87,222,15,185,80,244,22,185,72,7,30,185,45,23,37,185,241,35,44,185,132,45,51,185,215,51,58,185,219,54,65,185,128,54,72,185,183,50,79,185,113,43,86,185,159,32,93,185,49,18,100,185,26,0,107,185,73,234,113,185,176,208,120,185,64,179,127,185,245,72,131,185,79,182,134,185,168,33,138,185,248,138,141,185,56,242,144,185,95,87,148,185,104,186,151,185,75,27,155,185,0,122,158,185,128,214,161,185,197,48,165,185,199,136,168,185,127,222,171,185,229,49,175,185,244,130,178,185,163,209,181,185,236,29,185,185,200,103,188,185,47,175,191,185,28,244,194,185,135,54,198,185,104,118,201,185,187,179,204,185,118,238,207,185,149,38,211,185,15,92,214,185,223,142,217,185,253,190,220,185,100,236,223,185,11,23,227,185,238,62,230,185,4,100,233,185,72,134,236,185,180,165,239,185,64,194,242,185,230,219,245,185,160,242,248,185,104,6,252,185,54,23,255,185,131,18,1,186,232,151,2,186,199,27,4,186,29,158,5,186,231,30,7,186,34,158,8,186,203,27,10,186,223,151,11,186,90,18,13,186,58,139,14,186,124,2,16,186,29,120,17,186,25,236,18,186,110,94,20,186,26,207,21,186,24,62,23,186,103,171,24,186,3,23,26,186,234,128,27,186,24,233,28,186,139,79,30,186,64,180,31,186,52,23,33,186,101,120,34,186,208,215,35,186,114,53,37,186,72,145,38,186,80,235,39,186,135,67,41,186,234,153,42,186,119,238,43,186,43,65,45,186,4,146,46,186,254,224,47,186,24,46,49,186,79,121,50,186,161,194,51,186,10,10,53,186,137,79,54,186,26,147,55,186,188,212,56,186,109,20,58,186,41,82,59,186,238,141,60,186,186,199,61,186,139,255,62,186,95,53,64,186,50,105,65,186,3,155,66,186,208,202,67,186,150,248,68,186,83,36,70,186,4,78,71,186,169,117,72,186,62,155,73,186,193,190,74,186,49,224,75,186,139,255,76,186,205,28,78,186,245,55,79,186,1,81,80,186,239,103,81,186,189,124,82,186,105,143,83,186,241,159,84,186,84,174,85,186,142,186,86,186,159,196,87,186,133,204,88,186,61,210,89,186,198,213,90,186,31,215,91,186,68,214,92,186,53,211,93,186,240,205,94,186,115,198,95,186,188,188,96,186,202,176,97,186,155,162,98,186,45,146,99,186,127,127,100,186,144,106,101,186,93,83,102,186,229,57,103,186,39,30,104,186,32,0,105,186,209,223,105,186,55,189,106,186,80,152,107,186,28,113,108,186,152,71,109,186,197,27,110,186,159,237,110,186,38,189,111,186,89,138,112,186,54,85,113,186,189,29,114,186,235,227,114,186,192,167,115,186,58,105,116,186,88,40,117,186,26,229,117,186,125,159,118,186,129,87,119,186,37,13,120,186,104,192,120,186,73,113,121,186,198,31,122,186,223,203,122,186,146,117,123,186,224,28,124,186,198,193,124,186,68,100,125,186,90,4,126,186,5,162,126,186,70,61,127,186,27,214,127,186,66,54,128,186,64,128,128,186,8,201,128,186,152,16,129,186,240,86,129,186,17,156,129,186,249,223,129,186,169,34,130,186,32,100,130,186,93,164,130,186,98,227,130,186,44,33,131,186,189,93,131,186,19,153,131,186,47,211,131,186,16,12,132,186,182,67,132,186,34,122,132,186,82,175,132,186,70,227,132,186,255,21,133,186,124,71,133,186,189,119,133,186,194,166,133,186,138,212,133,186,23,1,134,186,103,44,134,186,122,86,134,186,81,127,134,186,235,166,134,186,72,205,134,186,105,242,134,186,77,22,135,186,243,56,135,186,94,90,135,186,139,122,135,186,123,153,135,186,47,183,135,186,165,211,135,186,223,238,135,186,220,8,136,186,157,33,136,186,33,57,136,186,104,79,136,186,115,100,136,186,66,120,136,186,213,138,136,186,43,156,136,186,70,172,136,186,37,187,136,186,200,200,136,186,48,213,136,186,93,224,136,186,78,234,136,186,5,243,136,186,129,250,136,186,195,0,137,186,203,5,137,186,152,9,137,186,44,12,137,186,135,13,137,186,169,13,137,186,146,12,137,186,66,10,137,186,186,6,137,186,250,1,137,186,3,252,136,186,213,244,136,186,111,236,136,186,211,226,136,186,2,216,136,186,250,203,136,186,189,190,136,186,75,176,136,186,165,160,136,186,203,143,136,186,189,125,136,186,124,106,136,186,8,86,136,186,98,64,136,186,138,41,136,186,129,17,136,186,72,248,135,186,222,221,135,186,68,194,135,186,123,165,135,186,131,135,135,186,93,104,135,186,10,72,135,186,138,38,135,186,221,3,135,186,5,224,134,186,1,187,134,186,211,148,134,186,122,109,134,186,249,68,134,186,78,27,134,186,124,240,133,186,130,196,133,186,98,151,133,186,27,105,133,186,176,57,133,186,31,9,133,186,107,215,132,186,147,164,132,186,153,112,132,186,125,59,132,186,64,5,132,186,227,205,131,186,102,149,131,186,202,91,131,186,17,33,131,186,58,229,130,186,72,168,130,186,57,106,130,186,16,43,130,186,205,234,129,186,113,169,129,186,253,102,129,186,113,35,129,186,207,222,128,186,24,153,128,186,75,82,128,186,107,10,128,186,240,130,127,186,230,238,126,186,185,88,126,186,108,192,125,186,1,38,125,186,122,137,124,186,215,234,123,186,28,74,123,186,74,167,122,186,99,2,122,186,106,91,121,186,95,178,120,186,70,7,120,186,32,90,119,186,239,170,118,186,181,249,117,186,116,70,117,186,47,145,116,186,232,217,115,186,160,32,115,186,90,101,114,186,24,168,113,186,220,232,112,186,168,39,112,186,127,100,111,186,99,159,110,186,85,216,109,186,89,15,109,186,112,68,108,186,157,119,107,186,226,168,106,186,65,216,105,186,189,5,105,186,87,49,104,186,19,91,103,186,243,130,102,186,249,168,101,186,40,205,100,186,129,239,99,186,7,16,99,186,190,46,98,186,166,75,97,186,195,102,96,186,24,128,95,186,166,151,94,186,112,173,93,186,120,193,92,186,194,211,91,186,80,228,90,186,36,243,89,186,65,0,89,186,169,11,88,186,95,21,87,186,102,29,86,186,192,35,85,186,112,40,84,186,121,43,83,186,221,44,82,186,158,44,81,186,193,42,80,186,70,39,79,186,50,34,78,186,134,27,77,186,70,19,76,186,116,9,75,186,19,254,73,186,37,241,72,186,174,226,71,186,177,210,70,186,47,193,69,186,44,174,68,186,171,153,67,186,174,131,66,186,57,108,65,186,78,83,64,186,240,56,63,186,34,29,62,186,231,255,60,186,66,225,59,186,53,193,58,186,196,159,57,186,241,124,56,186,192,88,55,186,51,51,54,186,78,12,53,186,18,228,51,186,132,186,50,186,167,143,49,186,124,99,48,186,7,54,47,186,76,7,46,186,77,215,44,186,14,166,43,186,144,115,42,186,216,63,41,186,232,10,40,186,196,212,38,186,110,157,37,186,234,100,36,186,58,43,35,186,98,240,33,186,101,180,32,186,70,119,31,186,8,57,30,186,174,249,28,186,59,185,27,186,179,119,26,186,24,53,25,186,110,241,23,186,184,172,22,186,248,102,21,186,51,32,20,186,107,216,18,186,164,143,17,186,224,69,16,186,36,251,14,186,113,175,13,186,203,98,12,186,54,21,11,186,181,198,9,186,75,119,8,186,250,38,7,186,199,213,5,186,181,131,4,186,198,48,3,186,254,220,1,186,97,136,0,186,225,101,254,185,99,185,251,185,76,11,249,185,165,91,246,185,114,170,243,185,187,247,240,185,134,67,238,185,217,141,235,185,188,214,232,185,53,30,230,185,74,100,227,185,2,169,224,185,100,236,221,185,118,46,219,185,62,111,216,185,196,174,213,185,14,237,210,185,34,42,208,185,8,102,205,185,197,160,202,185,97,218,199,185,225,18,197,185,77,74,194,185,172,128,191,185,3,182,188,185,90,234,185,185,183,29,183,185,33,80,180,185,159,129,177,185,54,178,174,185,239,225,171,185,206,16,169,185,220,62,166,185,31,108,163,185,156,152,160,185,92,196,157,185,101,239,154,185,189,25,152,185,107,67,149,185,117,108,146,185,227,148,143,185,187,188,140,185,3,228,137,185,195,10,135,185,0,49,132,185,194,86,129,185,31,248,124,185,221,65,119,185,204,138,113,185,249,210,107,185,114,26,102,185,67,97,96,185,122,167,90,185,37,237,84,185,80,50,79,185,9,119,73,185,93,187,67,185,89,255,61,185,10,67,56,185,126,134,50,185,193,201,44,185,226,12,39,185,236,79,33,185,238,146,27,185,244,213,21,185,11,25,16,185,66,92,10,185,163,159,4,185,124,198,253,184,61,78,242,184,164,214,230,184,202,95,219,184,203,233,207,184,191,116,196,184,194,0,185,184,236,141,173,184,88,28,162,184,32,172,150,184,94,61,139,184,85,160,127,184,64,201,104,184,177,245,81,184,219,37,59,184,242,89,36,184,40,146,13,184,96,157,237,183,124,31,192,183,10,171,146,183,220,128,74,183,58,128,223,182,10,165,168,181,153,1,139,54,99,255,31,55,89,102,122,55,114,90,170,55,30,117,215,55,103,65,2,56,141,193,24,56,210,58,47,56,2,173,69,56,236,23,92,56,94,123,114,56,148,107,132,56,140,149,143,56,126,187,154,56,82,221,165,56,240,250,176,56,62,20,188,56,37,41,199,56,140,57,210,56,92,69,221,56,123,76,232,56,211,78,243,56,74,76,254,56,101,162,4,57,28,28,10,57,64,147,15,57,196,7,21,57,157,121,26,57,190,232,31,57,27,85,37,57,170,190,42,57,95,37,48,57,45,137,53,57,9,234,58,57,232,71,64,57,190,162,69,57,127,250,74,57,32,79,80,57,150,160,85,57,213,238,90,57,210,57,96,57,129,129,101,57,216,197,106,57,203,6,112,57,78,68,117,57,87,126,122,57,219,180,127,57,231,115,130,57,147,11,133,57,108,161,135,57,108,53,138,57,142,199,140,57,204,87,143,57,33,230,145,57,136,114,148,57,251,252,150,57,118,133,153,57,241,11,156,57,106,144,158,57,217,18,161,57,58,147,163,57,136,17,166,57,189,141,168,57,212,7,171,57,201,127,173,57,150,245,175,57,53,105,178,57,162,218,180,57,216,73,183,57,210,182,185,57,139,33,188,57,253,137,190,57,36,240,192,57,251,83,195,57,125,181,197,57,165,20,200,57,111,113,202,57,212,203,204,57,210,35,207,57,98,121,209,57,129,204,211,57,41,29,214,57,86,107,216,57,2,183,218,57,43,0,221,57,202,70,223,57,219,138,225,57,90,204,227,57,66,11,230,57,143,71,232,57,60,129,234,57,68,184,236,57,164,236,238,57,87,30,241,57,89,77,243,57,165,121,245,57,54,163,247,57,10,202,249,57,26,238,251,57,100,15,254,57,242,22,0,58,202,36,1,58,56,49,2,58,58,60,3,58,207,69,4,58,244,77,5,58,168,84,6,58,231,89,7,58,177,93,8,58,3,96,9,58,220,96,10,58,57,96,11,58,24,94,12,58,121,90,13,58,88,85,14,58,179,78,15,58,138,70,16,58,218,60,17,58,161,49,18,58,222,36,19,58,143,22,20,58,177,6,21,58,68,245,21,58,69,226,22,58,179,205,23,58,140,183,24,58,206,159,25,58,120,134,26,58,136,107,27,58,252,78,28,58,210,48,29,58,10,17,30,58,162,239,30,58,151,204,31,58,232,167,32,58,148,129,33,58,154,89,34,58,247,47,35,58,170,4,36,58,178,215,36,58,13,169,37,58,186,120,38,58,183,70,39,58,4,19,40,58,157,221,40,58,131,166,41,58,179,109,42,58,45,51,43,58,239,246,43,58,248,184,44,58,69,121,45,58,215,55,46,58,172,244,46,58,195,175,47,58,26,105,48,58,175,32,49,58,131,214,49,58,148,138,50,58,224,60,51,58,102,237,51,58,38,156,52,58,30,73,53,58,76,244,53,58,177,157,54,58,75,69,55,58,24,235,55,58,25,143,56,58,75,49,57,58,173,209,57,58,64,112,58,58,1,13,59,58,240,167,59,58,12,65,60,58,85,216,60,58,200,109,61,58,101,1,62,58,44,147,62,58,27,35,63,58,51,177,63,58,112,61,64,58,212,199,64,58,94,80,65,58,12,215,65,58,221,91,66,58,210,222,66,58,233,95,67,58,34,223,67,58,124,92,68,58,246,215,68,58,144,81,69,58,73,201,69,58,33,63,70,58,22,179,70,58,41,37,71,58,89,149,71,58,164,3,72,58,12,112,72,58,143,218,72,58,45,67,73,58,228,169,73,58,182,14,74,58,161,113,74,58,166,210,74,58,195,49,75,58,248,142,75,58,69,234,75,58,169,67,76,58,37,155,76,58,184,240,76,58,97,68,77,58,33,150,77,58,247,229,77,58,227,51,78,58,228,127,78,58,251,201,78,58,39,18,79,58,104,88,79,58,190,156,79,58,41,223,79,58,169,31,80,58,61,94,80,58,230,154,80,58,163,213,80,58,117,14,81,58,91,69,81,58,85,122,81,58,100,173,81,58,135,222,81,58,191,13,82,58,11,59,82,58,108,102,82,58,226,143,82,58,108,183,82,58,11,221,82,58,191,0,83,58,137,34,83,58,103,66,83,58,92,96,83,58,102,124,83,58,134,150,83,58,189,174,83,58,10,197,83,58,109,217,83,58,232,235,83,58,123,252,83,58,37,11,84,58,231,23,84,58,193,34,84,58,181,43,84,58,194,50,84,58,232,55,84,58,40,59,84,58,131,60,84,58,249,59,84,58,138,57,84,58,56,53,84,58,1,47,84,58,232,38,84,58,236,28,84,58,15,17,84,58,80,3,84,58,176,243,83,58,49,226,83,58,209,206,83,58,147,185,83,58,119,162,83,58,125,137,83,58,167,110,83,58,244,81,83,58,102,51,83,58,253,18,83,58,186,240,82,58,158,204,82,58,170,166,82,58,223,126,82,58,60,85,82,58,196,41,82,58,119,252,81,58,85,205,81,58,96,156,81,58,153,105,81,58,1,53,81,58,151,254,80,58,95,198,80,58,87,140,80,58,130,80,80,58,224,18,80,58,114,211,79,58,58,146,79,58,56,79,79,58,110,10,79,58,220,195,78,58,131,123,78,58,102,49,78,58,131,229,77,58,222,151,77,58,119,72,77,58,79,247,76,58,103,164,76,58,193,79,76,58,94,249,75,58,62,161,75,58,100,71,75,58,207,235,74,58,131,142,74,58,127,47,74,58,198,206,73,58,88,108,73,58,55,8,73,58,100,162,72,58,224,58,72,58,174,209,71,58,205,102,71,58,64,250,70,58,8,140,70,58,38,28,70,58,156,170,69,58,107,55,69,58,149,194,68,58,26,76,68,58,254,211,67,58,64,90,67,58,227,222,66,58,232,97,66,58,81,227,65,58,31,99,65,58,83,225,64,58,240,93,64,58,246,216,63,58,104,82,63,58,71,202,62,58,148,64,62,58,82,181,61,58,130,40,61,58,37,154,60,58,61,10,60,58,205,120,59,58,212,229,58,58,86,81,58,58,85,187,57,58,208,35,57,58,204,138,56,58,72,240,55,58,71,84,55,58,203,182,54,58,214,23,54,58,105,119,53,58,134,213,52,58,46,50,52,58,101,141,51,58,43,231,50,58,131,63,50,58,110,150,49,58,238,235,48,58,5,64,48,58,181,146,47,58,0,228,46,58,232,51,46,58,110,130,45,58,149,207,44,58,94,27,44,58,204,101,43,58,225,174,42,58,158,246,41,58,6,61,41,58,25,130,40,58,220,197,39,58,79,8,39,58,116,73,38,58,78,137,37,58,223,199,36,58,40,5,36,58,44,65,35,58,236,123,34,58,108,181,33,58,172,237,32,58,176,36,32,58,121,90,31,58,9,143,30,58,98,194,29,58,135,244,28,58,122,37,28,58,60,85,27,58,209,131,26,58,58,177,25,58,121,221,24,58,145,8,24,58,131,50,23,58,82,91,22,58,1,131,21,58,145,169,20,58,5,207,19,58,94,243,18,58,160,22,18,58,204,56,17,58,229,89,16,58,236,121,15,58,229,152,14,58,209,182,13,58,179,211,12,58,141,239,11,58,97,10,11,58,50,36,10,58,1,61,9,58,211,84,8,58,167,107,7,58,130,129,6,58,101,150,5,58,82,170,4,58,77,189,3,58,87,207,2,58,114,224,1,58,162,240,0,58,208,255,255,57,142,28,254,57,130,55,252,57,178,80,250,57,34,104,248,57,215,125,246,57,213,145,244,57,34,164,242,57,194,180,240,57,187,195,238,57,17,209,236,57,200,220,234,57,231,230,232,57,114,239,230,57,109,246,228,57,222,251,226,57,201,255,224,57,52,2,223,57,35,3,221,57,155,2,219,57,162,0,217,57,60,253,214,57,110,248,212,57,62,242,210,57,176,234,208,57,201,225,206,57,142,215,204,57,4,204,202,57,49,191,200,57,25,177,198,57,193,161,196,57,46,145,194,57,102,127,192,57,109,108,190,57,73,88,188,57,254,66,186,57,146,44,184,57,9,21,182,57,106,252,179,57,184,226,177,57,249,199,175,57,49,172,173,57,103,143,171,57,159,113,169,57,223,82,167,57,43,51,165,57,136,18,163,57,252,240,160,57,139,206,158,57,59,171,156,57,18,135,154,57,19,98,152,57,68,60,150,57,171,21,148,57,76,238,145,57,45,198,143,57,82,157,141,57,193,115,139,57,128,73,137,57,146,30,135,57,253,242,132,57,199,198,130,57,245,153,128,57,21,217,124,57,29,125,120,57,10,32,116,57,231,193,111,57,191,98,107,57,155,2,103,57,135,161,98,57,139,63,94,57,179,220,89,57,9,121,85,57,150,20,81,57,102,175,76,57,131,73,72,57,246,226,67,57,202,123,63,57,9,20,59,57,190,171,54,57,243,66,50,57,177,217,45,57,4,112,41,57,244,5,37,57,142,155,32,57,218,48,28,57,228,197,23,57,180,90,19,57,86,239,14,57,211,131,10,57,54,24,6,57,136,172,1,57,170,129,250,56,75,170,241,56,9,211,232,56,248,251,223,56,43,37,215,56,183,78,206,56,176,120,197,56,43,163,188,56,59,206,179,56,244,249,170,56,107,38,162,56,179,83,153,56,225,129,144,56,9,177,135,56,123,194,125,56,39,37,108,56,62,138,90,56,231,241,72,56,74,92,55,56,142,201,37,56,219,57,20,56,88,173,2,56,91,72,226,55,3,61,191,55,247,56,156,55,13,121,114,55,254,143,44,55,184,110,205,54,18,191,3,54,183,24,19,182,192,211,212,182,183,250,47,183,24,120,117,183,181,112,157,183,9,27,192,183,187,186,226,183,191,167,2,184,132,236,19,184,134,43,37,184,158,100,54,184,166,151,71,184,122,196,88,184,242,234,105,184,234,10,123,184,30,18,134,184,97,155,142,184,44,33,151,184,108,163,159,184,14,34,168,184,255,156,176,184,46,20,185,184,136,135,193,184,251,246,201,184,115,98,210,184,224,201,218,184,47,45,227,184,77,140,235,184,40,231,243,184,175,61,252,184,232,71,2,185,188,110,6,185,75,147,10,185,139,181,14,185,117,213,18,185,255,242,22,185,33,14,27,185,209,38,31,185,6,61,35,185,186,80,39,185,225,97,43,185,116,112,47,185,107,124,51,185,188,133,55,185,95,140,59,185,75,144,63,185,120,145,67,185,222,143,71,185,116,139,75,185,50,132,79,185,15,122,83,185,3,109,87,185,6,93,91,185,15,74,95,185,22,52,99,185,19,27,103,185,254,254,106,185,207,223,110,185,125,189,114,185,2,152,118,185,83,111,122,185,107,67,126,185,32,10,129,185,229,240,130,185,1,214,132,185,113,185,134,185,47,155,136,185,57,123,138,185,139,89,140,185,31,54,142,185,244,16,144,185,5,234,145,185,77,193,147,185,203,150,149,185,121,106,151,185,84,60,153,185,88,12,155,185,130,218,156,185,205,166,158,185,56,113,160,185,188,57,162,185,88,0,164,185,8,197,165,185,199,135,167,185,147,72,169,185,104,7,171,185,67,196,172,185,31,127,174,185,250,55,176,185,208,238,177,185,157,163,179,185,95,86,181,185,18,7,183,185,179,181,184,185,62,98,186,185,176,12,188,185,5,181,189,185,60,91,191,185,79,255,192,185,60,161,194,185,1,65,196,185,153,222,197,185,1,122,199,185,55,19,201,185,56,170,202,185,255,62,204,185,139,209,205,185,217,97,207,185,228,239,208,185,171,123,210,185,42,5,212,185,94,140,213,185,69,17,215,185,219,147,216,185,30,20,218,185,11,146,219,185,159,13,221,185,215,134,222,185,177,253,223,185,41,114,225,185,62,228,226,185,236,83,228,185,49,193,229,185,9,44,231,185,115,148,232,185,108,250,233,185,242,93,235,185,1,191,236,185,151,29,238,185,178,121,239,185,79,211,240,185,108,42,242,185,6,127,243,185,28,209,244,185,170,32,246,185,174,109,247,185,38,184,248,185,16,0,250,185,105,69,251,185,47,136,252,185,96,200,253,185,250,5,255,185,125,32,0,186,176,188,0,186,147,87,1,186,39,241,1,186,106,137,2,186,91,32,3,186,248,181,3,186,66,74,4,186,55,221,4,186,214,110,5,186,30,255,5,186,15,142,6,186,166,27,7,186,228,167,7,186,200,50,8,186,80,188,8,186,123,68,9,186,74,203,9,186,186,80,10,186,204,212,10,186,126,87,11,186,207,216,11,186,191,88,12,186,77,215,12,186,120,84,13,186,63,208,13,186,162,74,14,186,160,195,14,186,56,59,15,186,106,177,15,186,52,38,16,186,151,153,16,186,145,11,17,186,33,124,17,186,72,235,17,186,4,89,18,186,85,197,18,186,59,48,19,186,180,153,19,186,192,1,20,186,95,104,20,186,144,205,20,186,83,49,21,186,166,147,21,186,138,244,21,186,254,83,22,186,1,178,22,186,148,14,23,186,181,105,23,186,100,195,23,186,161,27,24,186,107,114,24,186,194,199,24,186,166,27,25,186,22,110,25,186,17,191,25,186,152,14,26,186,170,92,26,186,71,169,26,186,111,244,26,186,32,62,27,186,92,134,27,186,33,205,27,186,112,18,28,186,72,86,28,186,169,152,28,186,146,217,28,186,4,25,29,186,255,86,29,186,129,147,29,186,140,206,29,186,31,8,30,186,57,64,30,186,219,118,30,186,4,172,30,186,181,223,30,186,238,17,31,186,173,66,31,186,244,113,31,186,194,159,31,186,23,204,31,186,244,246,31,186,87,32,32,186,66,72,32,186,180,110,32,186,173,147,32,186,46,183,32,186,54,217,32,186,197,249,32,186,220,24,33,186,123,54,33,186,161,82,33,186,80,109,33,186,134,134,33,186,69,158,33,186,140,180,33,186,92,201,33,186,180,220,33,186,149,238,33,186,0,255,33,186,244,13,34,186,114,27,34,186,122,39,34,186,12,50,34,186,40,59,34,186,208,66,34,186,2,73,34,186,192,77,34,186,10,81,34,186,224,82,34,186,67,83,34,186,50,82,34,186,175,79,34,186,185,75,34,186,82,70,34,186,121,63,34,186,47,55,34,186,116,45,34,186,74,34,34,186,176,21,34,186,166,7,34,186,47,248,33,186,72,231,33,186,245,212,33,186,52,193,33,186,7,172,33,186,110,149,33,186,105,125,33,186,250,99,33,186,33,73,33,186,222,44,33,186,50,15,33,186,30,240,32,186,162,207,32,186,192,173,32,186,119,138,32,186,200,101,32,186,180,63,32,186,60,24,32,186,97,239,31,186,35,197,31,186,131,153,31,186,130,108,31,186,32,62,31,186,94,14,31,186,62,221,30,186,191,170,30,186,227,118,30,186,170,65,30,186,22,11,30,186,39,211,29,186,222,153,29,186,60,95,29,186,66,35,29,186,240,229,28,186,73,167,28,186,75,103,28,186,249,37,28,186,83,227,27,186,91,159,27,186,17,90,27,186,118,19,27,186,139,203,26,186,82,130,26,186,202,55,26,186,246,235,25,186,215,158,25,186,108,80,25,186,184,0,25,186,187,175,24,186,118,93,24,186,235,9,24,186,27,181,23,186,6,95,23,186,174,7,23,186,20,175,22,186,57,85,22,186,31,250,21,186,197,157,21,186,46,64,21,186,91,225,20,186,76,129,20,186,3,32,20,186,130,189,19,186,201,89,19,186,217,244,18,186,181,142,18,186,92,39,18,186,209,190,17,186,20,85,17,186,40,234,16,186,12,126,16,186,194,16,16,186,77,162,15,186,172,50,15,186,225,193,14,186,239,79,14,186,213,220,13,186,149,104,13,186,49,243,12,186,170,124,12,186,1,5,12,186,55,140,11,186,79,18,11,186,74,151,10,186,40,27,10,186,235,157,9,186,149,31,9,186,40,160,8,186,163,31,8,186,10,158,7,186,93,27,7,186,158,151,6,186,206,18,6,186,239,140,5,186,3,6,5,186,10,126,4,186,6,245,3,186,249,106,3,186,229,223,2,186,202,83,2,186,170,198,1,186,136,56,1,186,100,169,0,186,63,25,0,186,57,16,255,185,250,235,253,185,195,197,252,185,154,157,251,185,127,115,250,185,120,71,249,185,134,25,248,185,174,233,246,185,242,183,245,185,86,132,244,185,221,78,243,185,138,23,242,185,96,222,240,185,99,163,239,185,150,102,238,185,252,39,237,185,153,231,235,185,112,165,234,185,133,97,233,185,218,27,232,185,116,212,230,185,85,139,229,185,128,64,228,185,251,243,226,185,199,165,225,185,232,85,224,185,98,4,223,185,56,177,221,185,110,92,220,185,6,6,219,185,6,174,217,185,111,84,216,185,70,249,214,185,142,156,213,185,75,62,212,185,128,222,210,185,49,125,209,185,97,26,208,185,20,182,206,185,77,80,205,185,17,233,203,185,99,128,202,185,69,22,201,185,189,170,199,185,205,61,198,185,122,207,196,185,198,95,195,185,182,238,193,185,77,124,192,185,143,8,191,185,127,147,189,185,34,29,188,185,123,165,186,185,141,44,185,185,93,178,183,185,238,54,182,185,67,186,180,185,97,60,179,185,76,189,177,185,7,61,176,185,149,187,174,185,251,56,173,185,60,181,171,185,93,48,170,185,96,170,168,185,74,35,167,185,31,155,165,185,226,17,164,185,151,135,162,185,65,252,160,185,230,111,159,185,136,226,157,185,44,84,156,185,213,196,154,185,134,52,153,185,69,163,151,185,21,17,150,185,249,125,148,185,245,233,146,185,14,85,145,185,71,191,143,185,164,40,142,185,41,145,140,185,218,248,138,185,186,95,137,185,206,197,135,185,26,43,134,185,161,143,132,185,103,243,130,185,112,86,129,185,127,113,127,185,181,52,124,185,137,246,120,185,2,183,117,185,40,118,114,185,3,52,111,185,155,240,107,185,248,171,104,185,33,102,101,185,30,31,98,185,248,214,94,185,181,141,91,185,95,67,88,185,252,247,84,185,148,171,81,185,48,94,78,185,214,15,75,185,144,192,71,185,101,112,68,185,92,31,65,185,126,205,61,185,210,122,58,185,96,39,55,185,48,211,51,185,74,126,48,185,181,40,45,185,122,210,41,185,161,123,38,185,48,36,35,185,48,204,31,185,169,115,28,185,163,26,25,185,37,193,21,185,55,103,18,185,225,12,15,185,43,178,11,185,28,87,8,185,189,251,4,185,21,160,1,185,88,136,252,184,19,208,245,184,106,23,239,184,110,94,232,184,46,165,225,184,186,235,218,184,32,50,212,184,112,120,205,184,187,190,198,184,14,5,192,184,122,75,185,184,14,146,178,184,217,216,171,184,235,31,165,184,83,103,158,184,32,175,151,184,98,247,144,184,40,64,138,184,129,137,131,184,249,166,121,184,83,60,108,184,47,211,94,184,171,107,81,184,230,5,68,184,253,161,54,184,14,64,41,184,57,224,27,184,155,130,14,184,81,39,1,184,246,156,231,183,107,240,204,183,62,73,178,183,171,167,151,183,217,23,122,183,126,236,68,183,185,205,15,183,6,120,181,182,67,223,22,182,122,230,115,53,96,76,136,54,39,254,241,54,157,200,45,55,87,130,98,55,231,149,139,55,70,226,165,55,15,38,192,55,8,97,218,55,247,146,244,55,209,93,7,56,105,109,20,56,37,120,33,56,234,125,46,56,154,126,59,56,26,122,72,56,77,112,85,56,22,97,98,56,90,76,111,56,252,49,124,56,240,136,132,56,246,245,138,56,0,96,145,56,2,199,151,56,237,42,158,56,180,139,164,56,73,233,170,56,158,67,177,56,165,154,183,56,82,238,189,56,149,62,196,56,98,139,202,56,171,212,208,56,99,26,215,56,124,92,221,56,232,154,227,56,156,213,233,56,136,12,240,56,161,63,246,56,216,110,252,56,17,77,1,57,184,96,4,57,91,114,7,57,243,129,10,57,121,143,13,57,233,154,16,57,58,164,19,57,103,171,22,57,105,176,25,57,58,179,28,57,212,179,31,57,48,178,34,57,71,174,37,57,21,168,40,57,147,159,43,57,185,148,46,57,132,135,49,57,235,119,52,57,233,101,55,57,120,81,58,57,147,58,61,57,50,33,64,57,80,5,67,57,231,230,69,57,242,197,72,57,105,162,75,57,72,124,78,57,137,83,81,57,37,40,84,57,23,250,86,57,89,201,89,57,230,149,92,57,184,95,95,57,200,38,98,57,19,235,100,57,145,172,103,57,61,107,106,57,19,39,109,57,12,224,111,57,34,150,114,57,81,73,117,57,147,249,119,57,227,166,122,57,59,81,125,57,150,248,127,57,120,78,129,57,32,159,130,57,66,238,131,57,220,59,133,57,234,135,134,57,106,210,135,57,89,27,137,57,181,98,138,57,123,168,139,57,170,236,140,57,61,47,142,57,52,112,143,57,139,175,144,57,65,237,145,57,82,41,147,57,188,99,148,57,125,156,149,57,147,211,150,57,251,8,152,57,179,60,153,57,185,110,154,57,10,159,155,57,165,205,156,57,134,250,157,57,172,37,159,57,20,79,160,57,188,118,161,57,163,156,162,57,197,192,163,57,33,227,164,57,181,3,166,57,127,34,167,57,124,63,168,57,170,90,169,57,8,116,170,57,147,139,171,57,73,161,172,57,41,181,173,57,48,199,174,57,93,215,175,57,173,229,176,57,30,242,177,57,176,252,178,57,95,5,180,57,42,12,181,57,15,17,182,57,13,20,183,57,33,21,184,57,73,20,185,57,133,17,186,57,210,12,187,57,46,6,188,57,152,253,188,57,15,243,189,57,143,230,190,57,25,216,191,57,169,199,192,57,63,181,193,57,218,160,194,57,118,138,195,57,19,114,196,57,176,87,197,57,75,59,198,57,225,28,199,57,115,252,199,57,254,217,200,57,129,181,201,57,250,142,202,57,105,102,203,57,203,59,204,57,32,15,205,57,102,224,205,57,156,175,206,57,192,124,207,57,210,71,208,57,207,16,209,57,184,215,209,57,137,156,210,57,67,95,211,57,229,31,212,57,108,222,212,57,216,154,213,57,40,85,214,57,91,13,215,57,111,195,215,57,99,119,216,57,56,41,217,57,234,216,217,57,123,134,218,57,232,49,219,57,48,219,219,57,83,130,220,57,80,39,221,57,38,202,221,57,212,106,222,57,89,9,223,57,180,165,223,57,229,63,224,57,234,215,224,57,196,109,225,57,112,1,226,57,239,146,226,57,64,34,227,57,98,175,227,57,85,58,228,57,23,195,228,57,169,73,229,57,9,206,229,57,55,80,230,57,50,208,230,57,250,77,231,57,143,201,231,57,239,66,232,57,27,186,232,57,18,47,233,57,211,161,233,57,94,18,234,57,179,128,234,57,209,236,234,57,184,86,235,57,104,190,235,57,223,35,236,57,31,135,236,57,38,232,236,57,245,70,237,57,139,163,237,57,232,253,237,57,11,86,238,57,245,171,238,57,166,255,238,57,29,81,239,57,90,160,239,57,92,237,239,57,37,56,240,57,180,128,240,57,9,199,240,57,35,11,241,57,4,77,241,57,170,140,241,57,22,202,241,57,73,5,242,57,65,62,242,57,255,116,242,57,132,169,242,57,207,219,242,57,225,11,243,57,186,57,243,57,89,101,243,57,192,142,243,57,238,181,243,57,228,218,243,57,162,253,243,57,40,30,244,57,119,60,244,57,143,88,244,57,112,114,244,57,26,138,244,57,143,159,244,57,207,178,244,57,217,195,244,57,174,210,244,57,80,223,244,57,190,233,244,57,248,241,244,57,1,248,244,57,215,251,244,57,123,253,244,57,239,252,244,57,51,250,244,57,70,245,244,57,43,238,244,57,226,228,244,57,107,217,244,57,199,203,244,57,246,187,244,57,251,169,244,57,212,149,244,57,132,127,244,57,11,103,244,57,105,76,244,57,159,47,244,57,176,16,244,57,154,239,243,57,95,204,243,57,1,167,243,57,127,127,243,57,220,85,243,57,23,42,243,57,50,252,242,57,46,204,242,57,12,154,242,57,205,101,242,57,114,47,242,57,251,246,241,57,107,188,241,57,194,127,241,57,2,65,241,57,43,0,241,57,63,189,240,57,62,120,240,57,43,49,240,57,5,232,239,57,207,156,239,57,138,79,239,57,55,0,239,57,215,174,238,57,108,91,238,57,247,5,238,57,120,174,237,57,243,84,237,57,103,249,236,57,214,155,236,57,67,60,236,57,173,218,235,57,23,119,235,57,129,17,235,57,239,169,234,57,96,64,234,57,214,212,233,57,84,103,233,57,218,247,232,57,106,134,232,57,6,19,232,57,174,157,231,57,102,38,231,57,46,173,230,57,7,50,230,57,245,180,229,57,247,53,229,57,17,181,228,57,67,50,228,57,144,173,227,57,248,38,227,57,127,158,226,57,37,20,226,57,236,135,225,57,214,249,224,57,229,105,224,57,27,216,223,57,121,68,223,57,1,175,222,57,182,23,222,57,153,126,221,57,171,227,220,57,239,70,220,57,104,168,219,57,21,8,219,57,250,101,218,57,25,194,217,57,115,28,217,57,11,117,216,57,226,203,215,57,251,32,215,57,87,116,214,57,248,197,213,57,225,21,213,57,20,100,212,57,146,176,211,57,94,251,210,57,122,68,210,57,231,139,209,57,169,209,208,57,193,21,208,57,50,88,207,57,253,152,206,57,36,216,205,57,171,21,205,57,146,81,204,57,221,139,203,57,141,196,202,57,165,251,201,57,39,49,201,57,22,101,200,57,114,151,199,57,64,200,198,57,129,247,197,57,55,37,197,57,100,81,196,57,12,124,195,57,48,165,194,57,211,204,193,57,247,242,192,57,158,23,192,57,203,58,191,57,129,92,190,57,192,124,189,57,141,155,188,57,234,184,187,57,216,212,186,57,91,239,185,57,116,8,185,57,39,32,184,57,117,54,183,57,98,75,182,57,239,94,181,57,31,113,180,57,246,129,179,57,116,145,178,57,157,159,177,57,116,172,176,57,250,183,175,57,51,194,174,57,33,203,173,57,198,210,172,57,37,217,171,57,65,222,170,57,29,226,169,57,186,228,168,57,28,230,167,57,70,230,166,57,57,229,165,57,248,226,164,57,135,223,163,57,231,218,162,57,28,213,161,57], "i8", ALLOC_NONE, Runtime.GLOBAL_BASE+51200);
/* memory initializer */ allocate([40,206,160,57,14,198,159,57,208,188,158,57,114,178,157,57,245,166,156,57,93,154,155,57,173,140,154,57,230,125,153,57,12,110,152,57,34,93,151,57,42,75,150,57,39,56,149,57,28,36,148,57,11,15,147,57,248,248,145,57,229,225,144,57,212,201,143,57,202,176,142,57,199,150,141,57,208,123,140,57,231,95,139,57,15,67,138,57,75,37,137,57,157,6,136,57,8,231,134,57,144,198,133,57,54,165,132,57,255,130,131,57,236,95,130,57,0,60,129,57,63,23,128,57,87,227,125,57,144,150,123,57,47,72,121,57,57,248,118,57,181,166,116,57,168,83,114,57,24,255,111,57,11,169,109,57,134,81,107,57,144,248,104,57,45,158,102,57,101,66,100,57,60,229,97,57,185,134,95,57,225,38,93,57,187,197,90,57,75,99,88,57,152,255,85,57,168,154,83,57,129,52,81,57,39,205,78,57,162,100,76,57,247,250,73,57,44,144,71,57,71,36,69,57,78,183,66,57,70,73,64,57,53,218,61,57,34,106,59,57,18,249,56,57,11,135,54,57,19,20,52,57,48,160,49,57,103,43,47,57,191,181,44,57,62,63,42,57,233,199,39,57,198,79,37,57,219,214,34,57,47,93,32,57,198,226,29,57,168,103,27,57,217,235,24,57,95,111,22,57,66,242,19,57,134,116,17,57,49,246,14,57,73,119,12,57,212,247,9,57,217,119,7,57,92,247,4,57,100,118,2,57,238,233,255,56,53,230,250,56,168,225,245,56,85,220,240,56,69,214,235,56,134,207,230,56,34,200,225,56,38,192,220,56,157,183,215,56,147,174,210,56,20,165,205,56,43,155,200,56,228,144,195,56,75,134,190,56,107,123,185,56,81,112,180,56,7,101,175,56,153,89,170,56,19,78,165,56,129,66,160,56,238,54,155,56,101,43,150,56,243,31,145,56,163,20,140,56,128,9,135,56,150,254,129,56,225,231,121,56,54,211,111,56,65,191,101,56,26,172,91,56,216,153,81,56,146,136,71,56,93,120,61,56,82,105,51,56,135,91,41,56,18,79,31,56,11,68,21,56,135,58,11,56,158,50,1,56,205,88,238,55,237,79,218,55,201,74,198,55,143,73,178,55,106,76,158,55,137,83,138,55,45,190,108,55,129,222,68,55,101,8,29,55,103,120,234,54,136,244,154,54,187,11,23,54,201,185,244,179,45,42,38,182,1,64,162,182,247,82,241,182,165,38,32,183,38,151,71,183,168,250,110,183,106,40,139,183,169,204,158,183,231,105,178,183,248,255,197,183,177,142,217,183,232,21,237,183,185,74,0,184,147,6,10,184,107,190,19,184,45,114,29,184,196,33,39,184,26,205,48,184,27,116,58,184,177,22,68,184,201,180,77,184,76,78,87,184,39,227,96,184,68,115,106,184,144,254,115,184,245,132,125,184,47,131,131,184,92,65,136,184,248,252,140,184,247,181,145,184,80,108,150,184,249,31,155,184,232,208,159,184,19,127,164,184,111,42,169,184,244,210,173,184,150,120,178,184,76,27,183,184,13,187,187,184,207,87,192,184,135,241,196,184,45,136,201,184,182,27,206,184,25,172,210,184,76,57,215,184,70,195,219,184,253,73,224,184,104,205,228,184,126,77,233,184,52,202,237,184,130,67,242,184,94,185,246,184,192,43,251,184,157,154,255,184,247,2,2,185,211,54,4,185,224,104,6,185,25,153,8,185,121,199,10,185,252,243,12,185,157,30,15,185,87,71,17,185,39,110,19,185,9,147,21,185,246,181,23,185,237,214,25,185,231,245,27,185,224,18,30,185,213,45,32,185,194,70,34,185,161,93,36,185,111,114,38,185,39,133,40,185,198,149,42,185,71,164,44,185,165,176,46,185,222,186,48,185,237,194,50,185,206,200,52,185,124,204,54,185,245,205,56,185,51,205,58,185,51,202,60,185,241,196,62,185,105,189,64,185,152,179,66,185,121,167,68,185,8,153,70,185,66,136,72,185,35,117,74,185,167,95,76,185,203,71,78,185,139,45,80,185,226,16,82,185,207,241,83,185,76,208,85,185,86,172,87,185,234,133,89,185,4,93,91,185,161,49,93,185,189,3,95,185,85,211,96,185,100,160,98,185,233,106,100,185,223,50,102,185,68,248,103,185,19,187,105,185,73,123,107,185,228,56,109,185,223,243,110,185,57,172,112,185,236,97,114,185,248,20,116,185,87,197,117,185,7,115,119,185,6,30,121,185,79,198,122,185,225,107,124,185,183,14,126,185,207,174,127,185,19,166,128,185,93,115,129,185,67,63,130,185,197,9,131,185,224,210,131,185,147,154,132,185,222,96,133,185,190,37,134,185,50,233,134,185,57,171,135,185,210,107,136,185,251,42,137,185,179,232,137,185,249,164,138,185,203,95,139,185,40,25,140,185,15,209,140,185,126,135,141,185,117,60,142,185,242,239,142,185,245,161,143,185,123,82,144,185,132,1,145,185,14,175,145,185,25,91,146,185,163,5,147,185,172,174,147,185,49,86,148,185,51,252,148,185,175,160,149,185,166,67,150,185,22,229,150,185,253,132,151,185,92,35,152,185,48,192,152,185,122,91,153,185,56,245,153,185,105,141,154,185,12,36,155,185,32,185,155,185,165,76,156,185,153,222,156,185,252,110,157,185,205,253,157,185,11,139,158,185,181,22,159,185,202,160,159,185,75,41,160,185,53,176,160,185,136,53,161,185,67,185,161,185,102,59,162,185,241,187,162,185,225,58,163,185,55,184,163,185,242,51,164,185,17,174,164,185,147,38,165,185,121,157,165,185,193,18,166,185,107,134,166,185,119,248,166,185,227,104,167,185,175,215,167,185,219,68,168,185,101,176,168,185,79,26,169,185,151,130,169,185,60,233,169,185,63,78,170,185,158,177,170,185,90,19,171,185,114,115,171,185,229,209,171,185,180,46,172,185,221,137,172,185,97,227,172,185,64,59,173,185,120,145,173,185,10,230,173,185,245,56,174,185,57,138,174,185,214,217,174,185,203,39,175,185,25,116,175,185,191,190,175,185,189,7,176,185,18,79,176,185,192,148,176,185,196,216,176,185,32,27,177,185,212,91,177,185,222,154,177,185,63,216,177,185,248,19,178,185,7,78,178,185,109,134,178,185,42,189,178,185,62,242,178,185,168,37,179,185,106,87,179,185,130,135,179,185,242,181,179,185,184,226,179,185,213,13,180,185,74,55,180,185,22,95,180,185,57,133,180,185,180,169,180,185,135,204,180,185,177,237,180,185,52,13,181,185,14,43,181,185,66,71,181,185,206,97,181,185,179,122,181,185,241,145,181,185,136,167,181,185,121,187,181,185,197,205,181,185,106,222,181,185,107,237,181,185,198,250,181,185,125,6,182,185,144,16,182,185,255,24,182,185,202,31,182,185,242,36,182,185,120,40,182,185,91,42,182,185,157,42,182,185,62,41,182,185,62,38,182,185,157,33,182,185,93,27,182,185,126,19,182,185,0,10,182,185,228,254,181,185,43,242,181,185,212,227,181,185,225,211,181,185,83,194,181,185,41,175,181,185,101,154,181,185,7,132,181,185,16,108,181,185,128,82,181,185,89,55,181,185,155,26,181,185,70,252,180,185,92,220,180,185,220,186,180,185,201,151,180,185,35,115,180,185,233,76,180,185,31,37,180,185,195,251,179,185,215,208,179,185,92,164,179,185,82,118,179,185,187,70,179,185,151,21,179,185,232,226,178,185,174,174,178,185,233,120,178,185,156,65,178,185,199,8,178,185,106,206,177,185,136,146,177,185,32,85,177,185,52,22,177,185,197,213,176,185,212,147,176,185,98,80,176,185,112,11,176,185,254,196,175,185,15,125,175,185,163,51,175,185,188,232,174,185,89,156,174,185,125,78,174,185,41,255,173,185,93,174,173,185,28,92,173,185,101,8,173,185,59,179,172,185,158,92,172,185,144,4,172,185,17,171,171,185,36,80,171,185,201,243,170,185,1,150,170,185,206,54,170,185,50,214,169,185,45,116,169,185,192,16,169,185,237,171,168,185,182,69,168,185,27,222,167,185,30,117,167,185,193,10,167,185,4,159,166,185,233,49,166,185,113,195,165,185,159,83,165,185,114,226,164,185,237,111,164,185,17,252,163,185,223,134,163,185,90,16,163,185,129,152,162,185,88,31,162,185,223,164,161,185,23,41,161,185,3,172,160,185,163,45,160,185,250,173,159,185,9,45,159,185,209,170,158,185,84,39,158,185,147,162,157,185,144,28,157,185,77,149,156,185,203,12,156,185,11,131,155,185,16,248,154,185,219,107,154,185,110,222,153,185,201,79,153,185,240,191,152,185,227,46,152,185,164,156,151,185,53,9,151,185,151,116,150,185,205,222,149,185,215,71,149,185,184,175,148,185,113,22,148,185,4,124,147,185,115,224,146,185,191,67,146,185,234,165,145,185,246,6,145,185,229,102,144,185,184,197,143,185,114,35,143,185,19,128,142,185,159,219,141,185,21,54,141,185,121,143,140,185,205,231,139,185,17,63,139,185,72,149,138,185,116,234,137,185,150,62,137,185,177,145,136,185,197,227,135,185,214,52,135,185,229,132,134,185,244,211,133,185,4,34,133,185,24,111,132,185,49,187,131,185,82,6,131,185,124,80,130,185,177,153,129,185,243,225,128,185,68,41,128,185,78,223,126,185,56,106,125,185,77,243,123,185,143,122,122,185,2,0,121,185,171,131,119,185,142,5,118,185,174,133,116,185,15,4,115,185,182,128,113,185,166,251,111,185,227,116,110,185,114,236,108,185,86,98,107,185,148,214,105,185,47,73,104,185,43,186,102,185,141,41,101,185,89,151,99,185,147,3,98,185,62,110,96,185,95,215,94,185,250,62,93,185,19,165,91,185,174,9,90,185,208,108,88,185,125,206,86,185,184,46,85,185,133,141,83,185,234,234,81,185,234,70,80,185,138,161,78,185,204,250,76,185,183,82,75,185,78,169,73,185,148,254,71,185,144,82,70,185,67,165,68,185,180,246,66,185,229,70,65,185,220,149,63,185,157,227,61,185,43,48,60,185,140,123,58,185,195,197,56,185,212,14,55,185,197,86,53,185,152,157,51,185,83,227,49,185,250,39,48,185,145,107,46,185,29,174,44,185,161,239,42,185,34,48,41,185,164,111,39,185,45,174,37,185,191,235,35,185,95,40,34,185,18,100,32,185,220,158,30,185,194,216,28,185,199,17,27,185,240,73,25,185,65,129,23,185,192,183,21,185,111,237,19,185,83,34,18,185,114,86,16,185,206,137,14,185,109,188,12,185,83,238,10,185,132,31,9,185,5,80,7,185,218,127,5,185,7,175,3,185,144,221,1,185,123,11,0,185,151,113,252,184,11,203,248,184,91,35,245,184,144,122,241,184,179,208,237,184,205,37,234,184,230,121,230,184,7,205,226,184,57,31,223,184,132,112,219,184,241,192,215,184,138,16,212,184,86,95,208,184,95,173,204,184,172,250,200,184,72,71,197,184,58,147,193,184,140,222,189,184,69,41,186,184,111,115,182,184,19,189,178,184,56,6,175,184,233,78,171,184,44,151,167,184,12,223,163,184,145,38,160,184,195,109,156,184,171,180,152,184,82,251,148,184,193,65,145,184,255,135,141,184,22,206,137,184,14,20,134,184,240,89,130,184,137,63,125,184,40,203,117,184,206,86,110,184,140,226,102,184,116,110,95,184,150,250,87,184,4,135,80,184,207,19,73,184,7,161,65,184,189,46,58,184,4,189,50,184,235,75,43,184,131,219,35,184,222,107,28,184,11,253,20,184,29,143,13,184,36,34,6,184,97,108,253,183,167,150,238,183,59,195,223,183,63,242,208,183,212,35,194,183,27,88,179,183,54,143,164,183,70,201,149,183,107,6,135,183,141,141,112,183,244,20,83,183,77,163,53,183,216,56,24,183,175,171,245,182,25,245,186,182,112,78,128,182,111,112,11,182,4,47,179,180,144,3,189,53,137,69,83,54,48,242,163,54,166,46,222,54,211,43,12,55,88,54,41,55,163,54,70,55,116,44,99,55,197,11,128,55,212,123,142,55,71,230,156,55,255,74,171,55,219,169,185,55,190,2,200,55,135,85,214,55,24,162,228,55,81,232,242,55,10,148,0,56,161,176,7,56,222,201,14,56,178,223,21,56,13,242,28,56,224,0,36,56,28,12,43,56,179,19,50,56,148,23,57,56,177,23,64,56,251,19,71,56,99,12,78,56,218,0,85,56,82,241,91,56,187,221,98,56,7,198,105,56,39,170,112,56,12,138,119,56,169,101,126,56,119,158,130,56,231,7,134,56,29,111,137,56,17,212,140,56,189,54,144,56,26,151,147,56,31,245,150,56,200,80,154,56,12,170,157,56,228,0,161,56,74,85,164,56,54,167,167,56,162,246,170,56,134,67,174,56,221,141,177,56,159,213,180,56,197,26,184,56,73,93,187,56,36,157,190,56,79,218,193,56,196,20,197,56,124,76,200,56,112,129,203,56,154,179,206,56,244,226,209,56,119,15,213,56,28,57,216,56,221,95,219,56,180,131,222,56,154,164,225,56,138,194,228,56,124,221,231,56,107,245,234,56,80,10,238,56,37,28,241,56,228,42,244,56,136,54,247,56,9,63,250,56,98,68,253,56,70,35,0,57,194,162,1,57,160,32,3,57,222,156,4,57,122,23,6,57,111,144,7,57,188,7,9,57,94,125,10,57,81,241,11,57,147,99,13,57,33,212,14,57,247,66,16,57,21,176,17,57,118,27,19,57,23,133,20,57,247,236,21,57,19,83,23,57,103,183,24,57,241,25,26,57,175,122,27,57,157,217,28,57,186,54,30,57,2,146,31,57,116,235,32,57,12,67,34,57,200,152,35,57,165,236,36,57,162,62,38,57,187,142,39,57,238,220,40,57,56,41,42,57,152,115,43,57,11,188,44,57,143,2,46,57,32,71,47,57,189,137,48,57,100,202,49,57,18,9,51,57,197,69,52,57,123,128,53,57,49,185,54,57,230,239,55,57,150,36,57,57,65,87,58,57,227,135,59,57,123,182,60,57,7,227,61,57,132,13,63,57,240,53,64,57,74,92,65,57,144,128,66,57,190,162,67,57,212,194,68,57,208,224,69,57,175,252,70,57,112,22,72,57,16,46,73,57,143,67,74,57,233,86,75,57,29,104,76,57,42,119,77,57,14,132,78,57,198,142,79,57,82,151,80,57,175,157,81,57,219,161,82,57,214,163,83,57,157,163,84,57,47,161,85,57,138,156,86,57,172,149,87,57,148,140,88,57,65,129,89,57,177,115,90,57,226,99,91,57,212,81,92,57,131,61,93,57,240,38,94,57,24,14,95,57,251,242,95,57,151,213,96,57,234,181,97,57,243,147,98,57,178,111,99,57,36,73,100,57,72,32,101,57,30,245,101,57,163,199,102,57,215,151,103,57,185,101,104,57,72,49,105,57,130,250,105,57,102,193,106,57,243,133,107,57,40,72,108,57,5,8,109,57,135,197,109,57,175,128,110,57,122,57,111,57,233,239,111,57,251,163,112,57,173,85,113,57,0,5,114,57,243,177,114,57,132,92,115,57,180,4,116,57,129,170,116,57,234,77,117,57,239,238,117,57,143,141,118,57,201,41,119,57,157,195,119,57,10,91,120,57,15,240,120,57,172,130,121,57,224,18,122,57,171,160,122,57,12,44,123,57,2,181,123,57,142,59,124,57,174,191,124,57,98,65,125,57,170,192,125,57,133,61,126,57,244,183,126,57,244,47,127,57,135,165,127,57,86,12,128,57,177,68,128,57,213,123,128,57,194,177,128,57,119,230,128,57,244,25,129,57,58,76,129,57,72,125,129,57,29,173,129,57,187,219,129,57,33,9,130,57,79,53,130,57,69,96,130,57,3,138,130,57,136,178,130,57,214,217,130,57,235,255,130,57,201,36,131,57,111,72,131,57,220,106,131,57,18,140,131,57,16,172,131,57,214,202,131,57,101,232,131,57,188,4,132,57,220,31,132,57,197,57,132,57,118,82,132,57,241,105,132,57,53,128,132,57,66,149,132,57,25,169,132,57,185,187,132,57,36,205,132,57,88,221,132,57,87,236,132,57,33,250,132,57,182,6,133,57,21,18,133,57,64,28,133,57,55,37,133,57,250,44,133,57,137,51,133,57,229,56,133,57,13,61,133,57,3,64,133,57,198,65,133,57,88,66,133,57,183,65,133,57,229,63,133,57,227,60,133,57,176,56,133,57,76,51,133,57,185,44,133,57,247,36,133,57,5,28,133,57,229,17,133,57,152,6,133,57,28,250,132,57,116,236,132,57,159,221,132,57,158,205,132,57,114,188,132,57,26,170,132,57,152,150,132,57,236,129,132,57,22,108,132,57,24,85,132,57,241,60,132,57,163,35,132,57,45,9,132,57,145,237,131,57,206,208,131,57,231,178,131,57,219,147,131,57,170,115,131,57,86,82,131,57,223,47,131,57,71,12,131,57,140,231,130,57,177,193,130,57,182,154,130,57,155,114,130,57,97,73,130,57,10,31,130,57,149,243,129,57,4,199,129,57,87,153,129,57,143,106,129,57,172,58,129,57,176,9,129,57,155,215,128,57,111,164,128,57,43,112,128,57,209,58,128,57,97,4,128,57,186,153,127,57,137,40,127,57,51,181,126,57,184,63,126,57,26,200,125,57,92,78,125,57,126,210,124,57,131,84,124,57,108,212,123,57,60,82,123,57,243,205,122,57,149,71,122,57,34,191,121,57,157,52,121,57,7,168,120,57,98,25,120,57,177,136,119,57,245,245,118,57,49,97,118,57,101,202,117,57,148,49,117,57,193,150,116,57,237,249,115,57,26,91,115,57,74,186,114,57,127,23,114,57,188,114,113,57,2,204,112,57,84,35,112,57,179,120,111,57,34,204,110,57,163,29,110,57,57,109,109,57,228,186,108,57,168,6,108,57,135,80,107,57,131,152,106,57,158,222,105,57,218,34,105,57,58,101,104,57,192,165,103,57,110,228,102,57,71,33,102,57,76,92,101,57,129,149,100,57,232,204,99,57,130,2,99,57,83,54,98,57,93,104,97,57,161,152,96,57,36,199,95,57,230,243,94,57,234,30,94,57,52,72,93,57,197,111,92,57,159,149,91,57,198,185,90,57,60,220,89,57,3,253,88,57,30,28,88,57,143,57,87,57,89,85,86,57,127,111,85,57,3,136,84,57,232,158,83,57,48,180,82,57,221,199,81,57,244,217,80,57,117,234,79,57,101,249,78,57,196,6,78,57,151,18,77,57,224,28,76,57,161,37,75,57,221,44,74,57,152,50,73,57,211,54,72,57,145,57,71,57,213,58,70,57,162,58,69,57,251,56,68,57,226,53,67,57,90,49,66,57,103,43,65,57,10,36,64,57,70,27,63,57,31,17,62,57,151,5,61,57,177,248,59,57,112,234,58,57,215,218,57,57,232,201,56,57,167,183,55,57,22,164,54,57,56,143,53,57,16,121,52,57,162,97,51,57,239,72,50,57,251,46,49,57,201,19,48,57,92,247,46,57,182,217,45,57,219,186,44,57,205,154,43,57,144,121,42,57,39,87,41,57,148,51,40,57,218,14,39,57,253,232,37,57,255,193,36,57,228,153,35,57,174,112,34,57,96,70,33,57,255,26,32,57,139,238,30,57,9,193,29,57,124,146,28,57,231,98,27,57,76,50,26,57,175,0,25,57,19,206,23,57,122,154,22,57,233,101,21,57,98,48,20,57,232,249,18,57,126,194,17,57,39,138,16,57,231,80,15,57,192,22,14,57,182,219,12,57,204,159,11,57,4,99,10,57,99,37,9,57,234,230,7,57,158,167,6,57,129,103,5,57,151,38,4,57,226,228,2,57,102,162,1,57,38,95,0,57,75,54,254,56,205,172,251,56,218,33,249,56,121,149,246,56,174,7,244,56,129,120,241,56,249,231,238,56,26,86,236,56,236,194,233,56,118,46,231,56,188,152,228,56,198,1,226,56,155,105,223,56,64,208,220,56,187,53,218,56,20,154,215,56,80,253,212,56,117,95,210,56,140,192,207,56,152,32,205,56,162,127,202,56,175,221,199,56,197,58,197,56,236,150,194,56,41,242,191,56,131,76,189,56,0,166,186,56,167,254,183,56,125,86,181,56,138,173,178,56,212,3,176,56,96,89,173,56,54,174,170,56,92,2,168,56,216,85,165,56,176,168,162,56,235,250,159,56,144,76,157,56,164,157,154,56,46,238,151,56,53,62,149,56,190,141,146,56,208,220,143,56,113,43,141,56,168,121,138,56,123,199,135,56,241,20,133,56,15,98,130,56,184,93,127,56,189,246,121,56,57,143,116,56,57,39,111,56,201,190,105,56,247,85,100,56,206,236,94,56,92,131,89,56,172,25,84,56,203,175,78,56,199,69,73,56,171,219,67,56,131,113,62,56,93,7,57,56,69,157,51,56,71,51,46,56,111,201,40,56,203,95,35,56,102,246,29,56,77,141,24,56,141,36,19,56,48,188,13,56,69,84,8,56,215,236,2,56,230,11,251,55,73,63,240,55,240,115,229,55,243,169,218,55,108,225,207,55,114,26,197,55,29,85,186,55,135,145,175,55,199,207,164,55,245,15,154,55,41,82,143,55,125,150,132,55,13,186,115,55,190,75,94,55,61,226,72,55,185,125,51,55,97,30,30,55,102,196,8,55,236,223,230,54,132,66,188,54,240,176,145,54,31,87,78,54,2,203,242,53,2,55,18,53,154,189,64,181,57,209,4,182,254,86,89,182,253,223,150,182,187,5,193,182,91,28,235,182,193,145,10,183,106,141,31,183,250,128,52,183,69,108,73,183,28,79,94,183,81,41,115,183,92,253,131,183,146,97,142,183,52,193,152,183,43,28,163,183,96,114,173,183,191,195,183,183,48,16,194,183,157,87,204,183,240,153,214,183,19,215,224,183,241,14,235,183,115,65,245,183,131,110,255,183,6,203,4,184,252,219,9,184,25,234,14,184,83,245,19,184,157,253,24,184,238,2,30,184,60,5,35,184,122,4,40,184,160,0,45,184,163,249,49,184,119,239,54,184,19,226,59,184,108,209,64,184,120,189,69,184,45,166,74,184,128,139,79,184,104,109,84,184,217,75,89,184,202,38,94,184,49,254,98,184,4,210,103,184,57,162,108,184,198,110,113,184,160,55,118,184,191,252,122,184,24,190,127,184,209,61,130,184,169,154,132,184,144,245,134,184,129,78,137,184,119,165,139,184,109,250,141,184,94,77,144,184,71,158,146,184,33,237,148,184,233,57,151,184,154,132,153,184,47,205,155,184,164,19,158,184,244,87,160,184,26,154,162,184,19,218,164,184,216,23,167,184,103,83,169,184,187,140,171,184,207,195,173,184,159,248,175,184,38,43,178,184,96,91,180,184,73,137,182,184,221,180,184,184,23,222,186,184,243,4,189,184,109,41,191,184,129,75,193,184,42,107,195,184,100,136,197,184,44,163,199,184,126,187,201,184,84,209,203,184,172,228,205,184,129,245,207,184,207,3,210,184,147,15,212,184,200,24,214,184,106,31,216,184,119,35,218,184,233,36,220,184,189,35,222,184,240,31,224,184,125,25,226,184,98,16,228,184,153,4,230,184,32,246,231,184,243,228,233,184,15,209,235,184,111,186,237,184,17,161,239,184,241,132,241,184,11,102,243,184,91,68,245,184,224,31,247,184,148,248,248,184,117,206,250,184,128,161,252,184,176,113,254,184,130,31,0,185,188,4,1,185,131,232,1,185,216,202,2,185,183,171,3,185,32,139,4,185,17,105,5,185,137,69,6,185,133,32,7,185,5,250,7,185,7,210,8,185,137,168,9,185,138,125,10,185,9,81,11,185,4,35,12,185,122,243,12,185,105,194,13,185,208,143,14,185,174,91,15,185,1,38,16,185,200,238,16,185,2,182,17,185,173,123,18,185,200,63,19,185,82,2,20,185,74,195,20,185,174,130,21,185,125,64,22,185,182,252,22,185,88,183,23,185,98,112,24,185,210,39,25,185,167,221,25,185,225,145,26,185,125,68,27,185,124,245,27,185,219,164,28,185,154,82,29,185,184,254,29,185,52,169,30,185,13,82,31,185,66,249,31,185,209,158,32,185,186,66,33,185,252,228,33,185,150,133,34,185,135,36,35,185,207,193,35,185,107,93,36,185,92,247,36,185,161,143,37,185,56,38,38,185,34,187,38,185,92,78,39,185,231,223,39,185,193,111,40,185,234,253,40,185,98,138,41,185,38,21,42,185,56,158,42,185,149,37,43,185,62,171,43,185,49,47,44,185,111,177,44,185,246,49,45,185,197,176,45,185,221,45,46,185,61,169,46,185,228,34,47,185,209,154,47,185,4,17,48,185,125,133,48,185,59,248,48,185,61,105,49,185,132,216,49,185,14,70,50,185,220,177,50,185,236,27,51,185,63,132,51,185,212,234,51,185,170,79,52,185,194,178,52,185,27,20,53,185,181,115,53,185,143,209,53,185,169,45,54,185,3,136,54,185,157,224,54,185,118,55,55,185,143,140,55,185,230,223,55,185,124,49,56,185,81,129,56,185,100,207,56,185,182,27,57,185,70,102,57,185,20,175,57,185,32,246,57,185,106,59,58,185,243,126,58,185,184,192,58,185,188,0,59,185,254,62,59,185,126,123,59,185,59,182,59,185,54,239,59,185,112,38,60,185,231,91,60,185,157,143,60,185,145,193,60,185,195,241,60,185,52,32,61,185,227,76,61,185,210,119,61,185,255,160,61,185,107,200,61,185,23,238,61,185,3,18,62,185,47,52,62,185,154,84,62,185,71,115,62,185,51,144,62,185,97,171,62,185,209,196,62,185,130,220,62,185,117,242,62,185,170,6,63,185,35,25,63,185,222,41,63,185,221,56,63,185,32,70,63,185,168,81,63,185,117,91,63,185,135,99,63,185,223,105,63,185,125,110,63,185,99,113,63,185,144,114,63,185,4,114,63,185,194,111,63,185,201,107,63,185,25,102,63,185,180,94,63,185,154,85,63,185,203,74,63,185,73,62,63,185,19,48,63,185,44,32,63,185,146,14,63,185,72,251,62,185,78,230,62,185,164,207,62,185,75,183,62,185,68,157,62,185,144,129,62,185,48,100,62,185,36,69,62,185,109,36,62,185,13,2,62,185,3,222,61,185,81,184,61,185,248,144,61,185,248,103,61,185,83,61,61,185,10,17,61,185,28,227,60,185,140,179,60,185,90,130,60,185,136,79,60,185,21,27,60,185,4,229,59,185,85,173,59,185,10,116,59,185,34,57,59,185,160,252,58,185,133,190,58,185,209,126,58,185,133,61,58,185,163,250,57,185,44,182,57,185,33,112,57,185,132,40,57,185,84,223,56,185,148,148,56,185,69,72,56,185,103,250,55,185,252,170,55,185,6,90,55,185,133,7,55,185,123,179,54,185,233,93,54,185,208,6,54,185,50,174,53,185,16,84,53,185,107,248,52,185,68,155,52,185,157,60,52,185,120,220,51,185,212,122,51,185,181,23,51,185,27,179,50,185,8,77,50,185,124,229,49,185,123,124,49,185,4,18,49,185,25,166,48,185,188,56,48,185,239,201,47,185,178,89,47,185,7,232,46,185,241,116,46,185,111,0,46,185,132,138,45,185,49,19,45,185,120,154,44,185,90,32,44,185,218,164,43,185,247,39,43,185,181,169,42,185,20,42,42,185,23,169,41,185,190,38,41,185,12,163,40,185,1,30,40,185,161,151,39,185,236,15,39,185,228,134,38,185,138,252,37,185,225,112,37,185,234,227,36,185,167,85,36,185,25,198,35,185,67,53,35,185,37,163,34,185,194,15,34,185,27,123,33,185,51,229,32,185,10,78,32,185,163,181,31,185,0,28,31,185,34,129,30,185,11,229,29,185,189,71,29,185,57,169,28,185,130,9,28,185,153,104,27,185,129,198,26,185,58,35,26,185,200,126,25,185,43,217,24,185,102,50,24,185,122,138,23,185,106,225,22,185,55,55,22,185,227,139,21,185,113,223,20,185,225,49,20,185,55,131,19,185,115,211,18,185,152,34,18,185,169,112,17,185,166,189,16,185,145,9,16,185,110,84,15,185,61,158,14,185,1,231,13,185,187,46,13,185,111,117,12,185,29,187,11,185,199,255,10,185,113,67,10,185,27,134,9,185,200,199,8,185,122,8,8,185,51,72,7,185,244,134,6,185,193,196,5,185,155,1,5,185,132,61,4,185,126,120,3,185,140,178,2,185,175,235,1,185,233,35,1,185,62,91,0,185,91,35,255,184,118,142,253,184,209,247,251,184,112,95,250,184,88,197,248,184,140,41,247,184,17,140,245,184,236,236,243,184,31,76,242,184,177,169,240,184,165,5,239,184,255,95,237,184,195,184,235,184,247,15,234,184,159,101,232,184,190,185,230,184,89,12,229,184,117,93,227,184,22,173,225,184,64,251,223,184,247,71,222,184,65,147,220,184,33,221,218,184,156,37,217,184,183,108,215,184,117,178,213,184,219,246,211,184,238,57,210,184,179,123,208,184,44,188,206,184,96,251,204,184,82,57,203,184,7,118,201,184,132,177,199,184,204,235,197,184,229,36,196,184,211,92,194,184,154,147,192,184,63,201,190,184,199,253,188,184,53,49,187,184,143,99,185,184,216,148,183,184,23,197,181,184,78,244,179,184,130,34,178,184,185,79,176,184,246,123,174,184,63,167,172,184,151,209,170,184,3,251,168,184,137,35,167,184,43,75,165,184,239,113,163,184,218,151,161,184,240,188,159,184,53,225,157,184,174,4,156,184,96,39,154,184,80,73,152,184,129,106,150,184,248,138,148,184,186,170,146,184,204,201,144,184,50,232,142,184,240,5,141,184,12,35,139,184,137,63,137,184,108,91,135,184,187,118,133,184,121,145,131,184,170,171,129,184,169,138,127,184,248,188,123,184,74,238,119,184,169,30,116,184,29,78,112,184,175,124,108,184,106,170,104,184,85,215,100,184,122,3,97,184,227,46,93,184,151,89,89,184,161,131,85,184,9,173,81,184,216,213,77,184,24,254,73,184,209,37,70,184,12,77,66,184,211,115,62,184,47,154,58,184,40,192,54,184,200,229,50,184,24,11,47,184,32,48,43,184,234,84,39,184,126,121,35,184,230,157,31,184,42,194,27,184,84,230,23,184,108,10,20,184,124,46,16,184,140,82,12,184,165,118,8,184,208,154,4,184,22,191,0,184,0,199,249,183,45,16,242,183,197,89,234,183,218,163,226,183,125,238,218,183,191,57,211,183,180,133,203,183,107,210,195,183,247,31,188,183,105,110,180,183,210,189,172,183,68,14,165,183,208,95,157,183,136,178,149,183,125,6,142,183,193,91,134,183,199,100,125,183,238,20,110,183,24,200,94,183,105,126,79,183,2,56,64,183,6,245,48,183,150,181,33,183,213,121,18,183,228,65,3,183,203,27,232,182,246,187,201,182,141,100,171,182,210,21,141,182,18,160,93,182,233,38,33,182,95,129,201,181,163,183,33,181,43,135,158,52,110,246,159,53,136,16,12,54,85,16,72,54,13,253,129,54,171,230,159,54,194,196,189,54,17,151,219,54,89,93,249,54,172,139,11,55,102,98,26,55,188,50,41,55,140,252,55,55,184,191,70,55,31,124,85,55,162,49,100,55,33,224,114,55,190,195,128,55,202,19,136,55,37,96,143,55,191,168,150,55,137,237,157,55,115,46,165,55,110,107,172,55,106,164,179,55,88,217,186,55,41,10,194,55,206,54,201,55,55,95,208,55,85,131,215,55,26,163,222,55,118,190,229,55,91,213,236,55,185,231,243,55,130,245,250,55,83,255,0,56,140,129,4,56,101,1,8,56,213,126,11,56,215,249,14,56,98,114,18,56,111,232,21,56,248,91,25,56,245,204,28,56,95,59,32,56,47,167,35,56,95,16,39,56,230,118,42,56,190,218,45,56,225,59,49,56,71,154,52,56,233,245,55,56,193,78,59,56,200,164,62,56,247,247,65,56,71,72,69,56,178,149,72,56,49,224,75,56,189,39,79,56,81,108,82,56,228,173,85,56,114,236,88,56,243,39,92,56,97,96,95,56,181,149,98,56,234,199,101,56,249,246,104,56,219,34,108,56,138,75,111,56,1,113,114,56,56,147,117,56,43,178,120,56,210,205,123,56,40,230,126,56,147,253,128,56,100,134,130,56,131,13,132,56,237,146,133,56,160,22,135,56,152,152,136,56,211,24,138,56,78,151,139,56,6,20,141,56,248,142,142,56,33,8,144,56,127,127,145,56,14,245,146,56,204,104,148,56,183,218,149,56,203,74,151,56,6,185,152,56,101,37,154,56,230,143,155,56,133,248,156,56,65,95,158,56,22,196,159,56,2,39,161,56,3,136,162,56,22,231,163,56,56,68,165,56,104,159,166,56,161,248,167,56,227,79,169,56,43,165,170,56,118,248,171,56,193,73,173,56,11,153,174,56,82,230,175,56,146,49,177,56,202,122,178,56,246,193,179,56,22,7,181,56,39,74,182,56,38,139,183,56,18,202,184,56,232,6,186,56,166,65,187,56,73,122,188,56,209,176,189,56,58,229,190,56,131,23,192,56,170,71,193,56,172,117,194,56,136,161,195,56,60,203,196,56,197,242,197,56,34,24,199,56,81,59,200,56,80,92,201,56,29,123,202,56,182,151,203,56,26,178,204,56,71,202,205,56,58,224,206,56,243,243,207,56,111,5,209,56,173,20,210,56,171,33,211,56,103,44,212,56,224,52,213,56,20,59,214,56,2,63,215,56,167,64,216,56,4,64,217,56,21,61,218,56,217,55,219,56,80,48,220,56,119,38,221,56,77,26,222,56,208,11,223,56,0,251,223,56,219,231,224,56,96,210,225,56,141,186,226,56,96,160,227,56,218,131,228,56,248,100,229,56,185,67,230,56,28,32,231,56,32,250,231,56,196,209,232,56,6,167,233,56,230,121,234,56,98,74,235,56,121,24,236,56,43,228,236,56,118,173,237,56,89,116,238,56,211,56,239,56,228,250,239,56,138,186,240,56,197,119,241,56,147,50,242,56,245,234,242,56,232,160,243,56,108,84,244,56,129,5,245,56,37,180,245,56,88,96,246,56,25,10,247,56,104,177,247,56,68,86,248,56,171,248,248,56,159,152,249,56,29,54,250,56,37,209,250,56,183,105,251,56,211,255,251,56,119,147,252,56,164,36,253,56,88,179,253,56,148,63,254,56,86,201,254,56,159,80,255,56,111,213,255,56,226,43,0,57,207,107,0,57,127,170,0,57,242,231,0,57,39,36,1,57,30,95,1,57,215,152,1,57,82,209,1,57,143,8,2,57,142,62,2,57,79,115,2,57,209,166,2,57,22,217,2,57,28,10,3,57,227,57,3,57,109,104,3,57,184,149,3,57,197,193,3,57,147,236,3,57,35,22,4,57,118,62,4,57,138,101,4,57,96,139,4,57,248,175,4,57,82,211,4,57,111,245,4,57,78,22,5,57,239,53,5,57,83,84,5,57,122,113,5,57,100,141,5,57,17,168,5,57,130,193,5,57,182,217,5,57,174,240,5,57,106,6,6,57,234,26,6,57,46,46,6,57,55,64,6,57,5,81,6,57,153,96,6,57,242,110,6,57,16,124,6,57,245,135,6,57,160,146,6,57,18,156,6,57,75,164,6,57,76,171,6,57,20,177,6,57,165,181,6,57,254,184,6,57,32,187,6,57,12,188,6,57,193,187,6,57,64,186,6,57,138,183,6,57,159,179,6,57,128,174,6,57,44,168,6,57,165,160,6,57,235,151,6,57,255,141,6,57,224,130,6,57,144,118,6,57,15,105,6,57,93,90,6,57,123,74,6,57,106,57,6,57,42,39,6,57,188,19,6,57,32,255,5,57,88,233,5,57,98,210,5,57,65,186,5,57,245,160,5,57,126,134,5,57,221,106,5,57,18,78,5,57,32,48,5,57,5,17,5,57,195,240,4,57,90,207,4,57,203,172,4,57,24,137,4,57,63,100,4,57,67,62,4,57,36,23,4,57,227,238,3,57,129,197,3,57,253,154,3,57,90,111,3,57,152,66,3,57,183,20,3,57,185,229,2,57,157,181,2,57,102,132,2,57,20,82,2,57,168,30,2,57,34,234,1,57,132,180,1,57,206,125,1,57,1,70,1,57,30,13,1,57,39,211,0,57,27,152,0,57,252,91,0,57,202,30,0,57,15,193,255,56,105,66,255,56,163,193,254,56,193,62,254,56,195,185,253,56,172,50,253,56,125,169,252,56,58,30,252,56,227,144,251,56,122,1,251,56,3,112,250,56,126,220,249,56,237,70,249,56,84,175,248,56,179,21,248,56,14,122,247,56,101,220,246,56,188,60,246,56,20,155,245,56,112,247,244,56,209,81,244,56,58,170,243,56,173,0,243,56,45,85,242,56,187,167,241,56,90,248,240,56,12,71,240,56,211,147,239,56,177,222,238,56,170,39,238,56,191,110,237,56,242,179,236,56,70,247,235,56,190,56,235,56,91,120,234,56,32,182,233,56,15,242,232,56,44,44,232,56,119,100,231,56,244,154,230,56,166,207,229,56,141,2,229,56,174,51,228,56,11,99,227,56,165,144,226,56,129,188,225,56,159,230,224,56,3,15,224,56,175,53,223,56,166,90,222,56,234,125,221,56,126,159,220,56,100,191,219,56,160,221,218,56,52,250,217,56,34,21,217,56,108,46,216,56,23,70,215,56,36,92,214,56,150,112,213,56,112,131,212,56,180,148,211,56,102,164,210,56,135,178,209,56,27,191,208,56,36,202,207,56,165,211,206,56,161,219,205,56,27,226,204,56,21,231,203,56,147,234,202,56,150,236,201,56,34,237,200,56,58,236,199,56,225,233,198,56,25,230,197,56,229,224,196,56,71,218,195,56,68,210,194,56,222,200,193,56,23,190,192,56,243,177,191,56,117,164,190,56,158,149,189,56,115,133,188,56,246,115,187,56,43,97,186,56,19,77,185,56,179,55,184,56,12,33,183,56,35,9,182,56,249,239,180,56,146,213,179,56,242,185,178,56,26,157,177,56,14,127,176,56,209,95,175,56,101,63,174,56,207,29,173,56,17,251,171,56,46,215,170,56,41,178,169,56,4,140,168,56,196,100,167,56,108,60,166,56,253,18,165,56,124,232,163,56,235,188,162,56,78,144,161,56,167,98,160,56,250,51,159,56,74,4,158,56,154,211,156,56,236,161,155,56,69,111,154,56,167,59,153,56,21,7,152,56,146,209,150,56,35,155,149,56,200,99,148,56,135,43,147,56,98,242,145,56,92,184,144,56,120,125,143,56,185,65,142,56,35,5,141,56,185,199,139,56,125,137,138,56,116,74,137,56,160,10,136,56,4,202,134,56,164,136,133,56,131,70,132,56,163,3,131,56,9,192,129,56,183,123,128,56,96,109,126,56,240,225,123,56,35,85,121,56,1,199,118,56,143,55,116,56,212,166,113,56,214,20,111,56,156,129,108,56,43,237,105,56,139,87,103,56,194,192,100,56,214,40,98,56,206,143,95,56,175,245,92,56,129,90,90,56,74,190,87,56,16,33,85,56,218,130,82,56,173,227,79,56,145,67,77,56,140,162,74,56,164,0,72,56,223,93,69,56,69,186,66,56,219,21,64,56,168,112,61,56,178,202,58,56,0,36,56,56,152,124,53,56,128,212,50,56,191,43,48,56,91,130,45,56,91,216,42,56,196,45,40,56,158,130,37,56,239,214,34,56,189,42,32,56,14,126,29,56,233,208,26,56,84,35,24,56,85,117,21,56,244,198,18,56,53,24,16,56,32,105,13,56,187,185,10,56,12,10,8,56,26,90,5,56,234,169,2,56,8,243,255,55,217,145,250,55,86,48,245,55,139,206,239,55,133,108,234,55,79,10,229,55,247,167,223,55,137,69,218,55,18,227,212,55,157,128,207,55,56,30,202,55,239,187,196,55,206,89,191,55,226,247,185,55,55,150,180,55,218,52,175,55,214,211,169,55,56,115,164,55,12,19,159,55,96,179,153,55,62,84,148,55,179,245,142,55,204,151,137,55,148,58,132,55,49,188,125,55,200,4,115,55,8,79,104,55,8,155,93,55,224,232,82,55,168,56,72,55,121,138,61,55,106,222,50,55,148,52,40,55,14,141,29,55,240,231,18,55,81,69,8,55,149,74,251,54,229,15,230,54,195,218,208,54,94,171,187,54,228,129,166,54,133,94,145,54,221,130,120,54,160,85,78,54,175,53,36,54,208,70,244,53,76,62,160,53,24,165,24,53,34,119,111,179,150,87,54,181,244,188,174,181,62,23,1,182,216,191,42,182,236,87,84,182,32,223,125,182,141,170,147,182,193,92,168,182,254,5,189,182,24,166,209,182,226,60,230,182,49,202,250,182,236,166,7,183,213,227,17,183,190,27,28,183,146,78,38,183,58,124,48,183,160,164,58,183,176,199,68,183,84,229,78,183,117,253,88,183,0,16,99,183,222,28,109,183,250,35,119,183,159,146,128,183,76,144,133,183,248,138,138,183,153,130,143,183,37,119,148,183,145,104,153,183,212,86,158,183,226,65,163,183,177,41,168,183,56,14,173,183,108,239,177,183,67,205,182,183,179,167,187,183,179,126,192,183,55,82,197,183,54,34,202,183,167,238,206,183,127,183,211,183,181,124,216,183,63,62,221,183,19,252,225,183,40,182,230,183,115,108,235,183,237,30,240,183,138,205,244,183,66,120,249,183,11,31,254,183,238,96,1,184,85,176,3,184,184,253,5,184,16,73,8,184,90,146,10,184,145,217,12,184,176,30,15,184,179,97,17,184,149,162,19,184,82,225,21,184,230,29,24,184,76,88,26,184,128,144,28,184,125,198,30,184,64,250,32,184,195,43,35,184,3,91,37,184,251,135,39,184,167,178,41,184,3,219,43,184,11,1,46,184,187,36,48,184,14,70,50,184,0,101,52,184,142,129,54,184,180,155,56,184,109,179,58,184,181,200,60,184,136,219,62,184,228,235,64,184,194,249,66,184,33,5,69,184,252,13,71,184,79,20,73,184,22,24,75,184,78,25,77,184,243,23,79,184,2,20,81,184,118,13,83,184,76,4,85,184,128,248,86,184,16,234,88,184,246,216,90,184,49,197,92,184,187,174,94,184,147,149,96,184,180,121,98,184,27,91,100,184,197,57,102,184,174,21,104,184,211,238,105,184,49,197,107,184,196,152,109,184,138,105,111,184,127,55,113,184,160,2,115,184,233,202,116,184,89,144,118,184,235,82,120,184,157,18,122,184,107,207,123,184,83,137,125,184,82,64,127,184,50,122,128,184,196,82,129,184,221,41,130,184,123,255,130,184,158,211,131,184,67,166,132,184,106,119,133,184,18,71,134,184,56,21,135,184,219,225,135,184,251,172,136,184,149,118,137,184,170,62,138,184,54,5,139,184,58,202,139,184,180,141,140,184,162,79,141,184,4,16,142,184,217,206,142,184,30,140,143,184,212,71,144,184,248,1,145,184,138,186,145,184,137,113,146,184,244,38,147,184,201,218,147,184,8,141,148,184,175,61,149,184,190,236,149,184,51,154,150,184,13,70,151,184,76,240,151,184,239,152,152,184,244,63,153,184,91,229,153,184,35,137,154,184,75,43,155,184,209,203,155,184,182,106,156,184,249,7,157,184,151,163,157,184,146,61,158,184,231,213,158,184,150,108,159,184,159,1,160,184,1,149,160,184,186,38,161,184,203,182,161,184,50,69,162,184,238,209,162,184,0,93,163,184,103,230,163,184,33,110,164,184,47,244,164,184,143,120,165,184,65,251,165,184,69,124,166,184,154,251,166,184,63,121,167,184,52,245,167,184,120,111,168,184,11,232,168,184,237,94,169,184,29,212,169,184,154,71,170,184,100,185,170,184,123,41,171,184,223,151,171,184,142,4,172,184,137,111,172,184,207,216,172,184,96,64,173,184,60,166,173,184,98,10,174,184,210,108,174,184,140,205,174,184,143,44,175,184,219,137,175,184,113,229,175,184,80,63,176,184,119,151,176,184,231,237,176,184,159,66,177,184,159,149,177,184,232,230,177,184,120,54,178,184,80,132,178,184,113,208,178,184,217,26,179,184,137,99,179,184,128,170,179,184,191,239,179,184,70,51,180,184,21,117,180,184,44,181,180,184,138,243,180,184,48,48,181,184,31,107,181,184,85,164,181,184,212,219,181,184,155,17,182,184,170,69,182,184,2,120,182,184,163,168,182,184,141,215,182,184,192,4,183,184,60,48,183,184,3,90,183,184,19,130,183,184,109,168,183,184,18,205,183,184,2,240,183,184,60,17,184,184,195,48,184,184,148,78,184,184,179,106,184,184,29,133,184,184,213,157,184,184,217,180,184,184,44,202,184,184,204,221,184,184,188,239,184,184,250,255,184,184,136,14,185,184,102,27,185,184,148,38,185,184,19,48,185,184,228,55,185,184,8,62,185,184,125,66,185,184,71,69,185,184,100,70,185,184,213,69,185,184,156,67,185,184,184,63,185,184,43,58,185,184,245,50,185,184,23,42,185,184,145,31,185,184,100,19,185,184,145,5,185,184,25,246,184,184,252,228,184,184,59,210,184,184,215,189,184,184,208,167,184,184,41,144,184,184,224,118,184,184,248,91,184,184,113,63,184,184,75,33,184,184,136,1,184,184,41,224,183,184,46,189,183,184,153,152,183,184,106,114,183,184,163,74,183,184,67,33,183,184,77,246,182,184,193,201,182,184,160,155,182,184,235,107,182,184,164,58,182,184,203,7,182,184,97,211,181,184,103,157,181,184,223,101,181,184,201,44,181,184,39,242,180,184,249,181,180,184,65,120,180,184,0,57,180,184,56,248,179,184,232,181,179,184], "i8", ALLOC_NONE, Runtime.GLOBAL_BASE+61440);
/* memory initializer */ allocate([19,114,179,184,185,44,179,184,220,229,178,184,125,157,178,184,158,83,178,184,62,8,178,184,97,187,177,184,6,109,177,184,47,29,177,184,222,203,176,184,19,121,176,184,209,36,176,184,24,207,175,184,233,119,175,184,71,31,175,184,50,197,174,184,171,105,174,184,181,12,174,184,80,174,173,184,126,78,173,184,64,237,172,184,152,138,172,184,134,38,172,184,13,193,171,184,47,90,171,184,235,241,170,184,68,136,170,184,60,29,170,184,212,176,169,184,12,67,169,184,232,211,168,184,104,99,168,184,141,241,167,184,91,126,167,184,208,9,167,184,241,147,166,184,189,28,166,184,56,164,165,184,97,42,165,184,59,175,164,184,200,50,164,184,8,181,163,184,254,53,163,184,172,181,162,184,18,52,162,184,51,177,161,184,16,45,161,184,171,167,160,184,6,33,160,184,34,153,159,184,1,16,159,184,164,133,158,184,14,250,157,184,64,109,157,184,60,223,156,184,4,80,156,184,153,191,155,184,252,45,155,184,49,155,154,184,56,7,154,184,20,114,153,184,198,219,152,184,80,68,152,184,179,171,151,184,243,17,151,184,15,119,150,184,11,219,149,184,232,61,149,184,168,159,148,184,77,0,148,184,217,95,147,184,76,190,146,184,171,27,146,184,245,119,145,184,46,211,144,184,87,45,144,184,113,134,143,184,128,222,142,184,132,53,142,184,128,139,141,184,118,224,140,184,103,52,140,184,86,135,139,184,68,217,138,184,51,42,138,184,38,122,137,184,30,201,136,184,29,23,136,184,38,100,135,184,58,176,134,184,91,251,133,184,140,69,133,184,205,142,132,184,34,215,131,184,140,30,131,184,14,101,130,184,168,170,129,184,94,239,128,184,50,51,128,184,73,236,126,184,113,112,125,184,225,242,123,184,155,115,122,184,165,242,120,184,2,112,119,184,182,235,117,184,198,101,116,184,54,222,114,184,9,85,113,184,69,202,111,184,237,61,110,184,5,176,108,184,146,32,107,184,152,143,105,184,27,253,103,184,31,105,102,184,169,211,100,184,188,60,99,184,93,164,97,184,145,10,96,184,91,111,94,184,191,210,92,184,195,52,91,184,106,149,89,184,184,244,87,184,178,82,86,184,92,175,84,184,187,10,83,184,210,100,81,184,167,189,79,184,60,21,78,184,152,107,76,184,189,192,74,184,176,20,73,184,118,103,71,184,19,185,69,184,140,9,68,184,228,88,66,184,32,167,64,184,68,244,62,184,85,64,61,184,87,139,59,184,78,213,57,184,63,30,56,184,46,102,54,184,32,173,52,184,25,243,50,184,28,56,49,184,48,124,47,184,87,191,45,184,151,1,44,184,243,66,42,184,113,131,40,184,20,195,38,184,225,1,37,184,221,63,35,184,11,125,33,184,112,185,31,184,17,245,29,184,241,47,28,184,22,106,26,184,131,163,24,184,62,220,22,184,74,20,21,184,171,75,19,184,103,130,17,184,130,184,15,184,255,237,13,184,228,34,12,184,53,87,10,184,246,138,8,184,43,190,6,184,217,240,4,184,5,35,3,184,178,84,1,184,203,11,255,183,70,109,251,183,223,205,247,183,159,45,244,183,141,140,240,183,179,234,236,183,26,72,233,183,203,164,229,183,205,0,226,183,42,92,222,183,234,182,218,183,22,17,215,183,183,106,211,183,214,195,207,183,123,28,204,183,174,116,200,183,121,204,196,183,228,35,193,183,248,122,189,183,189,209,185,183,59,40,182,183,125,126,178,183,138,212,174,183,106,42,171,183,38,128,167,183,200,213,163,183,87,43,160,183,219,128,156,183,95,214,152,183,233,43,149,183,130,129,145,183,52,215,141,183,6,45,138,183,0,131,134,183,45,217,130,183,37,95,126,183,117,12,119,183,90,186,111,183,229,104,104,183,38,24,97,183,47,200,89,183,16,121,82,183,217,42,75,183,156,221,67,183,104,145,60,183,79,70,53,183,96,252,45,183,173,179,38,183,70,108,31,183,58,38,24,183,156,225,16,183,122,158,9,183,229,92,2,183,219,57,246,182,71,189,231,182,47,68,217,182,179,206,202,182,242,92,188,182,13,239,173,182,37,133,159,182,88,31,145,182,199,189,130,182,36,193,104,182,176,15,76,182,114,103,47,182,168,200,18,182,35,103,236,181,218,80,179,181,227,157,116,181,206,195,2,181,98,163,136,179,150,222,192,52,97,198,81,53,240,119,161,53,105,245,217,53,145,45,9,54,80,84,37,54,182,110,65,54,133,124,93,54,129,125,121,54,184,184,138,54,9,172,152,54,152,152,166,54,69,126,180,54,245,92,194,54,136,52,208,54,226,4,222,54,230,205,235,54,117,143,249,54,186,164,3,55,226,125,10,55,37,83,17,55,117,36,24,55,194,241,30,55,255,186,37,55,30,128,44,55,16,65,51,55,200,253,57,55,54,182,64,55,79,106,71,55,2,26,78,55,68,197,84,55,5,108,91,55,56,14,98,55,208,171,104,55,191,68,111,55,248,216,117,55,108,104,124,55,135,121,129,55,106,188,132,55,214,252,135,55,197,58,139,55,50,118,142,55,20,175,145,55,103,229,148,55,34,25,152,55,65,74,155,55,188,120,158,55,142,164,161,55,175,205,164,55,25,244,167,55,199,23,171,55,178,56,174,55,211,86,177,55,38,114,180,55,162,138,183,55,67,160,186,55,3,179,189,55,219,194,192,55,197,207,195,55,187,217,198,55,185,224,201,55,182,228,204,55,175,229,207,55,156,227,210,55,121,222,213,55,63,214,216,55,232,202,219,55,112,188,222,55,209,170,225,55,4,150,228,55,5,126,231,55,205,98,234,55,88,68,237,55,160,34,240,55,159,253,242,55,81,213,245,55,175,169,248,55,181,122,251,55,93,72,254,55,81,137,0,56,192,236,1,56,120,78,3,56,119,174,4,56,186,12,6,56,63,105,7,56,4,196,8,56,5,29,10,56,65,116,11,56,181,201,12,56,95,29,14,56,60,111,15,56,73,191,16,56,133,13,18,56,238,89,19,56,128,164,20,56,58,237,21,56,25,52,23,56,27,121,24,56,62,188,25,56,128,253,26,56,223,60,28,56,87,122,29,56,232,181,30,56,143,239,31,56,74,39,33,56,23,93,34,56,243,144,35,56,221,194,36,56,212,242,37,56,211,32,39,56,219,76,40,56,233,118,41,56,250,158,42,56,13,197,43,56,33,233,44,56,51,11,46,56,65,43,47,56,74,73,48,56,75,101,49,56,67,127,50,56,49,151,51,56,18,173,52,56,229,192,53,56,168,210,54,56,89,226,55,56,247,239,56,56,128,251,57,56,242,4,59,56,76,12,60,56,141,17,61,56,178,20,62,56,186,21,63,56,164,20,64,56,111,17,65,56,24,12,66,56,158,4,67,56,0,251,67,56,61,239,68,56,83,225,69,56,65,209,70,56,5,191,71,56,158,170,72,56,11,148,73,56,74,123,74,56,91,96,75,56,60,67,76,56,236,35,77,56,105,2,78,56,179,222,78,56,201,184,79,56,169,144,80,56,82,102,81,56,195,57,82,56,251,10,83,56,249,217,83,56,188,166,84,56,67,113,85,56,142,57,86,56,154,255,86,56,104,195,87,56,246,132,88,56,68,68,89,56,80,1,90,56,26,188,90,56,161,116,91,56,228,42,92,56,227,222,92,56,156,144,93,56,15,64,94,56,60,237,94,56,33,152,95,56,190,64,96,56,18,231,96,56,29,139,97,56,222,44,98,56,85,204,98,56,128,105,99,56,96,4,100,56,244,156,100,56,60,51,101,56,54,199,101,56,227,88,102,56,67,232,102,56,83,117,103,56,22,0,104,56,137,136,104,56,173,14,105,56,130,146,105,56,7,20,106,56,59,147,106,56,31,16,107,56,179,138,107,56,245,2,108,56,231,120,108,56,136,236,108,56,215,93,109,56,213,204,109,56,129,57,110,56,220,163,110,56,229,11,111,56,157,113,111,56,2,213,111,56,23,54,112,56,217,148,112,56,74,241,112,56,106,75,113,56,56,163,113,56,181,248,113,56,225,75,114,56,188,156,114,56,70,235,114,56,128,55,115,56,105,129,115,56,2,201,115,56,75,14,116,56,69,81,116,56,240,145,116,56,75,208,116,56,88,12,117,56,23,70,117,56,136,125,117,56,171,178,117,56,130,229,117,56,12,22,118,56,74,68,118,56,60,112,118,56,228,153,118,56,64,193,118,56,83,230,118,56,29,9,119,56,157,41,119,56,213,71,119,56,198,99,119,56,112,125,119,56,212,148,119,56,242,169,119,56,203,188,119,56,96,205,119,56,178,219,119,56,193,231,119,56,142,241,119,56,26,249,119,56,102,254,119,56,115,1,120,56,65,2,120,56,209,0,120,56,37,253,119,56,61,247,119,56,27,239,119,56,190,228,119,56,40,216,119,56,91,201,119,56,87,184,119,56,29,165,119,56,174,143,119,56,11,120,119,56,54,94,119,56,47,66,119,56,248,35,119,56,146,3,119,56,253,224,118,56,60,188,118,56,79,149,118,56,55,108,118,56,247,64,118,56,142,19,118,56,254,227,117,56,73,178,117,56,112,126,117,56,117,72,117,56,87,16,117,56,26,214,116,56,190,153,116,56,68,91,116,56,175,26,116,56,255,215,115,56,55,147,115,56,86,76,115,56,96,3,115,56,84,184,114,56,54,107,114,56,7,28,114,56,199,202,113,56,121,119,113,56,29,34,113,56,183,202,112,56,71,113,112,56,207,21,112,56,80,184,111,56,205,88,111,56,71,247,110,56,191,147,110,56,56,46,110,56,179,198,109,56,49,93,109,56,181,241,108,56,65,132,108,56,213,20,108,56,116,163,107,56,32,48,107,56,219,186,106,56,166,67,106,56,131,202,105,56,117,79,105,56,124,210,104,56,155,83,104,56,212,210,103,56,41,80,103,56,156,203,102,56,46,69,102,56,226,188,101,56,185,50,101,56,182,166,100,56,219,24,100,56,41,137,99,56,163,247,98,56,75,100,98,56,35,207,97,56,44,56,97,56,106,159,96,56,222,4,96,56,138,104,95,56,112,202,94,56,147,42,94,56,245,136,93,56,151,229,92,56,125,64,92,56,168,153,91,56,26,241,90,56,214,70,90,56,222,154,89,56,52,237,88,56,218,61,88,56,212,140,87,56,34,218,86,56,199,37,86,56,198,111,85,56,33,184,84,56,219,254,83,56,244,67,83,56,113,135,82,56,83,201,81,56,157,9,81,56,81,72,80,56,113,133,79,56,0,193,78,56,1,251,77,56,117,51,77,56,95,106,76,56,194,159,75,56,160,211,74,56,251,5,74,56,215,54,73,56,53,102,72,56,24,148,71,56,130,192,70,56,118,235,69,56,247,20,69,56,7,61,68,56,169,99,67,56,223,136,66,56,171,172,65,56,17,207,64,56,19,240,63,56,179,15,63,56,244,45,62,56,217,74,61,56,101,102,60,56,153,128,59,56,121,153,58,56,7,177,57,56,69,199,56,56,56,220,55,56,224,239,54,56,65,2,54,56,94,19,53,56,57,35,52,56,213,49,51,56,53,63,50,56,90,75,49,56,73,86,48,56,3,96,47,56,140,104,46,56,230,111,45,56,20,118,44,56,25,123,43,56,247,126,42,56,177,129,41,56,75,131,40,56,198,131,39,56,37,131,38,56,108,129,37,56,157,126,36,56,186,122,35,56,200,117,34,56,200,111,33,56,189,104,32,56,170,96,31,56,146,87,30,56,120,77,29,56,94,66,28,56,72,54,27,56,56,41,26,56,49,27,25,56,54,12,24,56,74,252,22,56,112,235,21,56,170,217,20,56,251,198,19,56,103,179,18,56,239,158,17,56,152,137,16,56,99,115,15,56,85,92,14,56,111,68,13,56,180,43,12,56,40,18,11,56,205,247,9,56,166,220,8,56,183,192,7,56,1,164,6,56,137,134,5,56,80,104,4,56,90,73,3,56,169,41,2,56,65,9,1,56,74,208,255,55,174,140,253,55,180,71,251,55,99,1,249,55,192,185,246,55,209,112,244,55,157,38,242,55,40,219,239,55,121,142,237,55,149,64,235,55,131,241,232,55,72,161,230,55,234,79,228,55,111,253,225,55,221,169,223,55,58,85,221,55,139,255,218,55,215,168,216,55,35,81,214,55,117,248,211,55,212,158,209,55,68,68,207,55,204,232,204,55,113,140,202,55,58,47,200,55,45,209,197,55,78,114,195,55,165,18,193,55,55,178,190,55,9,81,188,55,34,239,185,55,136,140,183,55,63,41,181,55,79,197,178,55,189,96,176,55,143,251,173,55,202,149,171,55,117,47,169,55,149,200,166,55,48,97,164,55,77,249,161,55,239,144,159,55,31,40,157,55,225,190,154,55,58,85,152,55,50,235,149,55,206,128,147,55,19,22,145,55,7,171,142,55,176,63,140,55,20,212,137,55,56,104,135,55,35,252,132,55,218,143,130,55,98,35,128,55,132,109,123,55,254,147,118,55,62,186,113,55,79,224,108,55,60,6,104,55,17,44,99,55,217,81,94,55,160,119,89,55,113,157,84,55,87,195,79,55,93,233,74,55,143,15,70,55,248,53,65,55,162,92,60,55,154,131,55,55,235,170,50,55,159,210,45,55,194,250,40,55,95,35,36,55,128,76,31,55,50,118,26,55,126,160,21,55,113,203,16,55,21,247,11,55,116,35,7,55,155,80,2,55,39,253,250,54,210,90,241,54,76,186,231,54,170,27,222,54,4,127,212,54,109,228,202,54,253,75,193,54,199,181,183,54,226,33,174,54,99,144,164,54,96,1,155,54,237,116,145,54,31,235,135,54,25,200,124,54,148,191,105,54,217,188,86,54,17,192,67,54,103,201,48,54,5,217,29,54,19,239,10,54,119,23,240,53,79,94,202,53,1,179,164,53,188,43,126,53,113,14,51,53,134,29,208,52,172,106,233,51,215,84,54,180,123,112,240,180,32,187,66,181,159,142,134,181,252,174,171,181,86,190,208,181,96,188,245,181,100,84,13,182,161,193,31,182,191,37,50,182,151,128,68,182,2,210,86,182,218,25,105,182,247,87,123,182,26,198,134,182,53,219,143,182,57,235,152,182,19,246,161,182,177,251,170,182,255,251,179,182,236,246,188,182,99,236,197,182,82,220,206,182,168,198,215,182,81,171,224,182,59,138,233,182,84,99,242,182,137,54,251,182,229,1,2,183,129,101,6,183,16,198,10,183,138,35,15,183,229,125,19,183,25,213,23,183,28,41,28,183,230,121,32,183,111,199,36,183,173,17,41,183,152,88,45,183,39,156,49,183,83,220,53,183,17,25,58,183,91,82,62,183,38,136,66,183,108,186,70,183,36,233,74,183,69,20,79,183,200,59,83,183,163,95,87,183,208,127,91,183,69,156,95,183,252,180,99,183,235,201,103,183,11,219,107,183,84,232,111,183,190,241,115,183,66,247,119,183,215,248,123,183,119,246,127,183,12,248,129,183,218,242,131,183,162,235,133,183,95,226,135,183,15,215,137,183,173,201,139,183,53,186,141,183,165,168,143,183,248,148,145,183,43,127,147,183,58,103,149,183,34,77,151,183,223,48,153,183,110,18,155,183,203,241,156,183,242,206,158,183,225,169,160,183,148,130,162,183,8,89,164,183,56,45,166,183,35,255,167,183,196,206,169,183,25,156,171,183,29,103,173,183,207,47,175,183,42,246,176,183,44,186,178,183,209,123,180,183,22,59,182,183,249,247,183,183,117,178,185,183,137,106,187,183,49,32,189,183,107,211,190,183,50,132,192,183,133,50,194,183,96,222,195,183,193,135,197,183,164,46,199,183,8,211,200,183,232,116,202,183,67,20,204,183,21,177,205,183,93,75,207,183,23,227,208,183,64,120,210,183,215,10,212,183,216,154,213,183,64,40,215,183,14,179,216,183,64,59,218,183,209,192,219,183,192,67,221,183,11,196,222,183,175,65,224,183,170,188,225,183,249,52,227,183,154,170,228,183,140,29,230,183,202,141,231,183,84,251,232,183,40,102,234,183,66,206,235,183,162,51,237,183,68,150,238,183,39,246,239,183,72,83,241,183,166,173,242,183,63,5,244,183,16,90,245,183,25,172,246,183,86,251,247,183,198,71,249,183,103,145,250,183,56,216,251,183,54,28,253,183,96,93,254,183,180,155,255,183,152,107,0,184,233,7,1,184,205,162,1,184,67,60,2,184,74,212,2,184,224,106,3,184,6,0,4,184,187,147,4,184,253,37,5,184,205,182,5,184,41,70,6,184,17,212,6,184,132,96,7,184,129,235,7,184,9,117,8,184,25,253,8,184,178,131,9,184,210,8,10,184,122,140,10,184,169,14,11,184,94,143,11,184,152,14,12,184,88,140,12,184,156,8,13,184,101,131,13,184,176,252,13,184,127,116,14,184,209,234,14,184,165,95,15,184,250,210,15,184,209,68,16,184,41,181,16,184,1,36,17,184,90,145,17,184,50,253,17,184,138,103,18,184,97,208,18,184,183,55,19,184,140,157,19,184,222,1,20,184,175,100,20,184,253,197,20,184,201,37,21,184,18,132,21,184,216,224,21,184,27,60,22,184,218,149,22,184,22,238,22,184,206,68,23,184,3,154,23,184,179,237,23,184,223,63,24,184,135,144,24,184,171,223,24,184,74,45,25,184,101,121,25,184,251,195,25,184,13,13,26,184,155,84,26,184,163,154,26,184,40,223,26,184,39,34,27,184,163,99,27,184,154,163,27,184,12,226,27,184,250,30,28,184,100,90,28,184,74,148,28,184,172,204,28,184,138,3,29,184,229,56,29,184,188,108,29,184,15,159,29,184,224,207,29,184,45,255,29,184,248,44,30,184,64,89,30,184,6,132,30,184,74,173,30,184,12,213,30,184,77,251,30,184,13,32,31,184,75,67,31,184,9,101,31,184,71,133,31,184,5,164,31,184,68,193,31,184,3,221,31,184,68,247,31,184,7,16,32,184,75,39,32,184,18,61,32,184,92,81,32,184,41,100,32,184,123,117,32,184,80,133,32,184,171,147,32,184,138,160,32,184,240,171,32,184,220,181,32,184,79,190,32,184,73,197,32,184,204,202,32,184,215,206,32,184,108,209,32,184,138,210,32,184,51,210,32,184,102,208,32,184,38,205,32,184,114,200,32,184,75,194,32,184,177,186,32,184,166,177,32,184,43,167,32,184,63,155,32,184,227,141,32,184,25,127,32,184,225,110,32,184,60,93,32,184,43,74,32,184,174,53,32,184,198,31,32,184,116,8,32,184,185,239,31,184,149,213,31,184,10,186,31,184,24,157,31,184,193,126,31,184,5,95,31,184,228,61,31,184,97,27,31,184,123,247,30,184,52,210,30,184,141,171,30,184,135,131,30,184,34,90,30,184,95,47,30,184,64,3,30,184,198,213,29,184,241,166,29,184,195,118,29,184,60,69,29,184,94,18,29,184,41,222,28,184,159,168,28,184,193,113,28,184,144,57,28,184,12,0,28,184,56,197,27,184,20,137,27,184,160,75,27,184,224,12,27,184,211,204,26,184,122,139,26,184,215,72,26,184,235,4,26,184,184,191,25,184,62,121,25,184,126,49,25,184,122,232,24,184,51,158,24,184,170,82,24,184,224,5,24,184,215,183,23,184,144,104,23,184,13,24,23,184,77,198,22,184,84,115,22,184,33,31,22,184,183,201,21,184,22,115,21,184,64,27,21,184,55,194,20,184,251,103,20,184,142,12,20,184,242,175,19,184,39,82,19,184,47,243,18,184,11,147,18,184,189,49,18,184,71,207,17,184,169,107,17,184,229,6,17,184,253,160,16,184,241,57,16,184,196,209,15,184,118,104,15,184,10,254,14,184,128,146,14,184,218,37,14,184,26,184,13,184,65,73,13,184,81,217,12,184,74,104,12,184,47,246,11,184,2,131,11,184,194,14,11,184,115,153,10,184,22,35,10,184,172,171,9,184,54,51,9,184,183,185,8,184,48,63,8,184,162,195,7,184,16,71,7,184,121,201,6,184,225,74,6,184,73,203,5,184,178,74,5,184,30,201,4,184,143,70,4,184,6,195,3,184,133,62,3,184,14,185,2,184,161,50,2,184,65,171,1,184,240,34,1,184,175,153,0,184,127,15,0,184,199,8,255,183,185,240,253,183,217,214,252,183,42,187,251,183,175,157,250,183,108,126,249,183,99,93,248,183,154,58,247,183,18,22,246,183,208,239,244,183,215,199,243,183,42,158,242,183,204,114,241,183,194,69,240,183,15,23,239,183,182,230,237,183,187,180,236,183,33,129,235,183,235,75,234,183,30,21,233,183,189,220,231,183,203,162,230,183,76,103,229,183,68,42,228,183,182,235,226,183,165,171,225,183,22,106,224,183,11,39,223,183,137,226,221,183,147,156,220,183,45,85,219,183,90,12,218,183,29,194,216,183,124,118,215,183,120,41,214,183,23,219,212,183,91,139,211,183,72,58,210,183,226,231,208,183,45,148,207,183,44,63,206,183,227,232,204,183,85,145,203,183,134,56,202,183,123,222,200,183,54,131,199,183,188,38,198,183,16,201,196,183,54,106,195,183,49,10,194,183,6,169,192,183,184,70,191,183,74,227,189,183,193,126,188,183,33,25,187,183,108,178,185,183,168,74,184,183,214,225,182,183,253,119,181,183,30,13,180,183,62,161,178,183,97,52,177,183,138,198,175,183,189,87,174,183,254,231,172,183,81,119,171,183,186,5,170,183,60,147,168,183,218,31,167,183,154,171,165,183,127,54,164,183,140,192,162,183,197,73,161,183,46,210,159,183,203,89,158,183,159,224,156,183,176,102,155,183,255,235,153,183,145,112,152,183,106,244,150,183,142,119,149,183,0,250,147,183,196,123,146,183,223,252,144,183,83,125,143,183,37,253,141,183,88,124,140,183,240,250,138,183,241,120,137,183,95,246,135,183,62,115,134,183,145,239,132,183,92,107,131,183,163,230,129,183,106,97,128,183,104,183,125,183,11,171,122,183,196,157,119,183,155,143,116,183,151,128,113,183,192,112,110,183,28,96,107,183,181,78,104,183,145,60,101,183,184,41,98,183,50,22,95,183,5,2,92,183,57,237,88,183,215,215,85,183,228,193,82,183,106,171,79,183,111,148,76,183,250,124,73,183,20,101,70,183,196,76,67,183,16,52,64,183,1,27,61,183,158,1,58,183,238,231,54,183,249,205,51,183,199,179,48,183,93,153,45,183,197,126,42,183,4,100,39,183,35,73,36,183,41,46,33,183,29,19,30,183,7,248,26,183,237,220,23,183,216,193,20,183,206,166,17,183,214,139,14,183,248,112,11,183,60,86,8,183,167,59,5,183,66,33,2,183,39,14,254,182,70,218,247,182,239,166,241,182,48,116,235,182,24,66,229,182,180,16,223,182,20,224,216,182,68,176,210,182,84,129,204,182,80,83,198,182,73,38,192,182,74,250,185,182,99,207,179,182,162,165,173,182,19,125,167,182,198,85,161,182,200,47,155,182,39,11,149,182,240,231,142,182,50,198,136,182,249,165,130,182,170,14,121,182,164,212,108,182,252,157,96,182,204,106,84,182,49,59,72,182,69,15,60,182,35,231,47,182,229,194,35,182,167,162,23,182,131,134,11,182,39,221,254,181,230,181,230,181,119,151,206,181,16,130,182,181,228,117,158,181,41,115,134,181,34,244,92,181,161,21,45,181,112,150,250,180,152,42,155,180,35,162,239,179,198,187,140,51,11,27,130,52,248,218,224,52,248,182,31,53,148,233,78,53,237,4,126,53,78,132,150,53,33,250,173,53,188,99,197,53,239,192,220,53,136,17,244,53,171,170,5,54,20,70,17,54,231,218,28,54,12,105,40,54,107,240,51,54,235,112,63,54,118,234,74,54,242,92,86,54,73,200,97,54,99,44,109,54,40,137,120,54,65,239,129,54,44,150,135,54,74,57,141,54,144,216,146,54,241,115,152,54,99,11,158,54,219,158,163,54,76,46,169,54,173,185,174,54,241,64,180,54,13,196,185,54,247,66,191,54,163,189,196,54,7,52,202,54,24,166,207,54,202,19,213,54,20,125,218,54,234,225,223,54,66,66,229,54,18,158,234,54,78,245,239,54,236,71,245,54,226,149,250,54,38,223,255,54,215,145,2,55,183,49,5,55,46,207,7,55,56,106,10,55,208,2,13,55,240,152,15,55,147,44,18,55,182,189,20,55,81,76,23,55,98,216,25,55,227,97,28,55,207,232,30,55,33,109,33,55,214,238,35,55,231,109,38,55,81,234,40,55,15,100,43,55,28,219,45,55,116,79,48,55,17,193,50,55,241,47,53,55,14,156,55,55,99,5,58,55,237,107,60,55,167,207,62,55,141,48,65,55,154,142,67,55,202,233,69,55,25,66,72,55,131,151,74,55,4,234,76,55,151,57,79,55,56,134,81,55,228,207,83,55,150,22,86,55,75,90,88,55,254,154,90,55,171,216,92,55,79,19,95,55,230,74,97,55,108,127,99,55,221,176,101,55,53,223,103,55,113,10,106,55,141,50,108,55,134,87,110,55,88,121,112,55,254,151,114,55,119,179,116,55,190,203,118,55,208,224,120,55,169,242,122,55,70,1,125,55,164,12,127,55,96,138,128,55,202,140,129,55,144,141,130,55,176,140,131,55,40,138,132,55,247,133,133,55,27,128,134,55,147,120,135,55,93,111,136,55,119,100,137,55,224,87,138,55,151,73,139,55,155,57,140,55,233,39,141,55,128,20,142,55,96,255,142,55,134,232,143,55,242,207,144,55,162,181,145,55,148,153,146,55,200,123,147,55,59,92,148,55,238,58,149,55,222,23,150,55,11,243,150,55,115,204,151,55,21,164,152,55,240,121,153,55,3,78,154,55,76,32,155,55,203,240,155,55,126,191,156,55,100,140,157,55,125,87,158,55,199,32,159,55,66,232,159,55,235,173,160,55,195,113,161,55,200,51,162,55,250,243,162,55,87,178,163,55,223,110,164,55,144,41,165,55,106,226,165,55,108,153,166,55,149,78,167,55,229,1,168,55,90,179,168,55,244,98,169,55,178,16,170,55,147,188,170,55,151,102,171,55,189,14,172,55,4,181,172,55,107,89,173,55,242,251,173,55,153,156,174,55,94,59,175,55,65,216,175,55,66,115,176,55,96,12,177,55,154,163,177,55,240,56,178,55,98,204,178,55,238,93,179,55,149,237,179,55,86,123,180,55,48,7,181,55,36,145,181,55,49,25,182,55,86,159,182,55,147,35,183,55,232,165,183,55,85,38,184,55,216,164,184,55,115,33,185,55,36,156,185,55,236,20,186,55,202,139,186,55,190,0,187,55,199,115,187,55,231,228,187,55,27,84,188,55,102,193,188,55,197,44,189,55,58,150,189,55,195,253,189,55,98,99,190,55,22,199,190,55,222,40,191,55,188,136,191,55,174,230,191,55,182,66,192,55,210,156,192,55,4,245,192,55,75,75,193,55,167,159,193,55,25,242,193,55,160,66,194,55,61,145,194,55,239,221,194,55,184,40,195,55,151,113,195,55,141,184,195,55,153,253,195,55,188,64,196,55,247,129,196,55,73,193,196,55,179,254,196,55,53,58,197,55,207,115,197,55,131,171,197,55,80,225,197,55,54,21,198,55,55,71,198,55,82,119,198,55,136,165,198,55,217,209,198,55,70,252,198,55,208,36,199,55,119,75,199,55,58,112,199,55,28,147,199,55,29,180,199,55,60,211,199,55,124,240,199,55,219,11,200,55,92,37,200,55,254,60,200,55,194,82,200,55,169,102,200,55,180,120,200,55,227,136,200,55,55,151,200,55,177,163,200,55,81,174,200,55,25,183,200,55,8,190,200,55,33,195,200,55,99,198,200,55,208,199,200,55,104,199,200,55,45,197,200,55,30,193,200,55,62,187,200,55,140,179,200,55,10,170,200,55,185,158,200,55,154,145,200,55,173,130,200,55,244,113,200,55,112,95,200,55,34,75,200,55,10,53,200,55,42,29,200,55,131,3,200,55,21,232,199,55,227,202,199,55,237,171,199,55,52,139,199,55,185,104,199,55,126,68,199,55,131,30,199,55,202,246,198,55,84,205,198,55,35,162,198,55,54,117,198,55,145,70,198,55,51,22,198,55,30,228,197,55,84,176,197,55,214,122,197,55,164,67,197,55,193,10,197,55,45,208,196,55,234,147,196,55,250,85,196,55,93,22,196,55,21,213,195,55,36,146,195,55,138,77,195,55,74,7,195,55,100,191,194,55,219,117,194,55,175,42,194,55,226,221,193,55,117,143,193,55,107,63,193,55,196,237,192,55,130,154,192,55,166,69,192,55,50,239,191,55,41,151,191,55,138,61,191,55,88,226,190,55,148,133,190,55,64,39,190,55,94,199,189,55,239,101,189,55,244,2,189,55,112,158,188,55,101,56,188,55,210,208,187,55,188,103,187,55,34,253,186,55,7,145,186,55,109,35,186,55,85,180,185,55,192,67,185,55,178,209,184,55,43,94,184,55,45,233,183,55,186,114,183,55,212,250,182,55,124,129,182,55,181,6,182,55,128,138,181,55,223,12,181,55,211,141,180,55,96,13,180,55,133,139,179,55,71,8,179,55,165,131,178,55,162,253,177,55,65,118,177,55,130,237,176,55,105,99,176,55,246,215,175,55,43,75,175,55,11,189,174,55,152,45,174,55,211,156,173,55,191,10,173,55,93,119,172,55,175,226,171,55,184,76,171,55,121,181,170,55,244,28,170,55,44,131,169,55,35,232,168,55,217,75,168,55,82,174,167,55,144,15,167,55,148,111,166,55,97,206,165,55,248,43,165,55,92,136,164,55,143,227,163,55,147,61,163,55,106,150,162,55,22,238,161,55,153,68,161,55,245,153,160,55,45,238,159,55,66,65,159,55,55,147,158,55,14,228,157,55,201,51,157,55,106,130,156,55,244,207,155,55,103,28,155,55,200,103,154,55,23,178,153,55,87,251,152,55,139,67,152,55,180,138,151,55,212,208,150,55,238,21,150,55,4,90,149,55,25,157,148,55,45,223,147,55,69,32,147,55,97,96,146,55,133,159,145,55,177,221,144,55,234,26,144,55,48,87,143,55,135,146,142,55,239,204,141,55,109,6,141,55,1,63,140,55,175,118,139,55,120,173,138,55,95,227,137,55,102,24,137,55,143,76,136,55,220,127,135,55,81,178,134,55,239,227,133,55,184,20,133,55,176,68,132,55,215,115,131,55,49,162,130,55,192,207,129,55,133,252,128,55,132,40,128,55,126,167,126,55,112,252,124,55,227,79,123,55,219,161,121,55,93,242,119,55,110,65,118,55,18,143,116,55,79,219,114,55,41,38,113,55,164,111,111,55,197,183,109,55,145,254,107,55,13,68,106,55,62,136,104,55,39,203,102,55,206,12,101,55,56,77,99,55,105,140,97,55,102,202,95,55,52,7,94,55,215,66,92,55,84,125,90,55,176,182,88,55,240,238,86,55,24,38,85,55,46,92,83,55,53,145,81,55,50,197,79,55,43,248,77,55,36,42,76,55,34,91,74,55,41,139,72,55,63,186,70,55,103,232,68,55,168,21,67,55,4,66,65,55,131,109,63,55,38,152,61,55,245,193,59,55,243,234,57,55,38,19,56,55,145,58,54,55,58,97,52,55,37,135,50,55,87,172,48,55,213,208,46,55,164,244,44,55,200,23,43,55,69,58,41,55,34,92,39,55,98,125,37,55,10,158,35,55,31,190,33,55,165,221,31,55,162,252,29,55,25,27,28,55,16,57,26,55,139,86,24,55,144,115,22,55,34,144,20,55,70,172,18,55,1,200,16,55,88,227,14,55,79,254,12,55,235,24,11,55,49,51,9,55,37,77,7,55,204,102,5,55,42,128,3,55,68,153,1,55,63,100,255,54,128,149,251,54,85,198,247,54,199,246,243,54,223,38,240,54,167,86,236,54,39,134,232,54,105,181,228,54,118,228,224,54,87,19,221,54,21,66,217,54,185,112,213,54,76,159,209,54,215,205,205,54,99,252,201,54,250,42,198,54,164,89,194,54,106,136,190,54,86,183,186,54,111,230,182,54,192,21,179,54,80,69,175,54,41,117,171,54,84,165,167,54,217,213,163,54,194,6,160,54,23,56,156,54,224,105,152,54,39,156,148,54,245,206,144,54,81,2,141,54,70,54,137,54,219,106,133,54,25,160,129,54,16,172,123,54,100,25,116,54,62,136,108,54,175,248,100,54,199,106,93,54,153,222,85,54,53,84,78,54,171,203,70,54,13,69,63,54,108,192,55,54,216,61,48,54,98,189,40,54,27,63,33,54,20,195,25,54,92,73,18,54,4,210,10,54,30,93,3,54,113,213,247,53,201,245,232,53,102,27,218,53,102,70,203,53,235,118,188,53,22,173,173,53,5,233,158,53,217,42,144,53,178,114,129,53,95,129,101,53,227,41,72,53,47,223,42,53,129,161,13,53,51,226,224,52,105,156,166,52,72,228,88,52,117,143,201,51,213,189,113,178,139,197,2,180,89,52,118,180,171,179,180,180,199,46,238,180,132,197,19,181,250,99,48,181,139,242,76,181,252,112,105,181,136,239,130,181,70,30,145,181,155,68,159,181,106,98,173,181,149,119,187,181,0,132,201,181,142,135,215,181,34,130,229,181,159,115,243,181,245,173,0,182,114,157,7,182,58,136,14,182,62,110,21,182,113,79,28,182,196,43,35,182,41,3,42,182,148,213,48,182,246,162,55,182,66,107,62,182,107,46,69,182,98,236,75,182,27,165,82,182,136,88,89,182,156,6,96,182,74,175,102,182,133,82,109,182,64,240,115,182,110,136,122,182,129,141,128,182,248,211,131,182,150,23,135,182,83,88,138,182,43,150,141,182,22,209,144,182,14,9,148,182,14,62,151,182,15,112,154,182,12,159,157,182,253,202,160,182,222,243,163,182,168,25,167,182,85,60,170,182,224,91,173,182,67,120,176,182,119,145,179,182,120,167,182,182,64,186,185,182,200,201,188,182,11,214,191,182,4,223,194,182,173,228,197,182,1,231,200,182,250,229,203,182,147,225,206,182,198,217,209,182,143,206,212,182,231,191,215,182,202,173,218,182,51,152,221,182,27,127,224,182,127,98,227,182,89,66,230,182,164,30,233,182,92,247,235,182,122,204,238,182,251,157,241,182,217,107,244,182,16,54,247,182,154,252,249,182,116,191,252,182,153,126,255,182,1,29,1,183,215,120,2,183,203,210,3,183,219,42,5,183,5,129,6,183,71,213,7,183,159,39,9,183,9,120,10,183,133,198,11,183,16,19,13,183,168,93,14,183,74,166,15,183,246,236,16,183,168,49,18,183,96,116,19,183,26,181,20,183,213,243,21,183,143,48,23,183,70,107,24,183,248,163,25,183,163,218,26,183,70,15,28,183,223,65,29,183,107,114,30,183,233,160,31,183,87,205,32,183,180,247,33,183,254,31,35,183,51,70,36,183,81,106,37,183,88,140,38,183,68,172,39,183,21,202,40,183,201,229,41,183,94,255,42,183,212,22,44,183,39,44,45,183,88,63,46,183,100,80,47,183,74,95,48,183,9,108,49,183,159,118,50,183,11,127,51,183,75,133,52,183,95,137,53,183,68,139,54,183,250,138,55,183,128,136,56,183,211,131,57,183,244,124,58,183,224,115,59,183,151,104,60,183,23,91,61,183,95,75,62,183,111,57,63,183,69,37,64,183,223,14,65,183,62,246,65,183,96,219,66,183,68,190,67,183,233,158,68,183,79,125,69,183,115,89,70,183,86,51,71,183,247,10,72,183,84,224,72,183,109,179,73,183,65,132,74,183,207,82,75,183,23,31,76,183,23,233,76,183,208,176,77,183,64,118,78,183,103,57,79,183,68,250,79,183,214,184,80,183,30,117,81,183,25,47,82,183,201,230,82,183,43,156,83,183,65,79,84,183,8,0,85,183,130,174,85,183,172,90,86,183,136,4,87,183,21,172,87,183,81,81,88,183,61,244,88,183,217,148,89,183,36,51,90,183,30,207,90,183,199,104,91,183,30,0,92,183,35,149,92,183,215,39,93,183,56,184,93,183,71,70,94,183,4,210,94,183,110,91,95,183,133,226,95,183,74,103,96,183,189,233,96,183,220,105,97,183,169,231,97,183,35,99,98,183,75,220,98,183,32,83,99,183,162,199,99,183,210,57,100,183,176,169,100,183,60,23,101,183,118,130,101,183,95,235,101,183,246,81,102,183,60,182,102,183,49,24,103,183,213,119,103,183,41,213,103,183,45,48,104,183,225,136,104,183,70,223,104,183,92,51,105,183,35,133,105,183,156,212,105,183,200,33,106,183,167,108,106,183,57,181,106,183,127,251,106,183,121,63,107,183,40,129,107,183,141,192,107,183,168,253,107,183,121,56,108,183,2,113,108,183,67,167,108,183,61,219,108,183,240,12,109,183,93,60,109,183,133,105,109,183,105,148,109,183,9,189,109,183,102,227,109,183,129,7,110,183,91,41,110,183,245,72,110,183,79,102,110,183,106,129,110,183,72,154,110,183,234,176,110,183,79,197,110,183,122,215,110,183,107,231,110,183,35,245,110,183,163,0,111,183,237,9,111,183,0,17,111,183,224,21,111,183,139,24,111,183,5,25,111,183,77,23,111,183,101,19,111,183,78,13,111,183,9,5,111,183,152,250,110,183,252,237,110,183,53,223,110,183,70,206,110,183,47,187,110,183,242,165,110,183,144,142,110,183,10,117,110,183,99,89,110,183,154,59,110,183,178,27,110,183,172,249,109,183,137,213,109,183,75,175,109,183,243,134,109,183,131,92,109,183,252,47,109,183,96,1,109,183,176,208,108,183,238,157,108,183,27,105,108,183,57,50,108,183,73,249,107,183,77,190,107,183,71,129,107,183,57,66,107,183,35,1,107,183,8,190,106,183,233,120,106,183,200,49,106,183,167,232,105,183,135,157,105,183,107,80,105,183,83,1,105,183,66,176,104,183,57,93,104,183,59,8,104,183,73,177,103,183,100,88,103,183,143,253,102,183,204,160,102,183,29,66,102,183,131,225,101,183,0,127,101,183,150,26,101,183,71,180,100,183,21,76,100,183,3,226,99,183,17,118,99,183,66,8,99,183,152,152,98,183,21,39,98,183,187,179,97,183,140,62,97,183,139,199,96,183,184,78,96,183,22,212,95,183,168,87,95,183,111,217,94,183,110,89,94,183,166,215,93,183,26,84,93,183,204,206,92,183,190,71,92,183,243,190,91,183,108,52,91,183,43,168,90,183,51,26,90,183,134,138,89,183,39,249,88,183,23,102,88,183,89,209,87,183,239,58,87,183,219,162,86,183,32,9,86,183,192,109,85,183,189,208,84,183,26,50,84,183,217,145,83,183,252,239,82,183,133,76,82,183,119,167,81,183,213,0,81,183,161,88,80,183,220,174,79,183,138,3,79,183,173,86,78,183,71,168,77,183,91,248,76,183,235,70,76,183,250,147,75,183,138,223,74,183,158,41,74,183,55,114,73,183,89,185,72,183,6,255,71,183,65,67,71,183,12,134,70,183,105,199,69,183,91,7,69,183,229,69,68,183,9,131,67,183,202,190,66,183,42,249,65,183,44,50,65,183,211,105,64,183,32,160,63,183,23,213,62,183,186,8,62,183,12,59,61,183,16,108,60,183,199,155,59,183,53,202,58,183,92,247,57,183,64,35,57,183,225,77,56,183,68,119,55,183,107,159,54,183,89,198,53,183,15,236,52,183,146,16,52,183,226,51,51,183,5,86,50,183,251,118,49,183,200,150,48,183,110,181,47,183,240,210,46,183,81,239,45,183,147,10,45,183,185,36,44,183,199,61,43,183,190,85,42,183,161,108,41,183,116,130,40,183,56,151,39,183,241,170,38,183,162,189,37,183,76,207,36,183,244,223,35,183,155,239,34,183,69,254,33,183,243,11,33,183,170,24,32,183,108,36,31,183,59,47,30,183,26,57,29,183,13,66,28,183,21,74,27,183,54,81,26,183,115,87,25,183,206,92,24,183,74,97,23,183,234,100,22,183,177,103,21,183,161,105,20,183,189,106,19,183,9,107,18,183,135,106,17,183,57,105,16,183,35,103,15,183,72,100,14,183,170,96,13,183,76,92,12,183,48,87,11,183,91,81,10,183,206,74,9,183,140,67,8,183,153,59,7,183,247,50,6,183,168,41,5,183,177,31,4,183,18,21,3,183,209,9,2,183,238,253,0,183,218,226,255,182,163,200,253,182,58,173,251,182,167,144,249,182,239,114,247,182,22,84,245,182,36,52,243,182,29,19,241,182,8,241,238,182,234,205,236,182,200,169,234,182,168,132,232,182,145,94,230,182,135,55,228,182,144,15,226,182,178,230,223,182,242,188,221,182,86,146,219,182,229,102,217,182,162,58,215,182,148,13,213,182,193,223,210,182,46,177,208,182,225,129,206,182,224,81,204,182,47,33,202,182,212,239,199,182,214,189,197,182,58,139,195,182,5,88,193,182,61,36,191,182,231,239,188,182,9,187,186,182,169,133,184,182,203,79,182,182,119,25,180,182,176,226,177,182,125,171,175,182,227,115,173,182,232,59,171,182,145,3,169,182,228,202,166,182,229,145,164,182,156,88,162,182,12,31,160,182,60,229,157,182,49,171,155,182,240,112,153,182,127,54,151,182,227,251,148,182,34,193,146,182,65,134,144,182,69,75,142,182,52,16,140,182,19,213,137,182,232,153,135,182,183,94,133,182,135,35,131,182,92,232,128,182,120,90,125,182,88,228,120,182,99,110,116,182,163,248,111,182,35,131,107,182,237,13,103,182,12,153,98,182,138,36,94,182,115,176,89,182,207,60,85,182,169,201,80,182,13,87,76,182,3,229,71,182,151,115,67,182,210,2,63,182,192,146,58,182,105,35,54,182,217,180,49,182,26,71,45,182,52,218,40,182,52,110,36,182,34,3,32,182,9,153,27,182,243,47,23,182,233,199,18,182,246,96,14,182,36,251,9,182,124,150,5,182,8,51,1,182,165,161,249,181,202,223,240,181,146,32,232,181,16,100,223,181,89,170,214,181,126,243,205,181,149,63,197,181,175,142,188,181,224,224,179,181,58,54,171,181,210,142,162,181,185,234,153,181,2,74,145,181,193,172,136,181,7,19,128,181,208,249,110,181,236,212,93,181,134,183,76,181,196,161,59,181,202,147,42,181,188,141,25,181,191,143,8,181,238,51,239,180,16,89,205,180,42,143,171,180,134,214,137,180,211,94,80,180,54,52,13,180,132,91,148,179,132,128,233,177,228,193,109,51,85,14,252,51,134,119,64,52,129,96,129,52,139,113,162,52,157,110,195,52,115,87,228,52,229,149,2,53,175,245,18,53,246,74,35,53,154,149,51,53,121,213,67,53,115,10,84,53,102,52,100,53,50,83,116,53,92,51,130,53,106,55,138,53,182,53,146,53,45,46,154,53,194,32,162,53,99,13,170,53,2,244,177,53,142,212,185,53,249,174,193,53,50,131,201,53,44,81,209,53,213,24,217,53,33,218,224,53,254,148,232,53,95,73,240,53,53,247,247,53,113,158,255,53,130,159,3,54,112,108,7,54,252,53,11,54,29,252,14,54,205,190,18,54,5,126,22,54,190,57,26,54,241,241,29,54,151,166,33,54,169,87,37,54,32,5,41,54,245,174,44,54,35,85,48,54,161,247,51,54,106,150,55,54,118,49,59,54,192,200,62,54,65,92,66,54,241,235,69,54,204,119,73,54,203,255,76,54,231,131,80,54,26,4,84,54,94,128,87,54,173,248,90,54,0,109,94,54,82,221,97,54,157,73,101,54,219,177,104,54,5,22,108,54,23,118,111,54,10,210,114,54,216,41,118,54,125,125,121,54,241,204,124,54,24,12,128,54,155,175,129,54,253,80,131,54,60,240,132,54,86,141,134,54,71,40,136,54,14,193,137,54,167,87,139,54,16,236,140,54,71,126,142,54,73,14,144,54,19,156,145,54,163,39,147,54,247,176,148,54,11,56,150,54,223,188,151,54,111,63,153,54,184,191,154,54,186,61,156,54,113,185,157,54,219,50,159,54,246,169,160,54,192,30,162,54,54,145,163,54,86,1,165,54,31,111,166,54,142,218,167,54,161,67,169,54,86,170,170,54,172,14,172,54,159,112,173,54,46,208,174,54,88,45,176,54,25,136,177,54,113,224,178,54,94,54,180,54,221,137,181,54,237,218,182,54,140,41,184,54,184,117,185,54,112,191,186,54,178,6,188,54,125,75,189,54,206,141,190,54,163,205,191,54,253,10,193,54,216,69,194,54,51,126,195,54,13,180,196,54,101,231,197,54,57,24,199,54,135,70,200,54,78,114,201,54,140,155,202,54,65,194,203,54,108,230,204,54,9,8,206,54,26,39,207,54,156,67,208,54,141,93,209,54,238,116,210,54,188,137,211,54,247,155,212,54,157,171,213,54,174,184,214,54,40,195,215,54,11,203,216,54,85,208,217,54,5,211,218,54,27,211,219,54,149,208,220,54,115,203,221,54,180,195,222,54,86,185,223,54,90,172,224,54,190,156,225,54,130,138,226,54,165,117,227,54,38,94,228,54,4,68,229,54,64,39,230,54,215,7,231,54,203,229,231,54,25,193,232,54,194,153,233,54,197,111,234,54,34,67,235,54,216,19,236,54,230,225,236,54,77,173,237,54,11,118,238,54,33,60,239,54,142,255,239,54,82,192,240,54,109,126,241,54,222,57,242,54,165,242,242,54,194,168,243,54,53,92,244,54,253,12,245,54,27,187,245,54,142,102,246,54,87,15,247,54,116,181,247,54,231,88,248,54,176,249,248,54,205,151,249,54,64,51,250,54,8,204,250,54,37,98,251,54], "i8", ALLOC_NONE, Runtime.GLOBAL_BASE+71680);
/* memory initializer */ allocate([153,245,251,54,98,134,252,54,129,20,253,54,246,159,253,54,193,40,254,54,227,174,254,54,92,50,255,54,44,179,255,54,170,24,0,55,106,86,0,55,214,146,0,55,238,205,0,55,179,7,1,55,37,64,1,55,67,119,1,55,15,173,1,55,136,225,1,55,176,20,2,55,133,70,2,55,8,119,2,55,59,166,2,55,28,212,2,55,173,0,3,55,237,43,3,55,221,85,3,55,126,126,3,55,208,165,3,55,210,203,3,55,135,240,3,55,237,19,4,55,5,54,4,55,209,86,4,55,80,118,4,55,130,148,4,55,105,177,4,55,4,205,4,55,85,231,4,55,91,0,5,55,24,24,5,55,139,46,5,55,182,67,5,55,152,87,5,55,51,106,5,55,135,123,5,55,148,139,5,55,92,154,5,55,222,167,5,55,27,180,5,55,21,191,5,55,203,200,5,55,62,209,5,55,112,216,5,55,96,222,5,55,15,227,5,55,126,230,5,55,173,232,5,55,158,233,5,55,82,233,5,55,199,231,5,55,1,229,5,55,255,224,5,55,193,219,5,55,74,213,5,55,153,205,5,55,176,196,5,55,142,186,5,55,54,175,5,55,168,162,5,55,228,148,5,55,235,133,5,55,191,117,5,55,96,100,5,55,207,81,5,55,13,62,5,55,27,41,5,55,250,18,5,55,170,251,4,55,45,227,4,55,131,201,4,55,173,174,4,55,173,146,4,55,131,117,4,55,48,87,4,55,181,55,4,55,20,23,4,55,76,245,3,55,96,210,3,55,80,174,3,55,29,137,3,55,200,98,3,55,83,59,3,55,189,18,3,55,9,233,2,55,55,190,2,55,73,146,2,55,63,101,2,55,26,55,2,55,221,7,2,55,134,215,1,55,25,166,1,55,150,115,1,55,253,63,1,55,81,11,1,55,146,213,0,55,193,158,0,55,225,102,0,55,240,45,0,55,228,231,255,54,205,113,255,54,159,249,254,54,92,127,254,54,6,3,254,54,160,132,253,54,43,4,253,54,172,129,252,54,35,253,251,54,147,118,251,54,255,237,250,54,106,99,250,54,213,214,249,54,68,72,249,54,184,183,248,54,53,37,248,54,189,144,247,54,82,250,246,54,248,97,246,54,176,199,245,54,125,43,245,54,98,141,244,54,98,237,243,54,126,75,243,54,187,167,242,54,25,2,242,54,157,90,241,54,73,177,240,54,31,6,240,54,34,89,239,54,85,170,238,54,187,249,237,54,86,71,237,54,42,147,236,54,56,221,235,54,131,37,235,54,15,108,234,54,223,176,233,54,244,243,232,54,82,53,232,54,252,116,231,54,244,178,230,54,61,239,229,54,219,41,229,54,208,98,228,54,30,154,227,54,201,207,226,54,212,3,226,54,66,54,225,54,21,103,224,54,80,150,223,54,247,195,222,54,11,240,221,54,145,26,221,54,139,67,220,54,252,106,219,54,232,144,218,54,80,181,217,54,56,216,216,54,163,249,215,54,148,25,215,54,13,56,214,54,19,85,213,54,168,112,212,54,207,138,211,54,139,163,210,54,222,186,209,54,205,208,208,54,90,229,207,54,136,248,206,54,91,10,206,54,213,26,205,54,249,41,204,54,202,55,203,54,77,68,202,54,130,79,201,54,111,89,200,54,21,98,199,54,120,105,198,54,155,111,197,54,130,116,196,54,46,120,195,54,164,122,194,54,231,123,193,54,249,123,192,54,222,122,191,54,153,120,190,54,44,117,189,54,156,112,188,54,236,106,187,54,30,100,186,54,53,92,185,54,53,83,184,54,34,73,183,54,253,61,182,54,203,49,181,54,142,36,180,54,74,22,179,54,1,7,178,54,184,246,176,54,113,229,175,54,47,211,174,54,245,191,173,54,200,171,172,54,169,150,171,54,157,128,170,54,165,105,169,54,199,81,168,54,4,57,167,54,95,31,166,54,221,4,165,54,128,233,163,54,76,205,162,54,67,176,161,54,106,146,160,54,194,115,159,54,79,84,158,54,21,52,157,54,23,19,156,54,87,241,154,54,218,206,153,54,162,171,152,54,178,135,151,54,14,99,150,54,184,61,149,54,181,23,148,54,7,241,146,54,178,201,145,54,184,161,144,54,28,121,143,54,227,79,142,54,15,38,141,54,164,251,139,54,164,208,138,54,18,165,137,54,243,120,136,54,73,76,135,54,23,31,134,54,96,241,132,54,40,195,131,54,114,148,130,54,65,101,129,54,151,53,128,54,243,10,126,54,212,169,123,54,217,71,121,54,6,229,118,54,100,129,116,54,247,28,114,54,199,183,111,54,218,81,109,54,53,235,106,54,224,131,104,54,223,27,102,54,59,179,99,54,248,73,97,54,29,224,94,54,177,117,92,54,185,10,90,54,59,159,87,54,63,51,85,54,201,198,82,54,225,89,80,54,140,236,77,54,208,126,75,54,180,16,73,54,62,162,70,54,116,51,68,54,91,196,65,54,251,84,63,54,89,229,60,54,123,117,58,54,103,5,56,54,35,149,53,54,182,36,51,54,37,180,48,54,118,67,46,54,176,210,43,54,215,97,41,54,243,240,38,54,10,128,36,54,32,15,34,54,61,158,31,54,101,45,29,54,159,188,26,54,241,75,24,54,96,219,21,54,243,106,19,54,175,250,16,54,154,138,14,54,186,26,12,54,21,171,9,54,176,59,7,54,145,204,4,54,190,93,2,54,122,222,255,53,38,2,251,53,140,38,246,53,183,75,241,53,179,113,236,53,139,152,231,53,74,192,226,53,252,232,221,53,170,18,217,53,98,61,212,53,45,105,207,53,23,150,202,53,43,196,197,53,115,243,192,53,251,35,188,53,206,85,183,53,246,136,178,53,126,189,173,53,113,243,168,53,219,42,164,53,196,99,159,53,57,158,154,53,68,218,149,53,238,23,145,53,68,87,140,53,79,152,135,53,26,219,130,53,95,63,124,53,51,204,114,53,197,92,105,53,41,241,95,53,116,137,86,53,186,37,77,53,17,198,67,53,139,106,58,53,61,19,49,53,59,192,39,53,154,113,30,53,108,39,21,53,198,225,11,53,188,160,2,53,192,200,242,52,142,89,224,52,7,244,205,52,83,152,187,52,152,70,169,52,252,254,150,52,165,193,132,52,115,29,101,52,188,204,64,52,114,145,28,52,191,215,240,51,155,184,168,51,28,140,65,51,72,5,72,50,4,92,186,178,190,0,108,179,225,58,189,179,251,34,2,180,137,144,37,180,210,229,72,180,144,34,108,180,62,163,135,180,169,40,153,180,102,161,170,180,82,13,188,180,75,108,205,180,47,190,222,180,221,2,240,180,25,157,0,181,6,50,9,181,38,192,17,181,104,71,26,181,188,199,34,181,16,65,43,181,86,179,51,181,125,30,60,181,117,130,68,181,46,223,76,181,152,52,85,181,164,130,93,181,66,201,101,181,100,8,110,181,248,63,118,181,241,111,126,181,32,76,131,181,106,92,135,181,208,104,139,181,74,113,143,181,209,117,147,181,94,118,151,181,234,114,155,181,109,107,159,181,224,95,163,181,60,80,167,181,123,60,171,181,148,36,175,181,131,8,179,181,63,232,182,181,193,195,186,181,4,155,190,181,1,110,194,181,176,60,198,181,12,7,202,181,14,205,205,181,175,142,209,181,233,75,213,181,183,4,217,181,17,185,220,181,242,104,224,181,83,20,228,181,46,187,231,181,125,93,235,181,59,251,238,181,98,148,242,181,235,40,246,181,209,184,249,181,14,68,253,181,78,101,0,182,60,38,2,182,204,228,3,182,253,160,5,182,204,90,7,182,54,18,9,182,57,199,10,182,209,121,12,182,252,41,14,182,184,215,15,182,3,131,17,182,217,43,19,182,56,210,20,182,30,118,22,182,137,23,24,182,117,182,25,182,225,82,27,182,203,236,28,182,47,132,30,182,12,25,32,182,96,171,33,182,40,59,35,182,99,200,36,182,13,83,38,182,38,219,39,182,170,96,41,182,152,227,42,182,238,99,44,182,170,225,45,182,202,92,47,182,76,213,48,182,46,75,50,182,110,190,51,182,10,47,53,182,1,157,54,182,81,8,56,182,248,112,57,182,244,214,58,182,68,58,60,182,230,154,61,182,217,248,62,182,26,84,64,182,168,172,65,182,130,2,67,182,167,85,68,182,20,166,69,182,200,243,70,182,194,62,72,182,1,135,73,182,131,204,74,182,71,15,76,182,75,79,77,182,142,140,78,182,16,199,79,182,206,254,80,182,200,51,82,182,253,101,83,182,107,149,84,182,17,194,85,182,239,235,86,182,2,19,88,182,76,55,89,182,201,88,90,182,122,119,91,182,93,147,92,182,114,172,93,182,183,194,94,182,44,214,95,182,209,230,96,182,164,244,97,182,164,255,98,182,210,7,100,182,44,13,101,182,177,15,102,182,97,15,103,182,60,12,104,182,65,6,105,182,111,253,105,182,198,241,106,182,69,227,107,182,236,209,108,182,187,189,109,182,177,166,110,182,206,140,111,182,17,112,112,182,122,80,113,182,10,46,114,182,191,8,115,182,153,224,115,182,153,181,116,182,190,135,117,182,8,87,118,182,119,35,119,182,11,237,119,182,195,179,120,182,160,119,121,182,163,56,122,182,202,246,122,182,22,178,123,182,135,106,124,182,29,32,125,182,216,210,125,182,185,130,126,182,192,47,127,182,236,217,127,182,159,64,128,182,220,146,128,182,172,227,128,182,16,51,129,182,7,129,129,182,146,205,129,182,178,24,130,182,102,98,130,182,174,170,130,182,139,241,130,182,254,54,131,182,6,123,131,182,163,189,131,182,215,254,131,182,161,62,132,182,2,125,132,182,249,185,132,182,136,245,132,182,175,47,133,182,110,104,133,182,197,159,133,182,182,213,133,182,63,10,134,182,99,61,134,182,32,111,134,182,121,159,134,182,108,206,134,182,251,251,134,182,39,40,135,182,238,82,135,182,83,124,135,182,86,164,135,182,247,202,135,182,54,240,135,182,21,20,136,182,148,54,136,182,180,87,136,182,116,119,136,182,215,149,136,182,219,178,136,182,131,206,136,182,206,232,136,182,190,1,137,182,82,25,137,182,141,47,137,182,109,68,137,182,245,87,137,182,37,106,137,182,253,122,137,182,126,138,137,182,169,152,137,182,127,165,137,182,1,177,137,182,47,187,137,182,10,196,137,182,147,203,137,182,203,209,137,182,179,214,137,182,75,218,137,182,148,220,137,182,143,221,137,182,62,221,137,182,160,219,137,182,183,216,137,182,132,212,137,182,8,207,137,182,67,200,137,182,54,192,137,182,227,182,137,182,74,172,137,182,109,160,137,182,76,147,137,182,232,132,137,182,67,117,137,182,93,100,137,182,55,82,137,182,210,62,137,182,48,42,137,182,82,20,137,182,55,253,136,182,227,228,136,182,84,203,136,182,142,176,136,182,144,148,136,182,92,119,136,182,243,88,136,182,87,57,136,182,135,24,136,182,134,246,135,182,84,211,135,182,243,174,135,182,99,137,135,182,167,98,135,182,191,58,135,182,172,17,135,182,111,231,134,182,11,188,134,182,127,143,134,182,205,97,134,182,247,50,134,182,253,2,134,182,225,209,133,182,164,159,133,182,72,108,133,182,205,55,133,182,53,2,133,182,129,203,132,182,179,147,132,182,203,90,132,182,203,32,132,182,181,229,131,182,138,169,131,182,74,108,131,182,248,45,131,182,148,238,130,182,33,174,130,182,159,108,130,182,15,42,130,182,116,230,129,182,206,161,129,182,31,92,129,182,104,21,129,182,171,205,128,182,233,132,128,182,35,59,128,182,183,224,127,182,38,73,127,182,150,175,126,182,10,20,126,182,134,118,125,182,11,215,124,182,158,53,124,182,64,146,123,182,244,236,122,182,191,69,122,182,162,156,121,182,160,241,120,182,189,68,120,182,252,149,119,182,95,229,118,182,233,50,118,182,158,126,117,182,128,200,116,182,147,16,116,182,217,86,115,182,86,155,114,182,12,222,113,182,255,30,113,182,50,94,112,182,167,155,111,182,99,215,110,182,103,17,110,182,184,73,109,182,87,128,108,182,74,181,107,182,145,232,106,182,50,26,106,182,46,74,105,182,137,120,104,182,70,165,103,182,104,208,102,182,243,249,101,182,233,33,101,182,78,72,100,182,38,109,99,182,114,144,98,182,55,178,97,182,120,210,96,182,55,241,95,182,121,14,95,182,64,42,94,182,144,68,93,182,107,93,92,182,214,116,91,182,211,138,90,182,101,159,89,182,145,178,88,182,88,196,87,182,192,212,86,182,201,227,85,182,121,241,84,182,211,253,83,182,217,8,83,182,143,18,82,182,248,26,81,182,24,34,80,182,241,39,79,182,136,44,78,182,224,47,77,182,251,49,76,182,222,50,75,182,139,50,74,182,5,49,73,182,82,46,72,182,114,42,71,182,107,37,70,182,63,31,69,182,241,23,68,182,133,15,67,182,255,5,66,182,97,251,64,182,175,239,63,182,237,226,62,182,29,213,61,182,68,198,60,182,100,182,59,182,129,165,58,182,157,147,57,182,190,128,56,182,229,108,55,182,23,88,54,182,86,66,53,182,167,43,52,182,11,20,51,182,136,251,49,182,32,226,48,182,214,199,47,182,174,172,46,182,171,144,45,182,209,115,44,182,34,86,43,182,163,55,42,182,87,24,41,182,65,248,39,182,100,215,38,182,196,181,37,182,100,147,36,182,72,112,35,182,114,76,34,182,230,39,33,182,169,2,32,182,188,220,30,182,35,182,29,182,226,142,28,182,251,102,27,182,115,62,26,182,77,21,25,182,139,235,23,182,50,193,22,182,68,150,21,182,197,106,20,182,185,62,19,182,33,18,18,182,3,229,16,182,97,183,15,182,62,137,14,182,159,90,13,182,133,43,12,182,244,251,10,182,240,203,9,182,124,155,8,182,156,106,7,182,81,57,6,182,161,7,5,182,141,213,3,182,26,163,2,182,74,112,1,182,33,61,0,182,67,19,254,181,160,171,251,181,93,67,249,181,129,218,246,181,20,113,244,181,26,7,242,181,156,156,239,181,158,49,237,181,40,198,234,181,64,90,232,181,235,237,229,181,50,129,227,181,25,20,225,181,168,166,222,181,228,56,220,181,211,202,217,181,125,92,215,181,232,237,212,181,24,127,210,181,22,16,208,181,231,160,205,181,145,49,203,181,26,194,200,181,137,82,198,181,228,226,195,181,49,115,193,181,118,3,191,181,185,147,188,181,0,36,186,181,82,180,183,181,180,68,181,181,44,213,178,181,193,101,176,181,120,246,173,181,88,135,171,181,102,24,169,181,169,169,166,181,38,59,164,181,227,204,161,181,230,94,159,181,53,241,156,181,214,131,154,181,206,22,152,181,36,170,149,181,222,61,147,181,0,210,144,181,145,102,142,181,151,251,139,181,23,145,137,181,23,39,135,181,157,189,132,181,174,84,130,181,159,216,127,181,15,9,123,181,184,58,118,181,164,109,113,181,223,161,108,181,115,215,103,181,108,14,99,181,212,70,94,181,183,128,89,181,30,188,84,181,22,249,79,181,169,55,75,181,225,119,70,181,202,185,65,181,109,253,60,181,214,66,56,181,15,138,51,181,34,211,46,181,26,30,42,181,2,107,37,181,227,185,32,181,201,10,28,181,188,93,23,181,200,178,18,181,247,9,14,181,83,99,9,181,229,190,4,181,184,28,0,181,171,249,246,180,143,190,237,180,49,136,228,180,163,86,219,180,249,41,210,180,71,2,201,180,159,223,191,180,21,194,182,180,188,169,173,180,167,150,164,180,233,136,155,180,148,128,146,180,187,125,137,180,113,128,128,180,145,17,111,180,167,45,93,180,72,85,75,180,152,136,57,180,188,199,39,180,215,18,22,180,14,106,4,180,4,155,229,179,175,122,194,179,99,115,159,179,201,10,121,179,240,97,51,179,140,217,219,178,69,175,34,178,54,3,227,49,184,110,194,50,137,216,37,51,106,67,106,51,190,59,151,51,30,58,185,51,148,28,219,51,224,226,252,51,97,70,15,52,254,12,32,52,38,197,48,52,188,110,65,52,159,9,82,52,177,149,98,52,213,18,115,52,117,192,129,52,234,239,137,52,186,23,146,52,215,55,154,52,49,80,162,52,187,96,170,52,102,105,178,52,36,106,186,52,230,98,194,52,158,83,202,52,63,60,210,52,187,28,218,52,3,245,225,52,11,197,233,52,197,140,241,52,36,76,249,52,141,129,0,53,206,88,4,53,205,43,8,53,132,250,11,53,237,196,15,53,3,139,19,53,189,76,23,53,23,10,27,53,10,195,30,53,144,119,34,53,163,39,38,53,62,211,41,53,90,122,45,53,241,28,49,53,255,186,52,53,124,84,56,53,100,233,59,53,177,121,63,53,94,5,67,53,101,140,70,53,192,14,74,53,108,140,77,53,97,5,81,53,155,121,84,53,22,233,87,53,203,83,91,53,182,185,94,53,210,26,98,53,27,119,101,53,139,206,104,53,29,33,108,53,206,110,111,53,151,183,114,53,118,251,117,53,101,58,121,53,96,116,124,53,99,169,127,53,180,108,129,53,55,2,131,53,55,149,132,53,178,37,134,53,167,179,135,53,19,63,137,53,244,199,138,53,73,78,140,53,16,210,141,53,70,83,143,53,235,209,144,53,251,77,146,53,118,199,147,53,90,62,149,53,164,178,150,53,84,36,152,53,103,147,153,53,220,255,154,53,178,105,156,53,230,208,157,53,120,53,159,53,102,151,160,53,174,246,161,53,79,83,163,53,72,173,164,53,151,4,166,53,59,89,167,53,50,171,168,53,124,250,169,53,22,71,171,53,1,145,172,53,59,216,173,53,193,28,175,53,149,94,176,53,180,157,177,53,29,218,178,53,207,19,180,53,201,74,181,53,11,127,182,53,148,176,183,53,97,223,184,53,116,11,186,53,202,52,187,53,99,91,188,53,63,127,189,53,91,160,190,53,185,190,191,53,86,218,192,53,51,243,193,53,79,9,195,53,169,28,196,53,64,45,197,53,20,59,198,53,37,70,199,53,114,78,200,53,250,83,201,53,190,86,202,53,188,86,203,53,245,83,204,53,104,78,205,53,21,70,206,53,251,58,207,53,26,45,208,53,115,28,209,53,4,9,210,53,206,242,210,53,208,217,211,53,11,190,212,53,127,159,213,53,42,126,214,53,14,90,215,53,42,51,216,53,127,9,217,53,12,221,217,53,210,173,218,53,208,123,219,53,7,71,220,53,119,15,221,53,32,213,221,53,3,152,222,53,31,88,223,53,117,21,224,53,6,208,224,53,209,135,225,53,215,60,226,53,25,239,226,53,151,158,227,53,81,75,228,53,71,245,228,53,123,156,229,53,237,64,230,53,157,226,230,53,140,129,231,53,187,29,232,53,42,183,232,53,218,77,233,53,203,225,233,53,255,114,234,53,118,1,235,53,48,141,235,53,48,22,236,53,116,156,236,53,255,31,237,53,210,160,237,53,236,30,238,53,79,154,238,53,252,18,239,53,244,136,239,53,56,252,239,53,201,108,240,53,168,218,240,53,214,69,241,53,84,174,241,53,35,20,242,53,69,119,242,53,186,215,242,53,132,53,243,53,164,144,243,53,27,233,243,53,235,62,244,53,20,146,244,53,153,226,244,53,121,48,245,53,184,123,245,53,86,196,245,53,84,10,246,53,181,77,246,53,120,142,246,53,161,204,246,53,48,8,247,53,39,65,247,53,135,119,247,53,82,171,247,53,138,220,247,53,48,11,248,53,70,55,248,53,205,96,248,53,199,135,248,53,54,172,248,53,28,206,248,53,122,237,248,53,81,10,249,53,165,36,249,53,118,60,249,53,198,81,249,53,151,100,249,53,236,116,249,53,197,130,249,53,37,142,249,53,14,151,249,53,129,157,249,53,129,161,249,53,16,163,249,53,47,162,249,53,224,158,249,53,38,153,249,53,3,145,249,53,120,134,249,53,136,121,249,53,53,106,249,53,128,88,249,53,109,68,249,53,253,45,249,53,51,21,249,53,16,250,248,53,151,220,248,53,202,188,248,53,171,154,248,53,61,118,248,53,129,79,248,53,123,38,248,53,44,251,247,53,150,205,247,53,189,157,247,53,162,107,247,53,72,55,247,53,177,0,247,53,224,199,246,53,215,140,246,53,152,79,246,53,38,16,246,53,131,206,245,53,179,138,245,53,182,68,245,53,145,252,244,53,68,178,244,53,212,101,244,53,66,23,244,53,145,198,243,53,196,115,243,53,221,30,243,53,223,199,242,53,204,110,242,53,167,19,242,53,115,182,241,53,51,87,241,53,232,245,240,53,150,146,240,53,64,45,240,53,232,197,239,53,144,92,239,53,61,241,238,53,239,131,238,53,171,20,238,53,115,163,237,53,73,48,237,53,49,187,236,53,45,68,236,53,65,203,235,53,110,80,235,53,184,211,234,53,34,85,234,53,174,212,233,53,95,82,233,53,57,206,232,53,62,72,232,53,113,192,231,53,213,54,231,53,109,171,230,53,59,30,230,53,68,143,229,53,137,254,228,53,14,108,228,53,213,215,227,53,226,65,227,53,56,170,226,53,217,16,226,53,201,117,225,53,10,217,224,53,160,58,224,53,142,154,223,53,214,248,222,53,124,85,222,53,131,176,221,53,238,9,221,53,191,97,220,53,251,183,219,53,163,12,219,53,188,95,218,53,72,177,217,53,74,1,217,53,198,79,216,53,190,156,215,53,53,232,214,53,48,50,214,53,176,122,213,53,185,193,212,53,79,7,212,53,116,75,211,53,43,142,210,53,120,207,209,53,93,15,209,53,223,77,208,53,255,138,207,53,194,198,206,53,42,1,206,53,59,58,205,53,247,113,204,53,99,168,203,53,128,221,202,53,83,17,202,53,223,67,201,53,38,117,200,53,44,165,199,53,244,211,198,53,130,1,198,53,216,45,197,53,250,88,196,53,235,130,195,53,174,171,194,53,71,211,193,53,184,249,192,53,5,31,192,53,50,67,191,53,64,102,190,53,52,136,189,53,17,169,188,53,218,200,187,53,146,231,186,53,61,5,186,53,221,33,185,53,118,61,184,53,11,88,183,53,160,113,182,53,56,138,181,53,213,161,180,53,123,184,179,53,46,206,178,53,240,226,177,53,197,246,176,53,176,9,176,53,180,27,175,53,213,44,174,53,22,61,173,53,121,76,172,53,2,91,171,53,181,104,170,53,149,117,169,53,164,129,168,53,231,140,167,53,95,151,166,53,18,161,165,53,0,170,164,53,47,178,163,53,161,185,162,53,89,192,161,53,91,198,160,53,169,203,159,53,71,208,158,53,56,212,157,53,128,215,156,53,33,218,155,53,31,220,154,53,124,221,153,53,61,222,152,53,99,222,151,53,243,221,150,53,240,220,149,53,92,219,148,53,59,217,147,53,144,214,146,53,94,211,145,53,168,207,144,53,114,203,143,53,190,198,142,53,144,193,141,53,234,187,140,53,209,181,139,53,70,175,138,53,78,168,137,53,234,160,136,53,31,153,135,53,239,144,134,53,94,136,133,53,110,127,132,53,35,118,131,53,127,108,130,53,134,98,129,53,59,88,128,53,65,155,126,53,116,133,124,53,20,111,122,53,40,88,120,53,182,64,118,53,196,40,116,53,87,16,114,53,118,247,111,53,39,222,109,53,111,196,107,53,85,170,105,53,223,143,103,53,18,117,101,53,244,89,99,53,140,62,97,53,222,34,95,53,241,6,93,53,203,234,90,53,114,206,88,53,235,177,86,53,60,149,84,53,106,120,82,53,125,91,80,53,120,62,78,53,99,33,76,53,66,4,74,53,28,231,71,53,245,201,69,53,212,172,67,53,190,143,65,53,186,114,63,53,203,85,61,53,248,56,59,53,71,28,57,53,189,255,54,53,95,227,52,53,51,199,50,53,62,171,48,53,134,143,46,53,17,116,44,53,227,88,42,53,1,62,40,53,114,35,38,53,59,9,36,53,97,239,33,53,232,213,31,53,215,188,29,53,51,164,27,53,1,140,25,53,69,116,23,53,6,93,21,53,72,70,19,53,17,48,17,53,102,26,15,53,74,5,13,53,197,240,10,53,219,220,8,53,144,201,6,53,234,182,4,53,237,164,2,53,159,147,0,53,10,6,253,52,70,230,248,52,253,199,244,52,56,171,240,52,1,144,236,52,97,118,232,52,98,94,228,52,14,72,224,52,109,51,220,52,137,32,216,52,108,15,212,52,31,0,208,52,171,242,203,52,25,231,199,52,114,221,195,52,192,213,191,52,11,208,187,52,92,204,183,52,188,202,179,52,53,203,175,52,206,205,171,52,146,210,167,52,135,217,163,52,183,226,159,52,43,238,155,52,235,251,151,52,0,12,148,52,114,30,144,52,73,51,140,52,141,74,136,52,72,100,132,52,129,128,128,52,127,62,121,52,26,129,113,52,226,200,105,52,231,21,98,52,56,104,90,52,229,191,82,52,255,28,75,52,149,127,67,52,181,231,59,52,111,85,52,52,211,200,44,52,240,65,37,52,212,192,29,52,142,69,22,52,45,208,14,52,192,96,7,52,170,238,255,51,245,39,241,51,125,109,226,51,95,191,211,51,183,29,197,51,161,136,182,51,55,0,168,51,150,132,153,51,217,21,139,51,52,104,121,51,233,190,92,51,5,48,64,51,188,187,35,51,66,98,7,51,151,71,214,50,21,1,158,50,198,226,75,50,147,99,184,49,40,128,152,176,225,225,1,178,9,67,112,178,94,25,175,178,31,216,229,178,180,46,14,179,110,84,41,179,15,93,68,179,106,72,95,179,82,22,122,179,77,99,138,179,139,172,151,179,204,230,164,179,253,17,178,179,6,46,191,179,210,58,204,179,78,56,217,179,100,38,230,179,0,5,243,179,14,212,255,179,189,73,6,180,151,161,12,180,141,241,18,180,149,57,25,180,165,121,31,180,180,177,37,180,184,225,43,180,169,9,50,180,125,41,56,180,44,65,62,180,173,80,68,180,248,87,74,180,2,87,80,180,198,77,86,180,57,60,92,180,84,34,98,180,15,0,104,180,98,213,109,180,69,162,115,180,177,102,121,180,157,34,127,180,2,107,130,180,110,64,133,180,144,17,136,180,100,222,138,180,230,166,141,180,19,107,144,180,232,42,147,180,98,230,149,180,125,157,152,180,54,80,155,180,138,254,157,180,118,168,160,180,246,77,163,180,9,239,165,180,171,139,168,180,217,35,171,180,145,183,173,180,207,70,176,180,146,209,178,180,214,87,181,180,154,217,183,180,218,86,186,180,148,207,188,180,199,67,191,180,110,179,193,180,138,30,196,180,23,133,198,180,18,231,200,180,123,68,203,180,79,157,205,180,141,241,207,180,49,65,210,180,59,140,212,180,169,210,214,180,121,20,217,180,169,81,219,180,56,138,221,180,37,190,223,180,109,237,225,180,15,24,228,180,11,62,230,180,94,95,232,180,8,124,234,180,8,148,236,180,91,167,238,180,2,182,240,180,251,191,242,180,68,197,244,180,223,197,246,180,200,193,248,180,0,185,250,180,134,171,252,180,90,153,254,180,61,65,0,181,115,51,1,181,78,35,2,181,207,16,3,181,246,251,3,181,194,228,4,181,50,203,5,181,72,175,6,181,2,145,7,181,97,112,8,181,100,77,9,181,12,40,10,181,89,0,11,181,75,214,11,181,225,169,12,181,28,123,13,181,252,73,14,181,129,22,15,181,171,224,15,181,123,168,16,181,241,109,17,181,12,49,18,181,206,241,18,181,54,176,19,181,69,108,20,181,251,37,21,181,88,221,21,181,94,146,22,181,12,69,23,181,99,245,23,181,99,163,24,181,12,79,25,181,96,248,25,181,95,159,26,181,9,68,27,181,96,230,27,181,99,134,28,181,19,36,29,181,113,191,29,181,126,88,30,181,58,239,30,181,166,131,31,181,195,21,32,181,146,165,32,181,19,51,33,181,71,190,33,181,47,71,34,181,205,205,34,181,32,82,35,181,42,212,35,181,236,83,36,181,103,209,36,181,155,76,37,181,138,197,37,181,53,60,38,181,157,176,38,181,195,34,39,181,167,146,39,181,76,0,40,181,178,107,40,181,219,212,40,181,199,59,41,181,120,160,41,181,238,2,42,181,45,99,42,181,52,193,42,181,4,29,43,181,160,118,43,181,8,206,43,181,63,35,44,181,68,118,44,181,26,199,44,181,194,21,45,181,62,98,45,181,142,172,45,181,181,244,45,181,179,58,46,181,139,126,46,181,62,192,46,181,206,255,46,181,59,61,47,181,136,120,47,181,182,177,47,181,199,232,47,181,188,29,48,181,152,80,48,181,91,129,48,181,7,176,48,181,159,220,48,181,36,7,49,181,151,47,49,181,251,85,49,181,81,122,49,181,155,156,49,181,218,188,49,181,18,219,49,181,66,247,49,181,110,17,50,181,152,41,50,181,192,63,50,181,233,83,50,181,21,102,50,181,70,118,50,181,126,132,50,181,191,144,50,181,10,155,50,181,98,163,50,181,201,169,50,181,65,174,50,181,203,176,50,181,107,177,50,181,34,176,50,181,241,172,50,181,220,167,50,181,228,160,50,181,12,152,50,181,85,141,50,181,194,128,50,181,84,114,50,181,15,98,50,181,244,79,50,181,6,60,50,181,70,38,50,181,184,14,50,181,92,245,49,181,54,218,49,181,72,189,49,181,147,158,49,181,28,126,49,181,226,91,49,181,234,55,49,181,53,18,49,181,197,234,48,181,158,193,48,181,193,150,48,181,48,106,48,181,239,59,48,181,255,11,48,181,99,218,47,181,29,167,47,181,48,114,47,181,158,59,47,181,106,3,47,181,150,201,46,181,37,142,46,181,24,81,46,181,115,18,46,181,56,210,45,181,106,144,45,181,10,77,45,181,28,8,45,181,162,193,44,181,159,121,44,181,21,48,44,181,6,229,43,181,118,152,43,181,103,74,43,181,219,250,42,181,213,169,42,181,87,87,42,181,101,3,42,181,1,174,41,181,44,87,41,181,235,254,40,181,64,165,40,181,45,74,40,181,180,237,39,181,217,143,39,181,159,48,39,181,7,208,38,181,21,110,38,181,203,10,38,181,43,166,37,181,58,64,37,181,248,216,36,181,105,112,36,181,144,6,36,181,111,155,35,181,10,47,35,181,97,193,34,181,122,82,34,181,85,226,33,181,246,112,33,181,96,254,32,181,149,138,32,181,152,21,32,181,108,159,31,181,19,40,31,181,144,175,30,181,231,53,30,181,25,187,29,181,41,63,29,181,27,194,28,181,240,67,28,181,173,196,27,181,82,68,27,181,228,194,26,181,101,64,26,181,216,188,25,181,63,56,25,181,158,178,24,181,246,43,24,181,75,164,23,181,160,27,23,181,247,145,22,181,84,7,22,181,184,123,21,181,39,239,20,181,163,97,20,181,47,211,19,181,207,67,19,181,132,179,18,181,81,34,18,181,58,144,17,181,65,253,16,181,105,105,16,181,180,212,15,181,38,63,15,181,193,168,14,181,136,17,14,181,125,121,13,181,164,224,12,181,255,70,12,181,145,172,11,181,93,17,11,181,101,117,10,181,173,216,9,181,54,59,9,181,4,157,8,181,26,254,7,181,122,94,7,181,39,190,6,181,36,29,6,181,115,123,5,181,23,217,4,181,19,54,4,181,106,146,3,181,30,238,2,181,51,73,2,181,170,163,1,181,135,253,0,181,204,86,0,181,248,94,255,180,52,15,254,180,80,190,252,180,83,108,251,180,65,25,250,180,32,197,248,180,245,111,247,180,198,25,246,180,152,194,244,180,112,106,243,180,84,17,242,180,73,183,240,180,84,92,239,180,122,0,238,180,194,163,236,180,48,70,235,180,201,231,233,180,147,136,232,180,146,40,231,180,205,199,229,180,73,102,228,180,9,4,227,180,21,161,225,180,112,61,224,180,33,217,222,180,44,116,221,180,150,14,220,180,100,168,218,180,157,65,217,180,68,218,215,180,95,114,214,180,242,9,213,180,4,161,211,180,152,55,210,180,181,205,208,180,95,99,207,180,154,248,205,180,109,141,204,180,219,33,203,180,235,181,201,180,160,73,200,180,0,221,198,180,15,112,197,180,211,2,196,180,81,149,194,180,141,39,193,180,139,185,191,180,82,75,190,180,229,220,188,180,74,110,187,180,133,255,185,180,155,144,184,180,145,33,183,180,107,178,181,180,46,67,180,180,223,211,178,180,130,100,177,180,28,245,175,180,178,133,174,180,72,22,173,180,228,166,171,180,136,55,170,180,58,200,168,180,255,88,167,180,219,233,165,180,210,122,164,180,232,11,163,180,36,157,161,180,136,46,160,180,24,192,158,180,219,81,157,180,211,227,155,180,5,118,154,180,118,8,153,180,42,155,151,180,36,46,150,180,106,193,148,180,0,85,147,180,233,232,145,180,41,125,144,180,198,17,143,180,195,166,141,180,36,60,140,180,238,209,138,180,36,104,137,180,202,254,135,180,229,149,134,180,121,45,133,180,136,197,131,180,25,94,130,180,45,247,128,180,148,33,127,180,229,85,124,180,87,139,121,180,240,193,118,180,184,249,115,180,184,50,113,180,245,108,110,180,120,168,107,180,73,229,104,180,110,35,102,180,239,98,99,180,211,163,96,180,33,230,93,180,226,41,91,180,27,111,88,180,211,181,85,180,19,254,82,180,224,71,80,180,66,147,77,180,64,224,74,180,224,46,72,180,41,127,69,180,35,209,66,180,211,36,64,180,64,122,61,180,113,209,58,180,108,42,56,180,56,133,53,180,220,225,50,180,93,64,48,180,194,160,45,180,17,3,43,180,80,103,40,180,134,205,37,180,185,53,35,180,239,159,32,180,45,12,30,180,122,122,27,180,219,234,24,180,87,93,22,180,242,209,19,180,180,72,17,180,161,193,14,180,192,60,12,180,21,186,9,180,167,57,7,180,122,187,4,180,149,63,2,180,248,139,255,179,106,157,250,179,138,179,245,179,99,206,240,179,254,237,235,179,103,18,231,179,167,59,226,179,200,105,221,179,211,156,216,179,212,212,211,179,210,17,207,179,217,83,202,179,241,154,197,179,35,231,192,179,121,56,188,179,252,142,183,179,181,234,178,179,172,75,174,179,235,177,169,179,122,29,165,179,97,142,160,179,170,4,156,179,92,128,151,179,127,1,147,179,29,136,142,179,60,20,138,179,228,165,133,179,30,61,129,179,226,179,121,179,202,248,112,179,1,73,104,179,151,164,95,179,154,11,87,179,24,126,78,179,32,252,69,179,192,133,61,179,4,27,53,179,250,187,44,179,176,104,36,179,50,33,28,179,141,229,19,179,205,181,11,179,255,145,3,179,94,244,246,178,209,220,230,178,111,221,214,178,78,246,198,178,133,39,183,178,42,113,167,178,81,211,151,178,17,78,136,178,253,194,113,178,89,27,83,178,96,165,52,178,55,97,22,178,8,158,240,177,218,221,180,177,88,4,115,177,0,0,0,0,0,0,0,0,128,0,0,0,96,219,84,63,140,215,84,63,19,204,84,63,244,184,84,63,49,158,84,63,203,123,84,63,197,81,84,63,34,32,84,63,228,230,83,63,16,166,83,63,170,93,83,63,183,13,83,63,59,182,82,63,61,87,82,63,194,240,81,63,210,130,81,63,116,13,81,63,175,144,80,63,140,12,80,63,19,129,79,63,77,238,78,63,68,84,78,63,3,179,77,63,146,10,77,63,255,90,76,63,83,164,75,63,155,230,74,63,228,33,74,63,58,86,73,63,170,131,72,63,67,170,71,63,18,202,70,63,38,227,69,63,142,245,68,63,89,1,68,63,151,6,67,63,90,5,66,63,176,253,64,63,172,239,63,63,94,219,62,63,218,192,61,63,48,160,60,63,117,121,59,63,186,76,58,63,19,26,57,63,148,225,55,63,82,163,54,63,96,95,53,63,211,21,52,63,193,198,50,63,63,114,49,63,99,24,48,63,68,185,46,63,247,84,45,63,148,235,43,63,49,125,42,63,230,9,41,63,202,145,39,63,247,20,38,63,131,147,36,63,135,13,35,63,28,131,33,63,91,244,31,63,94,97,30,63,61,202,28,63,19,47,27,63,249,143,25,63,9,237,23,63,95,70,22,63,19,156,20,63,66,238,18,63,6,61,17,63,121,136,15,63,184,208,13,63,221,21,12,63,5,88,10,63,74,151,8,63,201,211,6,63,158,13,5,63,228,68,3,63,184,121,1,63,107,88,255,62,243,184,251,62,64,21,248,62,140,109,244,62,15,194,240,62,3,19,237,62,160,96,233,62,33,171,229,62,190,242,225,62,178,55,222,62,53,122,218,62,129,186,214,62,207,248,210,62,90,53,207,62,90,112,203,62,9,170,199,62,160,226,195,62,88,26,192,62,106,81,188,62,16,136,184,62,130,190,180,62,249,244,176,62,172,43,173,62,214,98,169,62,173,154,165,62,105,211,161,62,67,13,158,62,113,72,154,62,42,133,150,62,166,195,146,62,27,4,143,62,191,70,139,62,200,139,135,62,107,211,131,62,221,29,128,62,169,214,120,62,9,120,113,62,66,32,106,62,188,207,98,62,220,134,91,62,9,70,84,62,166,13,77,62,23,222,69,62,191,183,62,62,255,154,55,62,55,136,48,62,199,127,41,62,13,130,34,62,103,143,27,62,48,168,20,62,195,204,13,62,122,253,6,62,174,58,0,62,108,9,243,61,208,183,229,61,49,129,216,61,54,102,203,61,132,103,190,61,189,133,177,61,126,193,164,61,102,27,152,61,12,148,139,61,16,88,126,61,218,199,101,61,152,120,77,61,103,107,53,61,91,161,29,61,133,27,6,61,220,181,221,60,53,193,175,60,15,91,130,60,176,10,43,60,204,7,165,59,216,82,155,185,106,26,182,187,126,16,48,188,138,240,129,188,218,61,171,188,155,238,211,188,69,1,252,188,48,186,17,189,63,35,37,189,31,59,56,189,42,1,75,189,187,116,93,189,56,149,111,189,7,177,128,189,86,109,137,189,71,255,145,189,154,102,154,189,19,163,162,189,119,180,170,189,146,154,178,189,50,85,186,189,40,228,193,189,72,71,201,189,108,126,208,189,110,137,215,189,46,104,222,189,143,26,229,189,118,160,235,189,206,249,241,189,131,38,248,189,134,38,254,189,229,252,1,190,36,208,4,190,252,140,7,190,109,51,10,190,120,195,12,190,31,61,15,190,101,160,17,190,82,237,19,190,235,35,22,190,59,68,24,190,76,78,26,190,41,66,28,190,226,31,30,190,134,231,31,190,38,153,33,190,212,52,35,190,165,186,36,190,175,42,38,190,9,133,39,190,204,201,40,190,18,249,41,190,248,18,43,190,155,23,44,190,26,7,45,190,149,225,45,190,46,167,46,190,9,88,47,190,73,244,47,190,21,124,48,190,148,239,48,190,239,78,49,190,80,154,49,190,227,209,49,190,211,245,49,190,80,6,50,190,135,3,50,190,170,237,49,190,235,196,49,190,123,137,49,190,143,59,49,190,91,219,48,190,24,105,48,190,250,228,47,190,59,79,47,190,21,168,46,190,193,239,45,190,124,38,45,190,129,76,44,190,14,98,43,190,98,103,42,190,187,92,41,190,90,66,40,190,128,24,39,190,110,223,37,190,103,151,36,190,175,64,35,190,137,219,33,190,59,104,32,190,10,231,30,190,60,88,29,190,24,188,27,190,229,18,26,190,237,92,24,190,119,154,22,190,204,203,20,190,55,241,18,190,1,11,17,190,118,25,15,190,223,28,13,190,138,21,11,190,193,3,9,190,209,231,6,190,7,194,4,190,175,146,2,190,22,90,0,190,21,49,252,189,178,156,247,189,160,247,242,189,122,66,238,189,221,125,233,189,100,170,228,189,172,200,223,189,83,217,218,189,244,220,213,189,44,212,208,189,152,191,203,189,213,159,198,189,127,117,193,189,49,65,188,189,137,3,183,189,33,189,177,189,149,110,172,189,127,24,167,189,123,187,161,189,33,88,156,189,11,239,150,189,210,128,145,189,14,14,140,189,86,151,134,189,64,29,129,189,197,64,119,189,165,66,108,189,71,65,97,189,209,61,86,189,104,57,75,189,45,53,64,189,65,50,53,189,193,49,42,189,200,52,31,189,111,60,20,189,204,73,9,189,231,187,252,188,237,243,230,188,198,61,209,188,138,155,187,188,73,15,166,188,15,155,144,188,195,129,118,188,125,5,76,188,62,197,33,188,213,137,239,187,177,16,156,187,68,77,18,187,28,206,146,57,0,197,53,59,252,242,171,59,53,87,252,59,21,4,38,60,124,127,77,60,107,154,116,60,200,168,141,60,213,208,160,60,193,195,179,60,251,127,198,60,251,3,217,60,62,78,235,60,76,93,253,60,218,151,7,61,8,98,16,61,128,12,25,61,150,150,33,61,165,255,41,61,8,71,50,61,36,108,58,61,93,110,66,61,31,77,74,61,217,7,82,61,0,158,89,61,12,15,97,61,123,90,104,61,206,127,111,61,140,126,118,61,64,86,125,61,61,3,130,61,104,71,133,61,109,119,136,61,26,147,139,61,66,154,142,61,184,140,145,61,82,106,148,61,233,50,151,61,87,230,153,61,122,132,156,61,50,13,159,61,95,128,161,61,230,221,163,61,175,37,166,61,162,87,168,61,169,115,170,61,180,121,172,61,178,105,174,61,150,67,176,61,83,7,178,61,225,180,179,61,57,76,181,61,87,205,182,61,57,56,184,61,222,140,185,61,72,203,186,61,126,243,187,61,132,5,189,61,100,1,190,61,42,231,190,61,226,182,191,61,156,112,192,61,106,20,193,61,95,162,193,61,145,26,194,61,24,125,194,61,14,202,194,61,143,1,195,61,184,35,195,61,170,48,195,61,135,40,195,61,113,11,195,61,144,217,194,61,11,147,194,61,10,56,194,61,186,200,193,61,71,69,193,61,225,173,192,61,183,2,192,61,253,67,191,61,230,113,190,61,168,140,189,61,121,148,188,61,148,137,187,61,51,108,186,61,144,60,185,61,235,250,183,61,129,167,182,61,147,66,181,61,100,204,179,61,53,69,178,61,76,173,176,61,238,4,175,61,99,76,173,61,242,131,171,61,229,171,169,61,134,196,167,61,34,206,165,61,5,201,163,61,125,181,161,61,217,147,159,61,105,100,157,61,125,39,155,61,103,221,152,61,122,134,150,61,8,35,148,61,103,179,145,61,236,55,143,61,235,176,140,61,187,30,138,61,179,129,135,61,42,218,132,61,122,40,130,61,242,217,126,61,4,80,121,61,220,179,115,61,46,6,110,61,175,71,104,61,19,121,98,61,17,155,92,61,96,174,86,61,183,179,80,61,204,171,74,61,87,151,68,61,16,119,62,61,176,75,56,61,237,21,50,61,129,214,43,61,35,142,37,61,139,61,31,61,112,229,24,61,138,134,18,61,144,33,12,61,56,183,5,61,112,144,254,60,140,170,241,60,45,190,228,60,188,204,215,60,158,215,202,60,57,224,189,60,239,231,176,60,35,240,163,60,51,250,150,60,123,7,138,60,173,50,122,60,60,98,96,60,75,160,70,60,130,239,44,60,128,82,19,60,189,151,243,59,99,188,192,59,13,24,142,59,144,95,55,59,80,34,166,58,202,137,133,185,156,187,231,186,237,106,86,187,134,39,156,187,97,192,204,187,97,251,252,187,249,105,22,188,201,34,46,188,230,165,69,188,32,241,92,188,80,2,116,188,172,107,133,188,17,183,144,188,82,226,155,188,110,236,166,188,105,212,177,188,76,153,188,188,37,58,199,188,7,182,209,188,12,12,220,188,80,59,230,188,247,66,240,188,42,34,250,188,10,236,1,189,246,177,6,189,117,98,11,189,38,253,15,189,170,129,20,189,168,239,24,189,198,70,29,189,176,134,33,189,20,175,37,189,161,191,41,189,11,184,45,189,9,152,49,189,84,95,53,189,169,13,57,189,198,162,60,189,111,30,64,189,104,128,67,189,123,200,70,189,115,246,73,189,30,10,77,189,78,3,80,189,215,225,82,189,145,165,85,189,87,78,88,189,7,220,90,189,128,78,93,189,168,165,95,189,101,225,97,189,161,1,100,189,73,6,102,189,77,239,103,189,159,188,105,189,53,110,107,189,9,4,109,189,22,126,110,189,92,220,111,189,219,30,113,189,153,69,114,189,157,80,115,189,241,63,116,189,163,19,117,189,196,203,117,189,101,104,118,189,158,233,118,189,134,79,119,189,56,154,119,189,212,201,119,189,121,222,119,189,76,216,119,189,113,183,119,189,19,124,119,189,92,38,119,189,122,182,118,189,157,44,118,189,249,136,117,189,194,203,116,189,49,245,115,189,127,5,115,189,234,252,113,189,175,219,112,189,16,162,111,189,80,80,110,189,181,230,108,189,134,101,107,189,14,205,105,189,151,29,104,189,112,87,102,189,233,122,100,189,83,136,98,189,3,128,96,189,78,98,94,189,139,47,92,189,19,232,89,189,66,140,87,189,116,28,85,189,6,153,82,189,90,2,80,189,208,88,77,189,203,156,74,189,176,206,71,189,227,238,68,189,204,253,65,189,212,251,62,189,99,233,59,189,229,198,56,189,197,148,53,189,113,83,50,189,86,3,47,189,227,164,43,189,136,56,40,189,182,190,36,189,223,55,33,189,118,164,29,189,236,4,26,189,183,89,22,189,74,163,18,189,27,226,14,189,160,22,11,189,77,65,7,189,155,98,3,189,0,246,254,188,230,21,247,188,215,37,239,188,195,38,231,188,153,25,223,188,74,255,214,188,197,216,206,188,251,166,198,188,221,106,190,188,89,37,182,188,97,215,173,188,227,129,165,188,206,37,157,188,17,196,148,188], "i8", ALLOC_NONE, Runtime.GLOBAL_BASE+81920);
/* memory initializer */ allocate([153,93,140,188,85,243,131,188,93,12,119,188,38,46,102,188,216,77,85,188,69,109,68,188,61,142,51,188,143,178,34,188,4,220,17,188,100,12,1,188,232,138,224,187,235,17,191,187,76,177,157,187,0,217,120,187,232,141,54,187,53,16,233,186,70,57,75,186,171,140,105,57,69,77,159,58,177,84,16,59,132,156,80,59,222,59,136,59,8,240,167,59,165,103,199,59,164,159,230,59,128,202,2,60,95,34,18,60,247,85,33,60,216,99,48,60,148,74,63,60,199,8,78,60,16,157,92,60,24,6,107,60,138,66,121,60,141,168,131,60,66,152,138,60,195,111,145,60,118,46,152,60,192,211,158,60,14,95,165,60,205,207,171,60,112,37,178,60,108,95,184,60,59,125,190,60,89,126,196,60,72,98,202,60,139,40,208,60,172,208,213,60,55,90,219,60,187,196,224,60,205,15,230,60,5,59,235,60,254,69,240,60,89,48,245,60,184,249,249,60,197,161,254,60,21,148,1,61,76,198,3,61,98,231,5,61,50,247,7,61,154,245,9,61,123,226,11,61,182,189,13,61,48,135,15,61,208,62,17,61,124,228,18,61,32,120,20,61,168,249,21,61,3,105,23,61,32,198,24,61,243,16,26,61,112,73,27,61,141,111,28,61,66,131,29,61,140,132,30,61,101,115,31,61,206,79,32,61,197,25,33,61,79,209,33,61,111,118,34,61,43,9,35,61,141,137,35,61,158,247,35,61,108,83,36,61,3,157,36,61,117,212,36,61,210,249,36,61,47,13,37,61,161,14,37,61,65,254,36,61,38,220,36,61,108,168,36,61,48,99,36,61,144,12,36,61,173,164,35,61,167,43,35,61,164,161,34,61,200,6,34,61,57,91,33,61,33,159,32,61,170,210,31,61,254,245,30,61,75,9,30,61,192,12,29,61,140,0,28,61,226,228,26,61,243,185,25,61,245,127,24,61,28,55,23,61,160,223,21,61,186,121,20,61,162,5,19,61,147,131,17,61,202,243,15,61,131,86,14,61,253,171,12,61,120,244,10,61,50,48,9,61,111,95,7,61,113,130,5,61,123,153,3,61,209,164,1,61,115,73,255,60,244,50,251,60,180,6,247,60,66,197,242,60,49,111,238,60,18,5,234,60,122,135,229,60,254,246,224,60,54,84,220,60,186,159,215,60,34,218,210,60,10,4,206,60,11,30,201,60,196,40,196,60,208,36,191,60,205,18,186,60,91,243,180,60,24,199,175,60,164,142,170,60,159,74,165,60,170,251,159,60,103,162,154,60,119,63,149,60,122,211,143,60,20,95,138,60,229,226,132,60,32,191,126,60,109,171,115,60,245,139,104,60,250,97,93,60,191,46,82,60,134,243,70,60,144,177,59,60,30,106,48,60,109,30,37,60,190,207,25,60,75,127,14,60,81,46,3,60,18,188,239,59,86,31,217,59,221,136,194,59,13,251,171,59,75,120,149,59,242,5,126,59,228,58,81,59,25,148,36,59,110,44,240,58,172,139,151,58,48,60,253,57,102,3,68,185,156,179,95,186,66,180,198,186,133,131,14,187,193,100,57,187,146,249,99,187,224,30,135,187,146,22,156,187,209,225,176,187,153,126,197,187,235,234,217,187,210,36,238,187,48,21,1,188,215,252,10,188,113,200,20,188,17,119,30,188,211,7,40,188,213,121,49,188,57,204,58,188,39,254,67,188,202,14,77,188,84,253,85,188,249,200,94,188,244,112,103,188,132,244,111,188,237,82,120,188,188,69,128,188,185,78,132,188,24,68,136,188,132,37,140,188,173,242,143,188,67,171,147,188,251,78,151,188,138,221,154,188,171,86,158,188,25,186,161,188,148,7,165,188,219,62,168,188,179,95,171,188,228,105,174,188,54,93,177,188,118,57,180,188,115,254,182,188,255,171,185,188,239,65,188,188,25,192,190,188,89,38,193,188,138,116,195,188,141,170,197,188,69,200,199,188,150,205,201,188,105,186,203,188,168,142,205,188,66,74,207,188,39,237,208,188,73,119,210,188,160,232,211,188,35,65,213,188,206,128,214,188,159,167,215,188,151,181,216,188,185,170,217,188,13,135,218,188,153,74,219,188,107,245,219,188,144,135,220,188,26,1,221,188,26,98,221,188,168,170,221,188,219,218,221,188,206,242,221,188,160,242,221,188,112,218,221,188,96,170,221,188,149,98,221,188,54,3,221,188,109,140,220,188,102,254,219,188,78,89,219,188,87,157,218,188,179,202,217,188,150,225,216,188,56,226,215,188,209,204,214,188,158,161,213,188,220,96,212,188,201,10,211,188,168,159,209,188,187,31,208,188,71,139,206,188,149,226,204,188,236,37,203,188,151,85,201,188,228,113,199,188,31,123,197,188,154,113,195,188,165,85,193,188,147,39,191,188,185,231,188,188,110,150,186,188,8,52,184,188,225,192,181,188,83,61,179,188,186,169,176,188,114,6,174,188,218,83,171,188,81,146,168,188,55,194,165,188,238,227,162,188,217,247,159,188,91,254,156,188,217,247,153,188,184,228,150,188,94,197,147,188,51,154,144,188,158,99,141,188,7,34,138,188,217,213,134,188,124,127,131,188,91,31,128,188,194,107,121,188,243,134,114,188,31,145,107,188,32,139,100,188,205,117,93,188,1,82,86,188,149,32,79,188,99,226,71,188,70,152,64,188,25,67,57,188,181,227,49,188,247,122,42,188,183,9,35,188,210,144,27,188,31,17,20,188,123,139,12,188,188,0,5,188,121,227,250,187,168,190,235,187,179,148,220,187,71,103,205,187,15,56,190,187,178,8,175,187,217,218,159,187,39,176,144,187,61,138,129,187,118,213,100,187,121,166,70,187,182,138,40,187,89,133,10,187,12,51,217,186,183,148,157,186,194,107,68,186,159,114,156,185,42,135,29,57,145,89,28,58,20,81,136,58,237,23,194,58,136,123,251,58,24,59,26,59,31,129,54,59,18,141,82,59,50,92,110,59,230,245,132,59,155,156,146,59,233,32,160,59,134,129,173,59,46,189,186,59,163,210,199,59,172,192,212,59,22,134,225,59,182,33,238,59,99,146,250,59,128,107,3,60,56,119,9,60,209,107,15,60,196,72,21,60,142,13,27,60,175,185,32,60,170,76,38,60,7,198,43,60,79,37,49,60,18,106,54,60,223,147,59,60,76,162,64,60,241,148,69,60,106,107,74,60,87,37,79,60,91,194,83,60,29,66,88,60,71,164,92,60,136,232,96,60,145,14,101,60,24,22,105,60,215,254,108,60,138,200,112,60,243,114,116,60,213,253,119,60,250,104,123,60,45,180,126,60,159,239,128,60,1,117,130,60,40,234,131,60,0,79,133,60,123,163,134,60,136,231,135,60,28,27,137,60,43,62,138,60,173,80,139,60,153,82,140,60,236,67,141,60,161,36,142,60,182,244,142,60,44,180,143,60,5,99,144,60,69,1,145,60,241,142,145,60,16,12,146,60,172,120,146,60,208,212,146,60,135,32,147,60,226,91,147,60,239,134,147,60,192,161,147,60,106,172,147,60,0,167,147,60,154,145,147,60,80,108,147,60,61,55,147,60,123,242,146,60,40,158,146,60,99,58,146,60,76,199,145,60,4,69,145,60,175,179,144,60,114,19,144,60,114,100,143,60,216,166,142,60,203,218,141,60,119,0,141,60,7,24,140,60,167,33,139,60,135,29,138,60,213,11,137,60,194,236,135,60,129,192,134,60,67,135,133,60,62,65,132,60,167,238,130,60,179,143,129,60,156,36,128,60,48,91,125,60,196,85,122,60,104,57,119,60,146,6,116,60,189,189,112,60,98,95,109,60,252,235,105,60,11,100,102,60,13,200,98,60,131,24,95,60,239,85,91,60,213,128,87,60,186,153,83,60,36,161,79,60,155,151,75,60,166,125,71,60,209,83,67,60,164,26,63,60,172,210,58,60,117,124,54,60,140,24,50,60,127,167,45,60,220,41,41,60,52,160,36,60,21,11,32,60,15,107,27,60,180,192,22,60,147,12,18,60,63,79,13,60,73,137,8,60,65,187,3,60,118,203,253,59,142,18,244,59,239,76,234,59,188,123,224,59,22,160,214,59,33,187,204,59,254,205,194,59,207,217,184,59,179,223,174,59,202,224,164,59,50,222,154,59,7,217,144,59,103,210,134,59,214,150,121,59,88,138,101,59,131,129,81,59,129,126,61,59,122,131,41,59,145,146,21,59,230,173,1,59,39,175,219,58,94,35,180,58,151,188,140,58,210,253,74,58,143,185,249,57,93,120,60,57,105,178,241,184,20,49,214,185,220,128,55,186,204,179,129,186,149,98,167,186,15,201,204,186,137,227,241,186,49,87,11,187,4,147,29,187,123,163,47,187,217,134,65,187,108,59,83,187,135,191,100,187,134,17,118,187,230,151,131,187,97,12,140,187,111,101,148,187,78,162,156,187,63,194,164,187,137,196,172,187,117,168,180,187,84,109,188,187,121,18,196,187,58,151,203,187,244,250,210,187,9,61,218,187,221,92,225,187,219,89,232,187,112,51,239,187,18,233,245,187,55,122,252,187,46,115,1,188,130,150,4,188,218,166,7,188,253,163,10,188,178,141,13,188,195,99,16,188,255,37,19,188,52,212,21,188,53,110,24,188,214,243,26,188,237,100,29,188,86,193,31,188,234,8,34,188,138,59,36,188,21,89,38,188,112,97,40,188,127,84,42,188,45,50,44,188,99,250,45,188,15,173,47,188,34,74,49,188,140,209,50,188,68,67,52,188,64,159,53,188,122,229,54,188,238,21,56,188,155,48,57,188,130,53,58,188,166,36,59,188,13,254,59,188,191,193,60,188,199,111,61,188,48,8,62,188,12,139,62,188,106,248,62,188,94,80,63,188,254,146,63,188,100,192,63,188,167,216,63,188,230,219,63,188,63,202,63,188,211,163,63,188,196,104,63,188,56,25,63,188,85,181,62,188,69,61,62,188,50,177,61,188,75,17,61,188,188,93,60,188,184,150,59,188,113,188,58,188,27,207,57,188,237,206,56,188,30,188,55,188,234,150,54,188,138,95,53,188,61,22,52,188,64,187,50,188,213,78,49,188,62,209,47,188,189,66,46,188,152,163,44,188,20,244,42,188,123,52,41,188,20,101,39,188,43,134,37,188,10,152,35,188,255,154,33,188,89,143,31,188,102,117,29,188,118,77,27,188,220,23,25,188,234,212,22,188,243,132,20,188,76,40,18,188,74,191,15,188,67,74,13,188,143,201,10,188,133,61,8,188,126,166,5,188,212,4,3,188,223,88,0,188,247,69,251,187,8,199,245,187,167,53,240,187,142,146,234,187,117,222,228,187,22,26,223,187,45,70,217,187,115,99,211,187,166,114,205,187,129,116,199,187,193,105,193,187,35,83,187,187,99,49,181,187,64,5,175,187,118,207,168,187,194,144,162,187,227,73,156,187,147,251,149,187,145,166,143,187,152,75,137,187,100,235,130,187,95,13,121,187,109,60,108,187,100,101,95,187,183,137,82,187,214,170,69,187,48,202,56,187,50,233,43,187,71,9,31,187,215,43,18,187,71,82,5,187,248,251,240,186,172,96,215,186,102,213,189,186,219,92,164,186,189,249,138,186,101,93,99,186,191,252,48,186,124,173,253,185,21,225,153,185,244,121,218,184,227,64,176,56,24,33,142,57,76,138,239,57,18,33,40,58,135,31,88,58,232,221,131,58,165,120,155,58,177,221,178,58,205,10,202,58,195,253,224,58,103,180,247,58,76,22,7,59,32,50,18,59,167,44,29,59,225,4,40,59,209,185,50,59,130,74,61,59,2,182,71,59,101,251,81,59,198,25,92,59,69,16,102,59,8,222,111,59,57,130,121,59,5,126,129,59,90,37,134,59,185,182,138,59,196,49,143,59,32,150,147,59,117,227,151,59,109,25,156,59,183,55,160,59,4,62,164,59,8,44,168,59,122,1,172,59,21,190,175,59,151,97,179,59,193,235,182,59,87,92,186,59,32,179,189,59,231,239,192,59,122,18,196,59,169,26,199,59,73,8,202,59,49,219,204,59,61,147,207,59,73,48,210,59,54,178,212,59,234,24,215,59,75,100,217,59,68,148,219,59,195,168,221,59,185,161,223,59,26,127,225,59,221,64,227,59,252,230,228,59,118,113,230,59,74,224,231,59,124,51,233,59,19,107,234,59,24,135,235,59,153,135,236,59,165,108,237,59,78,54,238,59,170,228,238,59,210,119,239,59,224,239,239,59,244,76,240,59,45,143,240,59,176,182,240,59,163,195,240,59,48,182,240,59,130,142,240,59,199,76,240,59,50,241,239,59,245,123,239,59,71,237,238,59,96,69,238,59,124,132,237,59,216,170,236,59,181,184,235,59,84,174,234,59,250,139,233,59,238,81,232,59,120,0,231,59,229,151,229,59,128,24,228,59,154,130,226,59,132,214,224,59,144,20,223,59,20,61,221,59,103,80,219,59,226,78,217,59,224,56,215,59,188,14,213,59,212,208,210,59,137,127,208,59,59,27,206,59,77,164,203,59,34,27,201,59,33,128,198,59,176,211,195,59,55,22,193,59,32,72,190,59,212,105,187,59,192,123,184,59,81,126,181,59,243,113,178,59,22,87,175,59,41,46,172,59,157,247,168,59,227,179,165,59,108,99,162,59,173,6,159,59,23,158,155,59,31,42,152,59,58,171,148,59,219,33,145,59,122,142,141,59,138,241,137,59,130,75,134,59,217,156,130,59,10,204,125,59,249,78,118,59,108,195,110,59,80,42,103,59,149,132,95,59,38,211,87,59,241,22,80,59,226,80,72,59,231,129,64,59,235,170,56,59,217,204,48,59,155,232,40,59,28,255,32,59,68,17,25,59,251,31,17,59,39,44,9,59,174,54,1,59,231,128,242,58,182,148,226,58,138,170,210,58,36,196,194,58,62,227,178,58,145,9,163,58,211,56,147,58,179,114,131,58,191,113,103,58,2,26,72,58,118,225,40,58,92,203,9,58,215,181,213,57,160,38,152,57,183,222,53,57,209,177,112,56,52,245,114,184,126,210,52,185,157,3,150,185,231,39,209,185,44,232,5,186,161,251,34,186,137,203,63,186,39,85,92,186,199,149,120,186,99,69,138,186,198,24,152,186,199,195,165,186,39,69,179,186,175,155,192,186,44,198,205,186,117,195,218,186,103,146,231,186,230,49,244,186,110,80,0,187,31,111,6,187,129,116,12,187,22,96,18,187,97,49,24,187,236,231,29,187,65,131,35,187,241,2,41,187,142,102,46,187,177,173,51,187,244,215,56,187,247,228,61,187,93,212,66,187,205,165,71,187,241,88,76,187,121,237,80,187,23,99,85,187,131,185,89,187,119,240,93,187,178,7,98,187,248,254,101,187,13,214,105,187,191,140,109,187,219,34,113,187,51,152,116,187,160,236,119,187,251,31,123,187,35,50,126,187,126,145,128,187,53,249,129,187,45,80,131,187,92,150,132,187,188,203,133,187,72,240,134,187,252,3,136,187,216,6,137,187,220,248,137,187,12,218,138,187,107,170,139,187,0,106,140,187,211,24,141,187,238,182,141,187,93,68,142,187,46,193,142,187,112,45,143,187,52,137,143,187,141,212,143,187,144,15,144,187,83,58,144,187,238,84,144,187,122,95,144,187,20,90,144,187,215,68,144,187,226,31,144,187,85,235,143,187,82,167,143,187,252,83,143,187,118,241,142,187,232,127,142,187,120,255,141,187,79,112,141,187,152,210,140,187,125,38,140,187,44,108,139,187,211,163,138,187,161,205,137,187,199,233,136,187,118,248,135,187,226,249,134,187,62,238,133,187,191,213,132,187,157,176,131,187,14,127,130,187,74,65,129,187,23,239,127,187,24,68,125,187,14,130,122,187,112,169,119,187,184,186,116,187,99,182,113,187,236,156,110,187,211,110,107,187,151,44,104,187,185,214,100,187,189,109,97,187,38,242,93,187,120,100,90,187,60,197,86,187,246,20,83,187,48,84,79,187,114,131,75,187,70,163,71,187,56,180,67,187,210,182,63,187,159,171,59,187,46,147,55,187,10,110,51,187,193,60,47,187,226,255,42,187,249,183,38,187,150,101,34,187,70,9,30,187,153,163,25,187,30,53,21,187,98,190,16,187,244,63,12,187,100,186,7,187,62,46,3,187,36,56,253,186,216,8,244,186,179,207,234,186,208,141,225,186,70,68,216,186,45,244,206,186,155,158,197,186,165,68,188,186,94,231,178,186,216,135,169,186,34,39,160,186,75,198,150,186,94,102,141,186,103,8,132,186,217,90,117,186,234,172,98,186,7,9,80,186,50,113,61,186,105,231,42,186,163,109,24,186,211,5,6,186,200,99,231,185,124,231,194,185,131,154,158,185,37,1,117,185,166,58,45,185,130,209,203,184,149,148,248,183,143,3,29,56,121,21,218,56,45,68,50,57,138,230,118,57,162,117,157,57,245,37,191,57,22,129,224,57,244,193,0,58,179,21,17,58,73,58,33,58,66,46,49,58,50,240,64,58,179,126,80,58,107,216,95,58,6,252,110,58,58,232,125,58,226,77,134,58,181,138,141,58,255,169,148,58,43,171,155,58,171,141,162,58,244,80,169,58,127,244,175,58,204,119,182,58,92,218,188,58,184,27,195,58,109,59,201,58,10,57,207,58,38,20,213,58,92,204,218,58,73,97,224,58,147,210,229,58,225,31,235,58,226,72,240,58,70,77,245,58,196,44,250,58,24,231,254,58,1,190,1,59,164,245,3,59,89,26,6,59,8,44,8,59,153,42,10,59,249,21,12,59,22,238,13,59,224,178,15,59,75,100,17,59,75,2,19,59,215,140,20,59,233,3,22,59,125,103,23,59,146,183,24,59,39,244,25,59,63,29,27,59,223,50,28,59,14,53,29,59,212,35,30,59,62,255,30,59,88,199,31,59,50,124,32,59,221,29,33,59,110,172,33,59,248,39,34,59,149,144,34,59,93,230,34,59,108,41,35,59,223,89,35,59,214,119,35,59,114,131,35,59,213,124,35,59,37,100,35,59,137,57,35,59,39,253,34,59,43,175,34,59,192,79,34,59,20,223,33,59,85,93,33,59,180,202,32,59,98,39,32,59,149,115,31,59,128,175,30,59,90,219,29,59,92,247,28,59,189,3,28,59,186,0,27,59,142,238,25,59,118,205,24,59,176,157,23,59,125,95,22,59,28,19,21,59,209,184,19,59,221,80,18,59,132,219,16,59,13,89,15,59,188,201,13,59,217,45,12,59,170,133,10,59,122,209,8,59,145,17,7,59,57,70,5,59,189,111,3,59,105,142,1,59,17,69,255,58,210,88,251,58,175,88,247,58,69,69,243,58,47,31,239,58,11,231,234,58,120,157,230,58,22,67,226,58,131,216,221,58,97,94,217,58,80,213,212,58,242,61,208,58,233,152,203,58,215,230,198,58,93,40,194,58,31,94,189,58,191,136,184,58,223,168,179,58,34,191,174,58,42,204,169,58,152,208,164,58,15,205,159,58,47,194,154,58,154,176,149,58,240,152,144,58,209,123,139,58,219,89,134,58,173,51,129,58,201,19,120,58,60,186,109,58,236,91,99,58,14,250,88,58,213,149,78,58,115,48,68,58,23,203,57,58,237,102,47,58,32,5,37,58,215,166,26,58,54,77,16,58,95,249,5,58,228,88,247,57,17,207,226,57,121,87,206,57,69,244,185,57,152,167,165,57,143,115,145,57,124,180,122,57,103,187,82,57,236,255,42,57,6,134,3,57,69,163,184,56,124,154,85,56,113,140,108,55,77,33,188,183,34,242,117,184,121,57,198,184,63,97,8,185,153,70,45,185,105,201,81,185,93,230,117,185,27,205,140,185,229,112,158,185,1,221,175,185,235,15,193,185,43,8,210,185,83,196,226,185,252,66,243,185,103,193,1,186,58,193,9,186,86,160,17,186,27,94,25,186,241,249,32,186,67,115,40,186,132,201,47,186,41,252,54,186,174,10,62,186,148,244,68,186,97,185,75,186,162,88,82,186,230,209,88,186,196,36,95,186,216,80,101,186,193,85,107,186,39,51,113,186,180,232,118,186,26,118,124,186,134,237,128,186,165,139,131,186,72,21,134,186,83,138,136,186,171,234,138,186,56,54,141,186,229,108,143,186,160,142,145,186,88,155,147,186,255,146,149,186,139,117,151,186,243,66,153,186,48,251,154,186,64,158,156,186,34,44,158,186,214,164,159,186,97,8,161,186,201,86,162,186,23,144,163,186,84,180,164,186,144,195,165,186,218,189,166,186,66,163,167,186,223,115,168,186,197,47,169,186,14,215,169,186,213,105,170,186,54,232,170,186,81,82,171,186,70,168,171,186,57,234,171,186,79,24,172,186,175,50,172,186,131,57,172,186,244,44,172,186,49,13,172,186,103,218,171,186,200,148,171,186,134,60,171,186,212,209,170,186,232,84,170,186,250,197,169,186,67,37,169,186,252,114,168,186,99,175,167,186,180,218,166,186,46,245,165,186,19,255,164,186,163,248,163,186,33,226,162,186,211,187,161,186,253,133,160,186,230,64,159,186,214,236,157,186,22,138,156,186,241,24,155,186,176,153,153,186,161,12,152,186,16,114,150,186,75,202,148,186,161,21,147,186,97,84,145,186,220,134,143,186,98,173,141,186,70,200,139,186,217,215,137,186,111,220,135,186,91,214,133,186,240,197,131,186,132,171,129,186,214,14,127,186,243,179,122,186,12,71,118,186,204,200,113,186,223,57,109,186,242,154,104,186,176,236,99,186,200,47,95,186,230,100,90,186,183,140,85,186,234,167,80,186,42,183,75,186,37,187,70,186,136,180,65,186,255,163,60,186,54,138,55,186,216,103,50,186,146,61,45,186,12,12,40,186,241,211,34,186,234,149,29,186,159,82,24,186,182,10,19,186,214,190,13,186,165,111,8,186,198,29,3,186,183,147,251,185,16,233,240,185,215,60,230,185,73,144,219,185,163,228,208,185,27,59,198,185,230,148,187,185,55,243,176,185,59,87,166,185,30,194,155,185,7,53,145,185,25,177,134,185,233,110,120,185,107,146,99,185,232,206,78,185,137,38,58,185,109,155,37,185,172,47,17,185,168,202,249,184,215,124,209,184,224,121,169,184,170,197,129,184,21,200,52,184,17,99,205,183,101,120,202,182,151,96,77,55,251,121,254,55,145,88,74,56,16,82,138,56,136,12,175,56,121,88,211,56,192,50,247,56,39,76,13,57,22,195,30,57,185,252,47,57,171,247,64,57,141,178,81,57,15,44,98,57,233,98,114,57,240,42,129,57,225,1,137,57,181,181,144,57,221,69,152,57,211,177,159,57,20,249,166,57,35,27,174,57,137,23,181,57,211,237,187,57,150,157,194,57,107,38,201,57,242,135,207,57,206,193,213,57,170,211,219,57,53,189,225,57,37,126,231,57,52,22,237,57,34,133,242,57,181,202,247,57,185,230,252,57,126,236,0,58,171,80,3,58,208,159,5,58,223,217,7,58,201,254,9,58,133,14,12,58,10,9,14,58,84,238,15,58,95,190,17,58,43,121,19,58,187,30,21,58,19,175,22,58,59,42,24,58,60,144,25,58,35,225,26,58,254,28,28,58,222,67,29,58,214,85,30,58,251,82,31,58,101,59,32,58,47,15,33,58,116,206,33,58,82,121,34,58,234,15,35,58,95,146,35,58,213,0,36,58,114,91,36,58,96,162,36,58,199,213,36,58,214,245,36,58,186,2,37,58,163,252,36,58,195,227,36,58,77,184,36,58,120,122,36,58,121,42,36,58,137,200,35,58,226,84,35,58,192,207,34,58,96,57,34,58,1,146,33,58,226,217,32,58,69,17,32,58,109,56,31,58,156,79,30,58,25,87,29,58,42,79,28,58,21,56,27,58,36,18,26,58,160,221,24,58,212,154,23,58,10,74,22,58,144,235,20,58,178,127,19,58,192,6,18,58,6,129,16,58,214,238,14,58,127,80,13,58,82,166,11,58,161,240,9,58,189,47,8,58,250,99,6,58,169,141,4,58,31,173,2,58,176,194,0,58,92,157,253,57,223,162,249,57,144,150,245,57,25,121,241,57,36,75,237,57,91,13,233,57,105,192,228,57,248,100,224,57,177,251,219,57,64,133,215,57,79,2,211,57,134,115,206,57,145,217,201,57,22,53,197,57,192,134,192,57,54,207,187,57,32,15,183,57,35,71,178,57,230,119,173,57,14,162,168,57,63,198,163,57,28,229,158,57,70,255,153,57,95,21,149,57,6,40,144,57,218,55,139,57,119,69,134,57,122,81,129,57,248,184,120,57,45,206,110,57,192,227,100,57,221,250,90,57,170,20,81,57,78,50,71,57,231,84,61,57,148,125,51,57,109,173,41,57,136,229,31,57,245,38,22,57,196,114,12,57,251,201,2,57,68,91,242,56,112,61,223,56,115,60,204,56,62,90,185,56,183,152,166,56,187,249,147,56,31,127,129,56,92,85,94,56,81,252,57,56,141,246,21,56,214,142,228,55,102,228,157,55,95,232,47,55,151,35,22,54,232,146,198,182,212,130,106,183,217,11,184,183,109,254,249,183,219,137,29,184,55,163,61,184,189,72,93,184,243,119,124,184,59,151,141,184,252,180,156,184,33,148,171,184,151,51,186,184,89,146,200,184,106,175,214,184,217,137,228,184,191,32,242,184,65,115,255,184,71,64,6,185,241,163,12,185,63,228,18,185,217,0,25,185,109,249,30,185,173,205,36,185,81,125,42,185,21,8,48,185,188,109,53,185,13,174,58,185,213,200,63,185,229,189,68,185,21,141,73,185,65,54,78,185,73,185,82,185,21,22,87,185,142,76,91,185,166,92,95,185,80,70,99,185,135,9,103,185,74,166,106,185,155,28,110,185,131,108,113,185,15,150,116,185,79,153,119,185,90,118,122,185,73,45,125,185,59,190,127,185,170,20,129,185,92,55,130,185,76,71,131,185,143,68,132,185,64,47,133,185,123,7,134,185,93,205,134,185,6,129,135,185,151,34,136,185,53,178,136,185,4,48,137,185,43,156,137,185,211,246,137,185,38,64,138,185,81,120,138,185,129,159,138,185,229,181,138,185,174,187,138,185,15,177,138,185,58,150,138,185,102,107,138,185,200,48,138,185,153,230,137,185,18,141,137,185,108,36,137,185,228,172,136,185,182,38,136,185,31,146,135,185,96,239,134,185,183,62,134,185,102,128,133,185,173,180,132,185,209,219,131,185,20,246,130,185,187,3,130,185,10,5,129,185,144,244,127,185,119,199,125,185,85,131,123,185,188,40,121,185,59,184,118,185,99,50,116,185,200,151,113,185,252,232,110,185,147,38,108,185,35,81,105,185,64,105,102,185,128,111,99,185,122,100,96,185,196,72,93,185,245,28,90,185,164,225,86,185,105,151,83,185,217,62,80,185,142,216,76,185,28,101,73,185,29,229,69,185,37,89,66,185,204,193,62,185,168,31,59,185,76,115,55,185,80,189,51,185,70,254,47,185,195,54,44,185,88,103,40,185,153,144,36,185,22,179,32,185,96,207,28,185,5,230,24,185,148,247,20,185,153,4,17,185,162,13,13,185,55,19,9,185,227,21,5,185,46,22,1,185,58,41,250,184,109,35,242,184,252,27,234,184,234,19,226,184,57,12,218,184,230,5,210,184,236,1,202,184,66,1,194,184,218,4,186,184,165,13,178,184,143,28,170,184,127,50,162,184,91,80,154,184,3,119,146,184,82,167,138,184,35,226,130,184,145,80,118,184,39,245,102,184,159,179,87,184,138,141,72,184,109,132,57,184,200,153,42,184,14,207,27,184,174,37,13,184,18,62,253,183,245,120,224,183,167,254,195,183,184,209,167,183,166,244,139,183,184,211,96,183,100,103,42,183,184,81,233,182,23,114,126,182,24,106,52,181,218,82,33,54,27,99,182,54,21,76,13,55,164,160,62,55,185,43,111,55,250,116,143,55,17,236,166,55,151,249,189,55,24,156,212,55,48,210,234,55,71,77,0,56,251,249,10,56,159,110,21,56,166,170,31,56,142,173,41,56,220,118,51,56,32,6,61,56,244,90,70,56,247,116,79,56,214,83,88,56,66,247,96,56,249,94,105,56,191,138,113,56,97,122,121,56,219,150,128,56,78,82,132,56,125,239,135,56,96,110,139,56,243,206,142,56,52,17,146,56,41,53,149,56,215,58,152,56,76,34,155,56,150,235,157,56,201,150,160,56,251,35,163,56,71,147,165,56,203,228,167,56,168,24,170,56,4,47,172,56,7,40,174,56,221,3,176,56,180,194,177,56,191,100,179,56,52,234,180,56,74,83,182,56,62,160,183,56,77,209,184,56,184,230,185,56,196,224,186,56,183,191,187,56,218,131,188,56,121,45,189,56,227,188,189,56,103,50,190,56,89,142,190,56,15,209,190,56,223,250,190,56,35,12,191,56,54,5,191,56,118,230,190,56,67,176,190,56,253,98,190,56,7,255,189,56,199,132,189,56,160,244,188,56,253,78,188,56,68,148,187,56,225,196,186,56,63,225,185,56,203,233,184,56,243,222,183,56,37,193,182,56,210,144,181,56,105,78,180,56,94,250,178,56,33,149,177,56,39,31,176,56,225,152,174,56,198,2,173,56,72,93,171,56,222,168,169,56,252,229,167,56,24,21,166,56,167,54,164,56,32,75,162,56,248,82,160,56,166,78,158,56,158,62,156,56,86,35,154,56,69,253,151,56,222,204,149,56,152,146,147,56,230,78,145,56,60,2,143,56,14,173,140,56,208,79,138,56,242,234,135,56,231,126,133,56,32,12,131,56,13,147,128,56,60,40,124,56,129,31,119,56,196,12,114,56,224,240,108,56,171,204,103,56,250,160,98,56,160,110,93,56,109,54,88,56,48,249,82,56,181,183,77,56,196,114,72,56,36,43,67,56,153,225,61,56,228,150,56,56,195,75,51,56,242,0,46,56,41,183,40,56,29,111,35,56,129,41,30,56,4,231,24,56,81,168,19,56,19,110,14,56,237,56,9,56,131,9,4,56,231,192,253,55,179,124,243,55,154,71,233,55,196,34,223,55,85,15,213,55,103,14,203,55,13,33,193,55,84,72,183,55,65,133,173,55,210,216,163,55,254,67,154,55,180,199,144,55,220,100,135,55,173,56,124,55,249,221,105,55,63,187,87,55,19,210,69,55,252,35,52,55,112,178,34,55,213,126,17,55,134,138,0,55,153,173,223,54,198,201,190,54,237,107,158,54,144,44,125,54,236,149,62,54,239,23,1,54,193,108,137,53,223,76,23,52,208,165,66,181,149,66,211,181,211,111,33,182,98,18,88,182,68,195,134,182,3,229,160,182,109,109,186,182,154,91,211,182,190,174,235,182,20,179,1,183,159,64,13,183,195,127,24,183,76,112,35,183,22,18,46,183,7,101,56,183,20,105,66,183,58,30,76,183,134,132,85,183,14,156,94,183,245,100,103,183,105,223,111,183,162,11,120,183,229,233,127,183,64,189,131,183,229,94,135,183,21,218,138,183,5,47,142,183,241,93,145,183,26,103,148,183,196,74,151,183,57,9,154,183,198,162,156,183,191,23,159,183,122,104,161,183,80,149,163,183,162,158,165,183,208,132,167,183,66,72,169,183,96,233,170,183,153,104,172,183,92,198,173,183,30,3,175,183,85,31,176,183,123,27,177,183,13,248,177,183,139,181,178,183,117,84,179,183,82,213,179,183,167,56,180,183,255,126,180,183,227,168,180,183,227,182,180,183,140,169,180,183,112,129,180,183,33,63,180,183,52,227,179,183,62,110,179,183,213,224,178,183,146,59,178,183,15,127,177,183,228,171,176,183,172,194,175,183,4,196,174,183,135,176,173,183,210,136,172,183,130,77,171,183,52,255,169,183,134,158,168,183,22,44,167,183,128,168,165,183,99,20,164,183,92,112,162,183,6,189,160,183,0,251,158,183,229,42,157,183,80,77,155,183,220,98,153,183,34,108,151,183,188,105,149,183,66,92,147,183,75,68,145,183,109,34,143,183,60,247,140,183,75,195,138,183,46,135,136,183,116,67,134,183,173,248,131,183,102,167,129,183,87,160,126,183,14,231,121,183,4,36,117,183,69,88,112,183,219,132,107,183,204,170,102,183,26,203,97,183,194,230,92,183,190,254,87,183,4,20,83,183,131,39,78,183,41,58,73,183,222,76,68,183,133,96,63,183,252,117,58,183,29,142,53,183,190,169,48,183,175,201,43,183,187,238,38,183,168,25,34,183,55,75,29,183,37,132,24,183,41,197,19,183,244,14,15,183,51,98,10,183,141,191,5,183,165,39,1,183,48,54,249,182,251,52,240,182,206,76,231,182,196,126,222,182,238,203,213,182,78,53,205,182,225,187,196,182,148,96,188,182,78,36,180,182,232,7,172,182,51,12,164,182,244,49,156,182,229,121,148,182,184,228,140,182,19,115,133,182,33,75,124,182,135,249,109,182,103,242,95,182,187,54,82,182,108,199,68,182,75,165,55,182,25,209,42,182,132,75,30,182,35,21,18,182,127,46,6,182,20,48,245,181,78,164,222,181,73,186,200,181,130,114,179,181,80,205,158,181,232,202,138,181,189,214,110,181,74,93,73,181,26,41,37,181,142,57,2,181,145,27,193,180,94,73,128,180,168,243,3,180,0,0,0,0,0,0,0,0,73,110,116,101,114,110,97,108,32,101,114,114,111,114,46,32,78,111,32,112,114,111,99,101,115,115,32,112,111,105,110,116,101,114,46,0,0,0,0,0,115,114,99,95,112,114,111,99,101,115,115,40,41,32,99,97,108,108,101,100,32,119,105,116,104,111,117,116,32,114,101,115,101,116,32,97,102,116,101,114,32,101,110,100,95,111,102,95,105,110,112,117,116,46,0,0,83,82,67,32,114,97,116,105,111,32,111,117,116,115,105,100,101,32,91,49,47,50,53,54,44,32,50,53,54,93,32,114,97,110,103,101,46,0,0,0,73,110,116,101,114,110,97,108,32,101,114,114,111,114,46,32,78,111,32,112,114,105,118,97,116,101,32,100,97,116,97,46,0,0,0,0,0,0,0,0,83,82,67,95,68,65,84,65,45,62,100,97,116,97,95,111,117,116,32,105,115,32,78,85,76,76,46,0,0,0,0,0,83,82,67,95,68,65,84,65,32,112,111,105,110,116,101,114,32,105,115,32,78,85,76,76,46,0,0,0,0,0,0,0,83,82,67,95,83,84,65,84,69,32,112,111,105,110,116,101,114,32,105,115,32,78,85,76,76,46,0,0,0,0,0,0,80,108,97,99,101,104,111,108,100,101,114,46,32,78,111,32,101,114,114,111,114,32,100,101,102,105,110,101,100,32,102,111,114,32,116,104,105,115,32,101,114,114,111,114,32,110,117,109,98,101,114,46,0,0,0,0,73,110,116,101,114,110,97,108,32,101,114,114,111,114,32,58,32,66,97,100,32,108,101,110,103,116,104,32,105,110,32,112,114,101,112,97,114,101,95,100,97,116,97,32,40,41,46,0,84,104,105,115,32,99,111,110,118,101,114,116,101,114,32,111,110,108,121,32,97,108,108,111,119,115,32,99,111,110,115,116,97,110,116,32,99,111,110,118,101,114,115,105,111,110,32,114,97,116,105,111,115,46,0,0,67,97,108,108,98,97,99,107,32,102,117,110,99,116,105,111,110,32,112,111,105,110,116,101,114,32,105,115,32,78,85,76,76,32,105,110,32,115,114,99,95,99,97,108,108,98,97,99,107,95,114,101,97,100,32,40,41,46,0,0,0,0,0,0,77,97,108,108,111,99,32,102,97,105,108,101,100,46,0,0,67,97,108,108,105,110,103,32,109,111,100,101,32,100,105,102,102,101,114,115,32,102,114,111,109,32,105,110,105,116,105,97,108,105,115,97,116,105,111,110,32,109,111,100,101,32,40,105,101,32,112,114,111,99,101,115,115,32,118,32,99,97,108,108,98,97,99,107,41,46,0,0,83,117,112,112,108,105,101,100,32,99,97,108,108,98,97,99,107,32,102,117,110,99,116,105,111,110,32,112,111,105,110,116,101,114,32,105,115,32,78,85,76,76,46,0,0,0,0,0,73,110,112,117,116,32,97,110,100,32,111,117,116,112,117,116,32,100,97,116,97,32,97,114,114,97,121,115,32,111,118,101,114,108,97,112,46,0,0,0,73,110,116,101,114,110,97,108,32,101,114,114,111,114,46,32,80,114,105,118,97,116,101,32,112,111,105,110,116,101,114,32,105,115,32,78,85,76,76,46,32,80,108,101,97,115,101,32,114,101,112,111,114,116,32,116,104,105,115,46,0,0,0,0,73,110,116,101,114,110,97,108,32,101,114,114,111,114,46,32,73,110,112,117,116,32,100,97,116,97,32,47,32,105,110,116,101,114,110,97,108,32,98,117,102,102,101,114,32,115,105,122,101,32,100,105,102,102,101,114,101,110,99,101,46,32,80,108,101,97,115,101,32,114,101,112,111,114,116,32,116,104,105,115,46,0,0,0,0,0,0,0,73,110,116,101,114,110,97,108,32,101,114,114,111,114,46,32,66,97,100,32,98,117,102,102,101,114,32,108,101,110,103,116,104,46,32,80,108,101,97,115,101,32,114,101,112,111,114,116,32,116,104,105,115,46,0,0,67,104,97,110,110,101,108,32,99,111,117,110,116,32,109,117,115,116,32,98,101,32,62,61,32,49,46,0,0,0,0,0,66,97,100,32,99,111,110,118,101,114,116,101,114,32,110,117,109,98,101,114,46,0,0,0,73,110,116,101,114,110,97,108,32,101,114,114,111,114,46,32,70,105,108,116,101,114,32,108,101,110,103,116,104,32,116,111,111,32,108,97,114,103,101,46,0,0,0,0,0,0,0,0,73,110,116,101,114,110,97,108,32,101,114,114,111,114,46,32,83,72,73,70,84,95,66,73,84,83,32,116,111,111,32,108,97,114,103,101,46,0,0,0,78,111,32,101,114,114,111,114,46,0,0,0,0,0,0,0], "i8", ALLOC_NONE, Runtime.GLOBAL_BASE+92160);



var tempDoublePtr = Runtime.alignMemory(allocate(12, "i8", ALLOC_STATIC), 8);

assert(tempDoublePtr % 8 == 0);

function copyTempFloat(ptr) { // functions, because inlining this code increases code size too much

  HEAP8[tempDoublePtr] = HEAP8[ptr];

  HEAP8[tempDoublePtr+1] = HEAP8[ptr+1];

  HEAP8[tempDoublePtr+2] = HEAP8[ptr+2];

  HEAP8[tempDoublePtr+3] = HEAP8[ptr+3];

}

function copyTempDouble(ptr) {

  HEAP8[tempDoublePtr] = HEAP8[ptr];

  HEAP8[tempDoublePtr+1] = HEAP8[ptr+1];

  HEAP8[tempDoublePtr+2] = HEAP8[ptr+2];

  HEAP8[tempDoublePtr+3] = HEAP8[ptr+3];

  HEAP8[tempDoublePtr+4] = HEAP8[ptr+4];

  HEAP8[tempDoublePtr+5] = HEAP8[ptr+5];

  HEAP8[tempDoublePtr+6] = HEAP8[ptr+6];

  HEAP8[tempDoublePtr+7] = HEAP8[ptr+7];

}


  
   
  Module["_memset"] = _memset;var _llvm_memset_p0i8_i32=_memset;

  var _fabs=Math_abs;

  
  function _rint(x) {
      if (Math.abs(x % 1) !== 0.5) return Math.round(x);
      return x + x % 2 + ((x < 0) ? 1 : -1);
    }var _lrint=_rint;

  var _llvm_memset_p0i8_i64=_memset;

  
  
  function _emscripten_memcpy_big(dest, src, num) {
      HEAPU8.set(HEAPU8.subarray(src, src+num), dest);
      return dest;
    } 
  Module["_memcpy"] = _memcpy;var _llvm_memcpy_p0i8_p0i8_i32=_memcpy;

  
   
  Module["_memmove"] = _memmove;var _llvm_memmove_p0i8_p0i8_i32=_memmove;

  function _abort() {
      Module['abort']();
    }

  
  
  var ___errno_state=0;function ___setErrNo(value) {
      // For convenient setting and returning of errno.
      HEAP32[((___errno_state)>>2)]=value;
      return value;
    }function ___errno_location() {
      return ___errno_state;
    }

  function _sbrk(bytes) {
      // Implement a Linux-like 'memory area' for our 'process'.
      // Changes the size of the memory area by |bytes|; returns the
      // address of the previous top ('break') of the memory area
      // We control the "dynamic" memory - DYNAMIC_BASE to DYNAMICTOP
      var self = _sbrk;
      if (!self.called) {
        DYNAMICTOP = alignMemoryPage(DYNAMICTOP); // make sure we start out aligned
        self.called = true;
        assert(Runtime.dynamicAlloc);
        self.alloc = Runtime.dynamicAlloc;
        Runtime.dynamicAlloc = function() { abort('cannot dynamically allocate, sbrk now has control') };
      }
      var ret = DYNAMICTOP;
      if (bytes != 0) self.alloc(bytes);
      return ret;  // Previous break location.
    }

  
  var ERRNO_CODES={EPERM:1,ENOENT:2,ESRCH:3,EINTR:4,EIO:5,ENXIO:6,E2BIG:7,ENOEXEC:8,EBADF:9,ECHILD:10,EAGAIN:11,EWOULDBLOCK:11,ENOMEM:12,EACCES:13,EFAULT:14,ENOTBLK:15,EBUSY:16,EEXIST:17,EXDEV:18,ENODEV:19,ENOTDIR:20,EISDIR:21,EINVAL:22,ENFILE:23,EMFILE:24,ENOTTY:25,ETXTBSY:26,EFBIG:27,ENOSPC:28,ESPIPE:29,EROFS:30,EMLINK:31,EPIPE:32,EDOM:33,ERANGE:34,ENOMSG:42,EIDRM:43,ECHRNG:44,EL2NSYNC:45,EL3HLT:46,EL3RST:47,ELNRNG:48,EUNATCH:49,ENOCSI:50,EL2HLT:51,EDEADLK:35,ENOLCK:37,EBADE:52,EBADR:53,EXFULL:54,ENOANO:55,EBADRQC:56,EBADSLT:57,EDEADLOCK:35,EBFONT:59,ENOSTR:60,ENODATA:61,ETIME:62,ENOSR:63,ENONET:64,ENOPKG:65,EREMOTE:66,ENOLINK:67,EADV:68,ESRMNT:69,ECOMM:70,EPROTO:71,EMULTIHOP:72,EDOTDOT:73,EBADMSG:74,ENOTUNIQ:76,EBADFD:77,EREMCHG:78,ELIBACC:79,ELIBBAD:80,ELIBSCN:81,ELIBMAX:82,ELIBEXEC:83,ENOSYS:38,ENOTEMPTY:39,ENAMETOOLONG:36,ELOOP:40,EOPNOTSUPP:95,EPFNOSUPPORT:96,ECONNRESET:104,ENOBUFS:105,EAFNOSUPPORT:97,EPROTOTYPE:91,ENOTSOCK:88,ENOPROTOOPT:92,ESHUTDOWN:108,ECONNREFUSED:111,EADDRINUSE:98,ECONNABORTED:103,ENETUNREACH:101,ENETDOWN:100,ETIMEDOUT:110,EHOSTDOWN:112,EHOSTUNREACH:113,EINPROGRESS:115,EALREADY:114,EDESTADDRREQ:89,EMSGSIZE:90,EPROTONOSUPPORT:93,ESOCKTNOSUPPORT:94,EADDRNOTAVAIL:99,ENETRESET:102,EISCONN:106,ENOTCONN:107,ETOOMANYREFS:109,EUSERS:87,EDQUOT:122,ESTALE:116,ENOTSUP:95,ENOMEDIUM:123,EILSEQ:84,EOVERFLOW:75,ECANCELED:125,ENOTRECOVERABLE:131,EOWNERDEAD:130,ESTRPIPE:86};function _sysconf(name) {
      // long sysconf(int name);
      // http://pubs.opengroup.org/onlinepubs/009695399/functions/sysconf.html
      switch(name) {
        case 30: return PAGE_SIZE;
        case 132:
        case 133:
        case 12:
        case 137:
        case 138:
        case 15:
        case 235:
        case 16:
        case 17:
        case 18:
        case 19:
        case 20:
        case 149:
        case 13:
        case 10:
        case 236:
        case 153:
        case 9:
        case 21:
        case 22:
        case 159:
        case 154:
        case 14:
        case 77:
        case 78:
        case 139:
        case 80:
        case 81:
        case 79:
        case 82:
        case 68:
        case 67:
        case 164:
        case 11:
        case 29:
        case 47:
        case 48:
        case 95:
        case 52:
        case 51:
        case 46:
          return 200809;
        case 27:
        case 246:
        case 127:
        case 128:
        case 23:
        case 24:
        case 160:
        case 161:
        case 181:
        case 182:
        case 242:
        case 183:
        case 184:
        case 243:
        case 244:
        case 245:
        case 165:
        case 178:
        case 179:
        case 49:
        case 50:
        case 168:
        case 169:
        case 175:
        case 170:
        case 171:
        case 172:
        case 97:
        case 76:
        case 32:
        case 173:
        case 35:
          return -1;
        case 176:
        case 177:
        case 7:
        case 155:
        case 8:
        case 157:
        case 125:
        case 126:
        case 92:
        case 93:
        case 129:
        case 130:
        case 131:
        case 94:
        case 91:
          return 1;
        case 74:
        case 60:
        case 69:
        case 70:
        case 4:
          return 1024;
        case 31:
        case 42:
        case 72:
          return 32;
        case 87:
        case 26:
        case 33:
          return 2147483647;
        case 34:
        case 1:
          return 47839;
        case 38:
        case 36:
          return 99;
        case 43:
        case 37:
          return 2048;
        case 0: return 2097152;
        case 3: return 65536;
        case 28: return 32768;
        case 44: return 32767;
        case 75: return 16384;
        case 39: return 1000;
        case 89: return 700;
        case 71: return 256;
        case 40: return 255;
        case 2: return 100;
        case 180: return 64;
        case 25: return 20;
        case 5: return 16;
        case 6: return 6;
        case 73: return 4;
        case 84: return 1;
      }
      ___setErrNo(ERRNO_CODES.EINVAL);
      return -1;
    }

  function _time(ptr) {
      var ret = Math.floor(Date.now()/1000);
      if (ptr) {
        HEAP32[((ptr)>>2)]=ret;
      }
      return ret;
    }





   
  Module["_strlen"] = _strlen;

  
  
  
  var ERRNO_MESSAGES={0:"Success",1:"Not super-user",2:"No such file or directory",3:"No such process",4:"Interrupted system call",5:"I/O error",6:"No such device or address",7:"Arg list too long",8:"Exec format error",9:"Bad file number",10:"No children",11:"No more processes",12:"Not enough core",13:"Permission denied",14:"Bad address",15:"Block device required",16:"Mount device busy",17:"File exists",18:"Cross-device link",19:"No such device",20:"Not a directory",21:"Is a directory",22:"Invalid argument",23:"Too many open files in system",24:"Too many open files",25:"Not a typewriter",26:"Text file busy",27:"File too large",28:"No space left on device",29:"Illegal seek",30:"Read only file system",31:"Too many links",32:"Broken pipe",33:"Math arg out of domain of func",34:"Math result not representable",35:"File locking deadlock error",36:"File or path name too long",37:"No record locks available",38:"Function not implemented",39:"Directory not empty",40:"Too many symbolic links",42:"No message of desired type",43:"Identifier removed",44:"Channel number out of range",45:"Level 2 not synchronized",46:"Level 3 halted",47:"Level 3 reset",48:"Link number out of range",49:"Protocol driver not attached",50:"No CSI structure available",51:"Level 2 halted",52:"Invalid exchange",53:"Invalid request descriptor",54:"Exchange full",55:"No anode",56:"Invalid request code",57:"Invalid slot",59:"Bad font file fmt",60:"Device not a stream",61:"No data (for no delay io)",62:"Timer expired",63:"Out of streams resources",64:"Machine is not on the network",65:"Package not installed",66:"The object is remote",67:"The link has been severed",68:"Advertise error",69:"Srmount error",70:"Communication error on send",71:"Protocol error",72:"Multihop attempted",73:"Cross mount point (not really error)",74:"Trying to read unreadable message",75:"Value too large for defined data type",76:"Given log. name not unique",77:"f.d. invalid for this operation",78:"Remote address changed",79:"Can   access a needed shared lib",80:"Accessing a corrupted shared lib",81:".lib section in a.out corrupted",82:"Attempting to link in too many libs",83:"Attempting to exec a shared library",84:"Illegal byte sequence",86:"Streams pipe error",87:"Too many users",88:"Socket operation on non-socket",89:"Destination address required",90:"Message too long",91:"Protocol wrong type for socket",92:"Protocol not available",93:"Unknown protocol",94:"Socket type not supported",95:"Not supported",96:"Protocol family not supported",97:"Address family not supported by protocol family",98:"Address already in use",99:"Address not available",100:"Network interface is not configured",101:"Network is unreachable",102:"Connection reset by network",103:"Connection aborted",104:"Connection reset by peer",105:"No buffer space available",106:"Socket is already connected",107:"Socket is not connected",108:"Can't send after socket shutdown",109:"Too many references",110:"Connection timed out",111:"Connection refused",112:"Host is down",113:"Host is unreachable",114:"Socket already connected",115:"Connection already in progress",116:"Stale file handle",122:"Quota exceeded",123:"No medium (in tape drive)",125:"Operation canceled",130:"Previous owner died",131:"State not recoverable"};
  
  var TTY={ttys:[],init:function () {
        // https://github.com/kripken/emscripten/pull/1555
        // if (ENVIRONMENT_IS_NODE) {
        //   // currently, FS.init does not distinguish if process.stdin is a file or TTY
        //   // device, it always assumes it's a TTY device. because of this, we're forcing
        //   // process.stdin to UTF8 encoding to at least make stdin reading compatible
        //   // with text files until FS.init can be refactored.
        //   process['stdin']['setEncoding']('utf8');
        // }
      },shutdown:function () {
        // https://github.com/kripken/emscripten/pull/1555
        // if (ENVIRONMENT_IS_NODE) {
        //   // inolen: any idea as to why node -e 'process.stdin.read()' wouldn't exit immediately (with process.stdin being a tty)?
        //   // isaacs: because now it's reading from the stream, you've expressed interest in it, so that read() kicks off a _read() which creates a ReadReq operation
        //   // inolen: I thought read() in that case was a synchronous operation that just grabbed some amount of buffered data if it exists?
        //   // isaacs: it is. but it also triggers a _read() call, which calls readStart() on the handle
        //   // isaacs: do process.stdin.pause() and i'd think it'd probably close the pending call
        //   process['stdin']['pause']();
        // }
      },register:function (dev, ops) {
        TTY.ttys[dev] = { input: [], output: [], ops: ops };
        FS.registerDevice(dev, TTY.stream_ops);
      },stream_ops:{open:function (stream) {
          var tty = TTY.ttys[stream.node.rdev];
          if (!tty) {
            throw new FS.ErrnoError(ERRNO_CODES.ENODEV);
          }
          stream.tty = tty;
          stream.seekable = false;
        },close:function (stream) {
          // flush any pending line data
          if (stream.tty.output.length) {
            stream.tty.ops.put_char(stream.tty, 10);
          }
        },read:function (stream, buffer, offset, length, pos /* ignored */) {
          if (!stream.tty || !stream.tty.ops.get_char) {
            throw new FS.ErrnoError(ERRNO_CODES.ENXIO);
          }
          var bytesRead = 0;
          for (var i = 0; i < length; i++) {
            var result;
            try {
              result = stream.tty.ops.get_char(stream.tty);
            } catch (e) {
              throw new FS.ErrnoError(ERRNO_CODES.EIO);
            }
            if (result === undefined && bytesRead === 0) {
              throw new FS.ErrnoError(ERRNO_CODES.EAGAIN);
            }
            if (result === null || result === undefined) break;
            bytesRead++;
            buffer[offset+i] = result;
          }
          if (bytesRead) {
            stream.node.timestamp = Date.now();
          }
          return bytesRead;
        },write:function (stream, buffer, offset, length, pos) {
          if (!stream.tty || !stream.tty.ops.put_char) {
            throw new FS.ErrnoError(ERRNO_CODES.ENXIO);
          }
          for (var i = 0; i < length; i++) {
            try {
              stream.tty.ops.put_char(stream.tty, buffer[offset+i]);
            } catch (e) {
              throw new FS.ErrnoError(ERRNO_CODES.EIO);
            }
          }
          if (length) {
            stream.node.timestamp = Date.now();
          }
          return i;
        }},default_tty_ops:{get_char:function (tty) {
          if (!tty.input.length) {
            var result = null;
            if (ENVIRONMENT_IS_NODE) {
              result = process['stdin']['read']();
              if (!result) {
                if (process['stdin']['_readableState'] && process['stdin']['_readableState']['ended']) {
                  return null;  // EOF
                }
                return undefined;  // no data available
              }
            } else if (typeof window != 'undefined' &&
              typeof window.prompt == 'function') {
              // Browser.
              result = window.prompt('Input: ');  // returns null on cancel
              if (result !== null) {
                result += '\n';
              }
            } else if (typeof readline == 'function') {
              // Command line.
              result = readline();
              if (result !== null) {
                result += '\n';
              }
            }
            if (!result) {
              return null;
            }
            tty.input = intArrayFromString(result, true);
          }
          return tty.input.shift();
        },put_char:function (tty, val) {
          if (val === null || val === 10) {
            Module['print'](tty.output.join(''));
            tty.output = [];
          } else {
            tty.output.push(TTY.utf8.processCChar(val));
          }
        }},default_tty1_ops:{put_char:function (tty, val) {
          if (val === null || val === 10) {
            Module['printErr'](tty.output.join(''));
            tty.output = [];
          } else {
            tty.output.push(TTY.utf8.processCChar(val));
          }
        }}};
  
  var MEMFS={ops_table:null,CONTENT_OWNING:1,CONTENT_FLEXIBLE:2,CONTENT_FIXED:3,mount:function (mount) {
        return MEMFS.createNode(null, '/', 16384 | 511 /* 0777 */, 0);
      },createNode:function (parent, name, mode, dev) {
        if (FS.isBlkdev(mode) || FS.isFIFO(mode)) {
          // no supported
          throw new FS.ErrnoError(ERRNO_CODES.EPERM);
        }
        if (!MEMFS.ops_table) {
          MEMFS.ops_table = {
            dir: {
              node: {
                getattr: MEMFS.node_ops.getattr,
                setattr: MEMFS.node_ops.setattr,
                lookup: MEMFS.node_ops.lookup,
                mknod: MEMFS.node_ops.mknod,
                rename: MEMFS.node_ops.rename,
                unlink: MEMFS.node_ops.unlink,
                rmdir: MEMFS.node_ops.rmdir,
                readdir: MEMFS.node_ops.readdir,
                symlink: MEMFS.node_ops.symlink
              },
              stream: {
                llseek: MEMFS.stream_ops.llseek
              }
            },
            file: {
              node: {
                getattr: MEMFS.node_ops.getattr,
                setattr: MEMFS.node_ops.setattr
              },
              stream: {
                llseek: MEMFS.stream_ops.llseek,
                read: MEMFS.stream_ops.read,
                write: MEMFS.stream_ops.write,
                allocate: MEMFS.stream_ops.allocate,
                mmap: MEMFS.stream_ops.mmap
              }
            },
            link: {
              node: {
                getattr: MEMFS.node_ops.getattr,
                setattr: MEMFS.node_ops.setattr,
                readlink: MEMFS.node_ops.readlink
              },
              stream: {}
            },
            chrdev: {
              node: {
                getattr: MEMFS.node_ops.getattr,
                setattr: MEMFS.node_ops.setattr
              },
              stream: FS.chrdev_stream_ops
            },
          };
        }
        var node = FS.createNode(parent, name, mode, dev);
        if (FS.isDir(node.mode)) {
          node.node_ops = MEMFS.ops_table.dir.node;
          node.stream_ops = MEMFS.ops_table.dir.stream;
          node.contents = {};
        } else if (FS.isFile(node.mode)) {
          node.node_ops = MEMFS.ops_table.file.node;
          node.stream_ops = MEMFS.ops_table.file.stream;
          node.contents = [];
          node.contentMode = MEMFS.CONTENT_FLEXIBLE;
        } else if (FS.isLink(node.mode)) {
          node.node_ops = MEMFS.ops_table.link.node;
          node.stream_ops = MEMFS.ops_table.link.stream;
        } else if (FS.isChrdev(node.mode)) {
          node.node_ops = MEMFS.ops_table.chrdev.node;
          node.stream_ops = MEMFS.ops_table.chrdev.stream;
        }
        node.timestamp = Date.now();
        // add the new node to the parent
        if (parent) {
          parent.contents[name] = node;
        }
        return node;
      },ensureFlexible:function (node) {
        if (node.contentMode !== MEMFS.CONTENT_FLEXIBLE) {
          var contents = node.contents;
          node.contents = Array.prototype.slice.call(contents);
          node.contentMode = MEMFS.CONTENT_FLEXIBLE;
        }
      },node_ops:{getattr:function (node) {
          var attr = {};
          // device numbers reuse inode numbers.
          attr.dev = FS.isChrdev(node.mode) ? node.id : 1;
          attr.ino = node.id;
          attr.mode = node.mode;
          attr.nlink = 1;
          attr.uid = 0;
          attr.gid = 0;
          attr.rdev = node.rdev;
          if (FS.isDir(node.mode)) {
            attr.size = 4096;
          } else if (FS.isFile(node.mode)) {
            attr.size = node.contents.length;
          } else if (FS.isLink(node.mode)) {
            attr.size = node.link.length;
          } else {
            attr.size = 0;
          }
          attr.atime = new Date(node.timestamp);
          attr.mtime = new Date(node.timestamp);
          attr.ctime = new Date(node.timestamp);
          // NOTE: In our implementation, st_blocks = Math.ceil(st_size/st_blksize),
          //       but this is not required by the standard.
          attr.blksize = 4096;
          attr.blocks = Math.ceil(attr.size / attr.blksize);
          return attr;
        },setattr:function (node, attr) {
          if (attr.mode !== undefined) {
            node.mode = attr.mode;
          }
          if (attr.timestamp !== undefined) {
            node.timestamp = attr.timestamp;
          }
          if (attr.size !== undefined) {
            MEMFS.ensureFlexible(node);
            var contents = node.contents;
            if (attr.size < contents.length) contents.length = attr.size;
            else while (attr.size > contents.length) contents.push(0);
          }
        },lookup:function (parent, name) {
          throw FS.genericErrors[ERRNO_CODES.ENOENT];
        },mknod:function (parent, name, mode, dev) {
          return MEMFS.createNode(parent, name, mode, dev);
        },rename:function (old_node, new_dir, new_name) {
          // if we're overwriting a directory at new_name, make sure it's empty.
          if (FS.isDir(old_node.mode)) {
            var new_node;
            try {
              new_node = FS.lookupNode(new_dir, new_name);
            } catch (e) {
            }
            if (new_node) {
              for (var i in new_node.contents) {
                throw new FS.ErrnoError(ERRNO_CODES.ENOTEMPTY);
              }
            }
          }
          // do the internal rewiring
          delete old_node.parent.contents[old_node.name];
          old_node.name = new_name;
          new_dir.contents[new_name] = old_node;
          old_node.parent = new_dir;
        },unlink:function (parent, name) {
          delete parent.contents[name];
        },rmdir:function (parent, name) {
          var node = FS.lookupNode(parent, name);
          for (var i in node.contents) {
            throw new FS.ErrnoError(ERRNO_CODES.ENOTEMPTY);
          }
          delete parent.contents[name];
        },readdir:function (node) {
          var entries = ['.', '..']
          for (var key in node.contents) {
            if (!node.contents.hasOwnProperty(key)) {
              continue;
            }
            entries.push(key);
          }
          return entries;
        },symlink:function (parent, newname, oldpath) {
          var node = MEMFS.createNode(parent, newname, 511 /* 0777 */ | 40960, 0);
          node.link = oldpath;
          return node;
        },readlink:function (node) {
          if (!FS.isLink(node.mode)) {
            throw new FS.ErrnoError(ERRNO_CODES.EINVAL);
          }
          return node.link;
        }},stream_ops:{read:function (stream, buffer, offset, length, position) {
          var contents = stream.node.contents;
          if (position >= contents.length)
            return 0;
          var size = Math.min(contents.length - position, length);
          assert(size >= 0);
          if (size > 8 && contents.subarray) { // non-trivial, and typed array
            buffer.set(contents.subarray(position, position + size), offset);
          } else
          {
            for (var i = 0; i < size; i++) {
              buffer[offset + i] = contents[position + i];
            }
          }
          return size;
        },write:function (stream, buffer, offset, length, position, canOwn) {
          var node = stream.node;
          node.timestamp = Date.now();
          var contents = node.contents;
          if (length && contents.length === 0 && position === 0 && buffer.subarray) {
            // just replace it with the new data
            if (canOwn && offset === 0) {
              node.contents = buffer; // this could be a subarray of Emscripten HEAP, or allocated from some other source.
              node.contentMode = (buffer.buffer === HEAP8.buffer) ? MEMFS.CONTENT_OWNING : MEMFS.CONTENT_FIXED;
            } else {
              node.contents = new Uint8Array(buffer.subarray(offset, offset+length));
              node.contentMode = MEMFS.CONTENT_FIXED;
            }
            return length;
          }
          MEMFS.ensureFlexible(node);
          var contents = node.contents;
          while (contents.length < position) contents.push(0);
          for (var i = 0; i < length; i++) {
            contents[position + i] = buffer[offset + i];
          }
          return length;
        },llseek:function (stream, offset, whence) {
          var position = offset;
          if (whence === 1) {  // SEEK_CUR.
            position += stream.position;
          } else if (whence === 2) {  // SEEK_END.
            if (FS.isFile(stream.node.mode)) {
              position += stream.node.contents.length;
            }
          }
          if (position < 0) {
            throw new FS.ErrnoError(ERRNO_CODES.EINVAL);
          }
          stream.ungotten = [];
          stream.position = position;
          return position;
        },allocate:function (stream, offset, length) {
          MEMFS.ensureFlexible(stream.node);
          var contents = stream.node.contents;
          var limit = offset + length;
          while (limit > contents.length) contents.push(0);
        },mmap:function (stream, buffer, offset, length, position, prot, flags) {
          if (!FS.isFile(stream.node.mode)) {
            throw new FS.ErrnoError(ERRNO_CODES.ENODEV);
          }
          var ptr;
          var allocated;
          var contents = stream.node.contents;
          // Only make a new copy when MAP_PRIVATE is specified.
          if ( !(flags & 2) &&
                (contents.buffer === buffer || contents.buffer === buffer.buffer) ) {
            // We can't emulate MAP_SHARED when the file is not backed by the buffer
            // we're mapping to (e.g. the HEAP buffer).
            allocated = false;
            ptr = contents.byteOffset;
          } else {
            // Try to avoid unnecessary slices.
            if (position > 0 || position + length < contents.length) {
              if (contents.subarray) {
                contents = contents.subarray(position, position + length);
              } else {
                contents = Array.prototype.slice.call(contents, position, position + length);
              }
            }
            allocated = true;
            ptr = _malloc(length);
            if (!ptr) {
              throw new FS.ErrnoError(ERRNO_CODES.ENOMEM);
            }
            buffer.set(contents, ptr);
          }
          return { ptr: ptr, allocated: allocated };
        }}};
  
  var IDBFS={dbs:{},indexedDB:function () {
        return window.indexedDB || window.mozIndexedDB || window.webkitIndexedDB || window.msIndexedDB;
      },DB_VERSION:21,DB_STORE_NAME:"FILE_DATA",mount:function (mount) {
        // reuse all of the core MEMFS functionality
        return MEMFS.mount.apply(null, arguments);
      },syncfs:function (mount, populate, callback) {
        IDBFS.getLocalSet(mount, function(err, local) {
          if (err) return callback(err);
  
          IDBFS.getRemoteSet(mount, function(err, remote) {
            if (err) return callback(err);
  
            var src = populate ? remote : local;
            var dst = populate ? local : remote;
  
            IDBFS.reconcile(src, dst, callback);
          });
        });
      },getDB:function (name, callback) {
        // check the cache first
        var db = IDBFS.dbs[name];
        if (db) {
          return callback(null, db);
        }
  
        var req;
        try {
          req = IDBFS.indexedDB().open(name, IDBFS.DB_VERSION);
        } catch (e) {
          return callback(e);
        }
        req.onupgradeneeded = function(e) {
          var db = e.target.result;
          var transaction = e.target.transaction;
  
          var fileStore;
  
          if (db.objectStoreNames.contains(IDBFS.DB_STORE_NAME)) {
            fileStore = transaction.objectStore(IDBFS.DB_STORE_NAME);
          } else {
            fileStore = db.createObjectStore(IDBFS.DB_STORE_NAME);
          }
  
          fileStore.createIndex('timestamp', 'timestamp', { unique: false });
        };
        req.onsuccess = function() {
          db = req.result;
  
          // add to the cache
          IDBFS.dbs[name] = db;
          callback(null, db);
        };
        req.onerror = function() {
          callback(this.error);
        };
      },getLocalSet:function (mount, callback) {
        var entries = {};
  
        function isRealDir(p) {
          return p !== '.' && p !== '..';
        };
        function toAbsolute(root) {
          return function(p) {
            return PATH.join2(root, p);
          }
        };
  
        var check = FS.readdir(mount.mountpoint).filter(isRealDir).map(toAbsolute(mount.mountpoint));
  
        while (check.length) {
          var path = check.pop();
          var stat;
  
          try {
            stat = FS.stat(path);
          } catch (e) {
            return callback(e);
          }
  
          if (FS.isDir(stat.mode)) {
            check.push.apply(check, FS.readdir(path).filter(isRealDir).map(toAbsolute(path)));
          }
  
          entries[path] = { timestamp: stat.mtime };
        }
  
        return callback(null, { type: 'local', entries: entries });
      },getRemoteSet:function (mount, callback) {
        var entries = {};
  
        IDBFS.getDB(mount.mountpoint, function(err, db) {
          if (err) return callback(err);
  
          var transaction = db.transaction([IDBFS.DB_STORE_NAME], 'readonly');
          transaction.onerror = function() { callback(this.error); };
  
          var store = transaction.objectStore(IDBFS.DB_STORE_NAME);
          var index = store.index('timestamp');
  
          index.openKeyCursor().onsuccess = function(event) {
            var cursor = event.target.result;
  
            if (!cursor) {
              return callback(null, { type: 'remote', db: db, entries: entries });
            }
  
            entries[cursor.primaryKey] = { timestamp: cursor.key };
  
            cursor.continue();
          };
        });
      },loadLocalEntry:function (path, callback) {
        var stat, node;
  
        try {
          var lookup = FS.lookupPath(path);
          node = lookup.node;
          stat = FS.stat(path);
        } catch (e) {
          return callback(e);
        }
  
        if (FS.isDir(stat.mode)) {
          return callback(null, { timestamp: stat.mtime, mode: stat.mode });
        } else if (FS.isFile(stat.mode)) {
          return callback(null, { timestamp: stat.mtime, mode: stat.mode, contents: node.contents });
        } else {
          return callback(new Error('node type not supported'));
        }
      },storeLocalEntry:function (path, entry, callback) {
        try {
          if (FS.isDir(entry.mode)) {
            FS.mkdir(path, entry.mode);
          } else if (FS.isFile(entry.mode)) {
            FS.writeFile(path, entry.contents, { encoding: 'binary', canOwn: true });
          } else {
            return callback(new Error('node type not supported'));
          }
  
          FS.utime(path, entry.timestamp, entry.timestamp);
        } catch (e) {
          return callback(e);
        }
  
        callback(null);
      },removeLocalEntry:function (path, callback) {
        try {
          var lookup = FS.lookupPath(path);
          var stat = FS.stat(path);
  
          if (FS.isDir(stat.mode)) {
            FS.rmdir(path);
          } else if (FS.isFile(stat.mode)) {
            FS.unlink(path);
          }
        } catch (e) {
          return callback(e);
        }
  
        callback(null);
      },loadRemoteEntry:function (store, path, callback) {
        var req = store.get(path);
        req.onsuccess = function(event) { callback(null, event.target.result); };
        req.onerror = function() { callback(this.error); };
      },storeRemoteEntry:function (store, path, entry, callback) {
        var req = store.put(entry, path);
        req.onsuccess = function() { callback(null); };
        req.onerror = function() { callback(this.error); };
      },removeRemoteEntry:function (store, path, callback) {
        var req = store.delete(path);
        req.onsuccess = function() { callback(null); };
        req.onerror = function() { callback(this.error); };
      },reconcile:function (src, dst, callback) {
        var total = 0;
  
        var create = [];
        Object.keys(src.entries).forEach(function (key) {
          var e = src.entries[key];
          var e2 = dst.entries[key];
          if (!e2 || e.timestamp > e2.timestamp) {
            create.push(key);
            total++;
          }
        });
  
        var remove = [];
        Object.keys(dst.entries).forEach(function (key) {
          var e = dst.entries[key];
          var e2 = src.entries[key];
          if (!e2) {
            remove.push(key);
            total++;
          }
        });
  
        if (!total) {
          return callback(null);
        }
  
        var errored = false;
        var completed = 0;
        var db = src.type === 'remote' ? src.db : dst.db;
        var transaction = db.transaction([IDBFS.DB_STORE_NAME], 'readwrite');
        var store = transaction.objectStore(IDBFS.DB_STORE_NAME);
  
        function done(err) {
          if (err) {
            if (!done.errored) {
              done.errored = true;
              return callback(err);
            }
            return;
          }
          if (++completed >= total) {
            return callback(null);
          }
        };
  
        transaction.onerror = function() { done(this.error); };
  
        // sort paths in ascending order so directory entries are created
        // before the files inside them
        create.sort().forEach(function (path) {
          if (dst.type === 'local') {
            IDBFS.loadRemoteEntry(store, path, function (err, entry) {
              if (err) return done(err);
              IDBFS.storeLocalEntry(path, entry, done);
            });
          } else {
            IDBFS.loadLocalEntry(path, function (err, entry) {
              if (err) return done(err);
              IDBFS.storeRemoteEntry(store, path, entry, done);
            });
          }
        });
  
        // sort paths in descending order so files are deleted before their
        // parent directories
        remove.sort().reverse().forEach(function(path) {
          if (dst.type === 'local') {
            IDBFS.removeLocalEntry(path, done);
          } else {
            IDBFS.removeRemoteEntry(store, path, done);
          }
        });
      }};
  
  var NODEFS={isWindows:false,staticInit:function () {
        NODEFS.isWindows = !!process.platform.match(/^win/);
      },mount:function (mount) {
        assert(ENVIRONMENT_IS_NODE);
        return NODEFS.createNode(null, '/', NODEFS.getMode(mount.opts.root), 0);
      },createNode:function (parent, name, mode, dev) {
        if (!FS.isDir(mode) && !FS.isFile(mode) && !FS.isLink(mode)) {
          throw new FS.ErrnoError(ERRNO_CODES.EINVAL);
        }
        var node = FS.createNode(parent, name, mode);
        node.node_ops = NODEFS.node_ops;
        node.stream_ops = NODEFS.stream_ops;
        return node;
      },getMode:function (path) {
        var stat;
        try {
          stat = fs.lstatSync(path);
          if (NODEFS.isWindows) {
            // On Windows, directories return permission bits 'rw-rw-rw-', even though they have 'rwxrwxrwx', so 
            // propagate write bits to execute bits.
            stat.mode = stat.mode | ((stat.mode & 146) >> 1);
          }
        } catch (e) {
          if (!e.code) throw e;
          throw new FS.ErrnoError(ERRNO_CODES[e.code]);
        }
        return stat.mode;
      },realPath:function (node) {
        var parts = [];
        while (node.parent !== node) {
          parts.push(node.name);
          node = node.parent;
        }
        parts.push(node.mount.opts.root);
        parts.reverse();
        return PATH.join.apply(null, parts);
      },flagsToPermissionStringMap:{0:"r",1:"r+",2:"r+",64:"r",65:"r+",66:"r+",129:"rx+",193:"rx+",514:"w+",577:"w",578:"w+",705:"wx",706:"wx+",1024:"a",1025:"a",1026:"a+",1089:"a",1090:"a+",1153:"ax",1154:"ax+",1217:"ax",1218:"ax+",4096:"rs",4098:"rs+"},flagsToPermissionString:function (flags) {
        if (flags in NODEFS.flagsToPermissionStringMap) {
          return NODEFS.flagsToPermissionStringMap[flags];
        } else {
          return flags;
        }
      },node_ops:{getattr:function (node) {
          var path = NODEFS.realPath(node);
          var stat;
          try {
            stat = fs.lstatSync(path);
          } catch (e) {
            if (!e.code) throw e;
            throw new FS.ErrnoError(ERRNO_CODES[e.code]);
          }
          // node.js v0.10.20 doesn't report blksize and blocks on Windows. Fake them with default blksize of 4096.
          // See http://support.microsoft.com/kb/140365
          if (NODEFS.isWindows && !stat.blksize) {
            stat.blksize = 4096;
          }
          if (NODEFS.isWindows && !stat.blocks) {
            stat.blocks = (stat.size+stat.blksize-1)/stat.blksize|0;
          }
          return {
            dev: stat.dev,
            ino: stat.ino,
            mode: stat.mode,
            nlink: stat.nlink,
            uid: stat.uid,
            gid: stat.gid,
            rdev: stat.rdev,
            size: stat.size,
            atime: stat.atime,
            mtime: stat.mtime,
            ctime: stat.ctime,
            blksize: stat.blksize,
            blocks: stat.blocks
          };
        },setattr:function (node, attr) {
          var path = NODEFS.realPath(node);
          try {
            if (attr.mode !== undefined) {
              fs.chmodSync(path, attr.mode);
              // update the common node structure mode as well
              node.mode = attr.mode;
            }
            if (attr.timestamp !== undefined) {
              var date = new Date(attr.timestamp);
              fs.utimesSync(path, date, date);
            }
            if (attr.size !== undefined) {
              fs.truncateSync(path, attr.size);
            }
          } catch (e) {
            if (!e.code) throw e;
            throw new FS.ErrnoError(ERRNO_CODES[e.code]);
          }
        },lookup:function (parent, name) {
          var path = PATH.join2(NODEFS.realPath(parent), name);
          var mode = NODEFS.getMode(path);
          return NODEFS.createNode(parent, name, mode);
        },mknod:function (parent, name, mode, dev) {
          var node = NODEFS.createNode(parent, name, mode, dev);
          // create the backing node for this in the fs root as well
          var path = NODEFS.realPath(node);
          try {
            if (FS.isDir(node.mode)) {
              fs.mkdirSync(path, node.mode);
            } else {
              fs.writeFileSync(path, '', { mode: node.mode });
            }
          } catch (e) {
            if (!e.code) throw e;
            throw new FS.ErrnoError(ERRNO_CODES[e.code]);
          }
          return node;
        },rename:function (oldNode, newDir, newName) {
          var oldPath = NODEFS.realPath(oldNode);
          var newPath = PATH.join2(NODEFS.realPath(newDir), newName);
          try {
            fs.renameSync(oldPath, newPath);
          } catch (e) {
            if (!e.code) throw e;
            throw new FS.ErrnoError(ERRNO_CODES[e.code]);
          }
        },unlink:function (parent, name) {
          var path = PATH.join2(NODEFS.realPath(parent), name);
          try {
            fs.unlinkSync(path);
          } catch (e) {
            if (!e.code) throw e;
            throw new FS.ErrnoError(ERRNO_CODES[e.code]);
          }
        },rmdir:function (parent, name) {
          var path = PATH.join2(NODEFS.realPath(parent), name);
          try {
            fs.rmdirSync(path);
          } catch (e) {
            if (!e.code) throw e;
            throw new FS.ErrnoError(ERRNO_CODES[e.code]);
          }
        },readdir:function (node) {
          var path = NODEFS.realPath(node);
          try {
            return fs.readdirSync(path);
          } catch (e) {
            if (!e.code) throw e;
            throw new FS.ErrnoError(ERRNO_CODES[e.code]);
          }
        },symlink:function (parent, newName, oldPath) {
          var newPath = PATH.join2(NODEFS.realPath(parent), newName);
          try {
            fs.symlinkSync(oldPath, newPath);
          } catch (e) {
            if (!e.code) throw e;
            throw new FS.ErrnoError(ERRNO_CODES[e.code]);
          }
        },readlink:function (node) {
          var path = NODEFS.realPath(node);
          try {
            return fs.readlinkSync(path);
          } catch (e) {
            if (!e.code) throw e;
            throw new FS.ErrnoError(ERRNO_CODES[e.code]);
          }
        }},stream_ops:{open:function (stream) {
          var path = NODEFS.realPath(stream.node);
          try {
            if (FS.isFile(stream.node.mode)) {
              stream.nfd = fs.openSync(path, NODEFS.flagsToPermissionString(stream.flags));
            }
          } catch (e) {
            if (!e.code) throw e;
            throw new FS.ErrnoError(ERRNO_CODES[e.code]);
          }
        },close:function (stream) {
          try {
            if (FS.isFile(stream.node.mode) && stream.nfd) {
              fs.closeSync(stream.nfd);
            }
          } catch (e) {
            if (!e.code) throw e;
            throw new FS.ErrnoError(ERRNO_CODES[e.code]);
          }
        },read:function (stream, buffer, offset, length, position) {
          // FIXME this is terrible.
          var nbuffer = new Buffer(length);
          var res;
          try {
            res = fs.readSync(stream.nfd, nbuffer, 0, length, position);
          } catch (e) {
            throw new FS.ErrnoError(ERRNO_CODES[e.code]);
          }
          if (res > 0) {
            for (var i = 0; i < res; i++) {
              buffer[offset + i] = nbuffer[i];
            }
          }
          return res;
        },write:function (stream, buffer, offset, length, position) {
          // FIXME this is terrible.
          var nbuffer = new Buffer(buffer.subarray(offset, offset + length));
          var res;
          try {
            res = fs.writeSync(stream.nfd, nbuffer, 0, length, position);
          } catch (e) {
            throw new FS.ErrnoError(ERRNO_CODES[e.code]);
          }
          return res;
        },llseek:function (stream, offset, whence) {
          var position = offset;
          if (whence === 1) {  // SEEK_CUR.
            position += stream.position;
          } else if (whence === 2) {  // SEEK_END.
            if (FS.isFile(stream.node.mode)) {
              try {
                var stat = fs.fstatSync(stream.nfd);
                position += stat.size;
              } catch (e) {
                throw new FS.ErrnoError(ERRNO_CODES[e.code]);
              }
            }
          }
  
          if (position < 0) {
            throw new FS.ErrnoError(ERRNO_CODES.EINVAL);
          }
  
          stream.position = position;
          return position;
        }}};
  
  var _stdin=allocate(1, "i32*", ALLOC_STATIC);
  
  var _stdout=allocate(1, "i32*", ALLOC_STATIC);
  
  var _stderr=allocate(1, "i32*", ALLOC_STATIC);
  
  function _fflush(stream) {
      // int fflush(FILE *stream);
      // http://pubs.opengroup.org/onlinepubs/000095399/functions/fflush.html
      // we don't currently perform any user-space buffering of data
    }var FS={root:null,mounts:[],devices:[null],streams:[],nextInode:1,nameTable:null,currentPath:"/",initialized:false,ignorePermissions:true,ErrnoError:null,genericErrors:{},handleFSError:function (e) {
        if (!(e instanceof FS.ErrnoError)) throw e + ' : ' + stackTrace();
        return ___setErrNo(e.errno);
      },lookupPath:function (path, opts) {
        path = PATH.resolve(FS.cwd(), path);
        opts = opts || {};
  
        var defaults = {
          follow_mount: true,
          recurse_count: 0
        };
        for (var key in defaults) {
          if (opts[key] === undefined) {
            opts[key] = defaults[key];
          }
        }
  
        if (opts.recurse_count > 8) {  // max recursive lookup of 8
          throw new FS.ErrnoError(ERRNO_CODES.ELOOP);
        }
  
        // split the path
        var parts = PATH.normalizeArray(path.split('/').filter(function(p) {
          return !!p;
        }), false);
  
        // start at the root
        var current = FS.root;
        var current_path = '/';
  
        for (var i = 0; i < parts.length; i++) {
          var islast = (i === parts.length-1);
          if (islast && opts.parent) {
            // stop resolving
            break;
          }
  
          current = FS.lookupNode(current, parts[i]);
          current_path = PATH.join2(current_path, parts[i]);
  
          // jump to the mount's root node if this is a mountpoint
          if (FS.isMountpoint(current)) {
            if (!islast || (islast && opts.follow_mount)) {
              current = current.mounted.root;
            }
          }
  
          // by default, lookupPath will not follow a symlink if it is the final path component.
          // setting opts.follow = true will override this behavior.
          if (!islast || opts.follow) {
            var count = 0;
            while (FS.isLink(current.mode)) {
              var link = FS.readlink(current_path);
              current_path = PATH.resolve(PATH.dirname(current_path), link);
              
              var lookup = FS.lookupPath(current_path, { recurse_count: opts.recurse_count });
              current = lookup.node;
  
              if (count++ > 40) {  // limit max consecutive symlinks to 40 (SYMLOOP_MAX).
                throw new FS.ErrnoError(ERRNO_CODES.ELOOP);
              }
            }
          }
        }
  
        return { path: current_path, node: current };
      },getPath:function (node) {
        var path;
        while (true) {
          if (FS.isRoot(node)) {
            var mount = node.mount.mountpoint;
            if (!path) return mount;
            return mount[mount.length-1] !== '/' ? mount + '/' + path : mount + path;
          }
          path = path ? node.name + '/' + path : node.name;
          node = node.parent;
        }
      },hashName:function (parentid, name) {
        var hash = 0;
  
  
        for (var i = 0; i < name.length; i++) {
          hash = ((hash << 5) - hash + name.charCodeAt(i)) | 0;
        }
        return ((parentid + hash) >>> 0) % FS.nameTable.length;
      },hashAddNode:function (node) {
        var hash = FS.hashName(node.parent.id, node.name);
        node.name_next = FS.nameTable[hash];
        FS.nameTable[hash] = node;
      },hashRemoveNode:function (node) {
        var hash = FS.hashName(node.parent.id, node.name);
        if (FS.nameTable[hash] === node) {
          FS.nameTable[hash] = node.name_next;
        } else {
          var current = FS.nameTable[hash];
          while (current) {
            if (current.name_next === node) {
              current.name_next = node.name_next;
              break;
            }
            current = current.name_next;
          }
        }
      },lookupNode:function (parent, name) {
        var err = FS.mayLookup(parent);
        if (err) {
          throw new FS.ErrnoError(err);
        }
        var hash = FS.hashName(parent.id, name);
        for (var node = FS.nameTable[hash]; node; node = node.name_next) {
          var nodeName = node.name;
          if (node.parent.id === parent.id && nodeName === name) {
            return node;
          }
        }
        // if we failed to find it in the cache, call into the VFS
        return FS.lookup(parent, name);
      },createNode:function (parent, name, mode, rdev) {
        if (!FS.FSNode) {
          FS.FSNode = function(parent, name, mode, rdev) {
            if (!parent) {
              parent = this;  // root node sets parent to itself
            }
            this.parent = parent;
            this.mount = parent.mount;
            this.mounted = null;
            this.id = FS.nextInode++;
            this.name = name;
            this.mode = mode;
            this.node_ops = {};
            this.stream_ops = {};
            this.rdev = rdev;
          };
  
          FS.FSNode.prototype = {};
  
          // compatibility
          var readMode = 292 | 73;
          var writeMode = 146;
  
          // NOTE we must use Object.defineProperties instead of individual calls to
          // Object.defineProperty in order to make closure compiler happy
          Object.defineProperties(FS.FSNode.prototype, {
            read: {
              get: function() { return (this.mode & readMode) === readMode; },
              set: function(val) { val ? this.mode |= readMode : this.mode &= ~readMode; }
            },
            write: {
              get: function() { return (this.mode & writeMode) === writeMode; },
              set: function(val) { val ? this.mode |= writeMode : this.mode &= ~writeMode; }
            },
            isFolder: {
              get: function() { return FS.isDir(this.mode); },
            },
            isDevice: {
              get: function() { return FS.isChrdev(this.mode); },
            },
          });
        }
  
        var node = new FS.FSNode(parent, name, mode, rdev);
  
        FS.hashAddNode(node);
  
        return node;
      },destroyNode:function (node) {
        FS.hashRemoveNode(node);
      },isRoot:function (node) {
        return node === node.parent;
      },isMountpoint:function (node) {
        return !!node.mounted;
      },isFile:function (mode) {
        return (mode & 61440) === 32768;
      },isDir:function (mode) {
        return (mode & 61440) === 16384;
      },isLink:function (mode) {
        return (mode & 61440) === 40960;
      },isChrdev:function (mode) {
        return (mode & 61440) === 8192;
      },isBlkdev:function (mode) {
        return (mode & 61440) === 24576;
      },isFIFO:function (mode) {
        return (mode & 61440) === 4096;
      },isSocket:function (mode) {
        return (mode & 49152) === 49152;
      },flagModes:{"r":0,"rs":1052672,"r+":2,"w":577,"wx":705,"xw":705,"w+":578,"wx+":706,"xw+":706,"a":1089,"ax":1217,"xa":1217,"a+":1090,"ax+":1218,"xa+":1218},modeStringToFlags:function (str) {
        var flags = FS.flagModes[str];
        if (typeof flags === 'undefined') {
          throw new Error('Unknown file open mode: ' + str);
        }
        return flags;
      },flagsToPermissionString:function (flag) {
        var accmode = flag & 2097155;
        var perms = ['r', 'w', 'rw'][accmode];
        if ((flag & 512)) {
          perms += 'w';
        }
        return perms;
      },nodePermissions:function (node, perms) {
        if (FS.ignorePermissions) {
          return 0;
        }
        // return 0 if any user, group or owner bits are set.
        if (perms.indexOf('r') !== -1 && !(node.mode & 292)) {
          return ERRNO_CODES.EACCES;
        } else if (perms.indexOf('w') !== -1 && !(node.mode & 146)) {
          return ERRNO_CODES.EACCES;
        } else if (perms.indexOf('x') !== -1 && !(node.mode & 73)) {
          return ERRNO_CODES.EACCES;
        }
        return 0;
      },mayLookup:function (dir) {
        return FS.nodePermissions(dir, 'x');
      },mayCreate:function (dir, name) {
        try {
          var node = FS.lookupNode(dir, name);
          return ERRNO_CODES.EEXIST;
        } catch (e) {
        }
        return FS.nodePermissions(dir, 'wx');
      },mayDelete:function (dir, name, isdir) {
        var node;
        try {
          node = FS.lookupNode(dir, name);
        } catch (e) {
          return e.errno;
        }
        var err = FS.nodePermissions(dir, 'wx');
        if (err) {
          return err;
        }
        if (isdir) {
          if (!FS.isDir(node.mode)) {
            return ERRNO_CODES.ENOTDIR;
          }
          if (FS.isRoot(node) || FS.getPath(node) === FS.cwd()) {
            return ERRNO_CODES.EBUSY;
          }
        } else {
          if (FS.isDir(node.mode)) {
            return ERRNO_CODES.EISDIR;
          }
        }
        return 0;
      },mayOpen:function (node, flags) {
        if (!node) {
          return ERRNO_CODES.ENOENT;
        }
        if (FS.isLink(node.mode)) {
          return ERRNO_CODES.ELOOP;
        } else if (FS.isDir(node.mode)) {
          if ((flags & 2097155) !== 0 ||  // opening for write
              (flags & 512)) {
            return ERRNO_CODES.EISDIR;
          }
        }
        return FS.nodePermissions(node, FS.flagsToPermissionString(flags));
      },MAX_OPEN_FDS:4096,nextfd:function (fd_start, fd_end) {
        fd_start = fd_start || 0;
        fd_end = fd_end || FS.MAX_OPEN_FDS;
        for (var fd = fd_start; fd <= fd_end; fd++) {
          if (!FS.streams[fd]) {
            return fd;
          }
        }
        throw new FS.ErrnoError(ERRNO_CODES.EMFILE);
      },getStream:function (fd) {
        return FS.streams[fd];
      },createStream:function (stream, fd_start, fd_end) {
        if (!FS.FSStream) {
          FS.FSStream = function(){};
          FS.FSStream.prototype = {};
          // compatibility
          Object.defineProperties(FS.FSStream.prototype, {
            object: {
              get: function() { return this.node; },
              set: function(val) { this.node = val; }
            },
            isRead: {
              get: function() { return (this.flags & 2097155) !== 1; }
            },
            isWrite: {
              get: function() { return (this.flags & 2097155) !== 0; }
            },
            isAppend: {
              get: function() { return (this.flags & 1024); }
            }
          });
        }
        if (stream.__proto__) {
          // reuse the object
          stream.__proto__ = FS.FSStream.prototype;
        } else {
          var newStream = new FS.FSStream();
          for (var p in stream) {
            newStream[p] = stream[p];
          }
          stream = newStream;
        }
        var fd = FS.nextfd(fd_start, fd_end);
        stream.fd = fd;
        FS.streams[fd] = stream;
        return stream;
      },closeStream:function (fd) {
        FS.streams[fd] = null;
      },getStreamFromPtr:function (ptr) {
        return FS.streams[ptr - 1];
      },getPtrForStream:function (stream) {
        return stream ? stream.fd + 1 : 0;
      },chrdev_stream_ops:{open:function (stream) {
          var device = FS.getDevice(stream.node.rdev);
          // override node's stream ops with the device's
          stream.stream_ops = device.stream_ops;
          // forward the open call
          if (stream.stream_ops.open) {
            stream.stream_ops.open(stream);
          }
        },llseek:function () {
          throw new FS.ErrnoError(ERRNO_CODES.ESPIPE);
        }},major:function (dev) {
        return ((dev) >> 8);
      },minor:function (dev) {
        return ((dev) & 0xff);
      },makedev:function (ma, mi) {
        return ((ma) << 8 | (mi));
      },registerDevice:function (dev, ops) {
        FS.devices[dev] = { stream_ops: ops };
      },getDevice:function (dev) {
        return FS.devices[dev];
      },getMounts:function (mount) {
        var mounts = [];
        var check = [mount];
  
        while (check.length) {
          var m = check.pop();
  
          mounts.push(m);
  
          check.push.apply(check, m.mounts);
        }
  
        return mounts;
      },syncfs:function (populate, callback) {
        if (typeof(populate) === 'function') {
          callback = populate;
          populate = false;
        }
  
        var mounts = FS.getMounts(FS.root.mount);
        var completed = 0;
  
        function done(err) {
          if (err) {
            if (!done.errored) {
              done.errored = true;
              return callback(err);
            }
            return;
          }
          if (++completed >= mounts.length) {
            callback(null);
          }
        };
  
        // sync all mounts
        mounts.forEach(function (mount) {
          if (!mount.type.syncfs) {
            return done(null);
          }
          mount.type.syncfs(mount, populate, done);
        });
      },mount:function (type, opts, mountpoint) {
        var root = mountpoint === '/';
        var pseudo = !mountpoint;
        var node;
  
        if (root && FS.root) {
          throw new FS.ErrnoError(ERRNO_CODES.EBUSY);
        } else if (!root && !pseudo) {
          var lookup = FS.lookupPath(mountpoint, { follow_mount: false });
  
          mountpoint = lookup.path;  // use the absolute path
          node = lookup.node;
  
          if (FS.isMountpoint(node)) {
            throw new FS.ErrnoError(ERRNO_CODES.EBUSY);
          }
  
          if (!FS.isDir(node.mode)) {
            throw new FS.ErrnoError(ERRNO_CODES.ENOTDIR);
          }
        }
  
        var mount = {
          type: type,
          opts: opts,
          mountpoint: mountpoint,
          mounts: []
        };
  
        // create a root node for the fs
        var mountRoot = type.mount(mount);
        mountRoot.mount = mount;
        mount.root = mountRoot;
  
        if (root) {
          FS.root = mountRoot;
        } else if (node) {
          // set as a mountpoint
          node.mounted = mount;
  
          // add the new mount to the current mount's children
          if (node.mount) {
            node.mount.mounts.push(mount);
          }
        }
  
        return mountRoot;
      },unmount:function (mountpoint) {
        var lookup = FS.lookupPath(mountpoint, { follow_mount: false });
  
        if (!FS.isMountpoint(lookup.node)) {
          throw new FS.ErrnoError(ERRNO_CODES.EINVAL);
        }
  
        // destroy the nodes for this mount, and all its child mounts
        var node = lookup.node;
        var mount = node.mounted;
        var mounts = FS.getMounts(mount);
  
        Object.keys(FS.nameTable).forEach(function (hash) {
          var current = FS.nameTable[hash];
  
          while (current) {
            var next = current.name_next;
  
            if (mounts.indexOf(current.mount) !== -1) {
              FS.destroyNode(current);
            }
  
            current = next;
          }
        });
  
        // no longer a mountpoint
        node.mounted = null;
  
        // remove this mount from the child mounts
        var idx = node.mount.mounts.indexOf(mount);
        assert(idx !== -1);
        node.mount.mounts.splice(idx, 1);
      },lookup:function (parent, name) {
        return parent.node_ops.lookup(parent, name);
      },mknod:function (path, mode, dev) {
        var lookup = FS.lookupPath(path, { parent: true });
        var parent = lookup.node;
        var name = PATH.basename(path);
        var err = FS.mayCreate(parent, name);
        if (err) {
          throw new FS.ErrnoError(err);
        }
        if (!parent.node_ops.mknod) {
          throw new FS.ErrnoError(ERRNO_CODES.EPERM);
        }
        return parent.node_ops.mknod(parent, name, mode, dev);
      },create:function (path, mode) {
        mode = mode !== undefined ? mode : 438 /* 0666 */;
        mode &= 4095;
        mode |= 32768;
        return FS.mknod(path, mode, 0);
      },mkdir:function (path, mode) {
        mode = mode !== undefined ? mode : 511 /* 0777 */;
        mode &= 511 | 512;
        mode |= 16384;
        return FS.mknod(path, mode, 0);
      },mkdev:function (path, mode, dev) {
        if (typeof(dev) === 'undefined') {
          dev = mode;
          mode = 438 /* 0666 */;
        }
        mode |= 8192;
        return FS.mknod(path, mode, dev);
      },symlink:function (oldpath, newpath) {
        var lookup = FS.lookupPath(newpath, { parent: true });
        var parent = lookup.node;
        var newname = PATH.basename(newpath);
        var err = FS.mayCreate(parent, newname);
        if (err) {
          throw new FS.ErrnoError(err);
        }
        if (!parent.node_ops.symlink) {
          throw new FS.ErrnoError(ERRNO_CODES.EPERM);
        }
        return parent.node_ops.symlink(parent, newname, oldpath);
      },rename:function (old_path, new_path) {
        var old_dirname = PATH.dirname(old_path);
        var new_dirname = PATH.dirname(new_path);
        var old_name = PATH.basename(old_path);
        var new_name = PATH.basename(new_path);
        // parents must exist
        var lookup, old_dir, new_dir;
        try {
          lookup = FS.lookupPath(old_path, { parent: true });
          old_dir = lookup.node;
          lookup = FS.lookupPath(new_path, { parent: true });
          new_dir = lookup.node;
        } catch (e) {
          throw new FS.ErrnoError(ERRNO_CODES.EBUSY);
        }
        // need to be part of the same mount
        if (old_dir.mount !== new_dir.mount) {
          throw new FS.ErrnoError(ERRNO_CODES.EXDEV);
        }
        // source must exist
        var old_node = FS.lookupNode(old_dir, old_name);
        // old path should not be an ancestor of the new path
        var relative = PATH.relative(old_path, new_dirname);
        if (relative.charAt(0) !== '.') {
          throw new FS.ErrnoError(ERRNO_CODES.EINVAL);
        }
        // new path should not be an ancestor of the old path
        relative = PATH.relative(new_path, old_dirname);
        if (relative.charAt(0) !== '.') {
          throw new FS.ErrnoError(ERRNO_CODES.ENOTEMPTY);
        }
        // see if the new path already exists
        var new_node;
        try {
          new_node = FS.lookupNode(new_dir, new_name);
        } catch (e) {
          // not fatal
        }
        // early out if nothing needs to change
        if (old_node === new_node) {
          return;
        }
        // we'll need to delete the old entry
        var isdir = FS.isDir(old_node.mode);
        var err = FS.mayDelete(old_dir, old_name, isdir);
        if (err) {
          throw new FS.ErrnoError(err);
        }
        // need delete permissions if we'll be overwriting.
        // need create permissions if new doesn't already exist.
        err = new_node ?
          FS.mayDelete(new_dir, new_name, isdir) :
          FS.mayCreate(new_dir, new_name);
        if (err) {
          throw new FS.ErrnoError(err);
        }
        if (!old_dir.node_ops.rename) {
          throw new FS.ErrnoError(ERRNO_CODES.EPERM);
        }
        if (FS.isMountpoint(old_node) || (new_node && FS.isMountpoint(new_node))) {
          throw new FS.ErrnoError(ERRNO_CODES.EBUSY);
        }
        // if we are going to change the parent, check write permissions
        if (new_dir !== old_dir) {
          err = FS.nodePermissions(old_dir, 'w');
          if (err) {
            throw new FS.ErrnoError(err);
          }
        }
        // remove the node from the lookup hash
        FS.hashRemoveNode(old_node);
        // do the underlying fs rename
        try {
          old_dir.node_ops.rename(old_node, new_dir, new_name);
        } catch (e) {
          throw e;
        } finally {
          // add the node back to the hash (in case node_ops.rename
          // changed its name)
          FS.hashAddNode(old_node);
        }
      },rmdir:function (path) {
        var lookup = FS.lookupPath(path, { parent: true });
        var parent = lookup.node;
        var name = PATH.basename(path);
        var node = FS.lookupNode(parent, name);
        var err = FS.mayDelete(parent, name, true);
        if (err) {
          throw new FS.ErrnoError(err);
        }
        if (!parent.node_ops.rmdir) {
          throw new FS.ErrnoError(ERRNO_CODES.EPERM);
        }
        if (FS.isMountpoint(node)) {
          throw new FS.ErrnoError(ERRNO_CODES.EBUSY);
        }
        parent.node_ops.rmdir(parent, name);
        FS.destroyNode(node);
      },readdir:function (path) {
        var lookup = FS.lookupPath(path, { follow: true });
        var node = lookup.node;
        if (!node.node_ops.readdir) {
          throw new FS.ErrnoError(ERRNO_CODES.ENOTDIR);
        }
        return node.node_ops.readdir(node);
      },unlink:function (path) {
        var lookup = FS.lookupPath(path, { parent: true });
        var parent = lookup.node;
        var name = PATH.basename(path);
        var node = FS.lookupNode(parent, name);
        var err = FS.mayDelete(parent, name, false);
        if (err) {
          // POSIX says unlink should set EPERM, not EISDIR
          if (err === ERRNO_CODES.EISDIR) err = ERRNO_CODES.EPERM;
          throw new FS.ErrnoError(err);
        }
        if (!parent.node_ops.unlink) {
          throw new FS.ErrnoError(ERRNO_CODES.EPERM);
        }
        if (FS.isMountpoint(node)) {
          throw new FS.ErrnoError(ERRNO_CODES.EBUSY);
        }
        parent.node_ops.unlink(parent, name);
        FS.destroyNode(node);
      },readlink:function (path) {
        var lookup = FS.lookupPath(path);
        var link = lookup.node;
        if (!link.node_ops.readlink) {
          throw new FS.ErrnoError(ERRNO_CODES.EINVAL);
        }
        return link.node_ops.readlink(link);
      },stat:function (path, dontFollow) {
        var lookup = FS.lookupPath(path, { follow: !dontFollow });
        var node = lookup.node;
        if (!node.node_ops.getattr) {
          throw new FS.ErrnoError(ERRNO_CODES.EPERM);
        }
        return node.node_ops.getattr(node);
      },lstat:function (path) {
        return FS.stat(path, true);
      },chmod:function (path, mode, dontFollow) {
        var node;
        if (typeof path === 'string') {
          var lookup = FS.lookupPath(path, { follow: !dontFollow });
          node = lookup.node;
        } else {
          node = path;
        }
        if (!node.node_ops.setattr) {
          throw new FS.ErrnoError(ERRNO_CODES.EPERM);
        }
        node.node_ops.setattr(node, {
          mode: (mode & 4095) | (node.mode & ~4095),
          timestamp: Date.now()
        });
      },lchmod:function (path, mode) {
        FS.chmod(path, mode, true);
      },fchmod:function (fd, mode) {
        var stream = FS.getStream(fd);
        if (!stream) {
          throw new FS.ErrnoError(ERRNO_CODES.EBADF);
        }
        FS.chmod(stream.node, mode);
      },chown:function (path, uid, gid, dontFollow) {
        var node;
        if (typeof path === 'string') {
          var lookup = FS.lookupPath(path, { follow: !dontFollow });
          node = lookup.node;
        } else {
          node = path;
        }
        if (!node.node_ops.setattr) {
          throw new FS.ErrnoError(ERRNO_CODES.EPERM);
        }
        node.node_ops.setattr(node, {
          timestamp: Date.now()
          // we ignore the uid / gid for now
        });
      },lchown:function (path, uid, gid) {
        FS.chown(path, uid, gid, true);
      },fchown:function (fd, uid, gid) {
        var stream = FS.getStream(fd);
        if (!stream) {
          throw new FS.ErrnoError(ERRNO_CODES.EBADF);
        }
        FS.chown(stream.node, uid, gid);
      },truncate:function (path, len) {
        if (len < 0) {
          throw new FS.ErrnoError(ERRNO_CODES.EINVAL);
        }
        var node;
        if (typeof path === 'string') {
          var lookup = FS.lookupPath(path, { follow: true });
          node = lookup.node;
        } else {
          node = path;
        }
        if (!node.node_ops.setattr) {
          throw new FS.ErrnoError(ERRNO_CODES.EPERM);
        }
        if (FS.isDir(node.mode)) {
          throw new FS.ErrnoError(ERRNO_CODES.EISDIR);
        }
        if (!FS.isFile(node.mode)) {
          throw new FS.ErrnoError(ERRNO_CODES.EINVAL);
        }
        var err = FS.nodePermissions(node, 'w');
        if (err) {
          throw new FS.ErrnoError(err);
        }
        node.node_ops.setattr(node, {
          size: len,
          timestamp: Date.now()
        });
      },ftruncate:function (fd, len) {
        var stream = FS.getStream(fd);
        if (!stream) {
          throw new FS.ErrnoError(ERRNO_CODES.EBADF);
        }
        if ((stream.flags & 2097155) === 0) {
          throw new FS.ErrnoError(ERRNO_CODES.EINVAL);
        }
        FS.truncate(stream.node, len);
      },utime:function (path, atime, mtime) {
        var lookup = FS.lookupPath(path, { follow: true });
        var node = lookup.node;
        node.node_ops.setattr(node, {
          timestamp: Math.max(atime, mtime)
        });
      },open:function (path, flags, mode, fd_start, fd_end) {
        flags = typeof flags === 'string' ? FS.modeStringToFlags(flags) : flags;
        mode = typeof mode === 'undefined' ? 438 /* 0666 */ : mode;
        if ((flags & 64)) {
          mode = (mode & 4095) | 32768;
        } else {
          mode = 0;
        }
        var node;
        if (typeof path === 'object') {
          node = path;
        } else {
          path = PATH.normalize(path);
          try {
            var lookup = FS.lookupPath(path, {
              follow: !(flags & 131072)
            });
            node = lookup.node;
          } catch (e) {
            // ignore
          }
        }
        // perhaps we need to create the node
        if ((flags & 64)) {
          if (node) {
            // if O_CREAT and O_EXCL are set, error out if the node already exists
            if ((flags & 128)) {
              throw new FS.ErrnoError(ERRNO_CODES.EEXIST);
            }
          } else {
            // node doesn't exist, try to create it
            node = FS.mknod(path, mode, 0);
          }
        }
        if (!node) {
          throw new FS.ErrnoError(ERRNO_CODES.ENOENT);
        }
        // can't truncate a device
        if (FS.isChrdev(node.mode)) {
          flags &= ~512;
        }
        // check permissions
        var err = FS.mayOpen(node, flags);
        if (err) {
          throw new FS.ErrnoError(err);
        }
        // do truncation if necessary
        if ((flags & 512)) {
          FS.truncate(node, 0);
        }
        // we've already handled these, don't pass down to the underlying vfs
        flags &= ~(128 | 512);
  
        // register the stream with the filesystem
        var stream = FS.createStream({
          node: node,
          path: FS.getPath(node),  // we want the absolute path to the node
          flags: flags,
          seekable: true,
          position: 0,
          stream_ops: node.stream_ops,
          // used by the file family libc calls (fopen, fwrite, ferror, etc.)
          ungotten: [],
          error: false
        }, fd_start, fd_end);
        // call the new stream's open function
        if (stream.stream_ops.open) {
          stream.stream_ops.open(stream);
        }
        if (Module['logReadFiles'] && !(flags & 1)) {
          if (!FS.readFiles) FS.readFiles = {};
          if (!(path in FS.readFiles)) {
            FS.readFiles[path] = 1;
            Module['printErr']('read file: ' + path);
          }
        }
        return stream;
      },close:function (stream) {
        try {
          if (stream.stream_ops.close) {
            stream.stream_ops.close(stream);
          }
        } catch (e) {
          throw e;
        } finally {
          FS.closeStream(stream.fd);
        }
      },llseek:function (stream, offset, whence) {
        if (!stream.seekable || !stream.stream_ops.llseek) {
          throw new FS.ErrnoError(ERRNO_CODES.ESPIPE);
        }
        return stream.stream_ops.llseek(stream, offset, whence);
      },read:function (stream, buffer, offset, length, position) {
        if (length < 0 || position < 0) {
          throw new FS.ErrnoError(ERRNO_CODES.EINVAL);
        }
        if ((stream.flags & 2097155) === 1) {
          throw new FS.ErrnoError(ERRNO_CODES.EBADF);
        }
        if (FS.isDir(stream.node.mode)) {
          throw new FS.ErrnoError(ERRNO_CODES.EISDIR);
        }
        if (!stream.stream_ops.read) {
          throw new FS.ErrnoError(ERRNO_CODES.EINVAL);
        }
        var seeking = true;
        if (typeof position === 'undefined') {
          position = stream.position;
          seeking = false;
        } else if (!stream.seekable) {
          throw new FS.ErrnoError(ERRNO_CODES.ESPIPE);
        }
        var bytesRead = stream.stream_ops.read(stream, buffer, offset, length, position);
        if (!seeking) stream.position += bytesRead;
        return bytesRead;
      },write:function (stream, buffer, offset, length, position, canOwn) {
        if (length < 0 || position < 0) {
          throw new FS.ErrnoError(ERRNO_CODES.EINVAL);
        }
        if ((stream.flags & 2097155) === 0) {
          throw new FS.ErrnoError(ERRNO_CODES.EBADF);
        }
        if (FS.isDir(stream.node.mode)) {
          throw new FS.ErrnoError(ERRNO_CODES.EISDIR);
        }
        if (!stream.stream_ops.write) {
          throw new FS.ErrnoError(ERRNO_CODES.EINVAL);
        }
        var seeking = true;
        if (typeof position === 'undefined') {
          position = stream.position;
          seeking = false;
        } else if (!stream.seekable) {
          throw new FS.ErrnoError(ERRNO_CODES.ESPIPE);
        }
        if (stream.flags & 1024) {
          // seek to the end before writing in append mode
          FS.llseek(stream, 0, 2);
        }
        var bytesWritten = stream.stream_ops.write(stream, buffer, offset, length, position, canOwn);
        if (!seeking) stream.position += bytesWritten;
        return bytesWritten;
      },allocate:function (stream, offset, length) {
        if (offset < 0 || length <= 0) {
          throw new FS.ErrnoError(ERRNO_CODES.EINVAL);
        }
        if ((stream.flags & 2097155) === 0) {
          throw new FS.ErrnoError(ERRNO_CODES.EBADF);
        }
        if (!FS.isFile(stream.node.mode) && !FS.isDir(node.mode)) {
          throw new FS.ErrnoError(ERRNO_CODES.ENODEV);
        }
        if (!stream.stream_ops.allocate) {
          throw new FS.ErrnoError(ERRNO_CODES.EOPNOTSUPP);
        }
        stream.stream_ops.allocate(stream, offset, length);
      },mmap:function (stream, buffer, offset, length, position, prot, flags) {
        // TODO if PROT is PROT_WRITE, make sure we have write access
        if ((stream.flags & 2097155) === 1) {
          throw new FS.ErrnoError(ERRNO_CODES.EACCES);
        }
        if (!stream.stream_ops.mmap) {
          throw new FS.ErrnoError(ERRNO_CODES.ENODEV);
        }
        return stream.stream_ops.mmap(stream, buffer, offset, length, position, prot, flags);
      },ioctl:function (stream, cmd, arg) {
        if (!stream.stream_ops.ioctl) {
          throw new FS.ErrnoError(ERRNO_CODES.ENOTTY);
        }
        return stream.stream_ops.ioctl(stream, cmd, arg);
      },readFile:function (path, opts) {
        opts = opts || {};
        opts.flags = opts.flags || 'r';
        opts.encoding = opts.encoding || 'binary';
        if (opts.encoding !== 'utf8' && opts.encoding !== 'binary') {
          throw new Error('Invalid encoding type "' + opts.encoding + '"');
        }
        var ret;
        var stream = FS.open(path, opts.flags);
        var stat = FS.stat(path);
        var length = stat.size;
        var buf = new Uint8Array(length);
        FS.read(stream, buf, 0, length, 0);
        if (opts.encoding === 'utf8') {
          ret = '';
          var utf8 = new Runtime.UTF8Processor();
          for (var i = 0; i < length; i++) {
            ret += utf8.processCChar(buf[i]);
          }
        } else if (opts.encoding === 'binary') {
          ret = buf;
        }
        FS.close(stream);
        return ret;
      },writeFile:function (path, data, opts) {
        opts = opts || {};
        opts.flags = opts.flags || 'w';
        opts.encoding = opts.encoding || 'utf8';
        if (opts.encoding !== 'utf8' && opts.encoding !== 'binary') {
          throw new Error('Invalid encoding type "' + opts.encoding + '"');
        }
        var stream = FS.open(path, opts.flags, opts.mode);
        if (opts.encoding === 'utf8') {
          var utf8 = new Runtime.UTF8Processor();
          var buf = new Uint8Array(utf8.processJSString(data));
          FS.write(stream, buf, 0, buf.length, 0, opts.canOwn);
        } else if (opts.encoding === 'binary') {
          FS.write(stream, data, 0, data.length, 0, opts.canOwn);
        }
        FS.close(stream);
      },cwd:function () {
        return FS.currentPath;
      },chdir:function (path) {
        var lookup = FS.lookupPath(path, { follow: true });
        if (!FS.isDir(lookup.node.mode)) {
          throw new FS.ErrnoError(ERRNO_CODES.ENOTDIR);
        }
        var err = FS.nodePermissions(lookup.node, 'x');
        if (err) {
          throw new FS.ErrnoError(err);
        }
        FS.currentPath = lookup.path;
      },createDefaultDirectories:function () {
        FS.mkdir('/tmp');
      },createDefaultDevices:function () {
        // create /dev
        FS.mkdir('/dev');
        // setup /dev/null
        FS.registerDevice(FS.makedev(1, 3), {
          read: function() { return 0; },
          write: function() { return 0; }
        });
        FS.mkdev('/dev/null', FS.makedev(1, 3));
        // setup /dev/tty and /dev/tty1
        // stderr needs to print output using Module['printErr']
        // so we register a second tty just for it.
        TTY.register(FS.makedev(5, 0), TTY.default_tty_ops);
        TTY.register(FS.makedev(6, 0), TTY.default_tty1_ops);
        FS.mkdev('/dev/tty', FS.makedev(5, 0));
        FS.mkdev('/dev/tty1', FS.makedev(6, 0));
        // we're not going to emulate the actual shm device,
        // just create the tmp dirs that reside in it commonly
        FS.mkdir('/dev/shm');
        FS.mkdir('/dev/shm/tmp');
      },createStandardStreams:function () {
        // TODO deprecate the old functionality of a single
        // input / output callback and that utilizes FS.createDevice
        // and instead require a unique set of stream ops
  
        // by default, we symlink the standard streams to the
        // default tty devices. however, if the standard streams
        // have been overwritten we create a unique device for
        // them instead.
        if (Module['stdin']) {
          FS.createDevice('/dev', 'stdin', Module['stdin']);
        } else {
          FS.symlink('/dev/tty', '/dev/stdin');
        }
        if (Module['stdout']) {
          FS.createDevice('/dev', 'stdout', null, Module['stdout']);
        } else {
          FS.symlink('/dev/tty', '/dev/stdout');
        }
        if (Module['stderr']) {
          FS.createDevice('/dev', 'stderr', null, Module['stderr']);
        } else {
          FS.symlink('/dev/tty1', '/dev/stderr');
        }
  
        // open default streams for the stdin, stdout and stderr devices
        var stdin = FS.open('/dev/stdin', 'r');
        HEAP32[((_stdin)>>2)]=FS.getPtrForStream(stdin);
        assert(stdin.fd === 0, 'invalid handle for stdin (' + stdin.fd + ')');
  
        var stdout = FS.open('/dev/stdout', 'w');
        HEAP32[((_stdout)>>2)]=FS.getPtrForStream(stdout);
        assert(stdout.fd === 1, 'invalid handle for stdout (' + stdout.fd + ')');
  
        var stderr = FS.open('/dev/stderr', 'w');
        HEAP32[((_stderr)>>2)]=FS.getPtrForStream(stderr);
        assert(stderr.fd === 2, 'invalid handle for stderr (' + stderr.fd + ')');
      },ensureErrnoError:function () {
        if (FS.ErrnoError) return;
        FS.ErrnoError = function ErrnoError(errno) {
          this.errno = errno;
          for (var key in ERRNO_CODES) {
            if (ERRNO_CODES[key] === errno) {
              this.code = key;
              break;
            }
          }
          this.message = ERRNO_MESSAGES[errno];
        };
        FS.ErrnoError.prototype = new Error();
        FS.ErrnoError.prototype.constructor = FS.ErrnoError;
        // Some errors may happen quite a bit, to avoid overhead we reuse them (and suffer a lack of stack info)
        [ERRNO_CODES.ENOENT].forEach(function(code) {
          FS.genericErrors[code] = new FS.ErrnoError(code);
          FS.genericErrors[code].stack = '<generic error, no stack>';
        });
      },staticInit:function () {
        FS.ensureErrnoError();
  
        FS.nameTable = new Array(4096);
  
        FS.mount(MEMFS, {}, '/');
  
        FS.createDefaultDirectories();
        FS.createDefaultDevices();
      },init:function (input, output, error) {
        assert(!FS.init.initialized, 'FS.init was previously called. If you want to initialize later with custom parameters, remove any earlier calls (note that one is automatically added to the generated code)');
        FS.init.initialized = true;
  
        FS.ensureErrnoError();
  
        // Allow Module.stdin etc. to provide defaults, if none explicitly passed to us here
        Module['stdin'] = input || Module['stdin'];
        Module['stdout'] = output || Module['stdout'];
        Module['stderr'] = error || Module['stderr'];
  
        FS.createStandardStreams();
      },quit:function () {
        FS.init.initialized = false;
        for (var i = 0; i < FS.streams.length; i++) {
          var stream = FS.streams[i];
          if (!stream) {
            continue;
          }
          FS.close(stream);
        }
      },getMode:function (canRead, canWrite) {
        var mode = 0;
        if (canRead) mode |= 292 | 73;
        if (canWrite) mode |= 146;
        return mode;
      },joinPath:function (parts, forceRelative) {
        var path = PATH.join.apply(null, parts);
        if (forceRelative && path[0] == '/') path = path.substr(1);
        return path;
      },absolutePath:function (relative, base) {
        return PATH.resolve(base, relative);
      },standardizePath:function (path) {
        return PATH.normalize(path);
      },findObject:function (path, dontResolveLastLink) {
        var ret = FS.analyzePath(path, dontResolveLastLink);
        if (ret.exists) {
          return ret.object;
        } else {
          ___setErrNo(ret.error);
          return null;
        }
      },analyzePath:function (path, dontResolveLastLink) {
        // operate from within the context of the symlink's target
        try {
          var lookup = FS.lookupPath(path, { follow: !dontResolveLastLink });
          path = lookup.path;
        } catch (e) {
        }
        var ret = {
          isRoot: false, exists: false, error: 0, name: null, path: null, object: null,
          parentExists: false, parentPath: null, parentObject: null
        };
        try {
          var lookup = FS.lookupPath(path, { parent: true });
          ret.parentExists = true;
          ret.parentPath = lookup.path;
          ret.parentObject = lookup.node;
          ret.name = PATH.basename(path);
          lookup = FS.lookupPath(path, { follow: !dontResolveLastLink });
          ret.exists = true;
          ret.path = lookup.path;
          ret.object = lookup.node;
          ret.name = lookup.node.name;
          ret.isRoot = lookup.path === '/';
        } catch (e) {
          ret.error = e.errno;
        };
        return ret;
      },createFolder:function (parent, name, canRead, canWrite) {
        var path = PATH.join2(typeof parent === 'string' ? parent : FS.getPath(parent), name);
        var mode = FS.getMode(canRead, canWrite);
        return FS.mkdir(path, mode);
      },createPath:function (parent, path, canRead, canWrite) {
        parent = typeof parent === 'string' ? parent : FS.getPath(parent);
        var parts = path.split('/').reverse();
        while (parts.length) {
          var part = parts.pop();
          if (!part) continue;
          var current = PATH.join2(parent, part);
          try {
            FS.mkdir(current);
          } catch (e) {
            // ignore EEXIST
          }
          parent = current;
        }
        return current;
      },createFile:function (parent, name, properties, canRead, canWrite) {
        var path = PATH.join2(typeof parent === 'string' ? parent : FS.getPath(parent), name);
        var mode = FS.getMode(canRead, canWrite);
        return FS.create(path, mode);
      },createDataFile:function (parent, name, data, canRead, canWrite, canOwn) {
        var path = name ? PATH.join2(typeof parent === 'string' ? parent : FS.getPath(parent), name) : parent;
        var mode = FS.getMode(canRead, canWrite);
        var node = FS.create(path, mode);
        if (data) {
          if (typeof data === 'string') {
            var arr = new Array(data.length);
            for (var i = 0, len = data.length; i < len; ++i) arr[i] = data.charCodeAt(i);
            data = arr;
          }
          // make sure we can write to the file
          FS.chmod(node, mode | 146);
          var stream = FS.open(node, 'w');
          FS.write(stream, data, 0, data.length, 0, canOwn);
          FS.close(stream);
          FS.chmod(node, mode);
        }
        return node;
      },createDevice:function (parent, name, input, output) {
        var path = PATH.join2(typeof parent === 'string' ? parent : FS.getPath(parent), name);
        var mode = FS.getMode(!!input, !!output);
        if (!FS.createDevice.major) FS.createDevice.major = 64;
        var dev = FS.makedev(FS.createDevice.major++, 0);
        // Create a fake device that a set of stream ops to emulate
        // the old behavior.
        FS.registerDevice(dev, {
          open: function(stream) {
            stream.seekable = false;
          },
          close: function(stream) {
            // flush any pending line data
            if (output && output.buffer && output.buffer.length) {
              output(10);
            }
          },
          read: function(stream, buffer, offset, length, pos /* ignored */) {
            var bytesRead = 0;
            for (var i = 0; i < length; i++) {
              var result;
              try {
                result = input();
              } catch (e) {
                throw new FS.ErrnoError(ERRNO_CODES.EIO);
              }
              if (result === undefined && bytesRead === 0) {
                throw new FS.ErrnoError(ERRNO_CODES.EAGAIN);
              }
              if (result === null || result === undefined) break;
              bytesRead++;
              buffer[offset+i] = result;
            }
            if (bytesRead) {
              stream.node.timestamp = Date.now();
            }
            return bytesRead;
          },
          write: function(stream, buffer, offset, length, pos) {
            for (var i = 0; i < length; i++) {
              try {
                output(buffer[offset+i]);
              } catch (e) {
                throw new FS.ErrnoError(ERRNO_CODES.EIO);
              }
            }
            if (length) {
              stream.node.timestamp = Date.now();
            }
            return i;
          }
        });
        return FS.mkdev(path, mode, dev);
      },createLink:function (parent, name, target, canRead, canWrite) {
        var path = PATH.join2(typeof parent === 'string' ? parent : FS.getPath(parent), name);
        return FS.symlink(target, path);
      },forceLoadFile:function (obj) {
        if (obj.isDevice || obj.isFolder || obj.link || obj.contents) return true;
        var success = true;
        if (typeof XMLHttpRequest !== 'undefined') {
          throw new Error("Lazy loading should have been performed (contents set) in createLazyFile, but it was not. Lazy loading only works in web workers. Use --embed-file or --preload-file in emcc on the main thread.");
        } else if (Module['read']) {
          // Command-line.
          try {
            // WARNING: Can't read binary files in V8's d8 or tracemonkey's js, as
            //          read() will try to parse UTF8.
            obj.contents = intArrayFromString(Module['read'](obj.url), true);
          } catch (e) {
            success = false;
          }
        } else {
          throw new Error('Cannot load without read() or XMLHttpRequest.');
        }
        if (!success) ___setErrNo(ERRNO_CODES.EIO);
        return success;
      },createLazyFile:function (parent, name, url, canRead, canWrite) {
        if (typeof XMLHttpRequest !== 'undefined') {
          if (!ENVIRONMENT_IS_WORKER) throw 'Cannot do synchronous binary XHRs outside webworkers in modern browsers. Use --embed-file or --preload-file in emcc';
          // Lazy chunked Uint8Array (implements get and length from Uint8Array). Actual getting is abstracted away for eventual reuse.
          function LazyUint8Array() {
            this.lengthKnown = false;
            this.chunks = []; // Loaded chunks. Index is the chunk number
          }
          LazyUint8Array.prototype.get = function LazyUint8Array_get(idx) {
            if (idx > this.length-1 || idx < 0) {
              return undefined;
            }
            var chunkOffset = idx % this.chunkSize;
            var chunkNum = Math.floor(idx / this.chunkSize);
            return this.getter(chunkNum)[chunkOffset];
          }
          LazyUint8Array.prototype.setDataGetter = function LazyUint8Array_setDataGetter(getter) {
            this.getter = getter;
          }
          LazyUint8Array.prototype.cacheLength = function LazyUint8Array_cacheLength() {
              // Find length
              var xhr = new XMLHttpRequest();
              xhr.open('HEAD', url, false);
              xhr.send(null);
              if (!(xhr.status >= 200 && xhr.status < 300 || xhr.status === 304)) throw new Error("Couldn't load " + url + ". Status: " + xhr.status);
              var datalength = Number(xhr.getResponseHeader("Content-length"));
              var header;
              var hasByteServing = (header = xhr.getResponseHeader("Accept-Ranges")) && header === "bytes";
              var chunkSize = 1024*1024; // Chunk size in bytes
  
              if (!hasByteServing) chunkSize = datalength;
  
              // Function to get a range from the remote URL.
              var doXHR = (function(from, to) {
                if (from > to) throw new Error("invalid range (" + from + ", " + to + ") or no bytes requested!");
                if (to > datalength-1) throw new Error("only " + datalength + " bytes available! programmer error!");
  
                // TODO: Use mozResponseArrayBuffer, responseStream, etc. if available.
                var xhr = new XMLHttpRequest();
                xhr.open('GET', url, false);
                if (datalength !== chunkSize) xhr.setRequestHeader("Range", "bytes=" + from + "-" + to);
  
                // Some hints to the browser that we want binary data.
                if (typeof Uint8Array != 'undefined') xhr.responseType = 'arraybuffer';
                if (xhr.overrideMimeType) {
                  xhr.overrideMimeType('text/plain; charset=x-user-defined');
                }
  
                xhr.send(null);
                if (!(xhr.status >= 200 && xhr.status < 300 || xhr.status === 304)) throw new Error("Couldn't load " + url + ". Status: " + xhr.status);
                if (xhr.response !== undefined) {
                  return new Uint8Array(xhr.response || []);
                } else {
                  return intArrayFromString(xhr.responseText || '', true);
                }
              });
              var lazyArray = this;
              lazyArray.setDataGetter(function(chunkNum) {
                var start = chunkNum * chunkSize;
                var end = (chunkNum+1) * chunkSize - 1; // including this byte
                end = Math.min(end, datalength-1); // if datalength-1 is selected, this is the last block
                if (typeof(lazyArray.chunks[chunkNum]) === "undefined") {
                  lazyArray.chunks[chunkNum] = doXHR(start, end);
                }
                if (typeof(lazyArray.chunks[chunkNum]) === "undefined") throw new Error("doXHR failed!");
                return lazyArray.chunks[chunkNum];
              });
  
              this._length = datalength;
              this._chunkSize = chunkSize;
              this.lengthKnown = true;
          }
  
          var lazyArray = new LazyUint8Array();
          Object.defineProperty(lazyArray, "length", {
              get: function() {
                  if(!this.lengthKnown) {
                      this.cacheLength();
                  }
                  return this._length;
              }
          });
          Object.defineProperty(lazyArray, "chunkSize", {
              get: function() {
                  if(!this.lengthKnown) {
                      this.cacheLength();
                  }
                  return this._chunkSize;
              }
          });
  
          var properties = { isDevice: false, contents: lazyArray };
        } else {
          var properties = { isDevice: false, url: url };
        }
  
        var node = FS.createFile(parent, name, properties, canRead, canWrite);
        // This is a total hack, but I want to get this lazy file code out of the
        // core of MEMFS. If we want to keep this lazy file concept I feel it should
        // be its own thin LAZYFS proxying calls to MEMFS.
        if (properties.contents) {
          node.contents = properties.contents;
        } else if (properties.url) {
          node.contents = null;
          node.url = properties.url;
        }
        // override each stream op with one that tries to force load the lazy file first
        var stream_ops = {};
        var keys = Object.keys(node.stream_ops);
        keys.forEach(function(key) {
          var fn = node.stream_ops[key];
          stream_ops[key] = function forceLoadLazyFile() {
            if (!FS.forceLoadFile(node)) {
              throw new FS.ErrnoError(ERRNO_CODES.EIO);
            }
            return fn.apply(null, arguments);
          };
        });
        // use a custom read function
        stream_ops.read = function stream_ops_read(stream, buffer, offset, length, position) {
          if (!FS.forceLoadFile(node)) {
            throw new FS.ErrnoError(ERRNO_CODES.EIO);
          }
          var contents = stream.node.contents;
          if (position >= contents.length)
            return 0;
          var size = Math.min(contents.length - position, length);
          assert(size >= 0);
          if (contents.slice) { // normal array
            for (var i = 0; i < size; i++) {
              buffer[offset + i] = contents[position + i];
            }
          } else {
            for (var i = 0; i < size; i++) { // LazyUint8Array from sync binary XHR
              buffer[offset + i] = contents.get(position + i);
            }
          }
          return size;
        };
        node.stream_ops = stream_ops;
        return node;
      },createPreloadedFile:function (parent, name, url, canRead, canWrite, onload, onerror, dontCreateFile, canOwn) {
        Browser.init();
        // TODO we should allow people to just pass in a complete filename instead
        // of parent and name being that we just join them anyways
        var fullname = name ? PATH.resolve(PATH.join2(parent, name)) : parent;
        function processData(byteArray) {
          function finish(byteArray) {
            if (!dontCreateFile) {
              FS.createDataFile(parent, name, byteArray, canRead, canWrite, canOwn);
            }
            if (onload) onload();
            removeRunDependency('cp ' + fullname);
          }
          var handled = false;
          Module['preloadPlugins'].forEach(function(plugin) {
            if (handled) return;
            if (plugin['canHandle'](fullname)) {
              plugin['handle'](byteArray, fullname, finish, function() {
                if (onerror) onerror();
                removeRunDependency('cp ' + fullname);
              });
              handled = true;
            }
          });
          if (!handled) finish(byteArray);
        }
        addRunDependency('cp ' + fullname);
        if (typeof url == 'string') {
          Browser.asyncLoad(url, function(byteArray) {
            processData(byteArray);
          }, onerror);
        } else {
          processData(url);
        }
      },indexedDB:function () {
        return window.indexedDB || window.mozIndexedDB || window.webkitIndexedDB || window.msIndexedDB;
      },DB_NAME:function () {
        return 'EM_FS_' + window.location.pathname;
      },DB_VERSION:20,DB_STORE_NAME:"FILE_DATA",saveFilesToDB:function (paths, onload, onerror) {
        onload = onload || function(){};
        onerror = onerror || function(){};
        var indexedDB = FS.indexedDB();
        try {
          var openRequest = indexedDB.open(FS.DB_NAME(), FS.DB_VERSION);
        } catch (e) {
          return onerror(e);
        }
        openRequest.onupgradeneeded = function openRequest_onupgradeneeded() {
          console.log('creating db');
          var db = openRequest.result;
          db.createObjectStore(FS.DB_STORE_NAME);
        };
        openRequest.onsuccess = function openRequest_onsuccess() {
          var db = openRequest.result;
          var transaction = db.transaction([FS.DB_STORE_NAME], 'readwrite');
          var files = transaction.objectStore(FS.DB_STORE_NAME);
          var ok = 0, fail = 0, total = paths.length;
          function finish() {
            if (fail == 0) onload(); else onerror();
          }
          paths.forEach(function(path) {
            var putRequest = files.put(FS.analyzePath(path).object.contents, path);
            putRequest.onsuccess = function putRequest_onsuccess() { ok++; if (ok + fail == total) finish() };
            putRequest.onerror = function putRequest_onerror() { fail++; if (ok + fail == total) finish() };
          });
          transaction.onerror = onerror;
        };
        openRequest.onerror = onerror;
      },loadFilesFromDB:function (paths, onload, onerror) {
        onload = onload || function(){};
        onerror = onerror || function(){};
        var indexedDB = FS.indexedDB();
        try {
          var openRequest = indexedDB.open(FS.DB_NAME(), FS.DB_VERSION);
        } catch (e) {
          return onerror(e);
        }
        openRequest.onupgradeneeded = onerror; // no database to load from
        openRequest.onsuccess = function openRequest_onsuccess() {
          var db = openRequest.result;
          try {
            var transaction = db.transaction([FS.DB_STORE_NAME], 'readonly');
          } catch(e) {
            onerror(e);
            return;
          }
          var files = transaction.objectStore(FS.DB_STORE_NAME);
          var ok = 0, fail = 0, total = paths.length;
          function finish() {
            if (fail == 0) onload(); else onerror();
          }
          paths.forEach(function(path) {
            var getRequest = files.get(path);
            getRequest.onsuccess = function getRequest_onsuccess() {
              if (FS.analyzePath(path).exists) {
                FS.unlink(path);
              }
              FS.createDataFile(PATH.dirname(path), PATH.basename(path), getRequest.result, true, true, true);
              ok++;
              if (ok + fail == total) finish();
            };
            getRequest.onerror = function getRequest_onerror() { fail++; if (ok + fail == total) finish() };
          });
          transaction.onerror = onerror;
        };
        openRequest.onerror = onerror;
      }};var PATH={splitPath:function (filename) {
        var splitPathRe = /^(\/?|)([\s\S]*?)((?:\.{1,2}|[^\/]+?|)(\.[^.\/]*|))(?:[\/]*)$/;
        return splitPathRe.exec(filename).slice(1);
      },normalizeArray:function (parts, allowAboveRoot) {
        // if the path tries to go above the root, `up` ends up > 0
        var up = 0;
        for (var i = parts.length - 1; i >= 0; i--) {
          var last = parts[i];
          if (last === '.') {
            parts.splice(i, 1);
          } else if (last === '..') {
            parts.splice(i, 1);
            up++;
          } else if (up) {
            parts.splice(i, 1);
            up--;
          }
        }
        // if the path is allowed to go above the root, restore leading ..s
        if (allowAboveRoot) {
          for (; up--; up) {
            parts.unshift('..');
          }
        }
        return parts;
      },normalize:function (path) {
        var isAbsolute = path.charAt(0) === '/',
            trailingSlash = path.substr(-1) === '/';
        // Normalize the path
        path = PATH.normalizeArray(path.split('/').filter(function(p) {
          return !!p;
        }), !isAbsolute).join('/');
        if (!path && !isAbsolute) {
          path = '.';
        }
        if (path && trailingSlash) {
          path += '/';
        }
        return (isAbsolute ? '/' : '') + path;
      },dirname:function (path) {
        var result = PATH.splitPath(path),
            root = result[0],
            dir = result[1];
        if (!root && !dir) {
          // No dirname whatsoever
          return '.';
        }
        if (dir) {
          // It has a dirname, strip trailing slash
          dir = dir.substr(0, dir.length - 1);
        }
        return root + dir;
      },basename:function (path) {
        // EMSCRIPTEN return '/'' for '/', not an empty string
        if (path === '/') return '/';
        var lastSlash = path.lastIndexOf('/');
        if (lastSlash === -1) return path;
        return path.substr(lastSlash+1);
      },extname:function (path) {
        return PATH.splitPath(path)[3];
      },join:function () {
        var paths = Array.prototype.slice.call(arguments, 0);
        return PATH.normalize(paths.join('/'));
      },join2:function (l, r) {
        return PATH.normalize(l + '/' + r);
      },resolve:function () {
        var resolvedPath = '',
          resolvedAbsolute = false;
        for (var i = arguments.length - 1; i >= -1 && !resolvedAbsolute; i--) {
          var path = (i >= 0) ? arguments[i] : FS.cwd();
          // Skip empty and invalid entries
          if (typeof path !== 'string') {
            throw new TypeError('Arguments to path.resolve must be strings');
          } else if (!path) {
            continue;
          }
          resolvedPath = path + '/' + resolvedPath;
          resolvedAbsolute = path.charAt(0) === '/';
        }
        // At this point the path should be resolved to a full absolute path, but
        // handle relative paths to be safe (might happen when process.cwd() fails)
        resolvedPath = PATH.normalizeArray(resolvedPath.split('/').filter(function(p) {
          return !!p;
        }), !resolvedAbsolute).join('/');
        return ((resolvedAbsolute ? '/' : '') + resolvedPath) || '.';
      },relative:function (from, to) {
        from = PATH.resolve(from).substr(1);
        to = PATH.resolve(to).substr(1);
        function trim(arr) {
          var start = 0;
          for (; start < arr.length; start++) {
            if (arr[start] !== '') break;
          }
          var end = arr.length - 1;
          for (; end >= 0; end--) {
            if (arr[end] !== '') break;
          }
          if (start > end) return [];
          return arr.slice(start, end - start + 1);
        }
        var fromParts = trim(from.split('/'));
        var toParts = trim(to.split('/'));
        var length = Math.min(fromParts.length, toParts.length);
        var samePartsLength = length;
        for (var i = 0; i < length; i++) {
          if (fromParts[i] !== toParts[i]) {
            samePartsLength = i;
            break;
          }
        }
        var outputParts = [];
        for (var i = samePartsLength; i < fromParts.length; i++) {
          outputParts.push('..');
        }
        outputParts = outputParts.concat(toParts.slice(samePartsLength));
        return outputParts.join('/');
      }};var Browser={mainLoop:{scheduler:null,method:"",shouldPause:false,paused:false,queue:[],pause:function () {
          Browser.mainLoop.shouldPause = true;
        },resume:function () {
          if (Browser.mainLoop.paused) {
            Browser.mainLoop.paused = false;
            Browser.mainLoop.scheduler();
          }
          Browser.mainLoop.shouldPause = false;
        },updateStatus:function () {
          if (Module['setStatus']) {
            var message = Module['statusMessage'] || 'Please wait...';
            var remaining = Browser.mainLoop.remainingBlockers;
            var expected = Browser.mainLoop.expectedBlockers;
            if (remaining) {
              if (remaining < expected) {
                Module['setStatus'](message + ' (' + (expected - remaining) + '/' + expected + ')');
              } else {
                Module['setStatus'](message);
              }
            } else {
              Module['setStatus']('');
            }
          }
        }},isFullScreen:false,pointerLock:false,moduleContextCreatedCallbacks:[],workers:[],init:function () {
        if (!Module["preloadPlugins"]) Module["preloadPlugins"] = []; // needs to exist even in workers
  
        if (Browser.initted || ENVIRONMENT_IS_WORKER) return;
        Browser.initted = true;
  
        try {
          new Blob();
          Browser.hasBlobConstructor = true;
        } catch(e) {
          Browser.hasBlobConstructor = false;
          console.log("warning: no blob constructor, cannot create blobs with mimetypes");
        }
        Browser.BlobBuilder = typeof MozBlobBuilder != "undefined" ? MozBlobBuilder : (typeof WebKitBlobBuilder != "undefined" ? WebKitBlobBuilder : (!Browser.hasBlobConstructor ? console.log("warning: no BlobBuilder") : null));
        Browser.URLObject = typeof window != "undefined" ? (window.URL ? window.URL : window.webkitURL) : undefined;
        if (!Module.noImageDecoding && typeof Browser.URLObject === 'undefined') {
          console.log("warning: Browser does not support creating object URLs. Built-in browser image decoding will not be available.");
          Module.noImageDecoding = true;
        }
  
        // Support for plugins that can process preloaded files. You can add more of these to
        // your app by creating and appending to Module.preloadPlugins.
        //
        // Each plugin is asked if it can handle a file based on the file's name. If it can,
        // it is given the file's raw data. When it is done, it calls a callback with the file's
        // (possibly modified) data. For example, a plugin might decompress a file, or it
        // might create some side data structure for use later (like an Image element, etc.).
  
        var imagePlugin = {};
        imagePlugin['canHandle'] = function imagePlugin_canHandle(name) {
          return !Module.noImageDecoding && /\.(jpg|jpeg|png|bmp)$/i.test(name);
        };
        imagePlugin['handle'] = function imagePlugin_handle(byteArray, name, onload, onerror) {
          var b = null;
          if (Browser.hasBlobConstructor) {
            try {
              b = new Blob([byteArray], { type: Browser.getMimetype(name) });
              if (b.size !== byteArray.length) { // Safari bug #118630
                // Safari's Blob can only take an ArrayBuffer
                b = new Blob([(new Uint8Array(byteArray)).buffer], { type: Browser.getMimetype(name) });
              }
            } catch(e) {
              Runtime.warnOnce('Blob constructor present but fails: ' + e + '; falling back to blob builder');
            }
          }
          if (!b) {
            var bb = new Browser.BlobBuilder();
            bb.append((new Uint8Array(byteArray)).buffer); // we need to pass a buffer, and must copy the array to get the right data range
            b = bb.getBlob();
          }
          var url = Browser.URLObject.createObjectURL(b);
          var img = new Image();
          img.onload = function img_onload() {
            assert(img.complete, 'Image ' + name + ' could not be decoded');
            var canvas = document.createElement('canvas');
            canvas.width = img.width;
            canvas.height = img.height;
            var ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);
            Module["preloadedImages"][name] = canvas;
            Browser.URLObject.revokeObjectURL(url);
            if (onload) onload(byteArray);
          };
          img.onerror = function img_onerror(event) {
            console.log('Image ' + url + ' could not be decoded');
            if (onerror) onerror();
          };
          img.src = url;
        };
        Module['preloadPlugins'].push(imagePlugin);
  
        var audioPlugin = {};
        audioPlugin['canHandle'] = function audioPlugin_canHandle(name) {
          return !Module.noAudioDecoding && name.substr(-4) in { '.ogg': 1, '.wav': 1, '.mp3': 1 };
        };
        audioPlugin['handle'] = function audioPlugin_handle(byteArray, name, onload, onerror) {
          var done = false;
          function finish(audio) {
            if (done) return;
            done = true;
            Module["preloadedAudios"][name] = audio;
            if (onload) onload(byteArray);
          }
          function fail() {
            if (done) return;
            done = true;
            Module["preloadedAudios"][name] = new Audio(); // empty shim
            if (onerror) onerror();
          }
          if (Browser.hasBlobConstructor) {
            try {
              var b = new Blob([byteArray], { type: Browser.getMimetype(name) });
            } catch(e) {
              return fail();
            }
            var url = Browser.URLObject.createObjectURL(b); // XXX we never revoke this!
            var audio = new Audio();
            audio.addEventListener('canplaythrough', function() { finish(audio) }, false); // use addEventListener due to chromium bug 124926
            audio.onerror = function audio_onerror(event) {
              if (done) return;
              console.log('warning: browser could not fully decode audio ' + name + ', trying slower base64 approach');
              function encode64(data) {
                var BASE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
                var PAD = '=';
                var ret = '';
                var leftchar = 0;
                var leftbits = 0;
                for (var i = 0; i < data.length; i++) {
                  leftchar = (leftchar << 8) | data[i];
                  leftbits += 8;
                  while (leftbits >= 6) {
                    var curr = (leftchar >> (leftbits-6)) & 0x3f;
                    leftbits -= 6;
                    ret += BASE[curr];
                  }
                }
                if (leftbits == 2) {
                  ret += BASE[(leftchar&3) << 4];
                  ret += PAD + PAD;
                } else if (leftbits == 4) {
                  ret += BASE[(leftchar&0xf) << 2];
                  ret += PAD;
                }
                return ret;
              }
              audio.src = 'data:audio/x-' + name.substr(-3) + ';base64,' + encode64(byteArray);
              finish(audio); // we don't wait for confirmation this worked - but it's worth trying
            };
            audio.src = url;
            // workaround for chrome bug 124926 - we do not always get oncanplaythrough or onerror
            Browser.safeSetTimeout(function() {
              finish(audio); // try to use it even though it is not necessarily ready to play
            }, 10000);
          } else {
            return fail();
          }
        };
        Module['preloadPlugins'].push(audioPlugin);
  
        // Canvas event setup
  
        var canvas = Module['canvas'];
        canvas.requestPointerLock = canvas['requestPointerLock'] ||
                                    canvas['mozRequestPointerLock'] ||
                                    canvas['webkitRequestPointerLock'];
        canvas.exitPointerLock = document['exitPointerLock'] ||
                                 document['mozExitPointerLock'] ||
                                 document['webkitExitPointerLock'] ||
                                 function(){}; // no-op if function does not exist
        canvas.exitPointerLock = canvas.exitPointerLock.bind(document);
  
        function pointerLockChange() {
          Browser.pointerLock = document['pointerLockElement'] === canvas ||
                                document['mozPointerLockElement'] === canvas ||
                                document['webkitPointerLockElement'] === canvas;
        }
  
        document.addEventListener('pointerlockchange', pointerLockChange, false);
        document.addEventListener('mozpointerlockchange', pointerLockChange, false);
        document.addEventListener('webkitpointerlockchange', pointerLockChange, false);
  
        if (Module['elementPointerLock']) {
          canvas.addEventListener("click", function(ev) {
            if (!Browser.pointerLock && canvas.requestPointerLock) {
              canvas.requestPointerLock();
              ev.preventDefault();
            }
          }, false);
        }
      },createContext:function (canvas, useWebGL, setInModule, webGLContextAttributes) {
        var ctx;
        var errorInfo = '?';
        function onContextCreationError(event) {
          errorInfo = event.statusMessage || errorInfo;
        }
        try {
          if (useWebGL) {
            var contextAttributes = {
              antialias: false,
              alpha: false
            };
  
            if (webGLContextAttributes) {
              for (var attribute in webGLContextAttributes) {
                contextAttributes[attribute] = webGLContextAttributes[attribute];
              }
            }
  
  
            canvas.addEventListener('webglcontextcreationerror', onContextCreationError, false);
            try {
              ['experimental-webgl', 'webgl'].some(function(webglId) {
                return ctx = canvas.getContext(webglId, contextAttributes);
              });
            } finally {
              canvas.removeEventListener('webglcontextcreationerror', onContextCreationError, false);
            }
          } else {
            ctx = canvas.getContext('2d');
          }
          if (!ctx) throw ':(';
        } catch (e) {
          Module.print('Could not create canvas: ' + [errorInfo, e]);
          return null;
        }
        if (useWebGL) {
          // Set the background of the WebGL canvas to black
          canvas.style.backgroundColor = "black";
  
          // Warn on context loss
          canvas.addEventListener('webglcontextlost', function(event) {
            alert('WebGL context lost. You will need to reload the page.');
          }, false);
        }
        if (setInModule) {
          GLctx = Module.ctx = ctx;
          Module.useWebGL = useWebGL;
          Browser.moduleContextCreatedCallbacks.forEach(function(callback) { callback() });
          Browser.init();
        }
        return ctx;
      },destroyContext:function (canvas, useWebGL, setInModule) {},fullScreenHandlersInstalled:false,lockPointer:undefined,resizeCanvas:undefined,requestFullScreen:function (lockPointer, resizeCanvas) {
        Browser.lockPointer = lockPointer;
        Browser.resizeCanvas = resizeCanvas;
        if (typeof Browser.lockPointer === 'undefined') Browser.lockPointer = true;
        if (typeof Browser.resizeCanvas === 'undefined') Browser.resizeCanvas = false;
  
        var canvas = Module['canvas'];
        function fullScreenChange() {
          Browser.isFullScreen = false;
          if ((document['webkitFullScreenElement'] || document['webkitFullscreenElement'] ||
               document['mozFullScreenElement'] || document['mozFullscreenElement'] ||
               document['fullScreenElement'] || document['fullscreenElement']) === canvas) {
            canvas.cancelFullScreen = document['cancelFullScreen'] ||
                                      document['mozCancelFullScreen'] ||
                                      document['webkitCancelFullScreen'];
            canvas.cancelFullScreen = canvas.cancelFullScreen.bind(document);
            if (Browser.lockPointer) canvas.requestPointerLock();
            Browser.isFullScreen = true;
            if (Browser.resizeCanvas) Browser.setFullScreenCanvasSize();
          } else if (Browser.resizeCanvas){
            Browser.setWindowedCanvasSize();
          }
          if (Module['onFullScreen']) Module['onFullScreen'](Browser.isFullScreen);
        }
  
        if (!Browser.fullScreenHandlersInstalled) {
          Browser.fullScreenHandlersInstalled = true;
          document.addEventListener('fullscreenchange', fullScreenChange, false);
          document.addEventListener('mozfullscreenchange', fullScreenChange, false);
          document.addEventListener('webkitfullscreenchange', fullScreenChange, false);
        }
  
        canvas.requestFullScreen = canvas['requestFullScreen'] ||
                                   canvas['mozRequestFullScreen'] ||
                                   (canvas['webkitRequestFullScreen'] ? function() { canvas['webkitRequestFullScreen'](Element['ALLOW_KEYBOARD_INPUT']) } : null);
        canvas.requestFullScreen();
      },requestAnimationFrame:function requestAnimationFrame(func) {
        if (typeof window === 'undefined') { // Provide fallback to setTimeout if window is undefined (e.g. in Node.js)
          setTimeout(func, 1000/60);
        } else {
          if (!window.requestAnimationFrame) {
            window.requestAnimationFrame = window['requestAnimationFrame'] ||
                                           window['mozRequestAnimationFrame'] ||
                                           window['webkitRequestAnimationFrame'] ||
                                           window['msRequestAnimationFrame'] ||
                                           window['oRequestAnimationFrame'] ||
                                           window['setTimeout'];
          }
          window.requestAnimationFrame(func);
        }
      },safeCallback:function (func) {
        return function() {
          if (!ABORT) return func.apply(null, arguments);
        };
      },safeRequestAnimationFrame:function (func) {
        return Browser.requestAnimationFrame(function() {
          if (!ABORT) func();
        });
      },safeSetTimeout:function (func, timeout) {
        return setTimeout(function() {
          if (!ABORT) func();
        }, timeout);
      },safeSetInterval:function (func, timeout) {
        return setInterval(function() {
          if (!ABORT) func();
        }, timeout);
      },getMimetype:function (name) {
        return {
          'jpg': 'image/jpeg',
          'jpeg': 'image/jpeg',
          'png': 'image/png',
          'bmp': 'image/bmp',
          'ogg': 'audio/ogg',
          'wav': 'audio/wav',
          'mp3': 'audio/mpeg'
        }[name.substr(name.lastIndexOf('.')+1)];
      },getUserMedia:function (func) {
        if(!window.getUserMedia) {
          window.getUserMedia = navigator['getUserMedia'] ||
                                navigator['mozGetUserMedia'];
        }
        window.getUserMedia(func);
      },getMovementX:function (event) {
        return event['movementX'] ||
               event['mozMovementX'] ||
               event['webkitMovementX'] ||
               0;
      },getMovementY:function (event) {
        return event['movementY'] ||
               event['mozMovementY'] ||
               event['webkitMovementY'] ||
               0;
      },getMouseWheelDelta:function (event) {
        return Math.max(-1, Math.min(1, event.type === 'DOMMouseScroll' ? event.detail : -event.wheelDelta));
      },mouseX:0,mouseY:0,mouseMovementX:0,mouseMovementY:0,calculateMouseEvent:function (event) { // event should be mousemove, mousedown or mouseup
        if (Browser.pointerLock) {
          // When the pointer is locked, calculate the coordinates
          // based on the movement of the mouse.
          // Workaround for Firefox bug 764498
          if (event.type != 'mousemove' &&
              ('mozMovementX' in event)) {
            Browser.mouseMovementX = Browser.mouseMovementY = 0;
          } else {
            Browser.mouseMovementX = Browser.getMovementX(event);
            Browser.mouseMovementY = Browser.getMovementY(event);
          }
          
          // check if SDL is available
          if (typeof SDL != "undefined") {
          	Browser.mouseX = SDL.mouseX + Browser.mouseMovementX;
          	Browser.mouseY = SDL.mouseY + Browser.mouseMovementY;
          } else {
          	// just add the mouse delta to the current absolut mouse position
          	// FIXME: ideally this should be clamped against the canvas size and zero
          	Browser.mouseX += Browser.mouseMovementX;
          	Browser.mouseY += Browser.mouseMovementY;
          }        
        } else {
          // Otherwise, calculate the movement based on the changes
          // in the coordinates.
          var rect = Module["canvas"].getBoundingClientRect();
          var x, y;
          
          // Neither .scrollX or .pageXOffset are defined in a spec, but
          // we prefer .scrollX because it is currently in a spec draft.
          // (see: http://www.w3.org/TR/2013/WD-cssom-view-20131217/)
          var scrollX = ((typeof window.scrollX !== 'undefined') ? window.scrollX : window.pageXOffset);
          var scrollY = ((typeof window.scrollY !== 'undefined') ? window.scrollY : window.pageYOffset);
          if (event.type == 'touchstart' ||
              event.type == 'touchend' ||
              event.type == 'touchmove') {
            var t = event.touches.item(0);
            if (t) {
              x = t.pageX - (scrollX + rect.left);
              y = t.pageY - (scrollY + rect.top);
            } else {
              return;
            }
          } else {
            x = event.pageX - (scrollX + rect.left);
            y = event.pageY - (scrollY + rect.top);
          }
  
          // the canvas might be CSS-scaled compared to its backbuffer;
          // SDL-using content will want mouse coordinates in terms
          // of backbuffer units.
          var cw = Module["canvas"].width;
          var ch = Module["canvas"].height;
          x = x * (cw / rect.width);
          y = y * (ch / rect.height);
  
          Browser.mouseMovementX = x - Browser.mouseX;
          Browser.mouseMovementY = y - Browser.mouseY;
          Browser.mouseX = x;
          Browser.mouseY = y;
        }
      },xhrLoad:function (url, onload, onerror) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.responseType = 'arraybuffer';
        xhr.onload = function xhr_onload() {
          if (xhr.status == 200 || (xhr.status == 0 && xhr.response)) { // file URLs can return 0
            onload(xhr.response);
          } else {
            onerror();
          }
        };
        xhr.onerror = onerror;
        xhr.send(null);
      },asyncLoad:function (url, onload, onerror, noRunDep) {
        Browser.xhrLoad(url, function(arrayBuffer) {
          assert(arrayBuffer, 'Loading data file "' + url + '" failed (no arrayBuffer).');
          onload(new Uint8Array(arrayBuffer));
          if (!noRunDep) removeRunDependency('al ' + url);
        }, function(event) {
          if (onerror) {
            onerror();
          } else {
            throw 'Loading data file "' + url + '" failed.';
          }
        });
        if (!noRunDep) addRunDependency('al ' + url);
      },resizeListeners:[],updateResizeListeners:function () {
        var canvas = Module['canvas'];
        Browser.resizeListeners.forEach(function(listener) {
          listener(canvas.width, canvas.height);
        });
      },setCanvasSize:function (width, height, noUpdates) {
        var canvas = Module['canvas'];
        canvas.width = width;
        canvas.height = height;
        if (!noUpdates) Browser.updateResizeListeners();
      },windowedWidth:0,windowedHeight:0,setFullScreenCanvasSize:function () {
        var canvas = Module['canvas'];
        this.windowedWidth = canvas.width;
        this.windowedHeight = canvas.height;
        canvas.width = screen.width;
        canvas.height = screen.height;
        // check if SDL is available   
        if (typeof SDL != "undefined") {
        	var flags = HEAPU32[((SDL.screen+Runtime.QUANTUM_SIZE*0)>>2)];
        	flags = flags | 0x00800000; // set SDL_FULLSCREEN flag
        	HEAP32[((SDL.screen+Runtime.QUANTUM_SIZE*0)>>2)]=flags
        }
        Browser.updateResizeListeners();
      },setWindowedCanvasSize:function () {
        var canvas = Module['canvas'];
        canvas.width = this.windowedWidth;
        canvas.height = this.windowedHeight;
        // check if SDL is available       
        if (typeof SDL != "undefined") {
        	var flags = HEAPU32[((SDL.screen+Runtime.QUANTUM_SIZE*0)>>2)];
        	flags = flags & ~0x00800000; // clear SDL_FULLSCREEN flag
        	HEAP32[((SDL.screen+Runtime.QUANTUM_SIZE*0)>>2)]=flags
        }
        Browser.updateResizeListeners();
      }};
___errno_state = Runtime.staticAlloc(4); HEAP32[((___errno_state)>>2)]=0;
Module["requestFullScreen"] = function Module_requestFullScreen(lockPointer, resizeCanvas) { Browser.requestFullScreen(lockPointer, resizeCanvas) };
  Module["requestAnimationFrame"] = function Module_requestAnimationFrame(func) { Browser.requestAnimationFrame(func) };
  Module["setCanvasSize"] = function Module_setCanvasSize(width, height, noUpdates) { Browser.setCanvasSize(width, height, noUpdates) };
  Module["pauseMainLoop"] = function Module_pauseMainLoop() { Browser.mainLoop.pause() };
  Module["resumeMainLoop"] = function Module_resumeMainLoop() { Browser.mainLoop.resume() };
  Module["getUserMedia"] = function Module_getUserMedia() { Browser.getUserMedia() }
FS.staticInit();__ATINIT__.unshift({ func: function() { if (!Module["noFSInit"] && !FS.init.initialized) FS.init() } });__ATMAIN__.push({ func: function() { FS.ignorePermissions = false } });__ATEXIT__.push({ func: function() { FS.quit() } });Module["FS_createFolder"] = FS.createFolder;Module["FS_createPath"] = FS.createPath;Module["FS_createDataFile"] = FS.createDataFile;Module["FS_createPreloadedFile"] = FS.createPreloadedFile;Module["FS_createLazyFile"] = FS.createLazyFile;Module["FS_createLink"] = FS.createLink;Module["FS_createDevice"] = FS.createDevice;
__ATINIT__.unshift({ func: function() { TTY.init() } });__ATEXIT__.push({ func: function() { TTY.shutdown() } });TTY.utf8 = new Runtime.UTF8Processor();
if (ENVIRONMENT_IS_NODE) { var fs = require("fs"); NODEFS.staticInit(); }
STACK_BASE = STACKTOP = Runtime.alignMemory(STATICTOP);

staticSealed = true; // seal the static portion of memory

STACK_MAX = STACK_BASE + 5242880;

DYNAMIC_BASE = DYNAMICTOP = Runtime.alignMemory(STACK_MAX);

assert(DYNAMIC_BASE < TOTAL_MEMORY, "TOTAL_MEMORY not big enough for stack");


var Math_min = Math.min;
function invoke_ii(index,a1) {
  try {
    return Module["dynCall_ii"](index,a1);
  } catch(e) {
    if (typeof e !== 'number' && e !== 'longjmp') throw e;
    asm["setThrew"](1, 0);
  }
}

function invoke_v(index) {
  try {
    Module["dynCall_v"](index);
  } catch(e) {
    if (typeof e !== 'number' && e !== 'longjmp') throw e;
    asm["setThrew"](1, 0);
  }
}

function invoke_iii(index,a1,a2) {
  try {
    return Module["dynCall_iii"](index,a1,a2);
  } catch(e) {
    if (typeof e !== 'number' && e !== 'longjmp') throw e;
    asm["setThrew"](1, 0);
  }
}

function invoke_vi(index,a1) {
  try {
    Module["dynCall_vi"](index,a1);
  } catch(e) {
    if (typeof e !== 'number' && e !== 'longjmp') throw e;
    asm["setThrew"](1, 0);
  }
}

function asmPrintInt(x, y) {
  Module.print('int ' + x + ',' + y);// + ' ' + new Error().stack);
}
function asmPrintFloat(x, y) {
  Module.print('float ' + x + ',' + y);// + ' ' + new Error().stack);
}
// EMSCRIPTEN_START_ASM
var asm=(function(global,env,buffer){"use asm";var a=new global.Int8Array(buffer);var b=new global.Int16Array(buffer);var c=new global.Int32Array(buffer);var d=new global.Uint8Array(buffer);var e=new global.Uint16Array(buffer);var f=new global.Uint32Array(buffer);var g=new global.Float32Array(buffer);var h=new global.Float64Array(buffer);var i=env.STACKTOP|0;var j=env.STACK_MAX|0;var k=env.tempDoublePtr|0;var l=env.ABORT|0;var m=+env.NaN;var n=+env.Infinity;var o=0;var p=0;var q=0;var r=0;var s=0,t=0,u=0,v=0,w=0.0,x=0,y=0,z=0,A=0.0;var B=0;var C=0;var D=0;var E=0;var F=0;var G=0;var H=0;var I=0;var J=0;var K=0;var L=global.Math.floor;var M=global.Math.abs;var N=global.Math.sqrt;var O=global.Math.pow;var P=global.Math.cos;var Q=global.Math.sin;var R=global.Math.tan;var S=global.Math.acos;var T=global.Math.asin;var U=global.Math.atan;var V=global.Math.atan2;var W=global.Math.exp;var X=global.Math.log;var Y=global.Math.ceil;var Z=global.Math.imul;var _=env.abort;var $=env.assert;var aa=env.asmPrintInt;var ba=env.asmPrintFloat;var ca=env.min;var da=env.invoke_ii;var ea=env.invoke_v;var fa=env.invoke_iii;var ga=env.invoke_vi;var ha=env._sysconf;var ia=env._sbrk;var ja=env._fabs;var ka=env.___setErrNo;var la=env._rint;var ma=env.___errno_location;var na=env._abort;var oa=env._time;var pa=env._emscripten_memcpy_big;var qa=env._fflush;var ra=0.0;
// EMSCRIPTEN_START_FUNCS
function wa(a){a=a|0;var b=0;b=i;i=i+a|0;i=i+7&-8;return b|0}function xa(){return i|0}function ya(a){a=a|0;i=a}function za(a,b){a=a|0;b=b|0;if((o|0)==0){o=a;p=b}}function Aa(b){b=b|0;a[k]=a[b];a[k+1|0]=a[b+1|0];a[k+2|0]=a[b+2|0];a[k+3|0]=a[b+3|0]}function Ba(b){b=b|0;a[k]=a[b];a[k+1|0]=a[b+1|0];a[k+2|0]=a[b+2|0];a[k+3|0]=a[b+3|0];a[k+4|0]=a[b+4|0];a[k+5|0]=a[b+5|0];a[k+6|0]=a[b+6|0];a[k+7|0]=a[b+7|0]}function Ca(a){a=a|0;B=a}function Da(a){a=a|0;C=a}function Ea(a){a=a|0;D=a}function Fa(a){a=a|0;E=a}function Ga(a){a=a|0;F=a}function Ha(a){a=a|0;G=a}function Ia(a){a=a|0;H=a}function Ja(a){a=a|0;I=a}function Ka(a){a=a|0;J=a}function La(a){a=a|0;K=a}function Ma(){}function Na(a,b,d){a=a|0;b=b|0;d=d|0;var e=0,f=0,g=0,h=0;e=(d|0)!=0;if(e){c[d>>2]=0}if((b|0)<1){if(!e){f=0;return f|0}c[d>>2]=11;f=0;return f|0}g=ib(1,64)|0;h=g;if((g|0)==0){if(!e){f=0;return f|0}c[d>>2]=1;f=0;return f|0}c[g+20>>2]=b;c[g+24>>2]=555;do{if((Wa(h,a)|0)!=0){if((cb(h,a)|0)==0){break}if((Ta(h,a)|0)==0){break}if(e){c[d>>2]=10}hb(g);f=0;return f|0}}while(0);if((g|0)==0){f=0;return f|0}d=c[g+40>>2]|0;if((d|0)!=0){va[d&7](h)}c[g+56>>2]=0;c[g+52>>2]=0;jb(g|0,0,20)|0;f=g;return f|0}function Oa(a){a=a|0;var b=0,d=0,e=0;b=a;if((a|0)==0){d=2;return d|0}e=c[b+40>>2]|0;if((e|0)!=0){va[e&7](b)}c[b+56>>2]=0;c[b+52>>2]=0;jb(a|0,0,20)|0;d=0;return d|0}function Pa(a){a=a|0;var b=0;if((a|0)==0){return 0}b=c[a+28>>2]|0;if((b|0)!=0){hb(b)}hb(a);return 0}function Qa(a,b){a=a|0;b=b|0;var d=0,e=0,f=0,g=0,i=0,j=0,k=0,l=0.0,m=0,n=0,o=0,p=0,q=0.0,r=0.0,s=0.0;d=a;if((a|0)==0){e=2;return e|0}f=c[d+32>>2]|0;if((f|0)==0){e=7;return e|0}g=c[d+36>>2]|0;if((g|0)==0){e=7;return e|0}if((c[d+24>>2]|0)!=555){e=18;return e|0}if((b|0)==0){e=3;return e|0}i=c[b>>2]|0;if((i|0)==0){e=4;return e|0}j=c[b+4>>2]|0;if((j|0)==0){e=4;return e|0}k=b+32|0;l=+h[k>>3];if(l<.00390625|l>256.0){e=6;return e|0}m=b+8|0;n=c[m>>2]|0;if((n|0)<0){c[m>>2]=0;o=0}else{o=n}n=b+12|0;m=c[n>>2]|0;if((m|0)<0){c[n>>2]=0;p=0}else{p=m}m=c[d+20>>2]|0;do{if(i>>>0<j>>>0){if((i+((Z(m,o)|0)<<2)|0)>>>0>j>>>0){e=16}else{break}return e|0}else{if((j+((Z(m,p)|0)<<2)|0)>>>0>i>>>0){e=16}else{break}return e|0}}while(0);c[b+16>>2]=0;c[b+20>>2]=0;i=a;q=+h[i>>3];if(q<.00390625){h[i>>3]=l;r=l;s=+h[k>>3]}else{r=q;s=l}if(+M(+(r-s))<1.0e-15){e=ua[g&15](d,b)|0;return e|0}else{e=ua[f&15](d,b)|0;return e|0}return 0}function Ra(a,b){a=a|0;b=+b;var d=0,e=0;d=a;do{if((a|0)==0){e=2}else{if((c[d+32>>2]|0)==0){e=7;break}if((c[d+36>>2]|0)==0){e=7;break}if(b<.00390625|b>256.0){e=6;break}h[a>>3]=b;e=0}}while(0);return e|0}function Sa(a){a=a|0;var b=0;switch(a|0){case 8:{b=100616;break};case 9:{b=100568;break};case 6:{b=99728;break};case 19:{b=100064;break};case 20:{b=100008;break};case 21:{b=99960;break};case 17:{b=100216;break};case 18:{b=100144;break};case 14:{b=100304;break};case 16:{b=100264;break};case 10:{b=100544;break};case 11:{b=100512;break};case 12:{b=100456;break};case 13:{b=100368;break};case 7:{b=99632;break};case 1:{b=100128;break};case 2:{b=99872;break};case 3:{b=99840;break};case 4:{b=99808;break};case 5:{b=99768;break};case 15:{b=99672;break};case 22:{b=99904;break};case 0:{b=100656;break};default:{b=0}}return b|0}function Ta(a,b){a=a|0;b=b|0;var d=0,e=0,f=0,g=0;if((b|0)!=4){d=10;return d|0}b=a+28|0;e=c[b>>2]|0;if((e|0)!=0){hb(e);c[b>>2]=0}e=c[a+20>>2]|0;f=e<<2;g=ib(1,f+32|0)|0;if((g|0)==0){d=1;return d|0}c[b>>2]=g;c[g>>2]=126338300;c[a+36>>2]=10;c[a+32>>2]=10;c[a+40>>2]=4;c[g+4>>2]=e;c[g+8>>2]=1;jb(g+28|0,0,f|0)|0;d=0;return d|0}function Ua(a,b){a=a|0;b=b|0;var d=0,e=0,f=0,i=0,j=0,k=0,l=0,m=0,n=0,o=0,p=0,q=0,r=0,s=0.0,t=0.0,u=0,v=0,w=0.0,x=0.0,y=0,z=0.0,A=0.0,B=0.0,C=0.0,D=0,E=0,F=0,G=0,H=0,I=0,J=0,K=0.0,L=0.0,N=0.0,O=0,P=0.0,Q=0,R=0.0,S=0.0,T=0,U=0,V=0;d=c[b+8>>2]|0;if((d|0)<1){e=0;return e|0}f=c[a+28>>2]|0;if((f|0)==0){e=5;return e|0}i=f+8|0;j=f+4|0;k=c[j>>2]|0;if((c[i>>2]|0)==0){l=k}else{if((k|0)>0){m=c[b>>2]|0;n=f+28|0;o=0;while(1){g[n+(o<<2)>>2]=+g[m+(o<<2)>>2];p=o+1|0;q=c[j>>2]|0;if((p|0)<(q|0)){o=p}else{r=q;break}}}else{r=k}c[i>>2]=0;l=r}r=f+4|0;i=Z(l,d)|0;d=f+12|0;c[d>>2]=i;k=Z(l,c[b+12>>2]|0)|0;o=f+20|0;c[o>>2]=k;j=f+24|0;c[j>>2]=0;m=f+16|0;c[m>>2]=0;n=a|0;s=+h[n>>3];q=a+8|0;t=+h[q>>3];a:do{if(t<1.0){a=b+32|0;p=f+28|0;u=b|0;v=b+4|0;w=s;x=t;y=0;while(1){if((y|0)>=(k|0)){z=w;A=x;break a}if(!((x+1.0)*+(l|0)+0.0<+(i|0))){z=w;A=x;break a}do{if((k|0)>0){B=+h[a>>3];if(!(+M(+(s-B))>1.0e-20)){C=w;break}C=s+ +(y|0)*(B-s)/+(k|0)}else{C=w}}while(0);if((l|0)>0){D=c[u>>2]|0;E=c[v>>2]|0;F=0;G=y;while(1){B=+g[p+(F<<2)>>2];g[E+(G<<2)>>2]=B+x*(+g[D+(F<<2)>>2]-B);H=G+1|0;c[j>>2]=H;I=F+1|0;if((I|0)<(l|0)){F=I;G=H}else{J=H;break}}}else{J=y}B=x+1.0/C;if(B<1.0){w=C;x=B;y=J}else{z=C;A=B;break}}}else{z=s;A=t}}while(0);t=A- +(la(+A)|0);if(t<0.0){K=t+1.0}else{K=t}J=c[r>>2]|0;l=Z(la(+(A-K))|0,J)|0;J=(c[m>>2]|0)+l|0;c[m>>2]=J;l=c[j>>2]|0;k=c[o>>2]|0;b:do{if((l|0)<(k|0)){i=b+32|0;y=b|0;p=b+4|0;v=J;A=z;t=K;u=l;a=k;while(1){G=c[r>>2]|0;if(!(+(v|0)+t*+(G|0)<+(c[d>>2]|0))){L=A;N=t;O=v;break b}do{if((a|0)>0){s=+h[n>>3];C=+h[i>>3];if(!(+M(+(s-C))>1.0e-20)){P=A;break}P=s+ +(u|0)*(C-s)/+(a|0)}else{P=A}}while(0);if((G|0)>0){F=c[y>>2]|0;D=c[p>>2]|0;E=(G|0)>1?G:1;H=0;I=u;while(1){s=+g[F+(H-G+v<<2)>>2];g[D+(I<<2)>>2]=s+t*(+g[F+(v+H<<2)>>2]-s);Q=H+1|0;if((Q|0)<(G|0)){H=Q;I=I+1|0}else{break}}c[j>>2]=u+E}s=t+1.0/P;C=s- +(la(+s)|0);if(C<0.0){R=C+1.0}else{R=C}I=c[r>>2]|0;H=Z(la(+(s-R))|0,I)|0;I=(c[m>>2]|0)+H|0;c[m>>2]=I;H=c[j>>2]|0;G=c[o>>2]|0;if((H|0)<(G|0)){v=I;A=P;t=R;u=H;a=G}else{L=P;N=R;O=I;break}}}else{L=z;N=K;O=J}}while(0);J=c[d>>2]|0;if((O|0)>(J|0)){K=N+ +((O-J|0)/(c[r>>2]|0)|0|0);c[m>>2]=J;S=K;T=J}else{S=N;T=O}h[q>>3]=S;q=c[r>>2]|0;if((T|0)>0&(q|0)>0){O=c[b>>2]|0;J=f+28|0;f=0;d=q;o=T;while(1){g[J+(f<<2)>>2]=+g[O+(f-d+o<<2)>>2];k=f+1|0;l=c[r>>2]|0;a=c[m>>2]|0;if((k|0)<(l|0)){f=k;d=l;o=a}else{U=a;V=l;break}}}else{U=T;V=q}h[n>>3]=L;c[b+16>>2]=(U|0)/(V|0)|0;c[b+20>>2]=(c[j>>2]|0)/(V|0)|0;e=0;return e|0}function Va(a){a=a|0;var b=0,d=0;b=c[a+28>>2]|0;if((b|0)==0){return}d=c[a+20>>2]|0;c[b+4>>2]=d;c[b+8>>2]=1;jb(b+28|0,0,d<<2|0)|0;return}function Wa(a,b){a=a|0;b=b|0;var d=0,e=0,f=0,g=0,h=0,j=0,k=0,l=0,m=0,n=0,o=0,p=0,q=0,r=0,s=0;d=i;i=i+2088|0;e=d+16|0;f=a+28|0;g=c[f>>2]|0;if((g|0)!=0){hb(g);c[f>>2]=0}g=d|0;jb(g|0,0,16)|0;h=e;jb(h|0,0,12)|0;j=d+32|0;jb(j|0,0,2052)|0;k=c[a+20>>2]|0;if((k|0)>128){l=11;i=d;return l|0}do{if((k|0)==4){c[a+36>>2]=12;c[a+32>>2]=12}else if((k|0)==1){c[a+36>>2]=4;c[a+32>>2]=4}else if((k|0)==2){c[a+36>>2]=2;c[a+32>>2]=2}else{m=a+36|0;if((k|0)==6){c[m>>2]=14;c[a+32>>2]=14;break}else{c[m>>2]=8;c[a+32>>2]=8;break}}}while(0);c[a+40>>2]=6;if((b|0)==1){n=22437;o=491;p=12}else if((b|0)==2){n=2463;o=128;p=89772}else{l=10;i=d;return l|0}b=la(+(+(n|0)*2.5/+(o|0)*256.0))|0;a=Z((b|0)>4096?b:4096,k)|0;b=ib(1,(a+k<<2)+2120|0)|0;if((b|0)==0){l=1;i=d;return l|0}c[b>>2]=40521808;c[b+4>>2]=k;m=b+8|0;c[m>>2]=c[g>>2];c[m+4>>2]=c[g+4>>2];c[m+8>>2]=c[g+8>>2];c[m+12>>2]=c[g+12>>2];m=b+24|0;c[m>>2]=n;c[b+28>>2]=o;c[b+48>>2]=p;p=b+52|0;o=e;e=p;n=c[o+4>>2]|0;c[e>>2]=c[o>>2];c[e+4>>2]=n;c[b+64>>2]=a;kb(b+68|0,j|0,2052)|0;jb(g|0,-18|0,16)|0;jb(h|0,-18|0,12)|0;jb(j|0,-18|0,2052)|0;c[f>>2]=b;c[b+56>>2]=0;c[p>>2]=0;c[b+60>>2]=-1;p=b+2120|0;jb(b+32|0,0,16)|0;jb(p|0,0,a<<2|0)|0;jb(p+(a<<2)|0,-86|0,k<<2|0)|0;k=c[m>>2]|0;if((k|0)>1){q=k;r=0;s=1}else{l=0;i=d;return l|0}while(1){k=s|q;m=r+1|0;a=1<<m;if((a|0)<(k|0)){q=k;r=m;s=a}else{break}}l=(r+12|0)>31?9:0;i=d;return l|0}function Xa(a,b){a=a|0;b=b|0;var d=0,e=0,f=0,i=0,j=0,l=0,m=0,n=0,o=0,p=0.0,q=0,r=0,s=0.0,t=0,u=0.0,v=0.0,w=0.0,x=0,y=0.0,z=0,A=0,B=0,C=0,D=0,E=0,F=0,G=0,H=0,I=0,J=0,K=0,L=0,N=0,O=0.0,P=0.0,Q=0.0,R=0.0,S=0.0,T=0,U=0,V=0,W=0,X=0,Y=0,_=0.0,$=0.0,aa=0.0,ba=0,ca=0.0,da=0.0;d=c[a+28>>2]|0;if((d|0)==0){e=5;return e|0}f=d;i=d+4|0;j=c[i>>2]|0;c[d+8>>2]=Z(j,c[b+8>>2]|0)|0;l=d+16|0;c[l>>2]=Z(j,c[b+12>>2]|0)|0;m=d+20|0;c[m>>2]=0;n=d+12|0;c[n>>2]=0;o=a|0;p=+h[o>>3];q=d+24|0;r=d+28|0;s=(+(c[q>>2]|0)+2.0)/+(c[r>>2]|0);t=b+32|0;u=+h[t>>3];v=p<u?p:u;if(v<1.0){w=s/v}else{w=s}x=Z((la(+w)|0)+1|0,j)|0;j=a+8|0;w=+h[j>>3];s=w- +(la(+w)|0);if(s<0.0){y=s+1.0}else{y=s}z=d+52|0;A=c[z>>2]|0;B=c[i>>2]|0;C=(Z(la(+(w-y))|0,B)|0)+A|0;A=d+64|0;B=c[A>>2]|0;D=(C|0)%(B|0)|0;c[z>>2]=D;w=1.0/p+1.0e-20;a:do{if((c[m>>2]|0)<(c[l>>2]|0)){C=d+56|0;E=d+60|0;F=d+48|0;G=b+4|0;H=a+16|0;s=p;v=y;I=D;J=B;while(1){if((((c[C>>2]|0)-I+J|0)%(J|0)|0|0)>(x|0)){K=I}else{L=bb(f,b,x)|0;c[H>>2]=L;if((L|0)!=0){e=L;break}L=c[z>>2]|0;N=c[A>>2]|0;if((((c[C>>2]|0)-L+N|0)%(N|0)|0|0)>(x|0)){K=L}else{O=s;P=v;break a}}L=c[E>>2]|0;if((L|0)>-1){if(!(w+(v+ +(K|0))<+(L|0))){O=s;P=v;break a}}L=c[l>>2]|0;do{if((L|0)>0){u=+h[o>>3];Q=+h[t>>3];if(!(+M(+(u-Q))>1.0e-10)){R=s;break}R=u+(Q-u)*+(c[m>>2]|0)/+(L|0)}else{R=s}}while(0);u=+(c[r>>2]|0);if(R<1.0){S=R*u}else{S=u}L=la(+(S*4096.0))|0;N=la(+(v*S*4096.0))|0;u=+(c[r>>2]|0);T=c[q>>2]<<12;U=(T-N|0)/(L|0)|0;V=(Z(U,L)|0)+N|0;W=c[z>>2]|0;X=c[F>>2]|0;Y=W-U|0;U=V;Q=0.0;while(1){V=U>>12;_=+g[X+(V<<2)>>2];$=_+ +(U&4095|0)*.000244140625*(+g[X+(V+1<<2)>>2]-_);aa=Q+(Aa(f+2120+(Y<<2)|0),+g[k>>2])*$;V=U-L|0;if((V|0)>-1){Y=Y+1|0;U=V;Q=aa}else{break}}U=L-N|0;Y=(T-U|0)/(L|0)|0;V=W+1+Y|0;ba=(Z(Y,L)|0)+U|0;Q=0.0;while(1){U=ba>>12;$=+g[X+(U<<2)>>2];_=$+ +(ba&4095|0)*.000244140625*(+g[X+(U+1<<2)>>2]-$);ca=Q+(Aa(f+2120+(V<<2)|0),+g[k>>2])*_;U=ba-L|0;if((U|0)>0){V=V-1|0;ba=U;Q=ca}else{break}}ba=c[m>>2]|0;g[(c[G>>2]|0)+(ba<<2)>>2]=S/u*(aa+ca);c[m>>2]=ba+1;Q=v+1.0/R;_=Q- +(la(+Q)|0);if(_<0.0){da=_+1.0}else{da=_}ba=c[z>>2]|0;V=c[i>>2]|0;L=(Z(la(+(Q-da))|0,V)|0)+ba|0;ba=c[A>>2]|0;V=(L|0)%(ba|0)|0;c[z>>2]=V;if((c[m>>2]|0)<(c[l>>2]|0)){s=R;v=da;I=V;J=ba}else{O=R;P=da;break a}}return e|0}else{O=p;P=y}}while(0);h[j>>3]=P;h[o>>3]=O;o=c[i>>2]|0;c[b+16>>2]=(c[n>>2]|0)/(o|0)|0;c[b+20>>2]=(c[m>>2]|0)/(o|0)|0;e=0;return e|0}function Ya(a,b){a=a|0;b=b|0;var d=0,e=0,f=0,i=0,j=0,l=0,m=0,n=0,o=0,p=0.0,q=0,r=0,s=0.0,t=0,u=0.0,v=0.0,w=0.0,x=0,y=0.0,z=0,A=0,B=0,C=0,D=0,E=0,F=0,G=0,H=0,I=0,J=0,K=0,L=0,N=0,O=0.0,P=0.0,Q=0.0,R=0.0,S=0.0,T=0,U=0,V=0,W=0,X=0,Y=0,_=0,$=0,aa=0,ba=0.0,ca=0.0,da=0.0,ea=0.0,fa=0.0,ga=0,ha=0.0,ia=0.0,ja=0.0,ka=0.0;d=c[a+28>>2]|0;if((d|0)==0){e=5;return e|0}f=d;i=d+4|0;j=c[i>>2]|0;c[d+8>>2]=Z(j,c[b+8>>2]|0)|0;l=d+16|0;c[l>>2]=Z(j,c[b+12>>2]|0)|0;m=d+20|0;c[m>>2]=0;n=d+12|0;c[n>>2]=0;o=a|0;p=+h[o>>3];q=d+24|0;r=d+28|0;s=(+(c[q>>2]|0)+2.0)/+(c[r>>2]|0);t=b+32|0;u=+h[t>>3];v=p<u?p:u;if(v<1.0){w=s/v}else{w=s}x=Z((la(+w)|0)+1|0,j)|0;j=a+8|0;w=+h[j>>3];s=w- +(la(+w)|0);if(s<0.0){y=s+1.0}else{y=s}z=d+52|0;A=c[z>>2]|0;B=c[i>>2]|0;C=(Z(la(+(w-y))|0,B)|0)+A|0;A=d+64|0;B=c[A>>2]|0;D=(C|0)%(B|0)|0;c[z>>2]=D;w=1.0/p+1.0e-20;a:do{if((c[m>>2]|0)<(c[l>>2]|0)){C=d+56|0;E=d+60|0;F=b+4|0;G=d+48|0;H=a+16|0;s=p;v=y;I=D;J=B;while(1){if((((c[C>>2]|0)-I+J|0)%(J|0)|0|0)>(x|0)){K=I}else{L=bb(f,b,x)|0;c[H>>2]=L;if((L|0)!=0){e=L;break}L=c[z>>2]|0;N=c[A>>2]|0;if((((c[C>>2]|0)-L+N|0)%(N|0)|0|0)>(x|0)){K=L}else{O=s;P=v;break a}}L=c[E>>2]|0;if((L|0)>-1){if(!(w+(v+ +(K|0))<+(L|0))){O=s;P=v;break a}}L=c[l>>2]|0;do{if((L|0)>0){u=+h[o>>3];Q=+h[t>>3];if(!(+M(+(u-Q))>1.0e-10)){R=s;break}R=u+(Q-u)*+(c[m>>2]|0)/+(L|0)}else{R=s}}while(0);u=+(c[r>>2]|0);if(R<1.0){S=R*u}else{S=u}L=la(+(S*4096.0))|0;N=la(+(v*S*4096.0))|0;u=+(c[r>>2]|0);T=c[F>>2]|0;U=c[m>>2]|0;V=c[q>>2]<<12;W=(V-N|0)/(L|0)|0;X=(Z(W,L)|0)+N|0;Y=c[z>>2]|0;_=c[i>>2]|0;$=Y-(Z(_,W)|0)|0;W=c[G>>2]|0;aa=$;$=X;Q=0.0;ba=0.0;while(1){X=$>>12;ca=+g[W+(X<<2)>>2];da=ca+ +($&4095|0)*.000244140625*(+g[W+(X+1<<2)>>2]-ca);ea=Q+(Aa(f+2120+(aa<<2)|0),+g[k>>2])*da;fa=ba+(Aa(f+2120+(aa+1<<2)|0),+g[k>>2])*da;X=$-L|0;if((X|0)>-1){aa=aa+2|0;$=X;Q=ea;ba=fa}else{break}}ba=S/u;$=L-N|0;aa=(V-$|0)/(L|0)|0;X=(Z(aa+1|0,_)|0)+Y|0;ga=(Z(aa,L)|0)+$|0;Q=0.0;da=0.0;while(1){$=ga>>12;ca=+g[W+($<<2)>>2];ha=ca+ +(ga&4095|0)*.000244140625*(+g[W+($+1<<2)>>2]-ca);ia=Q+(Aa(f+2120+(X<<2)|0),+g[k>>2])*ha;ja=da+(Aa(f+2120+(X+1<<2)|0),+g[k>>2])*ha;$=ga-L|0;if(($|0)>0){X=X-2|0;ga=$;Q=ia;da=ja}else{break}}g[T+(U<<2)>>2]=ba*(ea+ia);g[T+(U+1<<2)>>2]=ba*(fa+ja);c[m>>2]=U+2;da=v+1.0/R;Q=da- +(la(+da)|0);if(Q<0.0){ka=Q+1.0}else{ka=Q}ga=c[z>>2]|0;X=c[i>>2]|0;L=(Z(la(+(da-ka))|0,X)|0)+ga|0;ga=c[A>>2]|0;X=(L|0)%(ga|0)|0;c[z>>2]=X;if((c[m>>2]|0)<(c[l>>2]|0)){s=R;v=ka;I=X;J=ga}else{O=R;P=ka;break a}}return e|0}else{O=p;P=y}}while(0);h[j>>3]=P;h[o>>3]=O;o=c[i>>2]|0;c[b+16>>2]=(c[n>>2]|0)/(o|0)|0;c[b+20>>2]=(c[m>>2]|0)/(o|0)|0;e=0;return e|0}function Za(a,b){a=a|0;b=b|0;var d=0,e=0,f=0,i=0,j=0,l=0,m=0,n=0,o=0,p=0.0,q=0,r=0,s=0.0,t=0,u=0.0,v=0.0,w=0.0,x=0,y=0.0,z=0,A=0,B=0,C=0,D=0,E=0,F=0,G=0,H=0,I=0,J=0,K=0,L=0,N=0,O=0.0,P=0.0,Q=0.0,R=0.0,S=0.0,T=0,U=0,V=0,W=0,X=0,Y=0,_=0,$=0,aa=0,ba=0.0,ca=0.0,da=0.0,ea=0.0,fa=0.0,ga=0.0,ha=0.0,ia=0.0,ja=0.0,ka=0,ma=0.0,na=0.0,oa=0.0,pa=0.0,qa=0.0,ra=0.0;d=c[a+28>>2]|0;if((d|0)==0){e=5;return e|0}f=d;i=d+4|0;j=c[i>>2]|0;c[d+8>>2]=Z(j,c[b+8>>2]|0)|0;l=d+16|0;c[l>>2]=Z(j,c[b+12>>2]|0)|0;m=d+20|0;c[m>>2]=0;n=d+12|0;c[n>>2]=0;o=a|0;p=+h[o>>3];q=d+24|0;r=d+28|0;s=(+(c[q>>2]|0)+2.0)/+(c[r>>2]|0);t=b+32|0;u=+h[t>>3];v=p<u?p:u;if(v<1.0){w=s/v}else{w=s}x=Z((la(+w)|0)+1|0,j)|0;j=a+8|0;w=+h[j>>3];s=w- +(la(+w)|0);if(s<0.0){y=s+1.0}else{y=s}z=d+52|0;A=c[z>>2]|0;B=c[i>>2]|0;C=(Z(la(+(w-y))|0,B)|0)+A|0;A=d+64|0;B=c[A>>2]|0;D=(C|0)%(B|0)|0;c[z>>2]=D;w=1.0/p+1.0e-20;a:do{if((c[m>>2]|0)<(c[l>>2]|0)){C=d+56|0;E=d+60|0;F=b+4|0;G=d+48|0;H=a+16|0;s=p;v=y;I=D;J=B;while(1){if((((c[C>>2]|0)-I+J|0)%(J|0)|0|0)>(x|0)){K=I}else{L=bb(f,b,x)|0;c[H>>2]=L;if((L|0)!=0){e=L;break}L=c[z>>2]|0;N=c[A>>2]|0;if((((c[C>>2]|0)-L+N|0)%(N|0)|0|0)>(x|0)){K=L}else{O=s;P=v;break a}}L=c[E>>2]|0;if((L|0)>-1){if(!(w+(v+ +(K|0))<+(L|0))){O=s;P=v;break a}}L=c[l>>2]|0;do{if((L|0)>0){u=+h[o>>3];Q=+h[t>>3];if(!(+M(+(u-Q))>1.0e-10)){R=s;break}R=u+(Q-u)*+(c[m>>2]|0)/+(L|0)}else{R=s}}while(0);u=+(c[r>>2]|0);if(R<1.0){S=R*u}else{S=u}L=la(+(S*4096.0))|0;N=la(+(v*S*4096.0))|0;u=+(c[r>>2]|0);T=c[F>>2]|0;U=c[m>>2]|0;V=c[q>>2]<<12;W=(V-N|0)/(L|0)|0;X=(Z(W,L)|0)+N|0;Y=c[z>>2]|0;_=c[i>>2]|0;$=Y-(Z(_,W)|0)|0;W=c[G>>2]|0;aa=$;$=X;Q=0.0;ba=0.0;ca=0.0;da=0.0;while(1){X=$>>12;ea=+g[W+(X<<2)>>2];fa=ea+ +($&4095|0)*.000244140625*(+g[W+(X+1<<2)>>2]-ea);ga=Q+(Aa(f+2120+(aa<<2)|0),+g[k>>2])*fa;ha=ba+(Aa(f+2120+(aa+1<<2)|0),+g[k>>2])*fa;ia=ca+fa*(Aa(f+2120+(aa+2<<2)|0),+g[k>>2]);ja=da+fa*(Aa(f+2120+(aa+3<<2)|0),+g[k>>2]);X=$-L|0;if((X|0)>-1){aa=aa+4|0;$=X;Q=ga;ba=ha;ca=ia;da=ja}else{break}}da=S/u;$=L-N|0;aa=(V-$|0)/(L|0)|0;X=(Z(aa+1|0,_)|0)+Y|0;ka=(Z(aa,L)|0)+$|0;ca=0.0;ba=0.0;Q=0.0;fa=0.0;while(1){$=ka>>12;ea=+g[W+($<<2)>>2];ma=ea+ +(ka&4095|0)*.000244140625*(+g[W+($+1<<2)>>2]-ea);na=ca+(Aa(f+2120+(X<<2)|0),+g[k>>2])*ma;oa=ba+(Aa(f+2120+(X+1<<2)|0),+g[k>>2])*ma;pa=Q+ma*(Aa(f+2120+(X+2<<2)|0),+g[k>>2]);qa=fa+ma*(Aa(f+2120+(X+3<<2)|0),+g[k>>2]);$=ka-L|0;if(($|0)>0){X=X-4|0;ka=$;ca=na;ba=oa;Q=pa;fa=qa}else{break}}g[T+(U<<2)>>2]=da*(ga+na);g[T+(U+1<<2)>>2]=da*(ha+oa);g[T+(U+2<<2)>>2]=da*(ia+pa);g[T+(U+3<<2)>>2]=da*(ja+qa);c[m>>2]=U+4;fa=v+1.0/R;Q=fa- +(la(+fa)|0);if(Q<0.0){ra=Q+1.0}else{ra=Q}ka=c[z>>2]|0;X=c[i>>2]|0;L=(Z(la(+(fa-ra))|0,X)|0)+ka|0;ka=c[A>>2]|0;X=(L|0)%(ka|0)|0;c[z>>2]=X;if((c[m>>2]|0)<(c[l>>2]|0)){s=R;v=ra;I=X;J=ka}else{O=R;P=ra;break a}}return e|0}else{O=p;P=y}}while(0);h[j>>3]=P;h[o>>3]=O;o=c[i>>2]|0;c[b+16>>2]=(c[n>>2]|0)/(o|0)|0;c[b+20>>2]=(c[m>>2]|0)/(o|0)|0;e=0;return e|0}function _a(a,b){a=a|0;b=b|0;var d=0,e=0,f=0,i=0,j=0,l=0,m=0,n=0,o=0,p=0.0,q=0,r=0,s=0.0,t=0,u=0.0,v=0.0,w=0.0,x=0,y=0.0,z=0,A=0,B=0,C=0,D=0,E=0,F=0,G=0,H=0,I=0,J=0,K=0,L=0,N=0,O=0.0,P=0.0,Q=0.0,R=0.0,S=0.0,T=0,U=0,V=0,W=0,X=0,Y=0,_=0,$=0,aa=0,ba=0.0,ca=0.0,da=0.0,ea=0.0,fa=0.0,ga=0.0,ha=0.0,ia=0.0,ja=0.0,ka=0.0,ma=0.0,na=0.0,oa=0.0,pa=0,qa=0.0,ra=0.0,sa=0.0,ta=0.0,ua=0.0,va=0.0,wa=0.0,xa=0.0;d=c[a+28>>2]|0;if((d|0)==0){e=5;return e|0}f=d;i=d+4|0;j=c[i>>2]|0;c[d+8>>2]=Z(j,c[b+8>>2]|0)|0;l=d+16|0;c[l>>2]=Z(j,c[b+12>>2]|0)|0;m=d+20|0;c[m>>2]=0;n=d+12|0;c[n>>2]=0;o=a|0;p=+h[o>>3];q=d+24|0;r=d+28|0;s=(+(c[q>>2]|0)+2.0)/+(c[r>>2]|0);t=b+32|0;u=+h[t>>3];v=p<u?p:u;if(v<1.0){w=s/v}else{w=s}x=Z((la(+w)|0)+1|0,j)|0;j=a+8|0;w=+h[j>>3];s=w- +(la(+w)|0);if(s<0.0){y=s+1.0}else{y=s}z=d+52|0;A=c[z>>2]|0;B=c[i>>2]|0;C=(Z(la(+(w-y))|0,B)|0)+A|0;A=d+64|0;B=c[A>>2]|0;D=(C|0)%(B|0)|0;c[z>>2]=D;w=1.0/p+1.0e-20;a:do{if((c[m>>2]|0)<(c[l>>2]|0)){C=d+56|0;E=d+60|0;F=b+4|0;G=d+48|0;H=a+16|0;s=p;v=y;I=D;J=B;while(1){if((((c[C>>2]|0)-I+J|0)%(J|0)|0|0)>(x|0)){K=I}else{L=bb(f,b,x)|0;c[H>>2]=L;if((L|0)!=0){e=L;break}L=c[z>>2]|0;N=c[A>>2]|0;if((((c[C>>2]|0)-L+N|0)%(N|0)|0|0)>(x|0)){K=L}else{O=s;P=v;break a}}L=c[E>>2]|0;if((L|0)>-1){if(!(w+(v+ +(K|0))<+(L|0))){O=s;P=v;break a}}L=c[l>>2]|0;do{if((L|0)>0){u=+h[o>>3];Q=+h[t>>3];if(!(+M(+(u-Q))>1.0e-10)){R=s;break}R=u+(Q-u)*+(c[m>>2]|0)/+(L|0)}else{R=s}}while(0);u=+(c[r>>2]|0);if(R<1.0){S=R*u}else{S=u}L=la(+(S*4096.0))|0;N=la(+(v*S*4096.0))|0;u=+(c[r>>2]|0);T=c[F>>2]|0;U=c[m>>2]|0;V=c[q>>2]<<12;W=(V-N|0)/(L|0)|0;X=(Z(W,L)|0)+N|0;Y=c[z>>2]|0;_=c[i>>2]|0;$=Y-(Z(_,W)|0)|0;W=c[G>>2]|0;aa=$;$=X;Q=0.0;ba=0.0;ca=0.0;da=0.0;ea=0.0;fa=0.0;while(1){X=$>>12;ga=+g[W+(X<<2)>>2];ha=ga+ +($&4095|0)*.000244140625*(+g[W+(X+1<<2)>>2]-ga);ia=Q+(Aa(f+2120+(aa<<2)|0),+g[k>>2])*ha;ja=ba+(Aa(f+2120+(aa+1<<2)|0),+g[k>>2])*ha;ka=ca+ha*(Aa(f+2120+(aa+2<<2)|0),+g[k>>2]);ma=da+ha*(Aa(f+2120+(aa+3<<2)|0),+g[k>>2]);na=ea+ha*(Aa(f+2120+(aa+4<<2)|0),+g[k>>2]);oa=fa+ha*(Aa(f+2120+(aa+5<<2)|0),+g[k>>2]);X=$-L|0;if((X|0)>-1){aa=aa+6|0;$=X;Q=ia;ba=ja;ca=ka;da=ma;ea=na;fa=oa}else{break}}fa=S/u;$=L-N|0;aa=(V-$|0)/(L|0)|0;X=(Z(aa+1|0,_)|0)+Y|0;pa=(Z(aa,L)|0)+$|0;ea=0.0;da=0.0;ca=0.0;ba=0.0;Q=0.0;ha=0.0;while(1){$=pa>>12;ga=+g[W+($<<2)>>2];qa=ga+ +(pa&4095|0)*.000244140625*(+g[W+($+1<<2)>>2]-ga);ra=ea+(Aa(f+2120+(X<<2)|0),+g[k>>2])*qa;sa=da+(Aa(f+2120+(X+1<<2)|0),+g[k>>2])*qa;ta=ca+qa*(Aa(f+2120+(X+2<<2)|0),+g[k>>2]);ua=ba+qa*(Aa(f+2120+(X+3<<2)|0),+g[k>>2]);va=Q+qa*(Aa(f+2120+(X+4<<2)|0),+g[k>>2]);wa=ha+qa*(Aa(f+2120+(X+5<<2)|0),+g[k>>2]);$=pa-L|0;if(($|0)>0){X=X-6|0;pa=$;ea=ra;da=sa;ca=ta;ba=ua;Q=va;ha=wa}else{break}}g[T+(U<<2)>>2]=fa*(ia+ra);g[T+(U+1<<2)>>2]=fa*(ja+sa);g[T+(U+2<<2)>>2]=fa*(ka+ta);g[T+(U+3<<2)>>2]=fa*(ma+ua);g[T+(U+4<<2)>>2]=fa*(na+va);g[T+(U+5<<2)>>2]=fa*(oa+wa);c[m>>2]=U+6;ha=v+1.0/R;Q=ha- +(la(+ha)|0);if(Q<0.0){xa=Q+1.0}else{xa=Q}pa=c[z>>2]|0;X=c[i>>2]|0;L=(Z(la(+(ha-xa))|0,X)|0)+pa|0;pa=c[A>>2]|0;X=(L|0)%(pa|0)|0;c[z>>2]=X;if((c[m>>2]|0)<(c[l>>2]|0)){s=R;v=xa;I=X;J=pa}else{O=R;P=xa;break a}}return e|0}else{O=p;P=y}}while(0);h[j>>3]=P;h[o>>3]=O;o=c[i>>2]|0;c[b+16>>2]=(c[n>>2]|0)/(o|0)|0;c[b+20>>2]=(c[m>>2]|0)/(o|0)|0;e=0;return e|0}function $a(a,b){a=a|0;b=b|0;var d=0,e=0,f=0,i=0,j=0,l=0,m=0,n=0,o=0,p=0.0,q=0,r=0,s=0.0,t=0,u=0.0,v=0.0,w=0.0,x=0,y=0.0,z=0,A=0,B=0,C=0,D=0,E=0,F=0,G=0,H=0,I=0,J=0,K=0,L=0,N=0,O=0,P=0,Q=0,R=0.0,S=0.0,T=0.0,U=0.0,V=0.0,W=0,X=0,Y=0,_=0,$=0,aa=0,ba=0,ca=0,da=0,ea=0.0,fa=0,ga=0,ha=0,ia=0,ja=0,ka=0,ma=0,na=0,oa=0,pa=0,qa=0,ra=0,sa=0,ta=0,ua=0,va=0,wa=0,xa=0,ya=0,za=0,Ba=0,Ca=0,Da=0,Ea=0.0;d=c[a+28>>2]|0;if((d|0)==0){e=5;return e|0}f=d;i=d+4|0;j=c[i>>2]|0;c[d+8>>2]=Z(j,c[b+8>>2]|0)|0;l=d+16|0;c[l>>2]=Z(j,c[b+12>>2]|0)|0;m=d+20|0;c[m>>2]=0;n=d+12|0;c[n>>2]=0;o=a|0;p=+h[o>>3];q=d+24|0;r=d+28|0;s=(+(c[q>>2]|0)+2.0)/+(c[r>>2]|0);t=b+32|0;u=+h[t>>3];v=p<u?p:u;if(v<1.0){w=s/v}else{w=s}x=Z((la(+w)|0)+1|0,j)|0;j=a+8|0;w=+h[j>>3];s=w- +(la(+w)|0);if(s<0.0){y=s+1.0}else{y=s}z=d+52|0;A=c[z>>2]|0;B=c[i>>2]|0;C=(Z(la(+(w-y))|0,B)|0)+A|0;A=d+64|0;B=c[A>>2]|0;D=(C|0)%(B|0)|0;c[z>>2]=D;w=1.0/p+1.0e-20;a:do{if((c[m>>2]|0)<(c[l>>2]|0)){C=d+56|0;E=d+60|0;F=b+4|0;G=d+72|0;H=d+48|0;I=d+1096|0;J=a+20|0;K=a+16|0;s=p;v=y;L=D;N=B;while(1){if((((c[C>>2]|0)-L+N|0)%(N|0)|0|0)>(x|0)){O=L}else{P=bb(f,b,x)|0;c[K>>2]=P;if((P|0)!=0){e=P;break}P=c[z>>2]|0;Q=c[A>>2]|0;if((((c[C>>2]|0)-P+Q|0)%(Q|0)|0|0)>(x|0)){O=P}else{R=s;S=v;break a}}P=c[E>>2]|0;if((P|0)>-1){if(!(w+(v+ +(O|0))<+(P|0))){R=s;S=v;break a}}P=c[l>>2]|0;do{if((P|0)>0){u=+h[o>>3];T=+h[t>>3];if(!(+M(+(u-T))>1.0e-10)){U=s;break}U=u+(T-u)*+(c[m>>2]|0)/+(P|0)}else{U=s}}while(0);u=+(c[r>>2]|0);if(U<1.0){V=U*u}else{V=u}P=la(+(V*4096.0))|0;Q=la(+(v*V*4096.0))|0;W=c[i>>2]|0;u=+(c[r>>2]|0);X=c[F>>2]|0;Y=c[m>>2]|0;_=c[q>>2]<<12;$=(_-Q|0)/(P|0)|0;aa=(Z($,P)|0)+Q|0;ba=(c[z>>2]|0)-(Z($,W)|0)|0;$=W<<3;jb(G|0,0,$|0)|0;ca=ba;ba=aa;while(1){aa=ba>>12;da=c[H>>2]|0;T=+g[da+(aa<<2)>>2];ea=T+ +(ba&4095|0)*.000244140625*(+g[da+(aa+1<<2)>>2]-T);aa=W;do{switch((aa|0)%8|0|0){case 7:{fa=aa;ga=22;break};case 6:{ha=aa;ga=23;break};case 5:{ia=aa;ga=24;break};case 4:{ja=aa;ga=25;break};case 3:{ka=aa;ga=26;break};case 2:{ma=aa;ga=27;break};case 1:{na=aa;break};default:{da=aa-1|0;oa=f+72+(da<<3)|0;h[oa>>3]=ea*(Aa(f+2120+(da+ca<<2)|0),+g[k>>2])+ +h[oa>>3];fa=da;ga=22}}if((ga|0)==22){ga=0;da=fa-1|0;T=ea*(Aa(f+2120+(da+ca<<2)|0),+g[k>>2]);oa=f+72+(da<<3)|0;h[oa>>3]=+h[oa>>3]+T;ha=da;ga=23}if((ga|0)==23){ga=0;da=ha-1|0;T=ea*(Aa(f+2120+(da+ca<<2)|0),+g[k>>2]);oa=f+72+(da<<3)|0;h[oa>>3]=+h[oa>>3]+T;ia=da;ga=24}if((ga|0)==24){ga=0;da=ia-1|0;T=ea*(Aa(f+2120+(da+ca<<2)|0),+g[k>>2]);oa=f+72+(da<<3)|0;h[oa>>3]=+h[oa>>3]+T;ja=da;ga=25}if((ga|0)==25){ga=0;da=ja-1|0;T=ea*(Aa(f+2120+(da+ca<<2)|0),+g[k>>2]);oa=f+72+(da<<3)|0;h[oa>>3]=+h[oa>>3]+T;ka=da;ga=26}if((ga|0)==26){ga=0;da=ka-1|0;T=ea*(Aa(f+2120+(da+ca<<2)|0),+g[k>>2]);oa=f+72+(da<<3)|0;h[oa>>3]=+h[oa>>3]+T;ma=da;ga=27}if((ga|0)==27){ga=0;da=ma-1|0;T=ea*(Aa(f+2120+(da+ca<<2)|0),+g[k>>2]);oa=f+72+(da<<3)|0;h[oa>>3]=+h[oa>>3]+T;na=da}aa=na-1|0;T=ea*(Aa(f+2120+(aa+ca<<2)|0),+g[k>>2]);da=f+72+(aa<<3)|0;h[da>>3]=+h[da>>3]+T;}while((aa|0)>0);aa=ba-P|0;if((aa|0)>-1){ca=ca+W|0;ba=aa}else{break}}ba=P-Q|0;ca=(_-ba|0)/(P|0)|0;aa=(Z(ca,P)|0)+ba|0;ba=(Z(ca+1|0,W)|0)+(c[z>>2]|0)|0;jb(I|0,0,$|0)|0;ca=ba;ba=aa;while(1){aa=ba>>12;da=c[H>>2]|0;ea=+g[da+(aa<<2)>>2];T=ea+ +(ba&4095|0)*.000244140625*(+g[da+(aa+1<<2)>>2]-ea);aa=W;do{switch((aa|0)%8|0|0){case 7:{pa=aa;ga=34;break};case 6:{qa=aa;ga=35;break};case 5:{ra=aa;ga=36;break};case 4:{sa=aa;ga=37;break};case 3:{ta=aa;ga=38;break};case 2:{ua=aa;ga=39;break};case 1:{va=aa;break};default:{da=aa-1|0;oa=f+1096+(da<<3)|0;h[oa>>3]=T*(Aa(f+2120+(da+ca<<2)|0),+g[k>>2])+ +h[oa>>3];pa=da;ga=34}}if((ga|0)==34){ga=0;da=pa-1|0;ea=T*(Aa(f+2120+(da+ca<<2)|0),+g[k>>2]);oa=f+1096+(da<<3)|0;h[oa>>3]=+h[oa>>3]+ea;qa=da;ga=35}if((ga|0)==35){ga=0;da=qa-1|0;ea=T*(Aa(f+2120+(da+ca<<2)|0),+g[k>>2]);oa=f+1096+(da<<3)|0;h[oa>>3]=+h[oa>>3]+ea;ra=da;ga=36}if((ga|0)==36){ga=0;da=ra-1|0;ea=T*(Aa(f+2120+(da+ca<<2)|0),+g[k>>2]);oa=f+1096+(da<<3)|0;h[oa>>3]=+h[oa>>3]+ea;sa=da;ga=37}if((ga|0)==37){ga=0;da=sa-1|0;ea=T*(Aa(f+2120+(da+ca<<2)|0),+g[k>>2]);oa=f+1096+(da<<3)|0;h[oa>>3]=+h[oa>>3]+ea;ta=da;ga=38}if((ga|0)==38){ga=0;da=ta-1|0;ea=T*(Aa(f+2120+(da+ca<<2)|0),+g[k>>2]);oa=f+1096+(da<<3)|0;h[oa>>3]=+h[oa>>3]+ea;ua=da;ga=39}if((ga|0)==39){ga=0;da=ua-1|0;ea=T*(Aa(f+2120+(da+ca<<2)|0),+g[k>>2]);oa=f+1096+(da<<3)|0;h[oa>>3]=+h[oa>>3]+ea;va=da}aa=va-1|0;ea=T*(Aa(f+2120+(aa+ca<<2)|0),+g[k>>2]);da=f+1096+(aa<<3)|0;h[da>>3]=+h[da>>3]+ea;}while((aa|0)>0);aa=ba-P|0;if((aa|0)>0){ca=ca-W|0;ba=aa}else{break}}T=V/u;ba=W;do{switch((ba|0)%8|0|0){case 7:{wa=ba;ga=45;break};case 6:{xa=ba;ga=46;break};case 5:{ya=ba;ga=47;break};case 4:{za=ba;ga=48;break};case 3:{Ba=ba;ga=49;break};case 2:{Ca=ba;ga=50;break};case 1:{Da=ba;break};default:{ca=ba-1|0;g[X+(ca+Y<<2)>>2]=T*(+h[f+72+(ca<<3)>>3]+ +h[f+1096+(ca<<3)>>3]);wa=ca;ga=45}}if((ga|0)==45){ga=0;ca=wa-1|0;g[X+(ca+Y<<2)>>2]=T*(+h[f+72+(ca<<3)>>3]+ +h[f+1096+(ca<<3)>>3]);xa=ca;ga=46}if((ga|0)==46){ga=0;ca=xa-1|0;g[X+(ca+Y<<2)>>2]=T*(+h[f+72+(ca<<3)>>3]+ +h[f+1096+(ca<<3)>>3]);ya=ca;ga=47}if((ga|0)==47){ga=0;ca=ya-1|0;g[X+(ca+Y<<2)>>2]=T*(+h[f+72+(ca<<3)>>3]+ +h[f+1096+(ca<<3)>>3]);za=ca;ga=48}if((ga|0)==48){ga=0;ca=za-1|0;g[X+(ca+Y<<2)>>2]=T*(+h[f+72+(ca<<3)>>3]+ +h[f+1096+(ca<<3)>>3]);Ba=ca;ga=49}if((ga|0)==49){ga=0;ca=Ba-1|0;g[X+(ca+Y<<2)>>2]=T*(+h[f+72+(ca<<3)>>3]+ +h[f+1096+(ca<<3)>>3]);Ca=ca;ga=50}if((ga|0)==50){ga=0;ca=Ca-1|0;g[X+(ca+Y<<2)>>2]=T*(+h[f+72+(ca<<3)>>3]+ +h[f+1096+(ca<<3)>>3]);Da=ca}ba=Da-1|0;g[X+(ba+Y<<2)>>2]=T*(+h[f+72+(ba<<3)>>3]+ +h[f+1096+(ba<<3)>>3]);}while((ba|0)>0);c[m>>2]=(c[m>>2]|0)+(c[J>>2]|0);T=v+1.0/U;u=T- +(la(+T)|0);if(u<0.0){Ea=u+1.0}else{Ea=u}ba=c[z>>2]|0;Y=c[i>>2]|0;X=(Z(la(+(T-Ea))|0,Y)|0)+ba|0;ba=c[A>>2]|0;Y=(X|0)%(ba|0)|0;c[z>>2]=Y;if((c[m>>2]|0)<(c[l>>2]|0)){s=U;v=Ea;L=Y;N=ba}else{R=U;S=Ea;break a}}return e|0}else{R=p;S=y}}while(0);h[j>>3]=S;h[o>>3]=R;o=c[i>>2]|0;c[b+16>>2]=(c[n>>2]|0)/(o|0)|0;c[b+20>>2]=(c[m>>2]|0)/(o|0)|0;e=0;return e|0}function ab(a){a=a|0;var b=0,d=0;b=c[a+28>>2]|0;if((b|0)==0){return}c[b+56>>2]=0;c[b+52>>2]=0;c[b+60>>2]=-1;a=b+2120|0;jb(b+32|0,0,16)|0;d=c[b+64>>2]|0;jb(a|0,0,d<<2|0)|0;jb(a+(d<<2)|0,-86|0,c[b+4>>2]<<2|0)|0;return}function bb(a,b,d){a=a|0;b=b|0;d=d|0;var e=0,f=0,g=0,h=0,i=0,j=0,k=0,l=0,m=0,n=0,o=0,p=0,q=0,r=0,s=0,t=0;e=a+60|0;if((c[e>>2]|0)>-1){f=0;return f|0}g=a+52|0;h=c[g>>2]|0;do{if((h|0)==0){i=c[a+64>>2]|0;c[a+56>>2]=d;c[g>>2]=d;j=i-(d<<1)|0;k=c[a+4>>2]|0;l=d;m=i}else{i=a+56|0;n=c[i>>2]|0;o=c[a+4>>2]|0;p=c[a+64>>2]|0;if((n+d+o|0)<(p|0)){q=p-h-d|0;j=(q|0)>0?q:0;k=o;l=n;m=p;break}else{q=n-h+d|0;lb(a+2120|0,a+2120+(h-d<<2)|0,q<<2|0)|0;c[g>>2]=d;c[i>>2]=q;i=p-d-d|0;j=(i|0)>0?i:0;k=o;l=q;m=p;break}}}while(0);h=a+8|0;p=a+12|0;q=c[p>>2]|0;o=(c[h>>2]|0)-q|0;i=(o|0)<(j|0)?o:j;j=i-((i|0)%(k|0)|0)|0;if((j|0)<0){f=21;return f|0}k=a+56|0;if((l+j|0)>(m|0)){f=21;return f|0}kb(a+2120+(l<<2)|0,(c[b>>2]|0)+(q<<2)|0,j<<2)|0;q=(c[k>>2]|0)+j|0;c[k>>2]=q;l=(c[p>>2]|0)+j|0;c[p>>2]=l;if((l|0)!=(c[h>>2]|0)){f=0;return f|0}h=c[g>>2]|0;l=q-h|0;if((l|0)>=(d<<1|0)){f=0;return f|0}if((c[b+24>>2]|0)==0){f=0;return f|0}b=c[a+64>>2]|0;p=d+5|0;if((b-q|0)<(p|0)){j=l+d|0;lb(a+2120|0,a+2120+(h-d<<2)|0,j<<2|0)|0;c[g>>2]=d;c[k>>2]=j;r=j}else{r=q}c[e>>2]=r;if((p|0)<0){s=16}else{if((r+p|0)>(b|0)){s=16}else{t=p}}if((s|0)==16){t=b-r|0}jb(a+2120+(r<<2)|0,0,t<<2|0)|0;c[k>>2]=(c[k>>2]|0)+t;f=0;return f|0}function cb(a,b){a=a|0;b=b|0;var d=0,e=0,f=0,g=0;if((b|0)!=3){d=10;return d|0}b=a+28|0;e=c[b>>2]|0;if((e|0)!=0){hb(e);c[b>>2]=0}e=c[a+20>>2]|0;f=e<<2;g=ib(1,f+32|0)|0;if((g|0)==0){d=1;return d|0}c[b>>2]=g;c[g>>2]=116853395;c[a+36>>2]=6;c[a+32>>2]=6;c[a+40>>2]=2;c[g+4>>2]=e;c[g+8>>2]=1;jb(g+28|0,0,f|0)|0;d=0;return d|0}function db(a,b){a=a|0;b=b|0;var d=0,e=0,f=0,i=0,j=0,k=0,l=0,m=0,n=0,o=0,p=0,q=0,r=0,s=0.0,t=0.0,u=0,v=0.0,w=0.0,x=0,y=0.0,z=0.0,A=0.0,B=0.0,C=0,D=0,E=0,F=0,G=0,H=0,I=0.0,J=0.0,K=0.0,L=0,N=0.0,O=0,P=0,Q=0,R=0.0,S=0.0,T=0,U=0,V=0;d=c[b+8>>2]|0;if((d|0)<1){e=0;return e|0}f=c[a+28>>2]|0;if((f|0)==0){e=5;return e|0}i=f+8|0;j=f+4|0;k=c[j>>2]|0;if((c[i>>2]|0)==0){l=k}else{if((k|0)>0){m=c[b>>2]|0;n=f+28|0;o=0;while(1){g[n+(o<<2)>>2]=+g[m+(o<<2)>>2];p=o+1|0;q=c[j>>2]|0;if((p|0)<(q|0)){o=p}else{r=q;break}}}else{r=k}c[i>>2]=0;l=r}r=f+4|0;i=Z(l,d)|0;d=f+12|0;c[d>>2]=i;k=Z(l,c[b+12>>2]|0)|0;o=f+20|0;c[o>>2]=k;j=f+24|0;c[j>>2]=0;m=f+16|0;c[m>>2]=0;n=a|0;s=+h[n>>3];q=a+8|0;t=+h[q>>3];a:do{if(t<1.0){a=b+32|0;p=f+28|0;u=b+4|0;v=s;w=t;x=0;while(1){if((x|0)>=(k|0)){y=v;z=w;break a}if(!(w*+(l|0)+0.0<+(i|0))){y=v;z=w;break a}do{if((k|0)>0){A=+h[a>>3];if(!(+M(+(s-A))>1.0e-20)){B=v;break}B=s+ +(x|0)*(A-s)/+(k|0)}else{B=v}}while(0);if((l|0)>0){C=c[u>>2]|0;D=0;E=x;while(1){g[C+(E<<2)>>2]=+g[p+(D<<2)>>2];F=E+1|0;c[j>>2]=F;G=D+1|0;if((G|0)<(l|0)){D=G;E=F}else{H=F;break}}}else{H=x}A=w+1.0/B;if(A<1.0){v=B;w=A;x=H}else{y=B;z=A;break}}}else{y=s;z=t}}while(0);t=z- +(la(+z)|0);if(t<0.0){I=t+1.0}else{I=t}H=c[r>>2]|0;l=Z(la(+(z-I))|0,H)|0;H=(c[m>>2]|0)+l|0;c[m>>2]=H;l=c[j>>2]|0;k=c[o>>2]|0;b:do{if((l|0)<(k|0)){i=b+32|0;x=b|0;p=b+4|0;u=H;z=y;t=I;a=l;E=k;while(1){D=c[r>>2]|0;if(+(u|0)+t*+(D|0)>+(c[d>>2]|0)){J=z;K=t;L=u;break b}do{if((E|0)>0){s=+h[n>>3];B=+h[i>>3];if(!(+M(+(s-B))>1.0e-20)){N=z;break}N=s+ +(a|0)*(B-s)/+(E|0)}else{N=z}}while(0);if((D|0)>0){C=c[x>>2]|0;F=c[p>>2]|0;G=(D|0)>1?D:1;O=0;P=a;while(1){g[F+(P<<2)>>2]=+g[C+(O-D+u<<2)>>2];Q=O+1|0;if((Q|0)<(D|0)){O=Q;P=P+1|0}else{break}}c[j>>2]=a+G}s=t+1.0/N;B=s- +(la(+s)|0);if(B<0.0){R=B+1.0}else{R=B}P=c[r>>2]|0;O=Z(la(+(s-R))|0,P)|0;P=(c[m>>2]|0)+O|0;c[m>>2]=P;O=c[j>>2]|0;D=c[o>>2]|0;if((O|0)<(D|0)){u=P;z=N;t=R;a=O;E=D}else{J=N;K=R;L=P;break}}}else{J=y;K=I;L=H}}while(0);H=c[d>>2]|0;if((L|0)>(H|0)){I=K+ +((L-H|0)/(c[r>>2]|0)|0|0);c[m>>2]=H;S=I;T=H}else{S=K;T=L}h[q>>3]=S;q=c[r>>2]|0;if((T|0)>0&(q|0)>0){L=c[b>>2]|0;H=f+28|0;f=0;d=q;o=T;while(1){g[H+(f<<2)>>2]=+g[L+(f-d+o<<2)>>2];k=f+1|0;l=c[r>>2]|0;E=c[m>>2]|0;if((k|0)<(l|0)){f=k;d=l;o=E}else{U=E;V=l;break}}}else{U=T;V=q}h[n>>3]=J;c[b+16>>2]=(U|0)/(V|0)|0;c[b+20>>2]=(c[j>>2]|0)/(V|0)|0;e=0;return e|0}function eb(a){a=a|0;var b=0,d=0;b=c[a+28>>2]|0;if((b|0)==0){return}d=c[a+20>>2]|0;c[b+4>>2]=d;c[b+8>>2]=1;jb(b+28|0,0,d<<2|0)|0;return}function fb(a,b,d,e,f,g,j,k,l){a=a|0;b=b|0;d=d|0;e=e|0;f=f|0;g=+g;j=j|0;k=k|0;l=l|0;var m=0,n=0;m=i;i=i+40|0;n=m|0;c[n>>2]=b;c[n+8>>2]=d;c[n+4>>2]=e;c[n+12>>2]=f;h[n+32>>3]=g;c[n+24>>2]=j;j=Qa(a,n)|0;c[k>>2]=c[n+16>>2];c[l>>2]=c[n+20>>2];i=m;return j|0}function gb(a){a=a|0;var b=0,d=0,e=0,f=0,g=0,h=0,i=0,j=0,k=0,l=0,m=0,n=0,o=0,p=0,q=0,r=0,s=0,t=0,u=0,v=0,w=0,x=0,y=0,z=0,A=0,B=0,C=0,D=0,E=0,F=0,G=0,H=0,I=0,J=0,K=0,L=0,M=0,N=0,O=0,P=0,Q=0,R=0,S=0,T=0,U=0,V=0,W=0,X=0,Y=0,Z=0,_=0,$=0,aa=0,ba=0,ca=0,da=0,ea=0,fa=0,ga=0,ja=0,ka=0,la=0,pa=0,qa=0,ra=0,sa=0,ta=0,ua=0,va=0,wa=0,xa=0,ya=0,za=0,Aa=0,Ba=0,Ca=0,Da=0,Ea=0,Fa=0,Ga=0,Ha=0,Ia=0,Ja=0,Ka=0,La=0;do{if(a>>>0<245>>>0){if(a>>>0<11>>>0){b=16}else{b=a+11&-8}d=b>>>3;e=c[25174]|0;f=e>>>(d>>>0);if((f&3|0)!=0){g=(f&1^1)+d|0;h=g<<1;i=100736+(h<<2)|0;j=100736+(h+2<<2)|0;h=c[j>>2]|0;k=h+8|0;l=c[k>>2]|0;do{if((i|0)==(l|0)){c[25174]=e&~(1<<g)}else{if(l>>>0<(c[25178]|0)>>>0){na();return 0}m=l+12|0;if((c[m>>2]|0)==(h|0)){c[m>>2]=i;c[j>>2]=l;break}else{na();return 0}}}while(0);l=g<<3;c[h+4>>2]=l|3;j=h+(l|4)|0;c[j>>2]=c[j>>2]|1;n=k;return n|0}if(!(b>>>0>(c[25176]|0)>>>0)){o=b;break}if((f|0)!=0){j=2<<d;l=f<<d&(j|-j);j=(l&-l)-1|0;l=j>>>12&16;i=j>>>(l>>>0);j=i>>>5&8;m=i>>>(j>>>0);i=m>>>2&4;p=m>>>(i>>>0);m=p>>>1&2;q=p>>>(m>>>0);p=q>>>1&1;r=(j|l|i|m|p)+(q>>>(p>>>0))|0;p=r<<1;q=100736+(p<<2)|0;m=100736+(p+2<<2)|0;p=c[m>>2]|0;i=p+8|0;l=c[i>>2]|0;do{if((q|0)==(l|0)){c[25174]=e&~(1<<r)}else{if(l>>>0<(c[25178]|0)>>>0){na();return 0}j=l+12|0;if((c[j>>2]|0)==(p|0)){c[j>>2]=q;c[m>>2]=l;break}else{na();return 0}}}while(0);l=r<<3;m=l-b|0;c[p+4>>2]=b|3;q=p;e=q+b|0;c[q+(b|4)>>2]=m|1;c[q+l>>2]=m;l=c[25176]|0;if((l|0)!=0){q=c[25179]|0;d=l>>>3;l=d<<1;f=100736+(l<<2)|0;k=c[25174]|0;h=1<<d;do{if((k&h|0)==0){c[25174]=k|h;s=f;t=100736+(l+2<<2)|0}else{d=100736+(l+2<<2)|0;g=c[d>>2]|0;if(!(g>>>0<(c[25178]|0)>>>0)){s=g;t=d;break}na();return 0}}while(0);c[t>>2]=q;c[s+12>>2]=q;c[q+8>>2]=s;c[q+12>>2]=f}c[25176]=m;c[25179]=e;n=i;return n|0}l=c[25175]|0;if((l|0)==0){o=b;break}h=(l&-l)-1|0;l=h>>>12&16;k=h>>>(l>>>0);h=k>>>5&8;p=k>>>(h>>>0);k=p>>>2&4;r=p>>>(k>>>0);p=r>>>1&2;d=r>>>(p>>>0);r=d>>>1&1;g=c[101e3+((h|l|k|p|r)+(d>>>(r>>>0))<<2)>>2]|0;r=g;d=g;p=(c[g+4>>2]&-8)-b|0;while(1){g=c[r+16>>2]|0;if((g|0)==0){k=c[r+20>>2]|0;if((k|0)==0){break}else{u=k}}else{u=g}g=(c[u+4>>2]&-8)-b|0;k=g>>>0<p>>>0;r=u;d=k?u:d;p=k?g:p}r=d;i=c[25178]|0;if(r>>>0<i>>>0){na();return 0}e=r+b|0;m=e;if(!(r>>>0<e>>>0)){na();return 0}e=c[d+24>>2]|0;f=c[d+12>>2]|0;do{if((f|0)==(d|0)){q=d+20|0;g=c[q>>2]|0;if((g|0)==0){k=d+16|0;l=c[k>>2]|0;if((l|0)==0){v=0;break}else{w=l;x=k}}else{w=g;x=q}while(1){q=w+20|0;g=c[q>>2]|0;if((g|0)!=0){w=g;x=q;continue}q=w+16|0;g=c[q>>2]|0;if((g|0)==0){break}else{w=g;x=q}}if(x>>>0<i>>>0){na();return 0}else{c[x>>2]=0;v=w;break}}else{q=c[d+8>>2]|0;if(q>>>0<i>>>0){na();return 0}g=q+12|0;if((c[g>>2]|0)!=(d|0)){na();return 0}k=f+8|0;if((c[k>>2]|0)==(d|0)){c[g>>2]=f;c[k>>2]=q;v=f;break}else{na();return 0}}}while(0);a:do{if((e|0)!=0){f=c[d+28>>2]|0;i=101e3+(f<<2)|0;do{if((d|0)==(c[i>>2]|0)){c[i>>2]=v;if((v|0)!=0){break}c[25175]=c[25175]&~(1<<f);break a}else{if(e>>>0<(c[25178]|0)>>>0){na();return 0}q=e+16|0;if((c[q>>2]|0)==(d|0)){c[q>>2]=v}else{c[e+20>>2]=v}if((v|0)==0){break a}}}while(0);if(v>>>0<(c[25178]|0)>>>0){na();return 0}c[v+24>>2]=e;f=c[d+16>>2]|0;do{if((f|0)!=0){if(f>>>0<(c[25178]|0)>>>0){na();return 0}else{c[v+16>>2]=f;c[f+24>>2]=v;break}}}while(0);f=c[d+20>>2]|0;if((f|0)==0){break}if(f>>>0<(c[25178]|0)>>>0){na();return 0}else{c[v+20>>2]=f;c[f+24>>2]=v;break}}}while(0);if(p>>>0<16>>>0){e=p+b|0;c[d+4>>2]=e|3;f=r+(e+4)|0;c[f>>2]=c[f>>2]|1}else{c[d+4>>2]=b|3;c[r+(b|4)>>2]=p|1;c[r+(p+b)>>2]=p;f=c[25176]|0;if((f|0)!=0){e=c[25179]|0;i=f>>>3;f=i<<1;q=100736+(f<<2)|0;k=c[25174]|0;g=1<<i;do{if((k&g|0)==0){c[25174]=k|g;y=q;z=100736+(f+2<<2)|0}else{i=100736+(f+2<<2)|0;l=c[i>>2]|0;if(!(l>>>0<(c[25178]|0)>>>0)){y=l;z=i;break}na();return 0}}while(0);c[z>>2]=e;c[y+12>>2]=e;c[e+8>>2]=y;c[e+12>>2]=q}c[25176]=p;c[25179]=m}f=d+8|0;if((f|0)==0){o=b;break}else{n=f}return n|0}else{if(a>>>0>4294967231>>>0){o=-1;break}f=a+11|0;g=f&-8;k=c[25175]|0;if((k|0)==0){o=g;break}r=-g|0;i=f>>>8;do{if((i|0)==0){A=0}else{if(g>>>0>16777215>>>0){A=31;break}f=(i+1048320|0)>>>16&8;l=i<<f;h=(l+520192|0)>>>16&4;j=l<<h;l=(j+245760|0)>>>16&2;B=14-(h|f|l)+(j<<l>>>15)|0;A=g>>>((B+7|0)>>>0)&1|B<<1}}while(0);i=c[101e3+(A<<2)>>2]|0;b:do{if((i|0)==0){C=0;D=r;E=0}else{if((A|0)==31){F=0}else{F=25-(A>>>1)|0}d=0;m=r;p=i;q=g<<F;e=0;while(1){B=c[p+4>>2]&-8;l=B-g|0;if(l>>>0<m>>>0){if((B|0)==(g|0)){C=p;D=l;E=p;break b}else{G=p;H=l}}else{G=d;H=m}l=c[p+20>>2]|0;B=c[p+16+(q>>>31<<2)>>2]|0;j=(l|0)==0|(l|0)==(B|0)?e:l;if((B|0)==0){C=G;D=H;E=j;break}else{d=G;m=H;p=B;q=q<<1;e=j}}}}while(0);if((E|0)==0&(C|0)==0){i=2<<A;r=k&(i|-i);if((r|0)==0){o=g;break}i=(r&-r)-1|0;r=i>>>12&16;e=i>>>(r>>>0);i=e>>>5&8;q=e>>>(i>>>0);e=q>>>2&4;p=q>>>(e>>>0);q=p>>>1&2;m=p>>>(q>>>0);p=m>>>1&1;I=c[101e3+((i|r|e|q|p)+(m>>>(p>>>0))<<2)>>2]|0}else{I=E}if((I|0)==0){J=D;K=C}else{p=I;m=D;q=C;while(1){e=(c[p+4>>2]&-8)-g|0;r=e>>>0<m>>>0;i=r?e:m;e=r?p:q;r=c[p+16>>2]|0;if((r|0)!=0){p=r;m=i;q=e;continue}r=c[p+20>>2]|0;if((r|0)==0){J=i;K=e;break}else{p=r;m=i;q=e}}}if((K|0)==0){o=g;break}if(!(J>>>0<((c[25176]|0)-g|0)>>>0)){o=g;break}q=K;m=c[25178]|0;if(q>>>0<m>>>0){na();return 0}p=q+g|0;k=p;if(!(q>>>0<p>>>0)){na();return 0}e=c[K+24>>2]|0;i=c[K+12>>2]|0;do{if((i|0)==(K|0)){r=K+20|0;d=c[r>>2]|0;if((d|0)==0){j=K+16|0;B=c[j>>2]|0;if((B|0)==0){L=0;break}else{M=B;N=j}}else{M=d;N=r}while(1){r=M+20|0;d=c[r>>2]|0;if((d|0)!=0){M=d;N=r;continue}r=M+16|0;d=c[r>>2]|0;if((d|0)==0){break}else{M=d;N=r}}if(N>>>0<m>>>0){na();return 0}else{c[N>>2]=0;L=M;break}}else{r=c[K+8>>2]|0;if(r>>>0<m>>>0){na();return 0}d=r+12|0;if((c[d>>2]|0)!=(K|0)){na();return 0}j=i+8|0;if((c[j>>2]|0)==(K|0)){c[d>>2]=i;c[j>>2]=r;L=i;break}else{na();return 0}}}while(0);c:do{if((e|0)!=0){i=c[K+28>>2]|0;m=101e3+(i<<2)|0;do{if((K|0)==(c[m>>2]|0)){c[m>>2]=L;if((L|0)!=0){break}c[25175]=c[25175]&~(1<<i);break c}else{if(e>>>0<(c[25178]|0)>>>0){na();return 0}r=e+16|0;if((c[r>>2]|0)==(K|0)){c[r>>2]=L}else{c[e+20>>2]=L}if((L|0)==0){break c}}}while(0);if(L>>>0<(c[25178]|0)>>>0){na();return 0}c[L+24>>2]=e;i=c[K+16>>2]|0;do{if((i|0)!=0){if(i>>>0<(c[25178]|0)>>>0){na();return 0}else{c[L+16>>2]=i;c[i+24>>2]=L;break}}}while(0);i=c[K+20>>2]|0;if((i|0)==0){break}if(i>>>0<(c[25178]|0)>>>0){na();return 0}else{c[L+20>>2]=i;c[i+24>>2]=L;break}}}while(0);do{if(J>>>0<16>>>0){e=J+g|0;c[K+4>>2]=e|3;i=q+(e+4)|0;c[i>>2]=c[i>>2]|1}else{c[K+4>>2]=g|3;c[q+(g|4)>>2]=J|1;c[q+(J+g)>>2]=J;i=J>>>3;if(J>>>0<256>>>0){e=i<<1;m=100736+(e<<2)|0;r=c[25174]|0;j=1<<i;do{if((r&j|0)==0){c[25174]=r|j;O=m;P=100736+(e+2<<2)|0}else{i=100736+(e+2<<2)|0;d=c[i>>2]|0;if(!(d>>>0<(c[25178]|0)>>>0)){O=d;P=i;break}na();return 0}}while(0);c[P>>2]=k;c[O+12>>2]=k;c[q+(g+8)>>2]=O;c[q+(g+12)>>2]=m;break}e=p;j=J>>>8;do{if((j|0)==0){Q=0}else{if(J>>>0>16777215>>>0){Q=31;break}r=(j+1048320|0)>>>16&8;i=j<<r;d=(i+520192|0)>>>16&4;B=i<<d;i=(B+245760|0)>>>16&2;l=14-(d|r|i)+(B<<i>>>15)|0;Q=J>>>((l+7|0)>>>0)&1|l<<1}}while(0);j=101e3+(Q<<2)|0;c[q+(g+28)>>2]=Q;c[q+(g+20)>>2]=0;c[q+(g+16)>>2]=0;m=c[25175]|0;l=1<<Q;if((m&l|0)==0){c[25175]=m|l;c[j>>2]=e;c[q+(g+24)>>2]=j;c[q+(g+12)>>2]=e;c[q+(g+8)>>2]=e;break}if((Q|0)==31){R=0}else{R=25-(Q>>>1)|0}l=J<<R;m=c[j>>2]|0;while(1){if((c[m+4>>2]&-8|0)==(J|0)){break}S=m+16+(l>>>31<<2)|0;j=c[S>>2]|0;if((j|0)==0){T=151;break}else{l=l<<1;m=j}}if((T|0)==151){if(S>>>0<(c[25178]|0)>>>0){na();return 0}else{c[S>>2]=e;c[q+(g+24)>>2]=m;c[q+(g+12)>>2]=e;c[q+(g+8)>>2]=e;break}}l=m+8|0;j=c[l>>2]|0;i=c[25178]|0;if(m>>>0<i>>>0){na();return 0}if(j>>>0<i>>>0){na();return 0}else{c[j+12>>2]=e;c[l>>2]=e;c[q+(g+8)>>2]=j;c[q+(g+12)>>2]=m;c[q+(g+24)>>2]=0;break}}}while(0);q=K+8|0;if((q|0)==0){o=g;break}else{n=q}return n|0}}while(0);K=c[25176]|0;if(!(o>>>0>K>>>0)){S=K-o|0;J=c[25179]|0;if(S>>>0>15>>>0){R=J;c[25179]=R+o;c[25176]=S;c[R+(o+4)>>2]=S|1;c[R+K>>2]=S;c[J+4>>2]=o|3}else{c[25176]=0;c[25179]=0;c[J+4>>2]=K|3;S=J+(K+4)|0;c[S>>2]=c[S>>2]|1}n=J+8|0;return n|0}J=c[25177]|0;if(o>>>0<J>>>0){S=J-o|0;c[25177]=S;J=c[25180]|0;K=J;c[25180]=K+o;c[K+(o+4)>>2]=S|1;c[J+4>>2]=o|3;n=J+8|0;return n|0}do{if((c[25168]|0)==0){J=ha(30)|0;if((J-1&J|0)==0){c[25170]=J;c[25169]=J;c[25171]=-1;c[25172]=-1;c[25173]=0;c[25285]=0;c[25168]=(oa(0)|0)&-16^1431655768;break}else{na();return 0}}}while(0);J=o+48|0;S=c[25170]|0;K=o+47|0;R=S+K|0;Q=-S|0;S=R&Q;if(!(S>>>0>o>>>0)){n=0;return n|0}O=c[25284]|0;do{if((O|0)!=0){P=c[25282]|0;L=P+S|0;if(L>>>0<=P>>>0|L>>>0>O>>>0){n=0}else{break}return n|0}}while(0);d:do{if((c[25285]&4|0)==0){O=c[25180]|0;e:do{if((O|0)==0){T=181}else{L=O;P=101144;while(1){U=P|0;M=c[U>>2]|0;if(!(M>>>0>L>>>0)){V=P+4|0;if((M+(c[V>>2]|0)|0)>>>0>L>>>0){break}}M=c[P+8>>2]|0;if((M|0)==0){T=181;break e}else{P=M}}if((P|0)==0){T=181;break}L=R-(c[25177]|0)&Q;if(!(L>>>0<2147483647>>>0)){W=0;break}m=ia(L|0)|0;e=(m|0)==((c[U>>2]|0)+(c[V>>2]|0)|0);X=e?m:-1;Y=e?L:0;Z=m;_=L;T=190}}while(0);do{if((T|0)==181){O=ia(0)|0;if((O|0)==-1){W=0;break}g=O;L=c[25169]|0;m=L-1|0;if((m&g|0)==0){$=S}else{$=S-g+(m+g&-L)|0}L=c[25282]|0;g=L+$|0;if(!($>>>0>o>>>0&$>>>0<2147483647>>>0)){W=0;break}m=c[25284]|0;if((m|0)!=0){if(g>>>0<=L>>>0|g>>>0>m>>>0){W=0;break}}m=ia($|0)|0;g=(m|0)==(O|0);X=g?O:-1;Y=g?$:0;Z=m;_=$;T=190}}while(0);f:do{if((T|0)==190){m=-_|0;if(!((X|0)==-1)){aa=Y;ba=X;T=201;break d}do{if((Z|0)!=-1&_>>>0<2147483647>>>0&_>>>0<J>>>0){g=c[25170]|0;O=K-_+g&-g;if(!(O>>>0<2147483647>>>0)){ca=_;break}if((ia(O|0)|0)==-1){ia(m|0)|0;W=Y;break f}else{ca=O+_|0;break}}else{ca=_}}while(0);if((Z|0)==-1){W=Y}else{aa=ca;ba=Z;T=201;break d}}}while(0);c[25285]=c[25285]|4;da=W;T=198}else{da=0;T=198}}while(0);do{if((T|0)==198){if(!(S>>>0<2147483647>>>0)){break}W=ia(S|0)|0;Z=ia(0)|0;if(!((Z|0)!=-1&(W|0)!=-1&W>>>0<Z>>>0)){break}ca=Z-W|0;Z=ca>>>0>(o+40|0)>>>0;Y=Z?W:-1;if(!((Y|0)==-1)){aa=Z?ca:da;ba=Y;T=201}}}while(0);do{if((T|0)==201){da=(c[25282]|0)+aa|0;c[25282]=da;if(da>>>0>(c[25283]|0)>>>0){c[25283]=da}da=c[25180]|0;g:do{if((da|0)==0){S=c[25178]|0;if((S|0)==0|ba>>>0<S>>>0){c[25178]=ba}c[25286]=ba;c[25287]=aa;c[25289]=0;c[25183]=c[25168];c[25182]=-1;S=0;do{Y=S<<1;ca=100736+(Y<<2)|0;c[100736+(Y+3<<2)>>2]=ca;c[100736+(Y+2<<2)>>2]=ca;S=S+1|0;}while(S>>>0<32>>>0);S=ba+8|0;if((S&7|0)==0){ea=0}else{ea=-S&7}S=aa-40-ea|0;c[25180]=ba+ea;c[25177]=S;c[ba+(ea+4)>>2]=S|1;c[ba+(aa-36)>>2]=40;c[25181]=c[25172]}else{S=101144;while(1){fa=c[S>>2]|0;ga=S+4|0;ja=c[ga>>2]|0;if((ba|0)==(fa+ja|0)){T=213;break}ca=c[S+8>>2]|0;if((ca|0)==0){break}else{S=ca}}do{if((T|0)==213){if((c[S+12>>2]&8|0)!=0){break}ca=da;if(!(ca>>>0>=fa>>>0&ca>>>0<ba>>>0)){break}c[ga>>2]=ja+aa;Y=(c[25177]|0)+aa|0;Z=da+8|0;if((Z&7|0)==0){ka=0}else{ka=-Z&7}Z=Y-ka|0;c[25180]=ca+ka;c[25177]=Z;c[ca+(ka+4)>>2]=Z|1;c[ca+(Y+4)>>2]=40;c[25181]=c[25172];break g}}while(0);if(ba>>>0<(c[25178]|0)>>>0){c[25178]=ba}S=ba+aa|0;Y=101144;while(1){la=Y|0;if((c[la>>2]|0)==(S|0)){T=223;break}ca=c[Y+8>>2]|0;if((ca|0)==0){break}else{Y=ca}}do{if((T|0)==223){if((c[Y+12>>2]&8|0)!=0){break}c[la>>2]=ba;S=Y+4|0;c[S>>2]=(c[S>>2]|0)+aa;S=ba+8|0;if((S&7|0)==0){pa=0}else{pa=-S&7}S=ba+(aa+8)|0;if((S&7|0)==0){qa=0}else{qa=-S&7}S=ba+(qa+aa)|0;ca=S;Z=pa+o|0;W=ba+Z|0;_=W;K=S-(ba+pa)-o|0;c[ba+(pa+4)>>2]=o|3;do{if((ca|0)==(c[25180]|0)){J=(c[25177]|0)+K|0;c[25177]=J;c[25180]=_;c[ba+(Z+4)>>2]=J|1}else{if((ca|0)==(c[25179]|0)){J=(c[25176]|0)+K|0;c[25176]=J;c[25179]=_;c[ba+(Z+4)>>2]=J|1;c[ba+(J+Z)>>2]=J;break}J=aa+4|0;X=c[ba+(J+qa)>>2]|0;if((X&3|0)==1){$=X&-8;V=X>>>3;h:do{if(X>>>0<256>>>0){U=c[ba+((qa|8)+aa)>>2]|0;Q=c[ba+(aa+12+qa)>>2]|0;R=100736+(V<<1<<2)|0;do{if((U|0)!=(R|0)){if(U>>>0<(c[25178]|0)>>>0){na();return 0}if((c[U+12>>2]|0)==(ca|0)){break}na();return 0}}while(0);if((Q|0)==(U|0)){c[25174]=c[25174]&~(1<<V);break}do{if((Q|0)==(R|0)){ra=Q+8|0}else{if(Q>>>0<(c[25178]|0)>>>0){na();return 0}m=Q+8|0;if((c[m>>2]|0)==(ca|0)){ra=m;break}na();return 0}}while(0);c[U+12>>2]=Q;c[ra>>2]=U}else{R=S;m=c[ba+((qa|24)+aa)>>2]|0;P=c[ba+(aa+12+qa)>>2]|0;do{if((P|0)==(R|0)){O=qa|16;g=ba+(J+O)|0;L=c[g>>2]|0;if((L|0)==0){e=ba+(O+aa)|0;O=c[e>>2]|0;if((O|0)==0){sa=0;break}else{ta=O;ua=e}}else{ta=L;ua=g}while(1){g=ta+20|0;L=c[g>>2]|0;if((L|0)!=0){ta=L;ua=g;continue}g=ta+16|0;L=c[g>>2]|0;if((L|0)==0){break}else{ta=L;ua=g}}if(ua>>>0<(c[25178]|0)>>>0){na();return 0}else{c[ua>>2]=0;sa=ta;break}}else{g=c[ba+((qa|8)+aa)>>2]|0;if(g>>>0<(c[25178]|0)>>>0){na();return 0}L=g+12|0;if((c[L>>2]|0)!=(R|0)){na();return 0}e=P+8|0;if((c[e>>2]|0)==(R|0)){c[L>>2]=P;c[e>>2]=g;sa=P;break}else{na();return 0}}}while(0);if((m|0)==0){break}P=c[ba+(aa+28+qa)>>2]|0;U=101e3+(P<<2)|0;do{if((R|0)==(c[U>>2]|0)){c[U>>2]=sa;if((sa|0)!=0){break}c[25175]=c[25175]&~(1<<P);break h}else{if(m>>>0<(c[25178]|0)>>>0){na();return 0}Q=m+16|0;if((c[Q>>2]|0)==(R|0)){c[Q>>2]=sa}else{c[m+20>>2]=sa}if((sa|0)==0){break h}}}while(0);if(sa>>>0<(c[25178]|0)>>>0){na();return 0}c[sa+24>>2]=m;R=qa|16;P=c[ba+(R+aa)>>2]|0;do{if((P|0)!=0){if(P>>>0<(c[25178]|0)>>>0){na();return 0}else{c[sa+16>>2]=P;c[P+24>>2]=sa;break}}}while(0);P=c[ba+(J+R)>>2]|0;if((P|0)==0){break}if(P>>>0<(c[25178]|0)>>>0){na();return 0}else{c[sa+20>>2]=P;c[P+24>>2]=sa;break}}}while(0);va=ba+(($|qa)+aa)|0;wa=$+K|0}else{va=ca;wa=K}J=va+4|0;c[J>>2]=c[J>>2]&-2;c[ba+(Z+4)>>2]=wa|1;c[ba+(wa+Z)>>2]=wa;J=wa>>>3;if(wa>>>0<256>>>0){V=J<<1;X=100736+(V<<2)|0;P=c[25174]|0;m=1<<J;do{if((P&m|0)==0){c[25174]=P|m;xa=X;ya=100736+(V+2<<2)|0}else{J=100736+(V+2<<2)|0;U=c[J>>2]|0;if(!(U>>>0<(c[25178]|0)>>>0)){xa=U;ya=J;break}na();return 0}}while(0);c[ya>>2]=_;c[xa+12>>2]=_;c[ba+(Z+8)>>2]=xa;c[ba+(Z+12)>>2]=X;break}V=W;m=wa>>>8;do{if((m|0)==0){za=0}else{if(wa>>>0>16777215>>>0){za=31;break}P=(m+1048320|0)>>>16&8;$=m<<P;J=($+520192|0)>>>16&4;U=$<<J;$=(U+245760|0)>>>16&2;Q=14-(J|P|$)+(U<<$>>>15)|0;za=wa>>>((Q+7|0)>>>0)&1|Q<<1}}while(0);m=101e3+(za<<2)|0;c[ba+(Z+28)>>2]=za;c[ba+(Z+20)>>2]=0;c[ba+(Z+16)>>2]=0;X=c[25175]|0;Q=1<<za;if((X&Q|0)==0){c[25175]=X|Q;c[m>>2]=V;c[ba+(Z+24)>>2]=m;c[ba+(Z+12)>>2]=V;c[ba+(Z+8)>>2]=V;break}if((za|0)==31){Aa=0}else{Aa=25-(za>>>1)|0}Q=wa<<Aa;X=c[m>>2]|0;while(1){if((c[X+4>>2]&-8|0)==(wa|0)){break}Ba=X+16+(Q>>>31<<2)|0;m=c[Ba>>2]|0;if((m|0)==0){T=296;break}else{Q=Q<<1;X=m}}if((T|0)==296){if(Ba>>>0<(c[25178]|0)>>>0){na();return 0}else{c[Ba>>2]=V;c[ba+(Z+24)>>2]=X;c[ba+(Z+12)>>2]=V;c[ba+(Z+8)>>2]=V;break}}Q=X+8|0;m=c[Q>>2]|0;$=c[25178]|0;if(X>>>0<$>>>0){na();return 0}if(m>>>0<$>>>0){na();return 0}else{c[m+12>>2]=V;c[Q>>2]=V;c[ba+(Z+8)>>2]=m;c[ba+(Z+12)>>2]=X;c[ba+(Z+24)>>2]=0;break}}}while(0);n=ba+(pa|8)|0;return n|0}}while(0);Y=da;Z=101144;while(1){Ca=c[Z>>2]|0;if(!(Ca>>>0>Y>>>0)){Da=c[Z+4>>2]|0;Ea=Ca+Da|0;if(Ea>>>0>Y>>>0){break}}Z=c[Z+8>>2]|0}Z=Ca+(Da-39)|0;if((Z&7|0)==0){Fa=0}else{Fa=-Z&7}Z=Ca+(Da-47+Fa)|0;W=Z>>>0<(da+16|0)>>>0?Y:Z;Z=W+8|0;_=ba+8|0;if((_&7|0)==0){Ga=0}else{Ga=-_&7}_=aa-40-Ga|0;c[25180]=ba+Ga;c[25177]=_;c[ba+(Ga+4)>>2]=_|1;c[ba+(aa-36)>>2]=40;c[25181]=c[25172];c[W+4>>2]=27;c[Z>>2]=c[25286];c[Z+4>>2]=c[25287];c[Z+8>>2]=c[25288];c[Z+12>>2]=c[25289];c[25286]=ba;c[25287]=aa;c[25289]=0;c[25288]=Z;Z=W+28|0;c[Z>>2]=7;if((W+32|0)>>>0<Ea>>>0){_=Z;while(1){Z=_+4|0;c[Z>>2]=7;if((_+8|0)>>>0<Ea>>>0){_=Z}else{break}}}if((W|0)==(Y|0)){break}_=W-da|0;Z=Y+(_+4)|0;c[Z>>2]=c[Z>>2]&-2;c[da+4>>2]=_|1;c[Y+_>>2]=_;Z=_>>>3;if(_>>>0<256>>>0){K=Z<<1;ca=100736+(K<<2)|0;S=c[25174]|0;m=1<<Z;do{if((S&m|0)==0){c[25174]=S|m;Ha=ca;Ia=100736+(K+2<<2)|0}else{Z=100736+(K+2<<2)|0;Q=c[Z>>2]|0;if(!(Q>>>0<(c[25178]|0)>>>0)){Ha=Q;Ia=Z;break}na();return 0}}while(0);c[Ia>>2]=da;c[Ha+12>>2]=da;c[da+8>>2]=Ha;c[da+12>>2]=ca;break}K=da;m=_>>>8;do{if((m|0)==0){Ja=0}else{if(_>>>0>16777215>>>0){Ja=31;break}S=(m+1048320|0)>>>16&8;Y=m<<S;W=(Y+520192|0)>>>16&4;Z=Y<<W;Y=(Z+245760|0)>>>16&2;Q=14-(W|S|Y)+(Z<<Y>>>15)|0;Ja=_>>>((Q+7|0)>>>0)&1|Q<<1}}while(0);m=101e3+(Ja<<2)|0;c[da+28>>2]=Ja;c[da+20>>2]=0;c[da+16>>2]=0;ca=c[25175]|0;Q=1<<Ja;if((ca&Q|0)==0){c[25175]=ca|Q;c[m>>2]=K;c[da+24>>2]=m;c[da+12>>2]=da;c[da+8>>2]=da;break}if((Ja|0)==31){Ka=0}else{Ka=25-(Ja>>>1)|0}Q=_<<Ka;ca=c[m>>2]|0;while(1){if((c[ca+4>>2]&-8|0)==(_|0)){break}La=ca+16+(Q>>>31<<2)|0;m=c[La>>2]|0;if((m|0)==0){T=331;break}else{Q=Q<<1;ca=m}}if((T|0)==331){if(La>>>0<(c[25178]|0)>>>0){na();return 0}else{c[La>>2]=K;c[da+24>>2]=ca;c[da+12>>2]=da;c[da+8>>2]=da;break}}Q=ca+8|0;_=c[Q>>2]|0;m=c[25178]|0;if(ca>>>0<m>>>0){na();return 0}if(_>>>0<m>>>0){na();return 0}else{c[_+12>>2]=K;c[Q>>2]=K;c[da+8>>2]=_;c[da+12>>2]=ca;c[da+24>>2]=0;break}}}while(0);da=c[25177]|0;if(!(da>>>0>o>>>0)){break}_=da-o|0;c[25177]=_;da=c[25180]|0;Q=da;c[25180]=Q+o;c[Q+(o+4)>>2]=_|1;c[da+4>>2]=o|3;n=da+8|0;return n|0}}while(0);c[(ma()|0)>>2]=12;n=0;return n|0}function hb(a){a=a|0;var b=0,d=0,e=0,f=0,g=0,h=0,i=0,j=0,k=0,l=0,m=0,n=0,o=0,p=0,q=0,r=0,s=0,t=0,u=0,v=0,w=0,x=0,y=0,z=0,A=0,B=0,C=0,D=0,E=0,F=0,G=0,H=0,I=0,J=0,K=0,L=0,M=0,N=0,O=0;if((a|0)==0){return}b=a-8|0;d=b;e=c[25178]|0;if(b>>>0<e>>>0){na()}f=c[a-4>>2]|0;g=f&3;if((g|0)==1){na()}h=f&-8;i=a+(h-8)|0;j=i;a:do{if((f&1|0)==0){k=c[b>>2]|0;if((g|0)==0){return}l=-8-k|0;m=a+l|0;n=m;o=k+h|0;if(m>>>0<e>>>0){na()}if((n|0)==(c[25179]|0)){p=a+(h-4)|0;if((c[p>>2]&3|0)!=3){q=n;r=o;break}c[25176]=o;c[p>>2]=c[p>>2]&-2;c[a+(l+4)>>2]=o|1;c[i>>2]=o;return}p=k>>>3;if(k>>>0<256>>>0){k=c[a+(l+8)>>2]|0;s=c[a+(l+12)>>2]|0;t=100736+(p<<1<<2)|0;do{if((k|0)!=(t|0)){if(k>>>0<e>>>0){na()}if((c[k+12>>2]|0)==(n|0)){break}na()}}while(0);if((s|0)==(k|0)){c[25174]=c[25174]&~(1<<p);q=n;r=o;break}do{if((s|0)==(t|0)){u=s+8|0}else{if(s>>>0<e>>>0){na()}v=s+8|0;if((c[v>>2]|0)==(n|0)){u=v;break}na()}}while(0);c[k+12>>2]=s;c[u>>2]=k;q=n;r=o;break}t=m;p=c[a+(l+24)>>2]|0;v=c[a+(l+12)>>2]|0;do{if((v|0)==(t|0)){w=a+(l+20)|0;x=c[w>>2]|0;if((x|0)==0){y=a+(l+16)|0;z=c[y>>2]|0;if((z|0)==0){A=0;break}else{B=z;C=y}}else{B=x;C=w}while(1){w=B+20|0;x=c[w>>2]|0;if((x|0)!=0){B=x;C=w;continue}w=B+16|0;x=c[w>>2]|0;if((x|0)==0){break}else{B=x;C=w}}if(C>>>0<e>>>0){na()}else{c[C>>2]=0;A=B;break}}else{w=c[a+(l+8)>>2]|0;if(w>>>0<e>>>0){na()}x=w+12|0;if((c[x>>2]|0)!=(t|0)){na()}y=v+8|0;if((c[y>>2]|0)==(t|0)){c[x>>2]=v;c[y>>2]=w;A=v;break}else{na()}}}while(0);if((p|0)==0){q=n;r=o;break}v=c[a+(l+28)>>2]|0;m=101e3+(v<<2)|0;do{if((t|0)==(c[m>>2]|0)){c[m>>2]=A;if((A|0)!=0){break}c[25175]=c[25175]&~(1<<v);q=n;r=o;break a}else{if(p>>>0<(c[25178]|0)>>>0){na()}k=p+16|0;if((c[k>>2]|0)==(t|0)){c[k>>2]=A}else{c[p+20>>2]=A}if((A|0)==0){q=n;r=o;break a}}}while(0);if(A>>>0<(c[25178]|0)>>>0){na()}c[A+24>>2]=p;t=c[a+(l+16)>>2]|0;do{if((t|0)!=0){if(t>>>0<(c[25178]|0)>>>0){na()}else{c[A+16>>2]=t;c[t+24>>2]=A;break}}}while(0);t=c[a+(l+20)>>2]|0;if((t|0)==0){q=n;r=o;break}if(t>>>0<(c[25178]|0)>>>0){na()}else{c[A+20>>2]=t;c[t+24>>2]=A;q=n;r=o;break}}else{q=d;r=h}}while(0);d=q;if(!(d>>>0<i>>>0)){na()}A=a+(h-4)|0;e=c[A>>2]|0;if((e&1|0)==0){na()}do{if((e&2|0)==0){if((j|0)==(c[25180]|0)){B=(c[25177]|0)+r|0;c[25177]=B;c[25180]=q;c[q+4>>2]=B|1;if((q|0)!=(c[25179]|0)){return}c[25179]=0;c[25176]=0;return}if((j|0)==(c[25179]|0)){B=(c[25176]|0)+r|0;c[25176]=B;c[25179]=q;c[q+4>>2]=B|1;c[d+B>>2]=B;return}B=(e&-8)+r|0;C=e>>>3;b:do{if(e>>>0<256>>>0){u=c[a+h>>2]|0;g=c[a+(h|4)>>2]|0;b=100736+(C<<1<<2)|0;do{if((u|0)!=(b|0)){if(u>>>0<(c[25178]|0)>>>0){na()}if((c[u+12>>2]|0)==(j|0)){break}na()}}while(0);if((g|0)==(u|0)){c[25174]=c[25174]&~(1<<C);break}do{if((g|0)==(b|0)){D=g+8|0}else{if(g>>>0<(c[25178]|0)>>>0){na()}f=g+8|0;if((c[f>>2]|0)==(j|0)){D=f;break}na()}}while(0);c[u+12>>2]=g;c[D>>2]=u}else{b=i;f=c[a+(h+16)>>2]|0;t=c[a+(h|4)>>2]|0;do{if((t|0)==(b|0)){p=a+(h+12)|0;v=c[p>>2]|0;if((v|0)==0){m=a+(h+8)|0;k=c[m>>2]|0;if((k|0)==0){E=0;break}else{F=k;G=m}}else{F=v;G=p}while(1){p=F+20|0;v=c[p>>2]|0;if((v|0)!=0){F=v;G=p;continue}p=F+16|0;v=c[p>>2]|0;if((v|0)==0){break}else{F=v;G=p}}if(G>>>0<(c[25178]|0)>>>0){na()}else{c[G>>2]=0;E=F;break}}else{p=c[a+h>>2]|0;if(p>>>0<(c[25178]|0)>>>0){na()}v=p+12|0;if((c[v>>2]|0)!=(b|0)){na()}m=t+8|0;if((c[m>>2]|0)==(b|0)){c[v>>2]=t;c[m>>2]=p;E=t;break}else{na()}}}while(0);if((f|0)==0){break}t=c[a+(h+20)>>2]|0;u=101e3+(t<<2)|0;do{if((b|0)==(c[u>>2]|0)){c[u>>2]=E;if((E|0)!=0){break}c[25175]=c[25175]&~(1<<t);break b}else{if(f>>>0<(c[25178]|0)>>>0){na()}g=f+16|0;if((c[g>>2]|0)==(b|0)){c[g>>2]=E}else{c[f+20>>2]=E}if((E|0)==0){break b}}}while(0);if(E>>>0<(c[25178]|0)>>>0){na()}c[E+24>>2]=f;b=c[a+(h+8)>>2]|0;do{if((b|0)!=0){if(b>>>0<(c[25178]|0)>>>0){na()}else{c[E+16>>2]=b;c[b+24>>2]=E;break}}}while(0);b=c[a+(h+12)>>2]|0;if((b|0)==0){break}if(b>>>0<(c[25178]|0)>>>0){na()}else{c[E+20>>2]=b;c[b+24>>2]=E;break}}}while(0);c[q+4>>2]=B|1;c[d+B>>2]=B;if((q|0)!=(c[25179]|0)){H=B;break}c[25176]=B;return}else{c[A>>2]=e&-2;c[q+4>>2]=r|1;c[d+r>>2]=r;H=r}}while(0);r=H>>>3;if(H>>>0<256>>>0){d=r<<1;e=100736+(d<<2)|0;A=c[25174]|0;E=1<<r;do{if((A&E|0)==0){c[25174]=A|E;I=e;J=100736+(d+2<<2)|0}else{r=100736+(d+2<<2)|0;h=c[r>>2]|0;if(!(h>>>0<(c[25178]|0)>>>0)){I=h;J=r;break}na()}}while(0);c[J>>2]=q;c[I+12>>2]=q;c[q+8>>2]=I;c[q+12>>2]=e;return}e=q;I=H>>>8;do{if((I|0)==0){K=0}else{if(H>>>0>16777215>>>0){K=31;break}J=(I+1048320|0)>>>16&8;d=I<<J;E=(d+520192|0)>>>16&4;A=d<<E;d=(A+245760|0)>>>16&2;r=14-(E|J|d)+(A<<d>>>15)|0;K=H>>>((r+7|0)>>>0)&1|r<<1}}while(0);I=101e3+(K<<2)|0;c[q+28>>2]=K;c[q+20>>2]=0;c[q+16>>2]=0;r=c[25175]|0;d=1<<K;do{if((r&d|0)==0){c[25175]=r|d;c[I>>2]=e;c[q+24>>2]=I;c[q+12>>2]=q;c[q+8>>2]=q}else{if((K|0)==31){L=0}else{L=25-(K>>>1)|0}A=H<<L;J=c[I>>2]|0;while(1){if((c[J+4>>2]&-8|0)==(H|0)){break}M=J+16+(A>>>31<<2)|0;E=c[M>>2]|0;if((E|0)==0){N=129;break}else{A=A<<1;J=E}}if((N|0)==129){if(M>>>0<(c[25178]|0)>>>0){na()}else{c[M>>2]=e;c[q+24>>2]=J;c[q+12>>2]=q;c[q+8>>2]=q;break}}A=J+8|0;B=c[A>>2]|0;E=c[25178]|0;if(J>>>0<E>>>0){na()}if(B>>>0<E>>>0){na()}else{c[B+12>>2]=e;c[A>>2]=e;c[q+8>>2]=B;c[q+12>>2]=J;c[q+24>>2]=0;break}}}while(0);q=(c[25182]|0)-1|0;c[25182]=q;if((q|0)==0){O=101152}else{return}while(1){q=c[O>>2]|0;if((q|0)==0){break}else{O=q+8|0}}c[25182]=-1;return}function ib(a,b){a=a|0;b=b|0;var d=0,e=0;do{if((a|0)==0){d=0}else{e=Z(b,a)|0;if(!((b|a)>>>0>65535>>>0)){d=e;break}d=((e>>>0)/(a>>>0)|0|0)==(b|0)?e:-1}}while(0);b=gb(d)|0;if((b|0)==0){return b|0}if((c[b-4>>2]&3|0)==0){return b|0}jb(b|0,0,d|0)|0;return b|0}function jb(b,d,e){b=b|0;d=d|0;e=e|0;var f=0,g=0,h=0,i=0;f=b+e|0;if((e|0)>=20){d=d&255;g=b&3;h=d|d<<8|d<<16|d<<24;i=f&~3;if(g){g=b+4-g|0;while((b|0)<(g|0)){a[b]=d;b=b+1|0}}while((b|0)<(i|0)){c[b>>2]=h;b=b+4|0}}while((b|0)<(f|0)){a[b]=d;b=b+1|0}return b-e|0}function kb(b,d,e){b=b|0;d=d|0;e=e|0;var f=0;if((e|0)>=4096)return pa(b|0,d|0,e|0)|0;f=b|0;if((b&3)==(d&3)){while(b&3){if((e|0)==0)return f|0;a[b]=a[d]|0;b=b+1|0;d=d+1|0;e=e-1|0}while((e|0)>=4){c[b>>2]=c[d>>2];b=b+4|0;d=d+4|0;e=e-4|0}}while((e|0)>0){a[b]=a[d]|0;b=b+1|0;d=d+1|0;e=e-1|0}return f|0}function lb(b,c,d){b=b|0;c=c|0;d=d|0;var e=0;if((c|0)<(b|0)&(b|0)<(c+d|0)){e=b;c=c+d|0;b=b+d|0;while((d|0)>0){b=b-1|0;c=c-1|0;d=d-1|0;a[b]=a[c]|0}b=e}else{kb(b,c,d)|0}return b|0}function mb(b){b=b|0;var c=0;c=b;while(a[c]|0){c=c+1|0}return c-b|0}function nb(a,b){a=a|0;b=b|0;return sa[a&1](b|0)|0}function ob(a){a=a|0;ta[a&1]()}function pb(a,b,c){a=a|0;b=b|0;c=c|0;return ua[a&15](b|0,c|0)|0}function qb(a,b){a=a|0;b=b|0;va[a&7](b|0)}function rb(a){a=a|0;_(0);return 0}function sb(){_(1)}function tb(a,b){a=a|0;b=b|0;_(2);return 0}function ub(a){a=a|0;_(3)}




// EMSCRIPTEN_END_FUNCS
var sa=[rb,rb];var ta=[sb,sb];var ua=[tb,tb,Ya,tb,Xa,tb,db,tb,$a,tb,Ua,tb,Za,tb,_a,tb];var va=[ub,ub,eb,ub,Va,ub,ab,ub];return{_malloc:gb,_strlen:mb,_free:hb,_src_reset:Oa,_memmove:lb,_src_delete:Pa,_memset:jb,_src_strerror:Sa,_src_set_ratio:Ra,_memcpy:kb,_src_new:Na,_src_js_process:fb,_calloc:ib,runPostSets:Ma,stackAlloc:wa,stackSave:xa,stackRestore:ya,setThrew:za,setTempRet0:Ca,setTempRet1:Da,setTempRet2:Ea,setTempRet3:Fa,setTempRet4:Ga,setTempRet5:Ha,setTempRet6:Ia,setTempRet7:Ja,setTempRet8:Ka,setTempRet9:La,dynCall_ii:nb,dynCall_v:ob,dynCall_iii:pb,dynCall_vi:qb}})


// EMSCRIPTEN_END_ASM
({ "Math": Math, "Int8Array": Int8Array, "Int16Array": Int16Array, "Int32Array": Int32Array, "Uint8Array": Uint8Array, "Uint16Array": Uint16Array, "Uint32Array": Uint32Array, "Float32Array": Float32Array, "Float64Array": Float64Array }, { "abort": abort, "assert": assert, "asmPrintInt": asmPrintInt, "asmPrintFloat": asmPrintFloat, "min": Math_min, "invoke_ii": invoke_ii, "invoke_v": invoke_v, "invoke_iii": invoke_iii, "invoke_vi": invoke_vi, "_sysconf": _sysconf, "_sbrk": _sbrk, "_fabs": _fabs, "___setErrNo": ___setErrNo, "_rint": _rint, "___errno_location": ___errno_location, "_abort": _abort, "_time": _time, "_emscripten_memcpy_big": _emscripten_memcpy_big, "_fflush": _fflush, "STACKTOP": STACKTOP, "STACK_MAX": STACK_MAX, "tempDoublePtr": tempDoublePtr, "ABORT": ABORT, "NaN": NaN, "Infinity": Infinity }, buffer);
var _malloc = Module["_malloc"] = asm["_malloc"];
var _strlen = Module["_strlen"] = asm["_strlen"];
var _free = Module["_free"] = asm["_free"];
var _src_reset = Module["_src_reset"] = asm["_src_reset"];
var _memmove = Module["_memmove"] = asm["_memmove"];
var _src_delete = Module["_src_delete"] = asm["_src_delete"];
var _memset = Module["_memset"] = asm["_memset"];
var _src_strerror = Module["_src_strerror"] = asm["_src_strerror"];
var _src_set_ratio = Module["_src_set_ratio"] = asm["_src_set_ratio"];
var _memcpy = Module["_memcpy"] = asm["_memcpy"];
var _src_new = Module["_src_new"] = asm["_src_new"];
var _src_js_process = Module["_src_js_process"] = asm["_src_js_process"];
var _calloc = Module["_calloc"] = asm["_calloc"];
var runPostSets = Module["runPostSets"] = asm["runPostSets"];
var dynCall_ii = Module["dynCall_ii"] = asm["dynCall_ii"];
var dynCall_v = Module["dynCall_v"] = asm["dynCall_v"];
var dynCall_iii = Module["dynCall_iii"] = asm["dynCall_iii"];
var dynCall_vi = Module["dynCall_vi"] = asm["dynCall_vi"];

Runtime.stackAlloc = function(size) { return asm['stackAlloc'](size) };
Runtime.stackSave = function() { return asm['stackSave']() };
Runtime.stackRestore = function(top) { asm['stackRestore'](top) };

// Warning: printing of i64 values may be slightly rounded! No deep i64 math used, so precise i64 code not included
var i64Math = null;

// === Auto-generated postamble setup entry stuff ===

if (memoryInitializer) {
  if (ENVIRONMENT_IS_NODE || ENVIRONMENT_IS_SHELL) {
    var data = Module['readBinary'](memoryInitializer);
    HEAPU8.set(data, STATIC_BASE);
  } else {
    addRunDependency('memory initializer');
    Browser.asyncLoad(memoryInitializer, function(data) {
      HEAPU8.set(data, STATIC_BASE);
      removeRunDependency('memory initializer');
    }, function(data) {
      throw 'could not load memory initializer ' + memoryInitializer;
    });
  }
}

function ExitStatus(status) {
  this.name = "ExitStatus";
  this.message = "Program terminated with exit(" + status + ")";
  this.status = status;
};
ExitStatus.prototype = new Error();
ExitStatus.prototype.constructor = ExitStatus;

var initialStackTop;
var preloadStartTime = null;
var calledMain = false;

dependenciesFulfilled = function runCaller() {
  // If run has never been called, and we should call run (INVOKE_RUN is true, and Module.noInitialRun is not false)
  if (!Module['calledRun'] && shouldRunNow) run();
  if (!Module['calledRun']) dependenciesFulfilled = runCaller; // try this again later, after new deps are fulfilled
}

Module['callMain'] = Module.callMain = function callMain(args) {
  assert(runDependencies == 0, 'cannot call main when async dependencies remain! (listen on __ATMAIN__)');
  assert(__ATPRERUN__.length == 0, 'cannot call main when preRun functions remain to be called');

  args = args || [];

  if (ENVIRONMENT_IS_WEB && preloadStartTime !== null) {
    Module.printErr('preload time: ' + (Date.now() - preloadStartTime) + ' ms');
  }

  ensureInitRuntime();

  var argc = args.length+1;
  function pad() {
    for (var i = 0; i < 4-1; i++) {
      argv.push(0);
    }
  }
  var argv = [allocate(intArrayFromString("/bin/this.program"), 'i8', ALLOC_NORMAL) ];
  pad();
  for (var i = 0; i < argc-1; i = i + 1) {
    argv.push(allocate(intArrayFromString(args[i]), 'i8', ALLOC_NORMAL));
    pad();
  }
  argv.push(0);
  argv = allocate(argv, 'i32', ALLOC_NORMAL);

  initialStackTop = STACKTOP;

  try {

    var ret = Module['_main'](argc, argv, 0);


    // if we're not running an evented main loop, it's time to exit
    if (!Module['noExitRuntime']) {
      exit(ret);
    }
  }
  catch(e) {
    if (e instanceof ExitStatus) {
      // exit() throws this once it's done to make sure execution
      // has been stopped completely
      return;
    } else if (e == 'SimulateInfiniteLoop') {
      // running an evented main loop, don't immediately exit
      Module['noExitRuntime'] = true;
      return;
    } else {
      if (e && typeof e === 'object' && e.stack) Module.printErr('exception thrown: ' + [e, e.stack]);
      throw e;
    }
  } finally {
    calledMain = true;
  }
}




function run(args) {
  args = args || Module['arguments'];

  if (preloadStartTime === null) preloadStartTime = Date.now();

  if (runDependencies > 0) {
    Module.printErr('run() called, but dependencies remain, so not running');
    return;
  }

  preRun();

  if (runDependencies > 0) return; // a preRun added a dependency, run will be called later
  if (Module['calledRun']) return; // run may have just been called through dependencies being fulfilled just in this very frame

  function doRun() {
    if (Module['calledRun']) return; // run may have just been called while the async setStatus time below was happening
    Module['calledRun'] = true;

    ensureInitRuntime();

    preMain();

    if (Module['_main'] && shouldRunNow) {
      Module['callMain'](args);
    }

    postRun();
  }

  if (Module['setStatus']) {
    Module['setStatus']('Running...');
    setTimeout(function() {
      setTimeout(function() {
        Module['setStatus']('');
      }, 1);
      if (!ABORT) doRun();
    }, 1);
  } else {
    doRun();
  }
}
Module['run'] = Module.run = run;

function exit(status) {
  ABORT = true;
  EXITSTATUS = status;
  STACKTOP = initialStackTop;

  // exit the runtime
  exitRuntime();

  // TODO We should handle this differently based on environment.
  // In the browser, the best we can do is throw an exception
  // to halt execution, but in node we could process.exit and
  // I'd imagine SM shell would have something equivalent.
  // This would let us set a proper exit status (which
  // would be great for checking test exit statuses).
  // https://github.com/kripken/emscripten/issues/1371

  // throw an exception to halt the current execution
  throw new ExitStatus(status);
}
Module['exit'] = Module.exit = exit;

function abort(text) {
  if (text) {
    Module.print(text);
    Module.printErr(text);
  }

  ABORT = true;
  EXITSTATUS = 1;

  throw 'abort() at ' + stackTrace();
}
Module['abort'] = Module.abort = abort;

// {{PRE_RUN_ADDITIONS}}

if (Module['preInit']) {
  if (typeof Module['preInit'] == 'function') Module['preInit'] = [Module['preInit']];
  while (Module['preInit'].length > 0) {
    Module['preInit'].pop()();
  }
}

// shouldRunNow refers to calling main(), not run().
var shouldRunNow = true;
if (Module['noInitialRun']) {
  shouldRunNow = false;
}


run();

// {{POST_RUN_ADDITIONS}}






// {{MODULE_ADDITIONS}}





// libsamplerate function wrappers

var isNode = typeof process === "object" && typeof require === "function";

var float32Len = Module.HEAPF32.BYTES_PER_ELEMENT;
var int32Len = Module.HEAP32.BYTES_PER_ELEMENT;
var int16Len = Module.HEAP16.BYTES_PER_ELEMENT;
var int8Len = Module.HEAP8.BYTES_PER_ELEMENT;

function Samplerate(args) {
  var type = args.type || Samplerate.LINEAR;
  var _err = _malloc(int16Len);
  var err;

  this._src = _src_new(type, 1, err);
  if (this._src === 0) {
    err = getValue(_err, "i16");
    _free(_err);
    this._onError(err);
    return;
  }

  _free(_err);

  this._written = _malloc(int32Len);
  this._used = _malloc(int32Len);

  return this;
};

Samplerate.MEDIUM_QUALITY = 1;
Samplerate.FASTEST = 2;
Samplerate.ZERO_ORDER_HOLD = 3;
Samplerate.LINEAR = 4;

Samplerate.prototype._onError = function (e) {
  throw _src_strerror(e);
};

Samplerate.prototype.close = function () {
  if (!this._src) {
    throw "closed";
  }

  _free(this._written);
  _free(this._used);
  _src_delete(this._src);
  this._src = this._written = this._used = null;
  return;
};

Samplerate.prototype.reset = function () {
  if (!this._src) {
    throw "closed";
  }

  _src_reset(this._src);
  return;
};

Samplerate.prototype.setRatio = function (ratio) {
  if (!this._src) {
    throw "closed";
  }

  var err = _src_set_ratio(this._src, ratio);
  if (err !== 0) {
    this._onError(err);
    return;
  } 

  return;
};

function clip(x) {
  return (x > 1 ? 1 : (x < -1 ? -1 : x));
}

function convertInt16(buf) {
  var samples = buf.length;
  var ret = new Float32(samples);

  var i;

  for (i=0;i<samples;i++) {
    ret[i] = clip(parseFloat(buf[i]) / 32767.0);
  }
  return ret;
}

function convertFloat32(buf) {
  var samples = buf.length;
  var ret = new Int16Array(samples);

  var i;
  for (i=0;i<samples;i++) {
    ret[i] = parseInt(clip(buf[i]) * 32767);
  }
  return ret;
}

Samplerate.prototype.process = function (args) {
  var data = args.data;
  var ratio = args.ratio;
  var last = args.last || false;

  if (data instanceof Int16Array) {
    data = convertInt16(data);
  }

  var inputSamples = data.length;
  var outputSamples = Math.ceil(inputSamples*ratio);
  
  var _input  = _malloc(inputSamples*float32Len);
  var _output = _malloc(outputSamples*float32Len);

  var input = Module.HEAPF32.subarray(_input/float32Len, _input/float32Len+inputSamples);
  var output = Module.HEAPF32.subarray(_output/float32Len, _output/float32Len+outputSamples);

  input.set(data);

  last = last ? 1 : 0;
  var err = _src_js_process(this._src, 
                            _input, inputSamples,
                            _output, outputSamples,
                            ratio, last, 
                            this._used,
                            this._written); 

  _free(_input);
  if (err) {
    _free(_output);
    this._onError(err);
    return;
  }

  var written = getValue(this._written, "i32");
  var result = new Float32Array(written);
  result.set(output.subarray(0, written));

  _free(_output);

  if (data instanceof Int16Array) {
    result = convertFloat32(result);
  }

  var ret = {
    data: result,
    used: getValue(this._used, "i32")
  };
  return ret;
};

if (isNode) {
  module.exports = Samplerate;
}

return Samplerate;

}).call(context)})();


