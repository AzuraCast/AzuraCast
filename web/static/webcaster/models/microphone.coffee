class Webcaster.Model.Microphone extends Webcaster.Model.Track
  initialize: ->
    super()

    @on "change:device", ->
      return unless @source?
      @createSource()

  createSource: (cb) ->
    @source.disconnect @destination if @source?

    constraints = {video:false}

    if @get("device")
      constraints.audio =
        exact: @get("device")
    else
      constraints.audio = true

    @node.createMicrophoneSource constraints, (@source) =>
      @source.connect @destination
      cb?()

  play: ->
    @prepare()

    @createSource =>
      @trigger "playing"
