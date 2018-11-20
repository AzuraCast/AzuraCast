window.Webcaster = Webcaster =
  View: {}
  Model: {}
  Source: {}

  prettifyTime: (time) ->
    hours   = parseInt time / 3600
    time   %= 3600
    minutes = parseInt time / 60
    seconds = parseInt time % 60

    minutes = "0#{minutes}" if minutes < 10
    seconds = "0#{seconds}" if seconds < 10

    result = "#{minutes}:#{seconds}"
    result = "#{hours}:#{result}" if hours > 0

    result
