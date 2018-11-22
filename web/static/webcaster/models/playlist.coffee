class Webcaster.Model.Playlist extends Webcaster.Model.Track
  initialize: (attributes, options) ->
    super(attributes, options)

    @mixGain = @node.context.createGain()
    @mixGain.connect @node.webcast

    @mixer.on "change:slider", @setMixGain

    @sink = @mixGain

  setMixGain: =>
    return unless @mixGain?

    if @get("side") == "left"
      @mixGain.gain.value = @mixer.getLeftVolume()
    else
      @mixGain.gain.value = @mixer.getRightVolume()

  appendFiles: (newFiles, cb) ->
    files = @get "files"

    onDone = _.after newFiles.length, =>
      @set files: files
      cb?()

    addFile = (file) ->
      file.readTaglibMetadata (data) =>
        files.push
          file     : file
          audio    : data.audio
          metadata : data.metadata

        onDone()

    addFile newFiles[i] for i in [0..newFiles.length-1]

  selectFile: (options = {}) ->
    files = @get "files"
    index = @get "fileIndex"

    return if files.length == 0

    index += if options.backward then -1 else 1

    index = files.length-1 if index < 0

    if index >= files.length
      unless @get("loop")
        @set fileIndex: -1
        return

      if index < 0
        index = files.length-1
      else
        index = 0

    file = files[index]
    @set fileIndex: index

    file

  play: (file) ->
    @prepare()

    @setMixGain()

    @node.createFileSource file, this, (@source) =>
      @source.connect @destination

      if @source.duration?
        @set duration: @source.duration()
      else
        @set duration: parseFloat(file.audio.length) if file.audio?.length?

      @source.play file
      @trigger "playing"

  onEnd: ->
    @stop()

    @play @selectFile() if @get("playThrough")
