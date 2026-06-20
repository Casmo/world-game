# Single-currency economy: NPC faucet, working makes goods not money, share-of-value wages

The economy uses one Money currency across Teams, the Marketplace, and players. Money enters the system in exactly one way — the **NPC world market buys resources at a low floor price** (and sells at a higher ceiling, which is a sink). **Working a Building produces Resources and XP, never Money**; Money reaches players only as **Wages paid from the Team treasury**, which the Team fills by selling goods (to the NPC or other Teams). New Teams start with **seed capital** to bootstrap the first wage cycles.

Wages for production Buildings are a **share of the market value (at NPC floor price) of the goods produced that shift**, with the Mayor setting the share between a system floor and cap. This was chosen deliberately to satisfy two pulling-apart constraints the user set — wages must be *never zero* yet *never bankrupt the Team*: because the Team can always resell the produced goods for more than the wage, labor is structurally net-positive, so paying wages cannot drain the treasury, while any production yields a positive wage. Service Buildings (bar, farm) pay only the flat floor wage and exist to provide community stats/consumables.

## Consequences

- Faucets (NPC purchases) must be tuned slightly below sinks (NPC sales, unit maintenance, settling, research, personal consumption) at scale to keep Money scarce and valued.
- Wage accounting must value each shift's output at the NPC floor price; the Mayor's wage-share lever needs enforced floor/cap bounds.
- The NPC floor price guarantees no Team is ever economically dead, even if isolated with no trade partners.
