# Contributing to JMR Textile Backend

## Branching Strategy

We use a nested branching strategy to organize our work around sprints and features.

### Hierarchy

1.  **`main`**: The production-ready code.
2.  **`dev`**: The main integration branch. All feature branches merge here first to be tested together.
3.  **Feature Branches** (e.g., `mail`): Specific branches for major components or features, created from `dev`.
4.  **Sprint Branches** (e.g., `sp-[date]-[title]`): Short-lived branches for specific sprint tasks, created from their respective feature branch.

### Workflow

1.  **Start a Sprint**: Create a new sprint branch from the relevant feature branch.
    *   Format: `sp-[DDMMYYYY]-[Title]`
    *   Example: `sp-05022026-init-mail`
2.  **Development**: Commit your changes to the sprint branch.
3.  **Completion**: create a Pull Request (PR) to merge your sprint branch back into the feature branch (e.g., `mail`).
4.  **Integration**: The feature branch will eventually be merged into `dev` for integration testing.

### Naming Conventions

*   **Sprint Branch**: `sp-[DDMMYYYY]-[Title]`
*   **Feature Branch**: `[feature-name]` (e.g., `mail`, `auth`, `products`)
