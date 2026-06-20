# Real-time via thin broadcast events over private team/user channels

The UI is kept live with WebSocket broadcasting over the Pusher protocol (hosted Pusher now; Reverb is a config-only swap later since Echo client code is identical). When the sweep (ADR-0007) or a background job applies a completion, it fires a Laravel `ShouldBroadcast` event.

Events are **thin signals**, not data payloads: an event says only "something you care about changed" (e.g. tile X updated). The client, on receiving it via Echo, triggers an **Inertia partial reload** (`router.reload({ only: [...] })`) that re-reads authoritative state over HTTP/MySQL through the normal controllers.

Two reasons for thin-over-fat:
1. **Single source of truth + fog of war.** All state and all visibility filtering stay server-side in one place (the controllers). The client never reconstructs game logic, and a broadcast can never leak data a player shouldn't see (e.g. exact enemy unit counts).
2. **Payload limits.** Pusher (and similar) cap WebSocket event size (~10KB); full state is fetched over HTTP instead.

All broadcasts go on **private channels** — `team.{id}` for team-wide changes (build done, under attack), `user.{id}` for personal ones (shift/activity completed) — authorized in `routes/channels.php`. Nothing is broadcast globally.

## Consequences

- Each event costs a small follow-up partial reload; kept cheap via `only`-scoped reloads.
- Channel authorization in `routes/channels.php` must enforce team membership / user identity.
- Client countdowns run locally from timestamps (ADR-0007); events/reloads reconcile, so even infrequent signals feel live.
