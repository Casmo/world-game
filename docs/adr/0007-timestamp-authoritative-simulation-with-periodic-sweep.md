# The simulation is timestamp-authoritative, advanced by a periodic sweep

The persistent world is almost entirely discrete timed events (work shifts, construction, research, unit travel/combat, activities). Rather than dispatching one delayed queued job per event, every timed thing stores authoritative `started_at` / `completes_at` (and the inputs needed to recompute) on its own domain row. A frequently scheduled command (the "tick" — `everySecond`/`everyMinute` as needed, `->onOneServer()` and overlap-protected) sweeps all rows where `completes_at <= now` and applies their effects. **The database is the source of truth; jobs/the scheduler only apply effects.** Continuous-feeling values (Energy, Needs, build progress) are computed on read from timestamps, not ticked.

We rejected per-event delayed jobs because they produce enormous job volume and make two essential features painful: **cancellation** (recalling an army, razing a building mid-construction) and **mutation** (a new worker joining an in-progress build to speed it up). Under the timestamp model, cancellation is a row delete and mutation is a single `completes_at` recompute. The model is also crash-resilient: after downtime, the sweep finds all overdue rows and catches up, because nothing was lost in an in-flight queue.

## Consequences

- `completes_at` columns must be indexed; the sweep batches due rows and must be idempotent.
- Domain rows must store enough inputs to recompute completion times when conditions change (worker count, bonuses).
- Up-to-one-tick latency on completions — negligible for builds, acceptable for combat at a sub-second/second tick.
- Reads compute current state from timestamps, so the UI is accurate even between sweeps.
