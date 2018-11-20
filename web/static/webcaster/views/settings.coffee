class Webcaster.View.Settings extends Backbone.View
  events:
    "change .uri"            : "onUri"
    "change input.encoder"   : "onEncoder"
    "change input.channels"  : "onChannels"
    "change .samplerate"     : "onSamplerate"
    "change .bitrate"        : "onBitrate"
    "change .asynchronous"   : "onAsynchronous"
    "click .passThrough"     : "onPassThrough"
    "click .start-stream"    : "onStart"
    "click .stop-stream"     : "onStop"
    "click .update-metadata" : "onMetadataUpdate"
    "submit"                 : "onSubmit"

  initialize: ({@node}) ->
    @model.on "change:passThrough", =>
      if @model.get("passThrough")
        @$(".passThrough").addClass("btn-cued").removeClass "btn-info"
      else
        @$(".passThrough").addClass("btn-info").removeClass "btn-cued"

  render: ->
    samplerate = @model.get "samplerate"
    @$(".samplerate").empty()
    _.each @model.get("samplerates"), (rate) =>
      selected = if samplerate == rate then "selected" else ""
      $("<option value='#{rate}' #{selected}>#{rate}</option>").
        appendTo @$(".samplerate")

    bitrate = @model.get "bitrate"
    @$(".bitrate").empty()
    _.each @model.get("bitrates"), (rate) =>
      selected = if bitrate == rate then "selected" else ""
      $("<option value='#{rate}' #{selected}>#{rate}</option>").
        appendTo @$(".bitrate")

    this

  onUri: ->
    @model.set uri: @$(".uri").val()

  onEncoder: (e) ->
    @model.set encoder: $(e.target).val()

  onChannels: (e) ->
    @model.set channels: parseInt($(e.target).val())

  onSamplerate: (e) ->
    @model.set samplerate: parseInt($(e.target).val())

  onBitrate: (e) ->
    @model.set bitrate: parseInt($(e.target).val())

  onAsynchronous: (e) ->
    @model.set asynchronous: $(e.target).is(":checked")

  onPassThrough: (e) ->
    e.preventDefault()

    @model.togglePassThrough()

  onStart: (e) ->
    e.preventDefault()

    @$(".stop-stream").show()
    @$(".start-stream").hide()
    @$("input, select").attr disabled: "disabled"
    @$(".manual-metadata, .update-metadata").removeAttr "disabled"

    @node.startStream()

  onStop: (e) ->
    e.preventDefault()

    @$(".stop-stream").hide()
    @$(".start-stream").show()
    @$("input, select").removeAttr "disabled"
    @$(".manual-metadata, .update-metadata").attr disabled: "disabled"

    @node.stopStream()

  onMetadataUpdate: (e) ->
    e.preventDefault()

    title = @$(".manual-metadata.artist").val()
    artist = @$(".manual-metadata.title").val()

    return unless artist != "" || title != ""

    @node.sendMetadata
      artist: artist
      title:  title

    @$(".metadata-updated").show 400, =>
     cb = =>
       @$(".metadata-updated").hide 400

     setTimeout cb, 2000

  onSubmit: (e) ->
    e.preventDefault()
