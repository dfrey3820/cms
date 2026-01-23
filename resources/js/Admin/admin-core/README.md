Admin Core Theme

This folder contains the shared admin core theme used by the application and packages as the authoritative admin UI templates and styles. It is intentionally kept inside the application at `resources/js/Admin/admin-core` and is not copied or moved during package install.

Build

Install dev deps and build inside this folder:

```bash
cd resources/js/Admin/admin-core
npm install
npm run build
```

Usage

- The package `buni/cms` or any theme may import components or templates from this core using relative paths or a provided alias (e.g. `@admin-core`).
- The output is placed by default into `resources/js/Admin/admin-core/public/vendor/admin-core` (configured in `vite.config.ts`) — adjust `outDir` as needed.

Notes

- This scaffold is minimal — extend components, add icons, refine the Vite config, and add integration with the package loader as required.
