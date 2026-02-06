# Clean Architecture (incremental, non-breaking)

## Goals
- Introduce Clean Architecture layers without breaking existing CodeIgniter structure.
- Migrate module-by-module over time.

## Layers
- Domain: Core business rules (entities, value objects, domain services).
- Application: Use cases, application services, DTOs.
- Infrastructure: External systems (DB, cache, email, file storage, vendors).
- Presentation: Delivery layer (HTTP, CLI). CodeIgniter controllers can remain in app/Controllers.

## Rules
- Domain must not depend on Application, Infrastructure, or Presentation.
- Application can depend on Domain, but not on Infrastructure or Presentation.
- Infrastructure can depend on Application and Domain.
- Presentation can depend on Application and Domain.

## Migration approach
1. Create new code in these folders.
2. Existing controllers/filters delegate to Application services.
3. Migrate one module at a time to avoid regressions.

## CodeIgniter mapping (non-breaking)
- Keep using app/Controllers, app/Filters, app/Models.
- Add new code to the clean layers and wire it from existing CodeIgniter classes.
