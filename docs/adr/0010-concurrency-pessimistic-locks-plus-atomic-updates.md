# Concurrency: pessimistic locks for multi-step mutations, atomic updates for counters

Shared state is written concurrently by multiple players and by the sweep (ADR-0007), so mutations must be race-safe. Two complementary techniques:

- **Pessimistic locking** is the default for any multi-step mutation: wrap it in a DB transaction and `lockForUpdate()` the affected rows (treasury, tile, building, battle) — locking rows in a consistent order to avoid deadlocks. Applies to battle resolution, settling, trades, and the sweep's "apply this completion" (so e.g. a build finishing cannot interleave with the Mayor demolishing that building).
- **Atomic conditional updates** for simple counters: express spends/claims as single-statement SQL with an affected-rows check (`UPDATE ... SET money = money - X WHERE money >= X`, work-slot claims, stockpile decrements), removing the read-then-write gap without holding locks.

Standard request validation sits on top as the outer guard. We rejected optimistic locking as the default (retry logic everywhere) and per-team queue serialization (adds latency, still doesn't solve cross-team combat). Contention is expected to be rare, but correctness in a money-handling game must not depend on that.

## Consequences

- Mutating code paths run inside transactions; hot rows (team treasury) may see brief contention — acceptable at expected volumes.
- The sweep must apply each completion in a locking transaction, consistent with player-action locking.
