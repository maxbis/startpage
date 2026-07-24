---
name: manage-msp-production
description: Safely access, inspect, and update the MSP production environment over SSH. Use for production checks, production troubleshooting, deployment verification, or explicitly requested Git operations such as pulling the current tracked branch in /var/www/qool/msp on vmi3072769.contaboserver.net.
---

# Manage MSP Production

Treat this host and directory as the live production environment:

- SSH target: `max@vmi3072769.contaboserver.net`
- SSH port: `1611`
- application directory: `/var/www/qool/msp`

## Safety boundary

- Default to read-only inspection.
- State that the target is production before proposing or performing a mutation.
- Require explicit user authorization for each production-changing operation, including `git pull`, checkout, dependency installation, migrations, cache clearing, service restarts, file edits, and deletions.
- Interpret a direct request such as "pull production" as authorization for that named operation only.
- Never copy credentials, private keys, environment files, database dumps, or other secrets into chat or the local repository.
- Never use destructive Git commands, force options, or overwrite local production changes.
- Stop when the worktree is dirty, the branch has diverged, no upstream is configured, authentication fails, or the requested operation would require an unmentioned follow-up mutation. Report the exact blocker.

## Connect and inspect

Use non-interactive SSH for a single command:

```bash
ssh -p 1611 -o BatchMode=yes -o ConnectTimeout=10 \
  max@vmi3072769.contaboserver.net \
  'cd /var/www/qool/msp && pwd'
```

Use an interactive session only when it materially helps:

```bash
ssh -p 1611 max@vmi3072769.contaboserver.net
cd /var/www/qool/msp
```

Before any Git update, inspect the repository without changing it:

```bash
ssh -p 1611 -o BatchMode=yes -o ConnectTimeout=10 \
  max@vmi3072769.contaboserver.net \
  'cd /var/www/qool/msp &&
   git status --short --branch &&
   git branch --show-current &&
   git rev-parse --abbrev-ref --symbolic-full-name @{upstream}'
```

Confirm that:

1. the command is running in `/var/www/qool/msp`;
2. the worktree has no modified, staged, or untracked files;
3. the current branch is the intended deployment branch;
4. the current branch has the expected upstream.

Do not infer the deployment branch name. Use the checked-out branch unless the user explicitly names another branch.

## Pull the production branch

Run this only after the user explicitly requests the pull and the preflight checks pass:

```bash
ssh -p 1611 -o BatchMode=yes -o ConnectTimeout=10 \
  max@vmi3072769.contaboserver.net \
  'cd /var/www/qool/msp && git pull --ff-only'
```

Use `--ff-only` to prevent an unexpected merge commit. Do not stash, reset, clean, switch branches, or force the pull to make it succeed.

After a successful pull, verify and report the deployed revision:

```bash
ssh -p 1611 -o BatchMode=yes -o ConnectTimeout=10 \
  max@vmi3072769.contaboserver.net \
  'cd /var/www/qool/msp &&
   git status --short --branch &&
   git log -1 --format="%H%n%h %s%n%ci"'
```

Report whether the repository changed, the resulting commit hash and subject, and any output that requires attention. Do not run application-specific post-deployment steps unless the user explicitly asks for them.
