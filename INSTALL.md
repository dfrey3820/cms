# Building the Core Tailwind CSS (Optional)

The package ships a small fallback stylesheet (`resources/css/tailwind.compiled.css`) so the CMS can run without Node tooling. For a full, production-ready Tailwind build (recommended), run the following from your application root where `vendor/buni/cms` is installed.

Prerequisites:
- Node.js and npm (or pnpm/yarn)
- The app's `node_modules` installed (run `npm ci` or `npm install`)

Build command (from the application root):

```bash
# install frontend deps (if not already)
npm ci

# build the full Tailwind output for the CMS core
npx tailwindcss -i vendor/buni/cms/resources/css/tailwind.css -o public/vendor/cms/tailwind.css --minify
```

Notes:
- The composer plugin will attempt to run an `npx` build automatically during package install/update when available. If the build fails (e.g. no Node/npx), the plugin copies a lightweight fallback stylesheet from the package into `public/vendor/cms/tailwind.css`.
- If you use `pnpm` or `yarn`, substitute the appropriate install command and run the `tailwindcss` binary from your package manager.
- For CI/CD, add the two commands above as part of your build pipeline so themes and the core receive the full Tailwind output.

If you want I can add an `artisan cms:build-tailwind` command to run this from PHP (it will only attempt the build when Node is present). Say the word and I'll add it.
