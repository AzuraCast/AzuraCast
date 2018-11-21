class Webcaster.View.Mixer extends Backbone.View
  events:
    "change .slider"  : "onMixerPositionChange"

  onMixerPositionChange: (e) ->
    @model.set slider: $(e.target).val()
