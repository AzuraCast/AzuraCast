class Webcaster.View.Microphone extends Webcaster.View.Track
  events:
    "click .record-audio"    : "onRecord"
    "click .passThrough"     : "onPassThrough"
    "submit"                 : "onSubmit"

  initialize: ->
    super()

    @model.on "playing", =>
      @$(".play-control").removeAttr "disabled"
      @$(".record-audio").addClass "btn-recording"
      @$(".volume-left").width "0%"
      @$(".volume-right").width "0%"

    @model.on "stopped", =>
      @$(".record-audio").removeClass "btn-recording"
      @$(".volume-left").width "0%"
      @$(".volume-right").width "0%"

  render: ->
    @$(".microphone-slider").slider
      orientation: "vertical"
      min: 0
      max: 150
      value: 100
      stop: =>
        @$("a.ui-slider-handle").tooltip "hide"
      slide: (e, ui) =>
        @model.set trackGain: ui.value
        @$("a.ui-slider-handle").tooltip "show"

    @$("a.ui-slider-handle").tooltip
      title: => @model.get "trackGain"
      trigger: ""
      animation: false
      placement: "left"

    navigator.mediaDevices.getUserMedia({audio:true, video:false}).then =>
      navigator.mediaDevices.enumerateDevices().then (devices) =>
        devices = _.filter devices, ({kind, deviceId}) ->
          kind == "audioinput"

        return if _.isEmpty devices

        $select = @$(".microphone-entry select")

        _.each devices, ({label,deviceId}) ->
          $select.append "<option value='#{deviceId}'>#{label}</option>"

        $select.find("option:eq(0)").prop "selected", true

        @model.set "device", $select.val()

        $select.select ->
          @model.set "device", $select.val()

        @$(".microphone-entry").show()

    this

  onRecord: (e) ->
    e.preventDefault()

    if @model.isPlaying()
      return @model.stop()

    @$(".play-control").attr disabled: "disabled"
    @model.play()
