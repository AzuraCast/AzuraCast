class Webcaster.Model.Mixer extends Backbone.Model
  getVolume: (position) ->
    if position < 0.5
      return 2*position

    1

  getSlider: ->
    parseFloat(@get("slider"))/100.00

  getLeftVolume: ->
    @getVolume(1.0 - @getSlider())
    
  getRightVolume: ->
    @getVolume @getSlider()
