class Webcaster.View.Mixer extends Backbone.View
  render: ->
    @$(".slider").slider
      stop: =>
        @$("a.ui-slider-handle").tooltip "hide"
      slide: (e, ui) =>
        @model.set slider: ui.value
        @$("a.ui-slider-handle").tooltip "show"

    @$("a.ui-slider-handle").tooltip
      title: => @model.get "slider"
      trigger: ""
      animation: false
      placement: "bottom"

    this
