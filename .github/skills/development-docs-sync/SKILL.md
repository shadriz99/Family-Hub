---
name: development-docs-sync
description: "Use whenever Family Hub changes code, configuration, dependencies, tooling, skills, instructions, or documentation. Update logbook and development plan for every change batch. For structural changes, review all project docs and synchronize every affected specification, ADR, runbook, checklist, path, and instruction before completion."
---

# Development documentation synchronization

## Required scope

Apply to every change batch in this repository, including documentation-only edits, environment setup, dependency updates, and agent customizations. Read-only investigation with no changes does not require a new entry.

Read the [masterplan](../../../docs/00-MASTERPLAN.md), [logbook](../../../docs/logbook.md), and [setup and development plan](../../../docs/development-plan.md) before changing anything. Read the relevant module specification for domain work. Respect reviewer authorization and existing uncommitted changes.

This skill is an agent workflow, not an automatic filesystem watcher or enforced CI gate. The repository instructions require its use even if automatic skill discovery misses a trigger.

## Workflow

1. Inspect the current worktree and define the change batch. Identify the task ID, affected behavior, documents, and evidence needed.
2. Classify structural impact. Structural changes include moving/renaming folders or files; changing architecture, module boundaries, contracts, schema, routes, stack versions, build/test/CI commands, deployment topology, authentication/tenancy, or the documentation organization.
3. Make the authorized changes. Do not expand scope, invent approval, rewrite unrelated work, or introduce application code when only documentation is authorized.
4. Update both tracking documents before finishing:
   - Append the next unique `LOG-NNN` entry under the actual date, preserving history. Record task ID when applicable, changes, decisions, commands/results, failures, skipped checks, risks, and next steps. Never include secrets or real household data.
   - Update the plan's affected setup, status, gates, blockers, or next steps and link the new log entry. If task status is unchanged, explicitly retain it and record the change in the appropriate evidence row; do not manufacture progress.
   - One coherent batch needs one entry, not an entry per edit/tool call. Edits synchronizing the log and plan belong to that same entry; do not create recursive entries.
5. For structural changes, inventory and review **every maintained document under `docs/`**, plus repository instructions, skills, and other documentation outside that folder. Check semantics as well as text matches:
   - Masterplan structure, stack, contracts, task dependencies, and acceptance criteria.
   - All module specifications, including cross-module behavior and terminology.
   - ADRs, setup/plan, deploy and backup runbooks, and UAT/release checklists.
   - Paths, examples, relative links/anchors, commands, and agent instructions/skills.
   Update every affected document in the same batch. Leave genuinely unaffected documents unchanged; "all docs" means complete consistency review, not meaningless edits to every file.
6. Preserve historical truth: do not rewrite old log entries or accepted ADR decisions to pretend the new structure always existed. Add a clarifying note/current link or a superseding ADR where needed. Report unresolved specification conflicts and request reviewer decisions rather than silently choosing.
7. Validate local links and relevant anchors, search for obsolete references, run `git diff --check`, and use the smallest repository-standard checks covering any code/configuration changes. Documentation-only work does not require application tests.
8. Record the structural review scope and which documents changed or remained unaffected in the batch log entry. Report checks that could not run. Review the final diff and ensure the log and plan agree with actual results.

## Completion rules

- No changed batch is complete while either the logbook or development plan is stale.
- A structural change is incomplete until all maintained docs have been reviewed and affected docs synchronized.
- Dependency installation, scaffold completion, runtime verification, CI results, and reviewer acceptance are distinct states. Mark complete only when the task acceptance criteria and reviewer verification justify it.
- Do not claim tests, deployments, commits, or MCP connections happened without evidence.
