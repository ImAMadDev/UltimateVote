# UltimateVote

<img height="100" src="assets/icon.png" width="100"/>

UltimateVote is a PocketMine-MP plugin for letting players claim their rewards from voting for your server.

[![](https://poggit.pmmp.io/shield.state/UltimateVote)](https://poggit.pmmp.io/p/BetterVoting) [![](https://poggit.pmmp.io/shield.dl.total/UltimateVote)](https://poggit.pmmp.io/p/UltimateVote)

## What's new in 2.0?

- All vote requests use a separate thread instead of async tasks, boosting performance
- Complete rewrite & overall performance boost
- '/vote info' has been added, it shows the server's last cached information
- '/vote top' now uses locally cached data, making it faster to respond
- All messages are now editable in the config
- Automatic vote claiming, when a player joins or the cache is updated, online players with unclaimed votes will have
  their vote automatically claimed
- PlayerVoteEvent has been added for plugin developers

> Note: Data is cached & reupdated every 3 minutes due to cache time on MinecraftPocket-Server's API

**UltimateVoteApi
[ ] Compatibility with all vote services 
[ ] Fully configurable 
[ ] Check if player has voted when joined, if player has voted he'll get a claim message 
[ ] Top voters hologram nd command
[ ] messages file

*config file:
version: 1.0
vote-api:
    fetch: url
    post: url
arguments:
    {player}: {playername}
rewards:
    commands: []
    items: []
vote-party:
    enabled: true
    amount: 250
    commands: []
    items: []
