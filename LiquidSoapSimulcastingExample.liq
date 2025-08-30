# ===== Multistream: YouTube + Facebook (AzuraCast radio + looping video + Now Playing overlay) =====

# ---- Station paths ----
station_base_dir = "/var/azuracast/stations/station_name"
video_file       = station_base_dir ^ "/media/videostream/video.mp4"
font_file        = station_base_dir ^ "/media/videostream/font.ttf"
nowplaying_file  = station_base_dir ^ "/config/nowplaying.txt"

# ---- Stream keys (replace with your real keys; keep secret) ----
youtube_key   = "YOUR_YOUTUBE_STREAM_KEY"
fb_stream_key = "YOUR_FACEBOOK_STREAM_KEY"

# ---- Text overlay styling/position (tune for your video resolution) ----
font_size  = "50"
font_x     = "340"
font_y     = "990"
font_color = "white"

# ---- Overlay: draws text from nowplaying_file, auto-reload every 5s ----
def add_nowplaying_text(s) =
  def mkfilter(graph) =
    let {video = video_track} = source.tracks(s)
    video_track = ffmpeg.filter.video.input(graph, video_track)
    video_track =
      ffmpeg.filter.drawtext(
        fontfile=font_file,
        fontsize=font_size,
        x=font_x,
        y=font_y,
        fontcolor=font_color,
        textfile=nowplaying_file,
        reload=5,
        graph,
        video_track
      )
    video_track = ffmpeg.filter.video.output(graph, video_track)
    source({video=video_track})
  end
  ffmpeg.filter.create(mkfilter)
end

# ---- Build A/V source ----
# Loop the background video, add overlay, then mux with your audio source `radio`
videostream = single(video_file)
videostream = add_nowplaying_text(videostream)
videostream = source.mux.video(video=videostream, radio)   # assumes `radio` already exists

# ---- Encoding settings (shared parameters) ----
# 2s keyframe interval is friendly for both YT and FB:
v_fps = 30
v_gop = 60       # g = 2s * fps
v_bps = "2500k"  # try 1500k–3500k for 720p; raise if you have bandwidth
a_bps = "128k"

# YouTube HLS encoder (MPEG-TS container)
enc_youtube =
  %ffmpeg(
    format = "mpegts",
    %video.raw(
      codec        = "libx264",
      pixel_format = "yuv420p",
      b            = v_bps,
      preset       = "veryfast",
      r            = v_fps,
      g            = v_gop
    ),
    %audio(
      codec      = "aac",
      samplerate = 44100,
      channels   = 2,
      b          = a_bps,
      profile    = "aac_low"
    )
  )

# Facebook RTMPS encoder (FLV container)
enc_facebook =
  %ffmpeg(
    format = "flv",
    %video.raw(
      codec        = "libx264",
      pixel_format = "yuv420p",
      b            = v_bps,
      preset       = "veryfast",
      r            = v_fps,
      g            = v_gop
    ),
    %audio(
      codec      = "aac",
      samplerate = 44100,
      channels   = 2,
      b          = a_bps,
      profile    = "aac_low"
    )
  )

# ---- Outputs ----

# YouTube Live (HLS ingest)
output.youtube.live.hls(
  key=youtube_key,
  fallible=true,
  encoder=enc_youtube,
  videostream
)

# Facebook Live (RTMPS)
fb_url = "rtmps://live-api-s.facebook.com:443/rtmp/" ^ fb_stream_key
output.url(
  url=fb_url,
  fallible=true,
  enc_facebook,
  videostream
)