---
description: How to work with Filipe
alwaysApply: true
---

<!-- Edit this file at .ai/preferences.md. .grok/rules and .cursor/rules are links to it. Do not update those paths, and do not paste these rules into AGENTS.md. -->

# Preferences

How Filipe works in this repo. Domain rules for signals are in `.ai/signals.md`.

## Consent

- Do not write to GitHub or any other integration unless he explicitly asks. That includes `git commit`, `git push`, `git add`, pull requests, issues, review comments, Laravel Cloud, deploys, and email.
- A drafted commit message is not permission to commit. He commits himself, including directly on main, and he commits before branching.
- Do not run `git add -A`. Leave unrelated untracked files alone. Do not add `.DS_Store`, `dump.rdb`, images under `public/assets`, or `public/js/app/`.
- Do not send mail. Artisan commands that can email (`btc:daily-prices-monitoring`) stay without `--send-email` unless he asks.

## Git

- Author is Filipe Knoedt &lt;fknoedt@gmail.com&gt;.
- Branch names look like `dev/164-pattern-matching-improvement`. Cross-cutting commits use `[trunk]`. Ticket commits use the ticket number. Changes that stay on main use `[main]`.
- Message shape: a bracket tag, one summary sentence, then dash bullets. Examples: `[92] Signals list improvements`, `[166] Get Started page polish`, `[main] adding missing blade files from [92]`.

## Review before a commit message

When he asks for the commit message, check whether the uncommitted diff was fully reviewed in this turn. If it was not, say so and review it before drafting the message.

The review covers everything changed and not yet committed:

- The work matches what was discussed. Pending items from the conversation stay visible. Do not drop them.
- Pest tests cover the behavior that changed. Add or update tests when the change needs them.
- Look for undesirable side effects.
- Update the other pages, docs, or copy that should move with the change, including ones he did not name.
- List untracked files that belong in the commit, and leave out files that do not.

Then draft the message but do not commit. The message should contain:
- start with the ticket number from the trello card or the current branch (make sure it's not on an older/commited branch) if you have it, otherwise use XXX or main if in the main branch. Format is [nnn] or [main].
- then summarize, in a very high level and in a brief single sentence, what is being changed or implemented.
- then add a bullet list of the changes made, also in a high level (what was done, not how).

## Code

- Match the surrounding code. Prefer the simpler version a person can read.
- Ask before introducing complexity (new abstractions, generic frameworks, dense one-liners) when a direct version would do. Ask when the harder version is actually the better trade-off, and wait for a yes.
- Do not add a client-side library by default. The ones already in the app have a history of advisories. If a library is really necessary, choose a popular, well-tested one and ask before adding it.
- Do not run `php artisan optimize:clear` or `view:clear`. Local `.env` uses `CACHE_DRIVER=none`.
- Do not edit compiled views in `storage/framework/views`.
- When he asks for a speedup, do the work and then summarize it at a high level.

## Browser and screenshots

- He browses in Brave. The local app is `https://orbit-btc.test`. Do not open another browser.
- Do not screenshot or paste a page he can already see. Capture only when he asks, or when the image is doctored.
- PhpStorm Agent Dock does not render markdown images or Mermaid. When a path is needed, give the absolute path as text. Mermaid source has to start with a diagram type (`block` or `flowchart`). He pastes it into mermaid.live.
- Headless Chrome is fine for help images that are part of the doc.

## UI

- Push back when an idea is weak. 
- Filipe is not an English native speaker, so always review the provided copy for small syntax or grammar improvements and apply them when they're no-brainers.
- The UI always has to be mobile ready.
- User-facing copy stays plain. Avoid parentheses. Don't break sentences (with dots) too much. Field names are bold, not orange.
- When a tooltip pattern already exists, copy it. Use static text unless he asked for values from the row.
- A visual change has to work in dark and light.

## Tests

- Tests are Pest. `phpunit.xml` forces SQLite `:memory:` and `CACHE_DRIVER=array`. `failOnRisky` is false. Risky tests are pre-existing noise. A real assertion failure still fails the run.
- `RefreshDatabase` is commented out in `tests/Pest.php`. Database tests create the tables they need.
