# Realistic, real-Earth tech-progression setting with asynchronous unit-based warfare

The game is deliberately grounded and realistic — set on the real Earth, with Teams advancing through real historical tech stages (early → industrial → modern) rather than a fantasy or sci-fi theme (the genre touchstones Travian and OGame were explicitly rejected for being fantasy/sci-fi). Warfare is military and resolved asynchronously, Travian-style: players send non-player Units (trained/bought in military Buildings) toward a target Tile, the Units travel for a real-time duration, and a computed battle outcome resolves on arrival. Players never fight directly.

Resource scarcity is the engine of inter-Team interaction: every Tile yields basic resources, but rare resources are unevenly distributed, pushing Teams to either Trade or go to War.

## Consequences

- A historical tech tree must gate Buildings/Units by era, which is a substantial content surface.
- Asynchronous combat requires durable travel timers and a server-side battle-resolution engine (jobs/queues), not real-time client interaction.
