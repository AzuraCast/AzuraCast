class Webcaster.Model.Settings extends Backbone.Model
  initialize: (attributes, options) ->
    @mixer = options.mixer

    @mixer.on "cue", =>
      @set passThrough: false

  togglePassThrough: ->
    passThrough = @get("passThrough")
    if passThrough
      @set passThrough: false
    else
      @mixer.trigger "cue"
      @set passThrough: true
