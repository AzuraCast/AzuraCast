General notes regarding playlist groups
-----------------------------------------------------------------------------
- Need to ensure that all places apart from the the autodj / scheduling stuff know about playlist groups
     - The APIs that include anything with playlists need to be checked
         - Identify which exactly and make notes on what needs to be done there
- Biggest place with work needed: UI
     - Need to make it possible to create playlist groups itself
     - Need to make it possible to add playlists & media to playlist groups
         - Need to prevent playlist groups to be added to self
         - Do we need to prevent playlists that are already part of the playlist group from being added again?
             - Probably not(?), could be wanted to say "sequentially play A then B, then C, then A again, etc..."
     - What to do with the schedule page?
         - How should we represent grouped playlists exactly?
         - Probably like regular playlists there too, maybe different color or with an icon?
         - Maybe add a hover tooltip / card that shows the list of the sub-playlists?
     - Need to make it possible to see & sort playlist group contents like with sequential playlists

Thoughts about settings for playlist groups
-----------------------------------------------------------------------------
- wouldn't allow advanced backend options at all
     - this would make handling groups much too hard imho as we would neet to figure out
       how to translate this into LS code
- wouldn't allow the following options in the beginning to keep the first version simple
     - include in on-demand player
     - hide metadata
     - allow requests
- Allowed PlaylistTypes
     - No issues with PlaylistTypes::Standard
     - Once every x should work
     - wouldn't allow PlaylistTypes::Advanced
         - can't really represent these in LS Code

@TODO
-------------------------------
All Playlists
- Reorder Button fails on an error that it is not a sequential playlist
- Need to also check the Reshuffle Button
- The Playback Queue Button seems useless here
