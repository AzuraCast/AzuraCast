class Webcaster.View.Playlist extends Webcaster.View.Track
  events:
    "click .play-audio"      : "onPlay"
    "click .pause-audio"     : "onPause"
    "click .previous"        : "onPrevious"
    "click .next"            : "onNext"
    "click .stop"            : "onStop"
    "click .progress-seek"   : "onSeek"
    "click .passThrough"     : "onPassThrough"
    "change .files"          : "onFiles"
    "change .playThrough"    : "onPlayThrough"
    "change .loop"           : "onLoop"
    "change .volume-slider"  : "onVolumeChange"
    "submit"                 : "onSubmit"

  initialize: (options) ->
    super(options)

    @model.on "change:fileIndex", =>
      @$(".track-row").removeClass "success"
      @$(".track-row-#{@model.get("fileIndex")}").addClass "success"

    @model.on "playing", =>
      @$(".play-control").removeAttr "disabled"
      @$(".play-audio").hide()
      @$(".pause-audio").show()
      @$(".track-position-text").removeClass("blink").text ""
      @$(".volume-left").width "0%"
      @$(".volume-right").width "0%"

      if @model.get("duration")
        @$(".progress-volume").css "cursor", "pointer"
      else
        @$(".track-position").addClass("progress-striped active")
        @setTrackProgress 100

    @model.on "paused", =>
      @$(".play-audio").show()
      @$(".pause-audio").hide()
      @$(".volume-left").width "0%"
      @$(".volume-right").width "0%"
      @$(".track-position-text").addClass "blink"

    @model.on "stopped", =>
      @$(".play-audio").show()
      @$(".pause-audio").hide()
      @$(".progress-volume").css "cursor", ""
      @$(".track-position").removeClass("progress-striped active")
      @setTrackProgress 0
      @$(".track-position-text").removeClass("blink").text ""
      @$(".volume-left").width "0%"
      @$(".volume-right").width "0%"

    @model.on "change:position", =>
      return unless duration = @model.get("duration")

      position = parseFloat @model.get("position")

      @setTrackProgress 100.0*position/parseFloat(duration)

      @$(".track-position-text").
        text "#{Webcaster.prettifyTime(position)} / #{Webcaster.prettifyTime(duration)}"

  render: ->
    files = @model.get "files"

    @$(".files-table").empty()

    return this unless files.length > 0

    _.each files, ({file, audio, metadata}, index) =>
      if audio?.length != 0
        time = Webcaster.prettifyTime audio.length
      else
        time = "N/A"

      if @model.get("fileIndex") == index
        klass = "success"
      else
        klass = ""
        
      @$(".files-table").append """
        <tr class='track-row track-row-#{index} #{klass}'>
          <td>#{index+1}</td>
          <td>#{metadata?.title || "Unknown Title"}</td>
          <td>#{metadata?.artist || "Unknown Artist"}</td>
          <td>#{time}</td>
        </tr>
                                """

    @$(".playlist-table").show()

    this

  setTrackProgress: (percent) ->
    @$(".track-position").width "#{percent*$(".progress-volume").width()/100}px"
    @$(".track-position-text,.progress-seek").width $(".progress-volume").width()

  play: (options) ->
    @model.stop()
    return unless @file = @model.selectFile options

    @$(".play-control").attr disabled: "disabled"
    @model.play @file

  onPlay: (e) ->
    e.preventDefault()
    if @model.isPlaying()
      @model.togglePause()
      return

    @play()

  onPause: (e) ->
    e.preventDefault()
    @model.togglePause()

  onPrevious: (e) ->
    e.preventDefault()
    return unless @model.isPlaying()?

    @play backward: true

  onNext: (e) ->
    e.preventDefault()
    return unless @model.isPlaying()

    @play()

  onStop: (e) ->
    e.preventDefault()

    @$(".track-row").removeClass "success"
    @model.stop()
    @file = null

  onSeek: (e) ->
    e.preventDefault()

    @model.seek ((e.pageX - $(e.target).offset().left) / $(e.target).width())

  onFiles: ->
    files = @$(".files")[0].files
    @$(".files").attr disabled: "disabled"

    @model.appendFiles files, =>
      @$(".files").removeAttr("disabled").val ""
      @render()

  onPlayThrough: (e) ->
    @model.set playThrough: $(e.target).is(":checked")

  onLoop: (e) ->
    @model.set loop: $(e.target).is(":checked")

  onVolumeChange: (e) ->
    @model.set trackGain: $(e.target).val()
