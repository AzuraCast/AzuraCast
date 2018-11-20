$ ->
  Webcaster.mixer = new Webcaster.Model.Mixer
    slider: 0

  Webcaster.settings = new Webcaster.Model.Settings({
    uri:          "ws://source:hackme@localhost:8080/mount"
    bitrate:      128
    bitrates:     [ 8, 16, 24, 32, 40, 48, 56,
                    64, 80, 96, 112, 128, 144,
                    160, 192, 224, 256, 320 ]
    samplerate:   44100
    samplerates:  [ 8000, 11025, 12000, 16000,
                    22050, 24000, 32000, 44100, 48000 ]
    channels:     2
    encoder:      "mp3"
    asynchronous: true
    passThrough:  false
  }, {
    mixer: Webcaster.mixer
  })

  Webcaster.node = new Webcaster.Node
    model: Webcaster.settings

  _.extend Webcaster,
    views:
      settings : new Webcaster.View.Settings
        model : Webcaster.settings
        node  : Webcaster.node
        el    : $("div.settings")

      mixer: new Webcaster.View.Mixer
        model : Webcaster.mixer
        el    : $("div.mixer")

      microphone: new Webcaster.View.Microphone
        model: new Webcaster.Model.Microphone({
          trackGain   : 100
          passThrough : false
        }, {
          mixer: Webcaster.mixer
          node:  Webcaster.node
        })
        el: $("div.microphone")

      playlistLeft : new Webcaster.View.Playlist
        model : new Webcaster.Model.Playlist({
          side        : "left"
          files       : []
          fileIndex   : -1
          volumeLeft  : 0
          volumeRight : 0
          trackGain   : 100
          passThrough : false
          playThrough : true
          position    : 0.0
          loop        : false
        }, {
          mixer : Webcaster.mixer
          node  : Webcaster.node
        })
        el : $("div.playlist-left")

      playlistRight : new Webcaster.View.Playlist
        model : new Webcaster.Model.Playlist({
          side        : "right"
          files       : []
          fileIndex   : -1
          volumeLeft  : 0
          volumeRight : 0
          trackGain   : 100
          passThrough : false
          playThrough : true
          position    : 0.0
          loop        : false
        }, {
          mixer : Webcaster.mixer
          node  : Webcaster.node
        })
        el : $("div.playlist-right")


  _.invoke Webcaster.views, "render"
