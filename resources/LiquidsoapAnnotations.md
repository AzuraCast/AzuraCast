# Liquidsoap Annotation Reference

## Appending Tracks

> liquidsoap/src/operators/append.ml

Append an extra track to every track.

### liq_append

Set the metadata 'liq_append' to 'false' to inhibit appending on one track.

## Crossing Tracks

> liquidsoap/src/operators/cross.ml

Common effects like cross-fading can be split into two parts: crossing, and fading. Here we implement crossing, not caring about fading: a arbitrary transition function is passed, taking care of the combination.

A buffer is needed to store the end of a track before combining it with the next track. We could always have a full buffer, but this would involve copying all the time. Instead, we try to fill the buffer only when getting close to the end of track. The problem then is to cope with tracks which are longer than expected, i.e. which end doesn't really fit in the buffer.

This operator works with any type of stream. All three parameters are durations in ticks.

### liq_start_next

Metadata field which, if present and containing a float, overrides the 'duration' parameter for current track.

## Cue Points

> liquidsoap/src/operators/cuepoint.ml

The [cue_cut] class is able to skip over the beginning and end of a track according to cue points. This involves quite a bit of trickery involving clocks, #seek as well as reverting frame contents. Even more trickery would be needed to implement a [cue_split] operator that splits tracks according to cue points: in particular, the frame manipulation would get nasty, involving storing chunks that have been fetched too early, replaying them later, glued with new content.

We use ticks for precision, but store them as Int64 to allow long durations. This should eventually be generalized to all of liquidsoap, removing limitations such as the duration passed to #seek or returned by #remaining. We introduce a few notations to make this comfortable.

Start track after a cue in point and stop it at cue out point. The cue points are given as metadata, in seconds from the begining of tracks.

### liq_cue_in

Metadata for cue in points.

### liq_cue_out

Metadata for cue out points. Note that cue-out should be given relative to the beginning of the file (0:00 of the file itself, not 0:00 as calculated by the cue-in point).

## Fader

> liquidsoap/src/operators/fade.ml

Fade durations (in, initial, out, final) are indicated in total seconds.

### liq_fade_type

Metadata field which, if present and correct, overrides the 'type' parameter for current track.

Options: lin|sin|log|exp (linear, sinusoidal, logarithmic or exponential)

Default: lin

### liq_fade_in

Fade the beginning of **tracks**.

### liq_fade_initial

Fade the beginning of **a stream**.

### liq_fade_out

Fade the end of **tracks**.

### liq_fade_final

Fade **a stream** to silence.

## Offset

> liquidsoap/src/operators/on_offset.ml

Call a given handler when position in track is equal or more than a given amount of time (the 'offset' parameter)

### liq_on_offset

Metadata field which, if present and containing a float, overrides the 'offset' parameter.

## Prepending Tracks

> liquidsoap/src/operators/prepend.ml

Prepend an extra track before every track.

### liq_prepend

Set the metadata 'liq_prepend' to 'false' to inhibit appending on one track.

## Set Volume

> liquidsoap/src/operators/setvol.ml

Multiply the amplitude of the signal.

### liq_amplify

Specify the name of a metadata field that, when present and well-formed, overrides the amplification factor for the current track. Well-formed values are floats in decimal notation (e.g. '0.7') which are taken as normal/linear multiplicative factors; values can be passed in decibels with the suffix 'dB' (e.g. '-8.2 dB', but the spaces do not matter).

## Video Fade

> liquidsoap/src/operators/video_fade.ml

### liq_video_fade_in

Fade the beginning of tracks. Metadata 'liq_video_fade_in' can be used to set the duration for a specific track (float in seconds).

### liq_video_fade_out

Fade the end of tracks. Metadata 'liq_video_fade_out' can be used to set the duration for a specific track (float in seconds).