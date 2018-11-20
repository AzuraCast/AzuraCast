navigator.mediaDevices ||= {}

navigator.mediaDevices.getUserMedia ||= (constraints) ->
  fn = navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia

  unless fn?
    return Promise.reject new Error("getUserMedia is not implemented in this browser")

  new Promise (resolve, reject) ->
    fn.call navigator, constraints, resolve, reject

navigator.mediaDevices.enumerateDevices ||= ->
  Promise.reject new Error("enumerateDevices is not implemented on this browser")
