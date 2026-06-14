General notes regarding playlist groups
-----------------------------------------------------------------------------
- Need to ensure that all places apart from the the autodj / scheduling stuff know about playlist groups
    - The APIs that include anything with playlists need to be checked
        - Identify which exactly and make notes on what needs to be done there
- Biggest place with work needed: UI
    - What to do with the schedule page?
        - How should we represent grouped playlists exactly?
        - Probably like regular playlists there too, maybe different color or with an icon?
        - Maybe add a hover tooltip / card that shows the list of the sub-playlists?

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
Features
- Think about how we want to handle disabled playlists that are part of groups
    - Should they even be allowed to be disabled?
    - Should disabled ones be allowed to be added to playlist groups?
    - Should disabling a playlist remove them from a playlist group?
- Add tracking of origin for media from PlaylistGroup & Consecutive Play number
