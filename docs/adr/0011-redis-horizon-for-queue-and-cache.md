# Redis + Horizon for queue and cache

Queue and cache run on **Redis**, with **Horizon** managing and monitoring the queue. Redis and Horizon are installed by default both locally (via Herd) and on the server, so the extra service is a non-issue.

The game's hot path is background work — the frequent sweep (ADR-0007), queued broadcast events (ADR-0009), and tile-resolution jobs hitting the geo API (ADR-0008). Keeping those on the `database` driver would put queue churn on the same MySQL that handles the locked, transactional game writes (ADR-0010), so queue I/O would contend with treasury/battle transactions. Redis isolates that load; Horizon provides worker autoscaling, metrics, and failed-job visibility for a live persistent world; and Redis doubles as a fast cache for hot reads (tile data, world map).

## Consequences

- Redis must be running in all environments (covered by default setup).
- Horizon is the operational dashboard for timers/broadcasts; failed-job alerting should be wired up before launch.
