class Webcaster.Model.Track extends Backbone.Model
  initialize: (attributes, options) ->
    @node = options.node
    @mixer = options.mixer

    @mixer.on "cue", =>
      @set passThrough: false

    @on "change:trackGain", @setTrackGain
    @on "ended", @stop

    @sink = @node.webcast

  togglePassThrough: ->
    passThrough = @get("passThrough")
    if passThrough
      @set passThrough: false
    else
      @mixer.trigger "cue"
      @set passThrough: true

  isPlaying: ->
    @source?

  createControlsNode: ->
    bufferSize = 4096
    bufferLength = parseFloat(bufferSize)/parseFloat(@node.context.sampleRate)

    bufferLog = Math.log parseFloat(bufferSize)
    log10     = 2.0 * Math.log(10)

    source = @node.context.createScriptProcessor bufferSize, 2, 2

    source.onaudioprocess = (buf) =>
      ret = {}

      if @source?.position?
        ret["position"] = @source.position()
      else
        if @source?
          ret["position"] = parseFloat(@get("position"))+bufferLength

      for channel in [0..buf.inputBuffer.numberOfChannels-1]
        channelData = buf.inputBuffer.getChannelData channel

        rms = 0.0
        for i in [0..channelData.length-1]
          rms += Math.pow channelData[i], 2
        volume = 100*Math.exp((Math.log(rms)-bufferLog)/log10)

        if channel == 0
          ret["volumeLeft"] = volume
        else
          ret["volumeRight"] = volume

        @set ret

        buf.outputBuffer.getChannelData(channel).set channelData

    source

  createPassThrough: ->
    source = @node.context.createScriptProcessor 256, 2, 2

    source.onaudioprocess = (buf) =>
      channelData = buf.inputBuffer.getChannelData channel

      for channel in [0..buf.inputBuffer.numberOfChannels-1]
        if @get("passThrough")
          buf.outputBuffer.getChannelData(channel).set channelData
        else
          buf.outputBuffer.getChannelData(channel).set (new Float32Array channelData.length)

    source

  setTrackGain: =>
    return unless @trackGain?
    @trackGain.gain.value = parseFloat(@get("trackGain"))/100.0

  prepare: ->
    @controlsNode = @createControlsNode()
    @controlsNode.connect @sink

    @trackGain = @node.context.createGain()
    @trackGain.connect @controlsNode
    @setTrackGain()

    @destination = @trackGain

    @passThrough = @createPassThrough()
    @passThrough.connect @node.context.destination
    @destination.connect @passThrough


  togglePause: ->
    return unless @source?.pause?

    if @source?.paused?()
      @source.play()
      @trigger "playing"
    else
      @source.pause()
      @trigger "paused"

  stop: ->
    @source?.stop?()
    @source?.disconnect()
    @trackGain?.disconnect()
    @controlsNode?.disconnect()
    @passThrough?.disconnect()

    @source = @trackGain = @controlsNode = @passThrough = null

    @set position: 0.0
    @trigger "stopped"

  seek: (percent) ->
    return unless position = @source?.seek?(percent)

    @set position: position

  sendMetadata: (file) ->
    @node.sendMetadata file.metadata
