# Team leadership is a fully elected Mayor, replacing the permanent Owner

Each Team is led by a single **Mayor** elected by its members, replacing the starter kit's permanent `Owner` role (where the creator held leadership forever and was excluded from reassignment). Any member may challenge the sitting Mayor, which triggers a Team-wide vote that resolves within a time limit; the Mayor is replaced only if support exceeds a configurable supermajority threshold (default ~66%). The founder is seeded as the first Mayor but holds no permanent privilege and can be voted out like anyone else.

We chose fully elected leadership over keeping a protected founder/Owner (or switching governance modes by headcount) because it best fits the "company run by its employees" vision and makes leadership turnover a genuine social dynamic rather than a fixed fact. Guardrails (challenge cooldowns/time limits, a supermajority quorum, and early-stage protection while a company is tiny) keep a healthy Mayor from being constantly destabilized.

## Consequences

- The `Owner` case and its permanent-creator semantics are retired; the Mayor is modeled as a per-Team elected position seeded with the founder.
- A voting/challenge subsystem (proposals, time-boxed ballots, thresholds, cooldowns) is required.
- Existing administrative permissions must be re-expressed as game Roles granted under an elected Mayor rather than a fixed Owner.
