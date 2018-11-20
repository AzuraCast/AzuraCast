class Webcaster.View.Track extends Backbone.View
  initialize: ->
    @model.on "change:passThrough", =>
      if @model.get("passThrough")
        @$(".passThrough").addClass("btn-cued").removeClass "btn-info"
      else
        @$(".passThrough").addClass("btn-info").removeClass "btn-cued"

    @model.on "change:volumeLeft", =>
      @$(".volume-left").width "#{@model.get("volumeLeft")}%"

    @model.on "change:volumeRight", =>
      @$(".volume-right").width "#{@model.get("volumeRight")}%"

  onPassThrough: (e) ->
    e.preventDefault()

    @model.togglePassThrough()

  onSubmit: (e) ->
    e.preventDefault()
