# Plugin Isolation & Fault Tolerance

**Status: Specification v0.1** · Referenced by [security-spec.md](security-spec.md) §4 · In-core layers (1–3) are **Stage 4 build requirements**, not aspirations.

## The problem, stated precisely

PHP cannot sandbox in-process code: a Composer plugin runs with full application privileges, and an uncaught fatal (OOM, infinite loop) kills the executing worker. This is the industry baseline — Strapi/Payload npm plugins have the same blast radius in Node. Magna's goal is not a fake sandbox; it is **bounded blast radius with automatic recovery**: a bad plugin may break *its own feature*; it must not keep the site down.

Two failure classes need different tools:

| Class | Examples | Catchable in-process? |
|---|---|---|
| Throwable | exceptions, TypeError, bad SQL | ✅ yes — Layer 1 |
| Fatal | OOM, `max_execution_time`, segfault | ❌ no — Layers 2 & 5 |

## Layer 1 — Fault-tolerant hook dispatch (core, Stage 4)

Every plugin hook/contract invocation goes through one dispatcher, never called directly:

```php
try {
    $result = $plugin->{$hook}(...$args);
} catch (\Throwable $e) {
    $this->failures->record($plugin, $hook, $e);   // audit log + admin surface
    return $default;                               // hook contract defines its safe default
}
```

- **Every hook contract defines a safe default** (empty nav group, unmodified query, no widget) so the page renders without the failing plugin's contribution.
- **Circuit breaker**: N failures (default 5) of the same plugin within a window auto-disables it, writes an audit entry, and notifies admins. Re-enable is a deliberate admin action.
- **Honest boundary**: this catches Throwables only. It converts "plugin bug = broken page" into "plugin bug = missing widget + admin alert" — which is most real-world failures.

## Layer 2 — Fatal recovery & safe mode (core, Stage 4)

For the uncatchable class, copy the best thing WordPress ever shipped (5.2 fatal recovery), done properly:

- The dispatcher records "plugin X entered hook Y" *before* invocation (request-scoped). A `register_shutdown_function` handler inspects `error_get_last()`; on fatal, it attributes the crash to the in-flight plugin, increments its failure count (feeding the same circuit breaker), and logs with full context.
- **Safe mode**: booting with `MAGNA_SAFE_MODE=1` (env or a `storage/` flag file, settable without a working admin) loads the kernel with **all plugins disabled** — the operational rescue hatch when a plugin breaks the site badly enough that the admin itself is down.
- Failure counts + last errors surface on the plugins admin screen: misbehaving plugins are visible before they take anything down.

## Layer 3 — Background execution for heavy work (core, Stage 4 + refined at Stage 12)

Process-per-hook is rejected for the request path (a PHP process spawn costs more than our entire p99 delivery budget). Instead:

- Plugins declare heavy hooks in `magna.json` (`"execution": "queued"`); the dispatcher runs those as queued jobs. A crash/OOM kills a **queue worker** (Horizon restarts it), never a web worker.
- For explicitly untrusted one-off executions (e.g., store-review tooling), a `Process`-based runner with `--timeout` and `php -d memory_limit=...` is available — CLI/queue contexts only.

## Layer 4 — Remote apps tier (Phase 4, with the open store)

Full isolation by construction for unreviewed third parties: apps run as separate services, integrating only via the API, signed webhooks, and declared UI extension points (the Shopify model). Core treats a dead app as a timeout, not a crash. Already committed in security-spec §4.5.

## Layer 5 — Infrastructure (deploy guide + Magna Cloud)

- **Worker recycling is a reference-deployment requirement**: Octane/FrankenPHP with `--max-requests` and memory-threshold restarts — a leaking plugin costs one worker restart. PHP-FPM deployments get this for free (shared-nothing per request).
- **Magna Cloud**: one container per site; the orchestrator restarts crashed containers and neighbors are unaffected.

## Dependency isolation (store tooling, Phase 2+)

Composer-installed plugins cannot collide at runtime — version conflicts fail loudly at `composer require`, which is a safety feature, not a bug. For **prebuilt/premium distributions** the store build pipeline runs **PHP-Scoper** (prefixing bundled dependencies' namespaces) so a plugin's vendored libraries cannot shadow the host's.

## What Magna will not claim

No in-process memory or CPU quota per plugin (impossible in PHP without process boundaries), no syscall filtering, no protection from a *malicious* in-process plugin beyond the store gates — malicious code defense is the trust model in security-spec §4 (manifests, static analysis, kill switch, advisories), not runtime containment. Marketing may say "fault-tolerant plugin runtime with automatic recovery"; it may never say "sandboxed."
