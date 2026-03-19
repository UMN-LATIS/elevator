# GitHub Copilot Instructions

## PR Review Suggestions

When responding to pull request review comments, prefer a **direct commit suggestion** (an inline code block that can be committed immediately) over opening a new implementation branch whenever the requested change is self-contained and small. Examples of changes that should always be handled as a commit suggestion:

- Whitespace or indentation fixes
- Case sensitivity corrections
- Renaming a variable, method, or class in a single file
- Fixing a typo or string literal
- Adjusting a single conditional expression
- Adding or removing a single import

Reserve the **implement** flow (which creates a new branch) for changes that span multiple files, require significant new logic, or cannot be expressed as a focused diff against the PR's current code.
