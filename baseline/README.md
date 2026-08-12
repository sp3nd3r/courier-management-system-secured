# Pre-remediation baseline — DO NOT EXECUTE OR DEPLOY

These files are a frozen copy of the **original, vulnerable** Royal Express code,
kept so the CI pipeline can report a like-for-like OPEN → RESOLVED comparison in
a single workflow run.

They are **reference data for a scanner**, not part of the application. Nothing
in the running app includes them. Web access is denied via `.htaccess` because
this repository doubles as an XAMPP document root.

## What is in here

Only the five files that carry findings. Together they reproduce all 51 findings
of the full original tree, so the rest of the original code adds nothing.

| File | Findings |
| --- | ---: |
| `server/inc/get.php` | 28 |
| `server/inc/update.php` | 11 |
| `server/inc/add.php` | 10 |
| `server/inc/delete.php` | 1 |
| `Admin/getbill.php` | 1 |

## Known vulnerabilities (this is the point)

| ID | Vulnerability | CWE | Baseline | Fixed tree |
| --- | --- | --- | ---: | ---: |
| V-01 | SQL injection | CWE-89 | 44 | 0 |
| V-02 | Plaintext password storage | CWE-256 | 6 | 0 |
| V-03 | Insecure Direct Object Reference | CWE-639 | 1 | 0 |

## How CI treats this directory

- Scanned separately by `.semgrep/royal-express.yml` to produce the OPEN column.
- **Excluded** from the application scan (`--exclude=baseline`), so these findings
  never reach the Security tab. Without that exclusion the fixed application would
  appear to still have all 51 vulnerabilities.
- Its findings are **not** uploaded to code scanning, for the same reason.
