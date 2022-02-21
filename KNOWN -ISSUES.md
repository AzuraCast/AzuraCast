# Known Issues

This file will include the majority of the known issues we're aware of with AzuraCast and that are in the stages of review / investigation / fixing. We don't provide a date on the fixes but rest assured we're aware of them.

---

## High Priority Bugs
We class bugs as 'high priority' when it impacts a large amount of users, related to CPU and Memory usages or security bugs. These bugs can be complex to debug and requires a large  amount of investigation and reviewing by our team.

- Abnormally high CPU and Memory usages for Liquidsoap (Impacts 0.15 onwards) #5099
 
- Docker installations `overlay` sub directories consuming abnormally high storage (Impacts very limited users) #5127 #5077


## Medium Priority

- Playlists not always following the schedule 

- Ansible users not being able to install on Ubuntu 18.04 due to ocaml-ffmpeg being unsupported #5007

- Deleting a station can fail to remove it from supervisord (very limited users impacted) #4898

- Remote Album Art can't find artwork that exists on Last.FM #5103

- Web DJ audio quality can be lower than expected #5116

- No metadata for FLAC mount point (Upstream issue) - #5008


## Low Priority

- Song playback bar can continue to play despite audio being stopped. #5031
