---
description: How signals are scored
alwaysApply: true
---

<!-- Edit this file at .ai/signals.md. .grok/rules and .cursor/rules are links to it. Do not update those paths, and do not paste these rules into AGENTS.md. -->

# Signals

How an Orbit signal is scored, and the pieces that stay as they are unless Filipe asks. Personal working style is in `.ai/preferences.md`.

There is no live trading. One simulated trade stakes `config('btc.trade_size_in_usd')` (default $1,000) through `UserSignalService::tradeSizeInUsd()`. Email is the real notification. Help must not say a fire is not a live order.

## Formula

Each day, for each metric, Orbit takes the percent change versus the value that many days ago:

`(1 - current / reference) * 100`

A rise is negative. A drop is positive. A missing or zero value adds nothing.

Direction is applied in `UserSignalMetric::matchesDirection`:

- `+` (Up) keeps a rise.
- `-` (Down) keeps a drop.
- `+-` (Both) keeps either move.

Points for that metric are `abs(percent) * weight`. The column is still `weight`. The form label is **Multiply movement by** (0.1 to 100, one decimal). The day's score is the sum. The signal fires when `score >= threshold`. There is no per-metric threshold, no usual-move division, and no 5× cap. Do not bring those back.

Look-back windows are frequency ids 1, 3, 5, 7, 14, and 30 days. `Frequency::MAX_FREQUENCY_IN_DAYS` is 30.

## The simulated trade

Horizons are 1, 3, 5, 10, and 30 days. Horizon 15 was removed. `price_change_14d` exists and is not a horizon.

Buy gains when the forward price change is positive. Sell flips the sign. P&L is `stake * (change / 100)`. A null forward change does not book a $0 trade and does not start the wait.

After a booked trade the wait (quarantine) equals the horizon, so the next trade can start on day N+1. The chart badge stays **Quarantined since last purchase.** One open trade is the whole wait. Reset the wait at the start of each signal in `updateDailyScores`. `MAX_DAYS_BACK` is 1096.

A metric with an empty `data_limited_at` throws during scoring. Leave those metrics unused. Coin Metrics rows (exchange flows, active addresses, and the rest of that set) are in that state.

MVRV, NUPL, and realized cap are one realized-cap fact. Do not treat them as independent confirmation. NUPL and NRPL also cross zero, so a percent change on them is unstable.

## Leveling

`leveling_moves` stores the median absolute percent for a metric and a frequency. `btc:update-leveling-moves` reads the database only. It does not call an API and it does not rescore. The wizard uses it for hints. The scorer does not.

Hint text is `Try {n} to level the metrics.` Undo is `Undo: {n}`. Show nothing when there is no number to suggest. The loudest metric is the anchor and gets no hint. With one metric the multiplier field is hidden and the percent is the points, so a threshold of 5 means a 5% move.

The composite note sits above the metric cards. Its buttons are **Level the metrics**, then **Learn more**.

## Do not change unless he asks

- `SignalGeneratorService`, including any rename.
- Mayer Multiple - Buy Signal, id 24469: multiplier 1, direction down, 30-day look-back, threshold 18.1. Local rescore on 2026-10-14 was about -$16.72 over 51 trades.
- BB RSI, id 24922. Rescored only. About -$849.70 over 15 trades.
- The four system composites, ids 24928–24931. Thresholds were not retuned after the percent-times-multiplier rescore. Stored totals: 24928 $1,130.70 / 28 trades, 24929 $1,077.50 / 28, 24930 $1,081.00 / 28, 24931 $1,500.67 / 79. Do not rescore them.
- The `weight` column name. The page can keep saying multiplier.
- Sell Fear & Greed, id 51, the only saved one-metric weight other than 1 (1.34). Do not save it from Edit. Edit forces weight 1.
- Production. It was not rescored with this formula.

`user_signals.name` is unique. System signals use user id 1000000 (`config('data.system_user_id')`) and show in every user's list. Hand-made system signals stay. Inserting rows is fine. Scoring happens only when `updateDailyScores` runs. SQL inserts alone do not score.

On 2026-10-16, `btc:update-future-price-change` filled 232 days of `price_change_*` that had stopped in early 2026 while closes ran through 2026-09-21. The protected signals above were not rescored after that fill. The next full score run can change their totals.

Local rows named `Study:` are a researched set of 100 composites on the system user (2–5 metrics, buy and sell). They are database rows, not product defaults. Do not delete or retune them unless he asks.

## Wizard and Help

Help is `resources/views/help/user-signal.blade.php`, shared with the performance page. Order: How Signals Work, Metrics, Composite Signals, The threshold, Simulated trades. Basic only. When a Help pass is finished, remind him that an Advanced section can come later. Do not put that sentence on the page.

Moves are percents. That has to be clear, because each metric box does not show it. One metric at multiplier 1: the percent is the points. Field hints must not say "A 5% move adds 5 points." The threshold decides when the signal fires. A metric row does not.

Help images, at their real pixel size, `max-width: 100%`, height auto: `public/images/help/metric-box.png` (920×126), `composite-metrics.png` (920×378), `trade-and-hold.png` (689×162). The Help panel stays dark in both themes via `.help-signal`. No KaTeX and no CDN for it.

The metrics step styles live in `resources/views/forms/components/metric-step-styles.blade.php`. Do not restyle that step while doing unrelated work. The line under the wizard step icons stays removed.

## Commands

- `btc:update-leveling-moves` fills an empty `leveling_moves` table from `daily_prices`. It does not need a full score run. Do not use `schedule:run-all`. That command ignores the clock and runs every scheduled task.
- `btc:daily-prices-monitoring` compares `daily_prices` columns with metrics. Run it without `--send-email`.
