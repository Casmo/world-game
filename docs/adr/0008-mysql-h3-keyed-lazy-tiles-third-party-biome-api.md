# MySQL storage, H3-keyed lazy tiles, third-party biome API, pure-PHP H3

The persistent world is stored in **MySQL/MariaDB** (InnoDB row-level locking covers the concurrent multi-writer load; chosen over PostgreSQL for team familiarity). SQLite — the starter-kit default — is explicitly retired as unfit for a concurrent persistent MMO.

Tiles are keyed by their **H3 cell index**; adjacency (fog of war, contiguous settling) is *computed* via H3 neighbor functions, not stored as edges. Tiles are **materialized lazily** — a Tile row is created only when first claimed or revealed — and a Tile's `{ biome, terrain, base resources }` is resolved on demand from a **third-party geographic/land-cover API**, called from **background queued jobs** (off the hot path; may pre-warm adjacent occupied tiles) and then **cached permanently in the MySQL tile row** (so the external API is hit at most once per tile, ever; rate limits are a non-issue).

H3 math that the biome API won't do (region tile generation, cell ↔ lat/long, neighbors) runs via a **thin in-house FFI binding to native `libh3`** (H3 v4), wrapped behind a single Tile/World service. We originally planned a pure-PHP H3 port with FFI as the fallback; a spike (see below) reversed that — pure PHP is not viable, so FFI is now the primary and only path.

## Spike result (resolved)

A spike validated the H3 options against known-good v4 reference values:

- **No usable pure-PHP H3 port exists.** The most-downloaded candidate (`michaellindahl/php-h3`), despite advertising "rewritten in PHP, no dependencies," is in fact FFI bindings to native `libh3`, is incomplete (no `gridDisk`/neighbors), and targets the stale v3 API. The only other Packagist option is explicitly FFI too.
- **FFI → native `libh3` v4 works correctly.** A direct `FFI::cdef` binding to `libh3.dylib` returned reference-matching results for `latLngToCell`, `cellToLatLng` (round-trip), `isValidCell`, and `gridDisk` (origin + 6 neighbors). PHP's FFI extension is available in PHP 8.4 / Herd; `libh3` installs via `brew install h3` (v4.5.0) and standard Linux packages.
- **Decision:** write our own minimal FFI binding (only the handful of v4 functions we use) behind the Tile/World service — no third-party PHP wrapper needed.

## Consequences

- **Deployment requirement (the new risk surface):** native `libh3` (v4) and PHP's FFI extension must be present in every environment — dev (Herd, via brew) and production (OS package or built lib). This replaces the old "find a correct pure-PHP library" risk.
- The Tile/World service is the single seam over FFI; if FFI is ever undesirable in prod, a sidecar/extension is the alternative without changing callers.
- The biome API provider and open-data source (e.g. ESA WorldCover, Copernicus, Natural Earth, OSM) is a swappable detail behind the materialization job.
- World tables stay small (only touched tiles exist); a tile's geo lookup is deferred to first use and never repeated.
