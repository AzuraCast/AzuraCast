/*
 * NchanSubscriber
 * usage: var sub = new NchanSubscriber(url, opt);
 * 
 * opt = {
 *   subscriber: 'longpoll', 'eventsource', or 'websocket',
 *     //or an array of the above indicating subscriber type preference
 *   reconnect: undefined or 'session' or 'persist'
 *     //if the HTML5 sessionStore or localStore should be used to resume
 *     //connections interrupted by a page load
 *   shared: true or undefined
 *     //share connection to same subscriber url between browser 
 *     //windows and tabs using localStorage. In shared mode, 
 *     //only 1 running subscriber is allowed per url per window/tab.
 * }
 * 
 * sub.on("message", function(message, message_metadata) {
 *   // message is a string
 *   // message_metadata may contain 'id' and 'content-type'
 * });
 * 
 * sub.on('connect', function(evt) {
 *   //fired when first connected. 
 * });
 * 
 * sub.on('disconnect', function(evt) {
 *   // when disconnected.
 * });
 * 
 * sub.on('error', function(code, message) {
 *   //error callback. not sure about the parameters yet
 * });
 * 
 * sub.reconnect; // should subscriber try to reconnect? true by default.
 * sub.reconnectTimeout; //how long to wait to reconnect? does not apply to EventSource, which reconnects on its own.
 * sub.lastMessageId; //last message id. useful for resuming a connection without loss or repetition.
 * 
 * sub.start(); // begin (or resume) subscribing
 * sub.stop(); // stop subscriber. do not reconnect.
 */

//Thanks Darren Whitlen ( @prawnsalad ) for your feedback


;(function (global, moduleName, factory) { // eslint-disable-line
  "use strict";
  /* eslint-disable no-undef */
  var newModule = factory(global);
  if (typeof module === "object" && module != null && module.exports) {
    module.exports = newModule;
  } else if (typeof define === "function" && define.amd) {
    define(function () { return newModule; });
  } else {
    global[moduleName] = newModule;
  }
  /* eslint-enable no-undef */
})(typeof window !== "undefined" ? window : this, "NchanSubscriber", function factory(global, undefined) { // eslint-disable-line

// https://github.com/yanatan16/nanoajax
var nanoajax={};
(function(){var e=["responseType","withCredentials","timeout","onprogress"];nanoajax.ajax=function(r,o){var a=r.headers||{},u=r.body,s=r.method||(u?"POST":"GET"),i=false;var f=t(r.cors);function l(e,t){return function(){if(!i){o(f.status===undefined?e:f.status,f.status===0?"Error":f.response||f.responseText||t,f);i=true}}}f.open(s,r.url,true);var d=f.onload=l(200);f.onreadystatechange=function(){if(f.readyState===4)d()};f.onerror=l(null,"Error");f.ontimeout=l(null,"Timeout");f.onabort=l(null,"Abort");if(u){n(a,"X-Requested-With","XMLHttpRequest");if(!global.FormData||!(u instanceof global.FormData)){n(a,"Content-Type","application/x-www-form-urlencoded")}}for(var p=0,c=e.length,g;p<c;p++){g=e[p];if(r[g]!==undefined)f[g]=r[g]}for(var g in a)f.setRequestHeader(g,a[g]);f.send(u);return f};function t(e){if(e&&global.XDomainRequest&&!/MSIE 1/.test(navigator.userAgent))return new XDomainRequest;if(global.XMLHttpRequest)return new XMLHttpRequest}function n(e,t,n){e[t]=e[t]||n}})(); // eslint-disable-line


 // https://github.com/component/emitter
function Emitter(t){return t?mixin(t):void 0}function mixin(t){for(var e in Emitter.prototype)t[e]=Emitter.prototype[e];return t}Emitter.prototype.on=Emitter.prototype.addEventListener=function(t,e){return this._callbacks=this._callbacks||{},(this._callbacks["$"+t]=this._callbacks["$"+t]||[]).push(e),this},Emitter.prototype.once=function(t,e){function i(){this.off(t,i),e.apply(this,arguments)}return i.fn=e,this.on(t,i),this},Emitter.prototype.off=Emitter.prototype.removeListener=Emitter.prototype.removeAllListeners=Emitter.prototype.removeEventListener=function(t,e){if(this._callbacks=this._callbacks||{},0==arguments.length)return this._callbacks={},this;var i=this._callbacks["$"+t];if(!i)return this;if(1==arguments.length)return delete this._callbacks["$"+t],this;for(var r,s=0;s<i.length;s++)if(r=i[s],r===e||r.fn===e){i.splice(s,1);break}return this},Emitter.prototype.emit=function(t){this._callbacks=this._callbacks||{};var e=[].slice.call(arguments,1),i=this._callbacks["$"+t];if(i){i=i.slice(0);for(var r=0,s=i.length;s>r;++r)i[r].apply(this,e)}return this},Emitter.prototype.listeners=function(t){return this._callbacks=this._callbacks||{},this._callbacks["$"+t]||[]},Emitter.prototype.hasListeners=function(t){return!!this.listeners(t).length};// eslint-disable-line

var ughbind = (Function.prototype.bind
  ? function ughbind(fn, thisObj) {
    return fn.bind(thisObj);
  }
  : function ughbind(fn, thisObj) {
    return function() {
      fn.apply(thisObj, arguments);
    };
  }
);

var sharedSubscriberTable={};

"use strict"; 
function NchanSubscriber(url, opt) {
  if(this === window) {
    throw "use 'new NchanSubscriber(...)' to initialize";
  }
  
  this.url = url;
  opt = opt || {};
  
  //which transport should i use?
  if(typeof opt === "string") {
    opt = {subscriber: opt}; 
  }
  if(opt.transport && !opt.subscriber) {
    opt.subscriber = opt.transport;
  }
  if(typeof opt.subscriber === "string") {
    opt.subscriber = [ opt.subscriber ];
  }
  this.desiredTransport = opt.subscriber;
  
  if(opt.shared) {
    if (!("localStorage" in global)) {
      throw "localStorage unavailable for use in shared NchanSubscriber";
    }
    
    var pre = "NchanSubscriber:" + this.url + ":shared:";
    var sharedKey = function(key) { return pre + key; };
    var localStorage = global.localStorage;
    this.shared = {
      id: "" + Math.random() + Math.random(),
      key: sharedKey,
      get: function(key) {
        return localStorage.getItem(sharedKey(key));
      },
      set: function(key, val) {
        return localStorage.setItem(sharedKey(key), val);
      },
      setWithId: ughbind(function(key, val) {
        return this.shared.set(key, "##" + this.shared.id + ":" + val);
      }, this),
      getWithId: ughbind(function(key) {
        return this.shared.stripIdFromVal(this.shared.get(key));
      }, this),
      stripIdFromVal: function(val) {
        if(!val) {
          return val;
        }
        var sep = val.indexOf(":");
        if(val[0]!=val[1] || val[0]!="#" || !sep) {
          //throw "not an event value with id";
          return val; //for backwards-compatibility
        }
        return val.substring(sep+1, val.length);
      },
      remove: function(key) {
        return localStorage.removeItem(sharedKey(key));
      },
      matchEventKey: ughbind(function(ev, key) {
        if(ev.storageArea && ev.storageArea != localStorage){
          return false;
        }
        return ev.key == sharedKey(key);
      }, this),
      matchEventKeyWithId: ughbind(function(ev, key) {
        if(this.shared.matchEventKey(ev, key)) {
          var val = ev.newValue;
          var sep = val.indexOf(":");
          if(val[0]!=val[1] || val[0]!="#" || !sep) {
            //throw "not an event value with id";
            return true; //for backwards-compatibility
          }
          var id = val.substring(2, sep);
          return (id != this.shared.id); //ignore own events (accomodations for IE. Fuckin' IE, even after all these years...)
        }
        else {
          return false;
        }
      }, this),
      setRole: ughbind(function(role) {
        //console.log(this.url, "set shared role to ", role);
        if(role == "master" && this.shared.role != "master") {
          var now = new Date().getTime()/1000;
          this.shared.setWithId("master:created", now);
          this.shared.setWithId("master:lastSeen", now);
        }
        if(role == "slave" && !this.lastMessageId) {
          this.lastMessageId = this.shared.get("msg:id");
        }
        this.shared.role = role;
        return this;
      }, this),
      
      demoteToSlave: ughbind(function() {
        //console.log("demote to slave");
        if(this.shared.role != "master") {
          throw "can't demote non-master to slave";
        }
        if(this.running) {
          this.stop();
          this.shared.setRole("slave");
          this.initializeTransport();
          this.start();
        }
        else {
          this.initializeTransport();
        }
      }, this),
      
      maybePromoteToMaster: ughbind(function() {
        if(!(this.running || this.starting)) {
          //console.log(this.url, "stopped Subscriber won't be promoted to master");
          return this;
        }
        if(this.shared.maybePromotingToMaster) {
          //console.log(this.url, " already maybePromotingToMaster");
          return;
        }
        this.shared.maybePromotingToMaster = true;
        
        //console.log(this.url, "maybe promote to master");
        var processRoll;
        
        var lotteryRoundDuration = 2000;
        var currentContenders = 0;
        
        //roll the dice
        var roll = Math.random();
        var bestRoll = roll;
        
        var checkRollInterval;
        var checkRoll = ughbind(function(dontProcess) {
          var latestSharedRollTime = parseFloat(this.shared.getWithId("lotteryTime"));
          var latestSharedRoll = parseFloat(this.shared.getWithId("lottery"));
          var notStale = !latestSharedRollTime || (latestSharedRollTime > (new Date().getTime() - lotteryRoundDuration * 2));
          if(notStale && latestSharedRoll && (!bestRoll || latestSharedRoll > bestRoll)) {
            bestRoll = latestSharedRoll;
          }
          if(!dontProcess) {
            processRoll();
          }
        }, this);
        
        checkRoll(true);
        this.shared.setWithId("lottery", roll);
        this.shared.setWithId("lotteryTime", new Date().getTime() / 1000);
        
        var rollCallback = ughbind(function(ev) {
          if(this.shared.matchEventKeyWithId(ev, "lottery") && ev.newValue) {
            currentContenders += 1;
            var newVal = parseFloat(this.shared.stripIdFromVal(ev.newValue));
            var oldVal = parseFloat(this.shared.stripIdFromVal(ev.oldValue));
            if(oldVal > newVal) {
              this.shared.setWithId("lottery", oldVal);
            }
            
            if(!bestRoll || newVal >= bestRoll) {
              //console.log("new bestRoll", newVal);
              bestRoll = newVal;
            }
          }
        }, this);
        global.addEventListener("storage", rollCallback);
        
        var finish = ughbind(function() {
          //console.log("finish");
          this.shared.maybePromotingToMaster = false;
          //console.log(this.url, this.shared.role);
          global.removeEventListener("storage", rollCallback);
          if(checkRollInterval) {
            clearInterval(checkRollInterval);
          }
          if(this.shared && this.shared.role == "master") {
            this.shared.remove("lottery");
            this.shared.remove("lotteryTime");
          }
          if(this.running) {
            this.stop();
            this.initializeTransport();
            this.start();
          }
          else {
            this.initializeTransport();
            if(this.starting) {
              this.start();
            }
          }
        }, this);
        
        processRoll = ughbind(function() {
          //console.log("roll, bestroll", roll, bestRoll);
          if(roll < bestRoll) {
            //console.log(this.url, "loser");
            this.shared.setRole("slave");
            finish();
          }
          else if(roll >= bestRoll) {
            //var now = new Date().getTime() / 1000;
            //var lotteryTime = parseFloat(this.shared.getWithId("lotteryTime"));
            //console.log(lotteryTime, now - lotteryRoundDuration/1000);
            if(currentContenders == 0) {
              //console.log("winner, no more contenders!");
              this.shared.setRole("master");
              finish();
            }
            else {
              //console.log("winning, but have contenders", currentContenders);
              currentContenders = 0;
            }
          }
        }, this);
        
        checkRollInterval = global.setInterval(checkRoll, lotteryRoundDuration);
      }, this),
      masterCheckInterval: 10000
    };
  }
  
  this.lastMessageId = opt.id || opt.msgId;
  this.reconnect = typeof opt.reconnect == "undefined" ? true : opt.reconnect;
  this.reconnectTimeout = opt.reconnectTimeout || 1000;
  
  
  var saveConnectionState;
  if(!opt.reconnect) {
    saveConnectionState = function() {};
  }
  else {
    var index = "NchanSubscriber:" + url + ":lastMessageId";
    var storage;
    if(opt.reconnect == "persist") {
      storage = ("localStorage" in global) && global.localStorage;
      if(!storage)
        throw "can't use reconnect: 'persist' option: localStorage not available";
    }
    else if(opt.reconnect == "session") {
      storage = ("sessionStorage" in global) && global.sessionStorage;
      if(!storage)
        throw "can't use reconnect: 'session' option: sessionStorage not available";
    }
    else {
      throw "invalid 'reconnect' option value " + opt.reconnect;
    }
    saveConnectionState = ughbind(function(msgid) {
      if(this.shared && this.shared.role == "slave") return;
      storage.setItem(index, msgid);
    }, this);
    this.lastMessageId = storage.getItem(index);
  }
  
  var onUnloadEvent = ughbind(function() {
    if(this.running) {
      this.stop();
    }
    if(this.shared && this.shared.role == "master") {
      this.shared.setWithId('status', "disconnected");
    }
  }, this);
  global.addEventListener('beforeunload', onUnloadEvent, false);
  // swap `beforeunload` to `unload` after DOM is loaded
  global.addEventListener('DOMContentLoaded', function() {
    global.removeEventListener('beforeunload', onUnloadEvent, false);
    global.addEventListener('unload', onUnloadEvent, false);
  }, false);
  
  
  var notifySharedSubscribers;
  if(this.shared) {
    notifySharedSubscribers = ughbind(function(name, data) {
      if(this.shared.role != "master") {
        return;
      }
      
      if(name == "message") {
        this.shared.set("msg:id", data[1] && data[1].id || "");
        this.shared.set("msg:content-type", data[1] && data[1]["content-type"] || "");
        this.shared.set("msg", data[0]);
      }
      else if(name == "error") {
        //TODO 
      }
      else if(name == "connecting") {
        this.shared.setWithId("status", "connecting");
      }
      else if(name == "connect") {
        this.shared.setWithId("status", "connected");
      }
      else if(name == "reconnect") {
        this.shared.setWithId("status", "reconnecting");
      }
      else if(name == "disconnect") {
        this.shared.setWithId("status", "disconnected");
      }
    }, this);
  }
  else {
    notifySharedSubscribers = function(){};
  }
  
  var restartTimeoutIndex;
  var stopHandler = ughbind(function() {
    if(!restartTimeoutIndex && this.running && this.reconnect && !this.transport.reconnecting && !this.transport.doNotReconnect) {
      //console.log("stopHAndler reconnect plz", this.running, this.reconnect);
      notifySharedSubscribers("reconnect");
      restartTimeoutIndex = global.setTimeout(ughbind(function() {
        restartTimeoutIndex = null;
        this.stop();
        this.start();
      }, this), this.reconnectTimeout);
    }
    else {
      notifySharedSubscribers("disconnect");
    }
  }, this);
  
  this.on("message", function msg(msg, meta) {
    this.lastMessageId=meta.id;
    if(meta.id) {
      saveConnectionState(meta.id);
    }
    notifySharedSubscribers("message", [msg, meta]);
    //console.log(msg, meta);
  });
  this.on("error", function fail(code, text) {
    stopHandler(code, text);
    notifySharedSubscribers("error", [code, text]);
    //console.log("failure", code, text);
  });
  this.on("connect", function() {
    this.connected = true;
    notifySharedSubscribers("connect");
  });
  this.on("__disconnect", function fail(code, text) {
    this.connected = false;
    this.emit("disconnect", code, text);
    stopHandler(code, text);
    //console.log("__disconnect", code, text);
  });
}

Emitter(NchanSubscriber.prototype);

NchanSubscriber.prototype.initializeTransport = function(possibleTransports) {
  if(possibleTransports) {
    this.desiredTransport = possibleTransports;
  }
  if(this.shared && this.shared.role == "slave") {
    this.transport = new this.SubscriberClass["__slave"](ughbind(this.emit, this)); //try it
  }
  else {
    var tryInitializeTransport = ughbind(function(name) {
      if(!this.SubscriberClass[name]) {
        throw "unknown subscriber type " + name;
      }
      try {
        this.transport = new this.SubscriberClass[name](ughbind(this.emit, this)); //try it
        return this.transport;
      } catch(err) { /*meh...*/ }
    }, this);
    
    var i;
    if(this.desiredTransport) {
      for(i=0; i<this.desiredTransport.length; i++) {
        if(tryInitializeTransport(this.desiredTransport[i])) {
          break;
        }
      }
    }
    else {
      for(i in this.SubscriberClass) {
        if (this.SubscriberClass.hasOwnProperty(i) && i[0] != "_" && tryInitializeTransport(i)) {
          break;
        }
      }
    }
  }
  if(! this.transport) {
    throw "can't use any transport type";
  }
};

var storageEventListener;

NchanSubscriber.prototype.start = function() {
  if(this.running)
    throw "Can't start NchanSubscriber, it's already started.";
  
  this.starting = true;
  
  if(this.shared) {
    if(sharedSubscriberTable[this.url] && sharedSubscriberTable[this.url] != this) {
      throw "Only 1 shared subscriber allowed per url per window/tab.";
    }
    sharedSubscriberTable[this.url] = this;
    
    if(!this.shared.role) {
      var status = this.shared.getWithId("status");
      storageEventListener = ughbind(function(ev) {
        if(this.shared.matchEventKeyWithId(ev, "status")) {
          var newValue = this.shared.stripIdFromVal(ev.newValue);
          if(newValue == "disconnected") {
            if(this.shared.role == "slave") {
              //play the promotion lottery
              //console.log(this.url, "status changed to disconnected, maybepromotetomaster", ev.newValue, ev.oldValue);
              this.shared.maybePromoteToMaster();
            }
            else if(this.shared.role == "master") {
              //do nothing
              //console.log(this.url, "current role is master, do nothing?...");
            }
          }
        }
        else if(this.shared.role == "master" && this.shared.matchEventKeyWithId(ev, "master:created") && ev.newValue) {
          //a new master has arrived. demote to slave.
          this.shared.demoteToSlave();
        }
      }, this);
      global.addEventListener("storage", storageEventListener);
      if(status == "disconnected") {
        //console.log(this.url, "status == disconnected, maybepromotetomaster");
        this.shared.maybePromoteToMaster();
      }
      else {
        this.shared.setRole(status ? "slave" : "master");
        this.initializeTransport();
      }
    }
    
    if(this.shared.role == "master") {
      this.shared.setWithId("status", "connecting");
      this.transport.listen(this.url, this.lastMessageId);
      this.running = true;
      delete this.starting;
      
      //master checkin interval
      this.shared.masterIntervalCheckID = global.setInterval(ughbind(function() {
        this.shared.setWithId("master:lastSeen", new Date().getTime() / 1000);
      }, this), this.shared.masterCheckInterval * 0.8);
    }
    else if(this.shared.role == "slave") {
      this.transport.listen(this.url, this.shared);
      this.running = true;
      delete this.starting;
      
      //slave check if master is around
      this.shared.masterIntervalCheckID = global.setInterval(ughbind(function() {
        var lastCheckin = parseFloat(this.shared.getWithId("master:lastSeen"));
        if(!lastCheckin || lastCheckin < (new Date().getTime() / 1000) - this.shared.masterCheckInterval / 1000) {
          //master hasn't checked in for too long. assume it's gone.
          this.shared.maybePromoteToMaster();
        }
      }, this), this.shared.masterCheckInterval);
    }
  }
  else {
    if(!this.transport) {
      this.initializeTransport();
    }
    this.transport.listen(this.url, this.lastMessageId);
    this.running = true;
    delete this.starting;
  }
  return this;
};

NchanSubscriber.prototype.stop = function() {
  if(!this.running)
    throw "Can't stop NchanSubscriber, it's not running.";
  
  this.running = false;
  if(storageEventListener) {
    global.removeEventListener("storage", storageEventListener);
  }
  this.transport.cancel();
  if(this.shared) {
    delete sharedSubscriberTable[this.url];
    if(this.shared.masterIntervalCheckID) {
      clearInterval(this.shared.masterIntervalCheckID);
      delete this.shared.masterIntervalCheckID;
    }
  }
  return this;
};

function addLastMsgIdToQueryString(url, msgid) {
  if(msgid) {
    var m = url.match(/(\?.*)$/);
    url += (m ? "&" : "?") + "last_event_id=" + encodeURIComponent(msgid);
  }
  return url;
}

NchanSubscriber.prototype.SubscriberClass = {
  'websocket': (function() {
    function WSWrapper(emit) {
      WebSocket;
      this.emit = emit;
    }
    
    WSWrapper.prototype.websocketizeURL = function(url) {
      var m = url.match(/^((\w+:)?\/\/([^\/]+))?(\/)?(.*)/);
      var protocol = m[2];
      var host = m[3];
      var absolute = m[4];
      var path = m[5];
      
      var loc;
      if(typeof window == "object") {
        loc = window.location;
      }
      
      if(!protocol && loc) {
        protocol = loc.protocol;
      }
      if(protocol == "https:") {
        protocol = "wss:";
      }
      else if(protocol == "http:") {
        protocol = "ws:";
      }
      else {
        protocol = "wss:"; //default setting: secure
      }
      
      if(!host && loc) {
        host = loc.host;
      }
      
      if(!absolute) {
        path = loc ? loc.pathname.match(/(.*\/)[^/]*/)[1] + path : "/" + path;
      }
      else {
        path = "/" + path;
      }
      
      return protocol + "//" + host + path;
    };
    
    WSWrapper.prototype.listen = function(url, msgid) {
      url = this.websocketizeURL(url);
      url = addLastMsgIdToQueryString(url, msgid);
      //console.log(url);
      if(this.listener) {
        throw "websocket already listening";
      }
      this.listener = new WebSocket(url, 'ws+meta.nchan');
      var l = this.listener;
      l.onmessage = ughbind(function(evt) {
        var m = evt.data.match(/^id: (.*)\n(content-type: (.*)\n)?\n/m);
        this.emit('message', evt.data.substr(m[0].length), {'id': m[1], 'content-type': m[3]});
      }, this);
      
      l.onopen = ughbind(function(evt) {
        this.emit('connect', evt);
        //console.log("connect", evt);
      }, this);
      
      l.onerror = ughbind(function(evt) {
        //console.log("error", evt);
        this.emit('error', evt, l);
        delete this.listener;
      }, this);
      
      l.onclose = ughbind(function(evt) {
        this.emit('__disconnect', evt);
        delete this.listener;
      }, this);
    };
    
    WSWrapper.prototype.cancel = function() {
      if(this.listener) {
        this.listener.close();
        delete this.listener;
      }
    };
    
    return WSWrapper;
  })(),
 
  'eventsource': (function() {
    function ESWrapper(emit) {
      EventSource;
      this.emit = emit;
    }
    
    ESWrapper.prototype.listen= function(url, msgid) {
      url = addLastMsgIdToQueryString(url, msgid);
      if(this.listener) {
        throw "there's a ES listener running already";
      }
      this.listener = new EventSource(url);
      var l = this.listener;
      l.onmessage = ughbind(function(evt){
        //console.log("message", evt);
        this.emit('message', evt.data, {id: evt.lastEventId});
      }, this);
      
      l.onopen = ughbind(function(evt) {
        this.reconnecting = false;
        //console.log("connect", evt);
        this.emit('connect', evt);
      }, this);
      
      l.onerror = ughbind(function(evt) {
        //EventSource will try to reconnect by itself
        //console.log("onerror", this.listener.readyState, evt);
        if(this.listener.readyState == EventSource.CONNECTING && !this.reconnecting) {
          if(!this.reconnecting) {
            this.reconnecting = true;
            this.emit('__disconnect', evt);
          }
        }
        else {
          this.emit('__disconnect', evt);
          //console.log('other __disconnect', evt);
        }
      }, this);
    };
    
    ESWrapper.prototype.cancel= function() {
      if(this.listener) {
        this.listener.close();
        delete this.listener;
      }
    };
    
    return ESWrapper;
  })(),
  
  'longpoll': (function () {
    function Longpoll(emit) {
      this.headers = {};
      this.longPollStartTime = null;
      this.maxLongPollTime = 5*60*1000; //5 minutes
      this.emit = emit;
    }
    
    Longpoll.prototype.listen = function(url, msgid) {
      if(this.req) {
        throw "already listening";
      }
      if(url) { this.url=url; }
      var setHeader = ughbind(function(incoming, name) {
        if(incoming) { this.headers[name]= incoming; }
      }, this);
      
      if(msgid) {
        this.headers = {"Etag": msgid};
      }
      
      this.reqStartTime = new Date().getTime();
      
      var  requestCallback;
      requestCallback = ughbind(function (code, response_text, req) {
        setHeader(req.getResponseHeader('Last-Modified'), 'If-Modified-Since');
        setHeader(req.getResponseHeader('Etag'), 'If-None-Match');
        
        if(code >= 200 && code <= 210) {
          //legit reply
          var content_type = req.getResponseHeader('Content-Type');
          if (!this.parseMultipartMixedMessage(content_type, response_text, req)) {
            this.emit("message", response_text || "", {'content-type': content_type, 'id': this.msgIdFromResponseHeaders(req)});
          }
          
          this.reqStartTime = new Date().getTime();
          this.req = nanoajax.ajax({url: this.url, headers: this.headers}, requestCallback);
        }
        else if((code == 0 && response_text == "Error" && req.readyState == 4) || (code === null && response_text != "Abort")) {
          //console.log("abort!!!");
          this.emit("__disconnect", code || 0, response_text);
          delete this.req;
        }
        else if(code !== null) {
          //HTTP error
          this.emit("error", code, response_text);
          delete this.req;
        }
        else {
          //don't care about abortions 
          delete this.req;
          this.emit("__disconnect");
          //console.log("abort!");
        }
      }, this);
      
      this.reqStartTime = new Date().getTime();
      this.req = nanoajax.ajax({url: this.url, headers: this.headers}, requestCallback);
      this.emit("connect");
      
      return this;
    };
    
    Longpoll.prototype.parseMultipartMixedMessage = function(content_type, text, req) {
      var m = content_type && content_type.match(/^multipart\/mixed;\s+boundary=(.*)$/);
      if(!m) { 
        return false;
      }
      var boundary = m[1];
      
      var msgs = text.split("--" + boundary);
      if(msgs[0] != "" || !msgs[msgs.length-1].match(/--\r?\n/)) { throw "weird multipart/mixed split"; }
      
      msgs = msgs.slice(1, -1);
      for(var i in msgs) {
        m = msgs[i].match(/^(.*)\r?\n\r?\n([\s\S]*)\r?\n$/m);
        var hdrs = m[1].split("\n");
        
        var meta = {};
        for(var j in hdrs) {
          var hdr = hdrs[j].match(/^([^:]+):\s+(.*)/);
          if(hdr && hdr[1] == "Content-Type") {
            meta["content-type"] = hdr[2];
          }
        }
        
        if(i == msgs.length - 1) {
          meta["id"] = this.msgIdFromResponseHeaders(req);
        }
        this.emit('message', m[2], meta);
      }
      return true;
    };
    
    Longpoll.prototype.msgIdFromResponseHeaders = function(req) {
      var lastModified, etag;
      lastModified = req.getResponseHeader('Last-Modified');
      etag = req.getResponseHeader('Etag');
      if(lastModified) {
        return "" + Date.parse(lastModified)/1000 + ":" + (etag || "0");
      }
      else if(etag) {
        return etag;
      }
      else {
        return null;
      }
    };
    
    Longpoll.prototype.cancel = function() {
      if(this.req) {
        this.req.abort();
        delete this.req;
      }
      return this; 
    };
    
    return Longpoll;
  })(),
  
  '__slave': (function() {
    function LocalStoreSlaveTransport(emit) {
      this.emit = emit;
      this.doNotReconnect = true;
    }
    
    LocalStoreSlaveTransport.prototype.listen = function(url, shared) {
      this.shared = shared;
      this.statusChangeChecker = ughbind(function(ev) {
        if(this.shared.matchEventKey(ev, "msg")) {
          var msgId = this.shared.get("msg:id");
          var contentType = this.shared.get("msg:content-type");
          var msg = this.shared.get("msg");
          this.emit('message', msg, {'id': msgId == "" ? undefined : msgId, 'content-type': contentType == "" ? undefined : contentType});
        }
      }, this);
      global.addEventListener("storage", this.statusChangeChecker);
    };
      
    LocalStoreSlaveTransport.prototype.cancel = function() {
      global.removeEventListener("storage", this.statusChangeChecker);
    };
    
    return LocalStoreSlaveTransport;
  })()

};

return NchanSubscriber;
});

