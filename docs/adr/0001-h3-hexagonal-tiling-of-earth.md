# Use H3 hexagonal tiling of the real Earth for the world grid

The world is the real Earth partitioned into a grid of tiles. We tile it with Uber's open-source **H3** hexagonal hierarchical geospatial index rather than a lat/long square grid, at a resolution giving roughly **25–50 km tiles**.

Hexagons give equal-ish area cells across the whole globe (a lat/long square grid distorts severely toward the poles), uniform single-distance adjacency (every tile has 6 equidistant neighbors, which simplifies fog-of-war reveal and territory expansion), and H3 provides built-in neighbor/ring lookups and multiple resolutions out of the box. The ~25–50 km size keeps land meaningfully scarce — controlling even one tile is significant — while leaving room for a large player population.

## Consequences

- Real-world geographic open data must be mapped onto H3 cells to derive each tile's Biome.
- Tile identity is the H3 cell index; the spatial model is committed to H3's resolution and indexing scheme.
