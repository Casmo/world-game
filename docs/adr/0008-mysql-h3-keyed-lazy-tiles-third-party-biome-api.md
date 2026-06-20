# MySQL storage, H3-keyed lazy tiles, third-party biome API, pure-PHP H3

The persistent world is stored in **MySQL/MariaDB** (InnoDB row-level locking covers the concurrent multi-writer load; chosen over PostgreSQL for team familiarity). SQLite — the starter-kit default — is explicitly retired as unfit for a concurrent persistent MMO.

Tiles are keyed by their **H3 cell index**; adjacency (fog of war, contiguous settling) is *computed* via H3 neighbor functions, not stored as edges. Tiles are **materialized lazily** — a Tile row is created only when first claimed or revealed — and a Tile's `{ biome, terrain, base resources }` is resolved on demand from a **third-party geographic/land-cover API**, called from **background queued jobs** (off the hot path; may pre-warm adjacent occupied tiles) and then **cached permanently in the MySQL tile row** (so the external API is hit at most once per tile, ever; rate limits are a non-issue).

H3 math that the biome API won't do (region tile generation, cell ↔ lat/long, neighbors) runs in a **pure-PHP H3 port** to keep everything inside Laravel with no extra service and a trivial deploy; if the port proves incomplete or incorrect, fall back to an FFI/extension binding to `libh3`.

## Consequences

- **Risk/spike:** confirm a correct pure-PHP H3 library exists and matches the C library's indexing *before* locking the spatial model. This is the one unproven piece.
- The biome API provider and open-data source (e.g. ESA WorldCover, Copernicus, Natural Earth, OSM) is a swappable detail behind the materialization job.
- World tables stay small (only touched tiles exist); a tile's geo lookup is deferred to first use and never repeated.
