# World Game

A browser-based, persistent multiplayer game played on a tiling of the real Earth. Teams claim tiles, build cities, and interact with rival teams primarily through **Trade**, with **War** as a secondary pressure valve. The primary loop is economic, not military. Most of the world is hidden; players only see the tiles near their own cities.

New players sign up and may optionally join a chosen Team, be randomly assigned to a Team that wants members, or — if neither — found their own one-person Team that spawns on an empty Tile (possibly near existing Teams).

## Language

**World**:
The real planet Earth, partitioned into a grid of tiles, running as a persistent real-time simulation (24/7 wall-clock time; no turns). There is no end-game; multiple parallel Worlds (servers) run independently, and new ones open so players can start fresh. Tile terrain/biome is derived from real-world open geographic data. Tone is grounded and realistic — not fantasy or sci-fi.
_Avoid_: Map, board

**Tech tree (Research progression)**:
A long graph of Buildings (and Units) a Team unlocks one at a time via Research, rather than jumping between discrete eras. Real historical stages (early → industrial → modern) are loose flavor/ordering; actual progression is granular and building-by-building. The tree must be deep enough to keep a persistent world engaging for a long time (it is the game's main source of longevity, since there is no end-game).
_Avoid_: Era as a hard gameplay gate — eras are flavor, the tech tree is the real mechanic.

**Research**:
The mechanism for unlocking the next Building. A dedicated research Building is worked by players to generate progress; the Mayor (or a member with the right Role) chooses which Building to research next. Once researched, that Building type becomes placeable on a Plot.

**Unit**:
A non-player military entity, trained or bought in a military Building (e.g. barracks). Units are sent to attack other Tiles (or garrisoned to defend); players do not personally fight. Units come in *types* that counter one another (e.g. aircraft vs anti-aircraft, and anti-unit types) — combat is resolved on type matchups, not a single attack/defense scalar. Units cost ongoing **maintenance**, so a standing army is a continuous drain that must be justified.

**Defense structure**:
A defensive Building (turrets and similar emplacements — *not* walls) that, together with garrisoned Units, makes up a Tile's defense. Typed to counter specific attacker Unit types (e.g. anti-aircraft). These are where the defender's-advantage multiplier (ADR-0005) primarily lives.

**Combat**:
Resolved asynchronously, Travian-style: a player sends Units toward a target Tile, the units travel for a real-time duration, and the battle produces a computed outcome on arrival. Outcome is a single graduated model — if attack exceeds defense, the defender loses an amount scaled by the margin, escalating through: Resources → Buildings → Units → the Tile itself. Early war is mostly raiding (resources); sustained pressure escalates to razing (buildings) and finally conquest (the Tile). A single raid never takes a Tile in one go.

Combat is deliberately *defender-favored*: defending is easier and less risky than attacking. A failed attack rewards the defender (attacker loses/has Units captured, loses Money, etc.), so attacking is high-risk/high-reward. This keeps war an expensive gamble rather than the default path (see Trade-first emphasis).

**Beginner's protection**:
A time-based shield: a new Team cannot be attacked for an initial period, giving newcomers a runway to learn and build before exposure. Tunable per World. Pairs with Respawn (protection keeps newcomers in; Respawn keeps the wiped from quitting).

**Settling (Expansion)**:
The primary way a Team gains additional Tiles: claim an *adjacent, empty* Tile, extending contiguous territory. Costs a fortune in Resources and requires a special unlocked-by-Research Unit/Building (a settler), and takes a long time — but larger Teams parallelize the effort and expand much faster. An *occupied* adjacent Tile cannot be settled; it can only be taken by conquest (see Combat). Consequence by design: big Teams snowball, while small Teams may never expand and remain vulnerable.

**Respawn**:
A Team is never permanently destroyed. If a Team loses its last Tile, it respawns on a new Tile elsewhere and starts over. (No permadeath — protects retention.)

**Trade**:
Exchange of Resources between Teams via the Marketplace only (no direct team-to-team deals), driven by Resource scarcity (see Resource).

**World market (NPC)**:
The system buyer/seller and the economy's only money faucet. It **buys** basic Resources at a low floor price (creating new Money — guarantees even an isolated Team can always convert labor → goods → money) and **sells** Resources at a higher ceiling price (destroying Money — a sink). Player-to-player trade on the Marketplace lives in the spread between floor and ceiling, so real traders always beat the NPC. Total faucets are tuned slightly below sinks at scale to keep Money scarce.
_Avoid_: Bank, central market

**Seed capital**:
The modest founding treasury every new Team starts with, so it can pay early Wages before production ramps up. Breaks the chicken-and-egg of "need money to pay workers, need workers to make money."

**Marketplace**:
A Building a Team must construct to unlock Trade. Teams post buy/sell orders priced in Money, or resource-for-resource (barter) offers; when another Team accepts, the goods are delivered to the buyer's Marketplace after a delay calculated from the distance between the two Tiles. Delivery is abstract — there is no physical, interceptable convoy, and goods in transit cannot (yet) be raided.

**Tile**:
A single cell of the world grid and the unit of territory. A team starts on one tile and may control several by the end game.
_Avoid_: Grid, cell, square, hex (these all mean Tile; "grid" refers to the whole tiling, not a cell)

**Biome**:
The terrain type of a Tile (e.g. forest, desert, mountain, water), computed from open geographic data. Determines what the Tile offers gameplay-wise.

**Team**:
A group of players who hold territory together. Canonical technical name (inherited from the Laravel starter kit's `Team` model: members, roles, permissions, invitations).
_Avoid_: Clan in code — but see Clan.

**Clan**:
The player-facing flavor name for a Team. Same concept as Team; use "Clan" in UI/fiction, "Team" in code.

**City**:
The built-up development on a single Tile — its collection of Buildings and worked resources. Owned by the Team, not by any individual player.

**Company / Employee** (framing):
The Team behaves like a *company* and each player like an *employee*. The Team owns all tiles, cities, buildings, and resources; players contribute labor and receive individual payment. A player's standing is their progression *within* the company (rank, skills, wealth), not personal land ownership.

**Plot** (working term):
A position within a Tile where a Building can be placed. A Tile's interior is a fixed *square* sub-grid of Plots — 10×10 (100) by default, configurable per World — even though the Tile itself is a hexagon on the world map. The fixed count makes city layout and adjacency bonuses a real puzzle, and caps a Tile's carrying capacity.
_Avoid_: Sub-tile, slot

**Building**:
A structure placed on a Plot. Must first be unlocked via Research, then placed by the Mayor. Takes time to construct: construction is itself a work Activity ("Construct") — members spend Energy to add build progress, earning construction XP + Wages, with more helpers (up to the Building's work-slot cap) completing it faster, subject to a minimum time floor. Once built, a Building may need to be worked to produce resources. Buildings may grant *adjacency bonuses* to neighbouring Buildings on the Plot sub-grid, so city layout is a strategic choice. Buildings damaged or destroyed in Combat can be repaired easily and cheaply, keeping the sting of razing low (reinforces the defender-favored, trade-first balance). Each Building caps how many players may work it at once (a **work-slot** limit that varies by Building type). Buildings are **upgradable** — upgrading raises the work-slot cap and output (a progression axis on top of Research-unlocking).

**Carrying capacity**:
A Tile holds a limited number of Buildings (its Plots) each with limited work-slots, so one Tile can only employ so many players productively. As a Team's headcount grows, its Tile fills up, forcing expansion to more Tiles (Settling or conquest) — this is the mechanic that ties team size to territorial growth. A full company with no room either expands or sees surplus employees leave for others.

**Mayor**:
The single elected leader of a Team. Places new Buildings and holds top authority. Leadership is fully elected and not permanent: any member may *challenge* the current Mayor, triggering a Team vote that resolves within a time limit and replaces the Mayor only if support exceeds a configurable supermajority threshold (e.g. 66%). The founder is merely the first Mayor and can be voted out like anyone else. Replaces the starter kit's permanent `Owner`.
_Avoid_: Major (misspelling), Owner (deprecated starter-kit term)

**Work (Shift)**:
Timed labor performed at a Building. A player starts working a Building for a fixed block (e.g. one hour) and may log off while it runs; on completion it produces Resources for the Team (no Money is created by working) and earns the player Experience in that work type plus Wages paid from the Team treasury. Block length depends on the kind of work.

**Experience**:
Per-work-type points a player accrues by working Buildings — the core of personal progression (e.g. becoming a master of a given trade).

**Money (Currency)**:
The single game-wide currency, used at every level — pricing Marketplace trades, paying wages, buying personal goods, and funding Team growth (settling, research, maintenance). The same unit lives in two kinds of wallet: the **Team treasury** and each player's **personal balance**. Money and Resources are exchangeable both ways (like real life).
_Avoid_: Gold, coins, credits as separate currencies — there is only one.

**Wages**:
Money paid from a Team's treasury into a player's personal balance for working Buildings. For *production* Buildings, the wage is a **share of the market value (at NPC floor price) of the goods that shift produced** — so labor is always net-positive for the Team (it can resell the goods for more than the wage) and can never bankrupt it, while staying above zero whenever anything is produced. The Mayor sets the wage *share* within a system floor (never zero) and cap (Team always profits) — a generous-vs-thrifty management lever. *Service* Buildings (bar, farm, etc.) produce stats/consumables rather than sellable goods, so they pay the flat **floor wage** and exist mainly to serve the community. Players spend wages on personal Needs, work-stat boosts, Housing, upgrades, and cosmetics. Deliberately kept *out* of the voting system: money cannot buy political power.

**Stat (Meter)**:
A player has four personal meters: **Energy**, **Hunger**, **Thirst**, and **Social** (Hunger and Thirst may be merged into one). They divide into two roles: Energy is a *hard gate* (see Energy); Hunger/Thirst/Social are *soft boosts* — the fuller they are, the more effective the player is (e.g. a full Hunger bar yields more resource income from Work). None is ever fatal. Depletion is tied to activity, not wall-clock time; a logged-off player's meters do not drain.

**Energy**:
The gating meter. Performing activities (notably Work) requires and spends Energy, and it is restored *only* by the Sleep activity. Run out and you cannot work until you sleep. (Absorbs the earlier notion of a "sleep need"; Sleep is the recovery Activity, Energy is the meter.)

**Activity**:
A timed action a player performs — Work, Sleep, Eat, Drink, Socialize. Activities are *exclusive and blocking*: a player does one at a time and cannot start another until it completes (so an activity's duration is also effectively its cooldown). Durations vary — Work is a longer earning shift; Sleep is longest and restores Energy; Eat/Drink/Socialize take ~1h and refill their matching Stat. Players may log off while an Activity runs and return when it completes.

**Housing (House)**:
A personal property a player buys with Money. Grants a permanent benefit — notably free sleep (instead of paying to rent a room each night to restore the sleep Need) — plus lasting personal status. A durable money sink, in contrast to one-off consumables.

**Role**:
A fixed tier within a Team determining permissions — kept deliberately simple (three tiers):
- **Mayor** (elected): full authority, including the governance-only levers — setting Wages and appointing/removing Officers.
- **Officer** (appointed by the Mayor): the full *operational* kit — place/upgrade Buildings, choose Research, post/accept Trades, train Units, plan Raids, and Settle new Tiles. Almost as powerful as the Mayor; lacks only governance.
- **Member** (default): work Buildings, help construct, use community Buildings.

Reworks the starter kit's `Owner/Admin/Member` tiers into `Mayor/Officer/Member`, replacing the administrative `TeamPermission` entries (`team:update`, `member:add`, `invitation:*`) with game permissions (`building:place`, `research:choose`, `trade:post`, `unit:train`, `raid:plan`, `tile:settle`, and the Mayor-only `wage:set`, `role:assign`).

**Resource**:
A material gathered on a Tile, either natural (wood from trees, water, stone from mountains) or produced by worked Buildings. Every Tile yields the *basic* resources, but some Tiles hold *rare* resources others lack — this scarcity is what drives Trade and War. Resources accrue to the Team; the contributing player receives some individual payment. Food specifically has multiple sources: bought on the Marketplace (Money), foraged free from nature, or produced by working a Farm Building (which yields both Money and food).

**Fog of war**:
Information about the world comes in three tiers:
1. **Always visible to everyone** — the whole planet as an abstract world map: every Tile's existence, terrain/Biome, and which Team owns it.
2. **Visible when explored** (within a Team's reveal radius, or after scouting) — the *Buildings* on a Tile.
3. **Never freely visible** — fine details like exact Unit counts and Resource stockpiles.

A Team has a fixed reveal radius around its holdings, extendable by building **Radar**. **Scout** Units and Radar can also target specific distant Tiles to explore them remotely (revealing their Buildings). The world map is presented as a stylized/abstract globe, since the game maps 1:1 onto the real Earth.

**Radar**:
A Building that extends a Team's reveal radius and/or targets specific Tiles for remote exploration.

**Scout**:
A Unit sent (with travel time, like an attack) to explore a target Tile and reveal its Buildings before committing to an attack — essential in the defender-favored balance, since attacking blind is a bad gamble.
