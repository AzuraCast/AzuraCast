class Webcaster.Node
  _.extend @prototype, Backbone.Events

  defaultChannels = 2

  constructor: ({@model}) ->
    if typeof webkitAudioContext != "undefined"
      @context = new webkitAudioContext
    else
      @context = new AudioContext

    @webcast = @context.createWebcastSource 4096, defaultChannels

    @connect()

    @model.on "change:passThrough", =>
      @webcast.setPassThrough @model.get("passThrough")

    @model.on "change:channels", =>
      @reconnect()

  connect: ->
    if @model.get("channels") == 1
      @merger ||= @context.createChannelMerger @defaultChannels
      @merger.connect @context.destination
      @webcast.connect @merger
    else
      @webcast.connect @context.destination   

  disconnect: ->
    @webcast.disconnect()
    @merger?.disconnect()

  reconnect: ->
    @disconnect()
    @connect()

  startStream: ->
    switch @model.get("encoder")
      when "mp3"
        encoder = Webcast.Encoder.Mp3
      when "raw"
        encoder = Webcast.Encoder.Raw

    @encoder = new encoder
      channels   : @model.get("channels")
      samplerate : @model.get("samplerate")
      bitrate    : @model.get("bitrate")

    if @model.get("samplerate") != @context.sampleRate
      @encoder = new Webcast.Encoder.Resample
        encoder    : @encoder
        type       : Samplerate.LINEAR,
        samplerate : @context.sampleRate

    if @model.get("asynchronous")
      @encoder = new Webcast.Encoder.Asynchronous
        encoder : @encoder
        scripts: [
          "https://cdn.rawgit.com/webcast/libsamplerate.js/master/dist/libsamplerate.js",
          "https://cdn.rawgit.com/savonet/shine/master/js/dist/libshine.js",
          "https://cdn.rawgit.com/webcast/webcast.js/master/lib/webcast.js"
        ]

    @webcast.connectSocket @encoder, @model.get("uri")

  stopStream: ->
    @webcast.close()

  createAudioSource: ({file, audio}, model, cb) ->
    el = new Audio URL.createObjectURL(file)
    el.controls = false
    el.autoplay = false
    el.loop     = false

    el.addEventListener "ended", =>
      model.onEnd()

    source = null

    el.addEventListener "canplay", =>
      return if source?

      source = @context.createMediaElementSource el

      source.play = ->
        el.play()

      source.position = ->
        el.currentTime

      source.duration = ->
        el.duration

      source.paused = ->
        el.paused

      source.stop = ->
        el.pause()
        el.remove()

      source.pause = ->
        el.pause()

      source.seek = (percent) ->
        time = percent*parseFloat(audio.length)

        el.currentTime = time
        time

      cb source

  createFileSource: (file, model, cb) ->
    @source?.disconnect()

    @createAudioSource file, model, cb

  createMicrophoneSource: (constraints, cb) ->
    navigator.mediaDevices.getUserMedia(constraints).then (stream) =>
      source = @context.createMediaStreamSource stream

      source.stop = ->
        stream.getAudioTracks()?[0].stop()

      cb source

  sendMetadata: (data) ->
    @webcast.sendMetadata data

  close: (cb) ->
    @webcast.close cb
