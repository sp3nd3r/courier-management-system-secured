#!/usr/bin/env python3
"""Render the V-01/V-02/V-03 remediation status into the GitHub Actions job summary.

The three assignment rules are expected to report ZERO findings against the fixed
application. Because an absence of findings proves nothing on its own, the
workflow runs `semgrep --test` first; this script only reports the scan result,
and the build already failed if the rules were not firing.
"""
import collections
import json
import os
import sys

ASSIGNMENT_RULES = [
    ("royal-v01-sqli-interpolated-value", "V-01", "SQL injection", "CWE-89"),
    ("royal-v02-plaintext-password-in-sql", "V-02", "Plaintext password storage", "CWE-256"),
    ("royal-v03-idor-unverified-owner", "V-03", "Insecure Direct Object Reference", "CWE-639"),
]

REPORT = "semgrep.json"


def main() -> int:
    try:
        with open(REPORT) as fh:
            results = json.load(fh)["results"]
    except (OSError, ValueError, KeyError) as exc:
        print(f"could not read {REPORT}: {exc}", file=sys.stderr)
        return 1

    counts = collections.Counter(r["check_id"].split(".")[-1] for r in results)

    lines = [
        "## Royal Express — secure-coding remediation status",
        "",
        "Ruleset: `p/php` (Semgrep registry) + `.semgrep/royal-express.yml` (assignment rules).",
        "Custom rules were verified to fire by `semgrep --test` before this scan ran.",
        "",
        "| ID | Vulnerability | CWE | Findings | Status |",
        "| --- | --- | --- | ---: | --- |",
    ]

    outstanding = 0
    for rule_id, vid, name, cwe in ASSIGNMENT_RULES:
        n = counts.get(rule_id, 0)
        outstanding += n
        status = "✅ RESOLVED" if n == 0 else "❌ OPEN"
        lines.append(f"| {vid} | {name} | {cwe} | {n} | {status} |")

    other = collections.Counter(
        {k: v for k, v in counts.items()
         if k not in {r[0] for r in ASSIGNMENT_RULES}}
    )
    other_total = sum(other.values())

    lines += [
        "",
        f"**Assignment vulnerabilities outstanding: {outstanding}**",
        "",
    ]

    if other_total:
        lines += [
            f"<details><summary>Other findings from the registry pack ({other_total}) "
            "— outside the three remediated vulnerabilities</summary>",
            "",
            "| Rule | Findings |",
            "| --- | ---: |",
        ]
        lines += [f"| `{k}` | {v} |" for k, v in sorted(other.items(), key=lambda x: -x[1])]
        lines += ["", "</details>", ""]

    summary = "\n".join(lines)
    print(summary)

    path = os.environ.get("GITHUB_STEP_SUMMARY")
    if path:
        with open(path, "a") as fh:
            fh.write(summary + "\n")

    return 0


if __name__ == "__main__":
    sys.exit(main())
