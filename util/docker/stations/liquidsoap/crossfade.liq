# Smart transition for crossfade
# @category Source / Fade
# @param ~fade_in  Fade-in duration, if any.
# @param ~fade_out Fade-out duration, if any.
# @param ~high     Value, in dB, for loud sound level.
# @param ~medium   Value, in dB, for medium sound level.
# @param ~margin   Margin to detect sources that have too different sound level for crossing.
# @param ~default Smart crossfade: transition used when no rule applies (default: sequence).
# @param a Ending track
# @param b Starting track
def cross.smart(
  ~id=null(),
  ~fade_in=3.,
  ~fade_out=3.,
  ~default=(fun (a, b) -> (sequence([a, b]) : source)),
  ~high=-15.,
  ~medium=-32.,
  ~margin=4.,
  a,
  b
) =
  id = string.id.default(default="crossfade", id)
  def log(~level=3, x) =
    log(label=id, level=level, x)
  end
  let fade.out = fun (s) -> fade.out(type="sin", duration=fade_out, s)
  let fade.in = fun (s) -> fade.in(type="sin", duration=fade_in, s)
  add = fun (a, b) -> add(normalize=false, [b, a])

  # This is for the type system..
  ignore(a.metadata["foo"])
  ignore(b.metadata["foo"])

  if
    # If A and B are not too loud and close, fully cross-fade them.
    a.db_level <= medium
  and
    b.db_level <= medium
  and
    abs(a.db_level - b.db_level) <= margin
  then
    log(
      "Old <= medium, new <= medium and |old-new| <= margin."
    )
    log(
      "Old and new source are not too loud and close."
    )
    log(
      "Transition: crossed, fade-in, fade-out."
    )
    add(fade.out(a.source), fade.in(b.source))
  elsif
    # If B is significantly louder than A, only fade-out A.
    # We don't want to fade almost silent things, ask for >medium.
    b.db_level >= a.db_level + margin
  and
    a.db_level >= medium
  and
    b.db_level <= high
  then
    log(
      "new >= old + margin, old >= medium and new <= high."
    )
    log(
      "New source is significantly louder than old one."
    )
    log(
      "Transition: crossed, fade-out."
    )
    add(fade.out(a.source), b.source)
  elsif
    # Opposite as the previous one.
    a.db_level >= b.db_level + margin
  and
    b.db_level >= medium
  and
    a.db_level <= high
  then
    log(
      "old >= new + margin, new >= medium and old <= high"
    )
    log(
      "Old source is significantly louder than new one."
    )
    log(
      "Transition: crossed, fade-in."
    )
    add(a.source, fade.in(b.source))
  elsif
    # Do not fade if it's already very low.
    b.db_level >= a.db_level + margin
  and
    a.db_level <= medium
  and
    b.db_level <= high
  then
    log(
      "new >= old + margin, old <= medium and new <= high."
    )
    log(
      "Do not fade if it's already very low."
    )
    log(
      "Transition: crossed, no fade."
    )
    add(a.source, b.source)

    # What to do with a loud end and a quiet beginning ?
    # A good idea is to use a jingle to separate the two tracks,
    # but that's another story.
  else
    # Otherwise, A and B are just too loud to overlap nicely, or the
    # difference between them is too large and overlapping would completely
    # mask one of them.
    log(
      "No transition: using default."
    )
    default(a.source, b.source)
  end
end
