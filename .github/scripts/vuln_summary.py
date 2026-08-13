#!/usr/bin/env python3
"""Render the V-01/V-02/V-03 OPEN -> RESOLVED comparison into the job summary.

Two Semgrep reports feed this:
  baseline.json - the frozen pre-remediation code in baseline/ (the OPEN column)
  semgrep.json  - the fixed application                        (the RESOLVED column)

Scanning both with the same rules in the same run is what makes the comparison
like-for-like. The baseline is what stops "0 findings" from being vacuous: it
proves the rules still fire, on this run, against the code they were written for.
"""
import collections
import json
import os
import sys
from datetime import datetime, timezone

DETECTED_DATE = "2026-07-06"
RESOLVED_DATE = datetime.now(timezone.utc).strftime("%Y-%m-%d")

ASSIGNMENT_RULES = [
    ("royal-v01-sqli-interpolated-value", "V-01", "SQL injection", "CWE-89"),
    ("royal-v02-plaintext-password-in-sql", "V-02", "Plaintext password storage", "CWE-256"),
    ("royal-v03-idor-unverified-owner", "V-03", "Insecure Direct Object Reference", "CWE-639"),
]

FIXED_REPORT = "semgrep.json"
BASELINE_REPORT = "baseline.json"


def load(path):
    """Return a Counter of rule-name -> count, or None if the report is missing."""
    try:
        with open(path) as fh:
            results = json.load(fh)["results"]
    except (OSError, ValueError, KeyError) as exc:
        print(f"could not read {path}: {exc}", file=sys.stderr)
        return None
    return collections.Counter(r["check_id"].split(".")[-1] for r in results)


def main() -> int:
    fixed = load(FIXED_REPORT)
    if fixed is None:
        return 1
    baseline = load(BASELINE_REPORT)

    lines = [
        "## Royal Express — secure-coding remediation status",
        "",
        "Ruleset: `p/php` (Semgrep registry) + `.semgrep/royal-express.yml` (assignment rules).",
        "",
    ]

    if baseline is None:
        lines += ["> Baseline report missing — showing the fixed tree only.", ""]
        lines += [
            "| ID | Vulnerability | CWE | Findings | Status |",
            "| --- | --- | --- | ---: | --- |",
        ]
    else:
        lines += [
            "`BEFORE` is the frozen pre-remediation code in `baseline/`; `AFTER` is the "
            "application. Both scanned with the same rules, in this run.",
            "",
            "| ID | Vulnerability | CWE | BEFORE | AFTER | Detected | Resolved | Status |",
            "| --- | --- | --- | ---: | ---: | --- | --- | --- |",
        ]

    outstanding = 0
    before_total = 0
    for rule_id, vid, name, cwe in ASSIGNMENT_RULES:
        after = fixed.get(rule_id, 0)
        outstanding += after
        status = "✅ RESOLVED" if after == 0 else "❌ OPEN"
        if baseline is None:
            lines.append(f"| {vid} | {name} | {cwe} | {after} | {status} |")
        else:
            before = baseline.get(rule_id, 0)
            before_total += before
            # A rule that finds nothing in the baseline is not evidence of a fix,
            # it is evidence the rule stopped working.
            if before == 0:
                status = "⚠️ RULE NOT FIRING"
            resolved = RESOLVED_DATE if after == 0 else "—"
            lines.append(f"| {vid} | {name} | {cwe} | {before} | {after} | {DETECTED_DATE} | {resolved} | {status} |")

    lines.append("")
    if baseline is None:
        lines.append(f"**Assignment vulnerabilities outstanding: {outstanding}**")
    else:
        lines.append(
            f"**{before_total} findings in the pre-remediation baseline → "
            f"{outstanding} in the fixed application.**"
        )
        lines.append("")
        lines.append(
            f"_Vulnerabilities detected {DETECTED_DATE} (baseline); "
            f"verified resolved by this automated scan on {RESOLVED_DATE}._"
        )
    lines.append("")

    other = {k: v for k, v in fixed.items()
             if k not in {r[0] for r in ASSIGNMENT_RULES}}
    other_total = sum(other.values())
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
